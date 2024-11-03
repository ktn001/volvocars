# vim: tabstop=4 autoindent expandtab
import logging
import sys
import os
import argparse
import time
import traceback
import requests
import signal
import base64
import socketserver
import json
from socketserver import StreamRequestHandler, ThreadingTCPServer

OAUTH_AUTH_URL = "https://volvoid.eu.volvocars.com/as/authorization.oauth2"
OAUTH_TOKEN_URL = "https://volvoid.eu.volvocars.com/as/token.oauth2"

_socket_port = None
_pidfile = None
_apikey = None
_netAdapter = None
auth_session = None

def logResponse(title, response):
    logging.debug("-DEBUT-------------" + title + "--------------------")
    logging.debug("  Request")
    logging.debug("    URL")
    logging.debug("      " + response.request.url)
    logging.debug("    Body")
    if response.request.body:
        logging.debug("      " + response.request.body)
    logging.debug("    Headers")
    for header in response.request.headers:
        logging.debug("      " + header + ": " + response.request.headers[header])
    logging.debug("  Response")
    logging.debug("    Body")
    logging.debug("      " + response.content.decode('utf-8'))
    logging.debug("-FIN---------------" + title + "--------------------")

# ------------------------------------------------------------------------------------------------------
# Class HttpError
# ------------------------------------------------------------------------------------------------------
class HttpError(Exception):
    def __init__ (self, httpCode, Content):
        Exception.__init__(self,httpCode, Content)
        self.httpCode = httpCode
        self.content = Content

# ------------------------------------------------------------------------------------------------------
# Class StateError
# ------------------------------------------------------------------------------------------------------
class StateError(Exception):
    def __init__ (self, State, Content):
        Exception.__init__(self,State, content)
        self.state = State
        self.content = content

# ------------------------------------------------------------------------------------------------------
# Class socket_handler
# ------------------------------------------------------------------------------------------------------
class socket_handler(StreamRequestHandler):
    def getAuthSession(self):
        global auth_session
        logging.debug("Création d'une nouvelle session")
        auth_session = requests.Session()
        auth_session.headers = {
            "authorization": "Basic aDRZZjBiOlU4WWtTYlZsNnh3c2c1WVFxWmZyZ1ZtSWFEcGhPc3kxUENhVXNpY1F0bzNUUjVrd2FKc2U0QVpkZ2ZJZmNMeXc=",
            "User-Agent": "vca-android/5.46.0",
            "Accept-Encoding": "gzip",
            "Content-Type": "application/json; charset=utf-8"
        }

    def initAuthorize(self):
        url_params = ( "?client_id=h4Yf0b"
            "&response_type=code"
            "&acr_values=urn:volvoid:aal:bronze:2sv"
            "&response_mode=pi.flow"
            "&scope="
                "openid "
                "email "
                "profile "
                "care_by_volvo:financial_information:invoice:read "
                "care_by_volvo:financial_information:payment_method "
                "care_by_volvo:subscription:read "
                "customer:attributes "
                "customer:attributes:write "
                "order:attributes "
                "vehicle:attributes "
                "tsp_customer_api:all "
                "conve:brake_status "
                "conve:climatization_start_stop "
                "conve:command_accessibility "
                "conve:commands "
                "conve:diagnostics_engine_status "
                "conve:diagnostics_workshop "
                "conve:doors_status "
                "conve:engine_status "
                "conve:environment "
                "conve:fuel_status "
                "conve:honk_flash "
                "conve:lock "
                "conve:lock_status "
                "conve:navigation "
                "conve:odometer_status "
                "conve:trip_statistics "
                "conve:tyre_status "
                "conve:unlock "
                "conve:vehicle_relation "
                "conve:warnings "
                "conve:windows_status "
                "energy:battery_charge_level "
                "energy:charging_connection_status "
                "energy:charging_system_status "
                "energy:electric_range "
                "energy:estimated_charging_time "
                "energy:recharge_status "
                "vehicle:attributes"
            )
        response = auth_session.get(OAUTH_AUTH_URL + url_params)
        logResponse("LOGIN", response)
        if response.status_code == 200:
            auth = response.json()
            return auth
        else:
            logging.error(f'Httpcode: {response.status_code} returnend by initAuthorize')
            raise HttpError(response.status_code, response.content)
   
    def sendUsernamePassword(self, url, username, password):
        url = url + "?action=checkUsernamePassword"
        body = {'username': username, 'password': password}
        response = auth_session.post(url, data=json.dumps(body))
        logResponse("CHECK USER PASSWORD", response)
        if response.status_code == 200:
            auth = response.json()
            return auth
        elif response.status_code == 400:
            auth = response.json()
            return auth
        else:
            logging.error(f'Httpcode: {response.status_code} returnend by checkUsernamePassword')
            raise HttpError(response.status_code, response.content)

    def login_phase1(self, payload):
        self.getAuthSession()
        auth = self.initAuthorize()
        auth_status = auth['status']
        auth_session.headers.update({"x-xsrf-header": "PingFederate"})

        if auth_status == 'USERNAME_PASSWORD_REQUIRED':
            login = payload['login']
            password = payload['password']
            url = auth['_links']["checkUsernamePassword"]["href"]
            auth = self.sendUsernamePassword(url, login, password)
            return auth
        else:
            logging.error(f'Unknow State "{auth_status}" returnend by initAuthorize')
            raise StaterError (auth_status)

    def sendOTP(self, url, otp):
        url = url + "?action=checkOtp"
        body = {"otp": otp}
        response = auth_session.post(url, data=json.dumps(body))
        logResponse("CHECK OTP", response)
        if response.status_code == 200:
            auth = response.json()
            return auth
        else:
            content = response.json()
            if 'details' in content:
                message = json.dumps(content['details'][0])
            else:
                message = response.content
            logging.error(f'Httpcode: {response.status_code} returnend by checkOTP')
            raise HttpError(response.status_code, message)

    def continue_auth(self, url):
        url = url + "?action=continueAuthentication"
        response = auth_session.get(url)
        logResponse("CONTINUEAUTHENTICATION", response)
        if response.status_code == 200:
            auth = response.json()
            return auth
        else:
            logging.error(f'Httpcode: {response.status_code} returnend by continueAuth')
            raise HttpError(response.status_code, response.content)

    def get_token(self, code):
        url = OAUTH_TOKEN_URL
        auth_session.headers.update({"content-type": "application/x-www-form-urlencoded"})
        body = {
            "code": code,
            "grant_type": "authorization_code"
        }
        response = auth_session.post(url, data=body)
        logResponse("CONTINUEAUTHENTICATION", response)
        if response.status_code == 200:
            tokens = response.json()
            return tokens
        else:
            logging.error(f'Httpcode: {response.status_code} returnend by getToken')
            raise HttpError(response.status_code, response.content)

    def login_phase2(self, payload):
        auth = json.loads(payload['auth'])
        otp = payload['otp']
        url = auth['_links']['checkOtp']['href']
        auth = self.sendOTP(url, otp)
        auth_status = auth['status']

        if auth_status == 'OTP_VERIFIED':
            url = auth['_links']["continueAuthentication"]["href"]
            auth = self.continue_auth(url)
            auth_status = auth['status']
        else:
            logging.error(f'Unknow State "{auth_status}" returnend by continueAuthentication')
            raise StaterError (auth_status)

        if auth_status == 'COMPLETED':
            code = auth['authorizeResponse']['code']
            tokens = self.get_token(code)
            return tokens
        else:
            logging.error(f'Unknow State "{auth_status}" returnend by getToken')
            raise StaterError (auth_status)

    def resendOTP(self, payload):
        logging.debug(payload['url'])
        response = auth_session.get(payload['url'])
        if response.status_code == 200:
            return response.content
        logging.error(f'Httpcode: {response.status_code} returnend by resendOTP')
        raise HttpError(response.status_code, response.content)

    def handle(self):
        logging.info("Client connected to [%s:%d]" % self.client_address)
        payload = self.rfile.readline().decode('utf-8')
        logging.info("Message read from socket: " + str(payload.strip()))
        payload = json.loads(payload)
        if 'apikey' not in payload or payload['apikey'] != _apikey:
            logging.error("Invalid apikey" )
            return
        try:

            if payload['action'] == 'login':
                auth = self.login_phase1(payload)
                auth = json.dumps(auth).encode()
                self.wfile.write(auth)

            elif payload['action'] == 'resendOTP':
                response = self.resendOT(payload)
                self.wfile.write(response)

            elif payload['action'] == 'sendOTP':
                tokens = self.login_phase2(payload)
                tokens = json.dumps(tokens).encode()
                self.wfile.write(tokens)

        except HttpError as e:
            response = {
                "error" : "HttpCode",
                "HttpCode" : e.httpCode,
                "content" : e.content
            }
            self.wfile.write(json.dumps(response).encode('utf-8'))
        except StateError as e:
            response = {
                "error" : "State",
                "state" : e.state,
                "content" : e.content.decode()
            }
            self.wfile.write(json.dumps(response))
        logging.info("Client disconnected from [%s:%d]" % self.client_address)

def set_log_level(level="error"):
    LEVELS = {
        "debug": logging.DEBUG,
        "info": logging.INFO,
        "notice": logging.WARNING,
        "warning": logging.WARNING,
        "error": logging.ERROR,
        "critical": logging.CRITICAL,
        "none": logging.CRITICAL,
    }

    FORMAT = "[%(asctime)-15s][%(levelname)s][%(module)-18s] %(message)s"
    logging.basicConfig(
        level=LEVELS[level],
        format=FORMAT,
        datefmt="%Y-%m-%d %H:%M:%S",
    )

def options():
    global _socket_port
    global _pidfile
    global _apikey
    parser = argparse.ArgumentParser()
    parser.add_argument("-l", "--logLevel", help="Le niveau logging", choices=["debug","info","warning","error"])
    parser.add_argument("-p", "--pidFile", help="fichier pid", type=str, required=True,)
    parser.add_argument("-P", "--port", help="port pour communication avec le plugin", type=int, required=True)
    parser.add_argument("-a", "--apikey", help="Apikey", type=str, required=True)
    args = parser.parse_args()

    if args.logLevel:
        set_log_level(args.logLevel)
    else:
        set_log_level()

    if args.pidFile:
        _pidfile = args.pidFile

    if args.port:
        _socket_port = args.port
        
    if args.apikey:
        _apikey = args.apikey
        
def write_pid(path):
    pid = str(os.getpid())
    logging.info("Writing PID " + pid + " to " + str(path))
    open(path, "w").write("%s\n" % pid)

def authorize():
    logging.info("Starting login with OTP")

def listen():
    global _netAdapter
    socketserver.ThreadingTCPServer.allow_reuse_address = True
    _netAdapter = ThreadingTCPServer(('127.0.0.1',_socket_port), socket_handler)
    if (_netAdapter):
        logging.info("Socket interface started")
        _netAdapter.serve_forever(poll_interval=0.5)
        while 1:
            time.sleep(0.5)
        logging.info("Socket interface stoped")
    else:
        logging.error("Cannot start socket interface")

def signal_handler(signum=None, frame=None):
    logging.debug("Signal %i caught, exiting..." % int(signum))
    shutdown()

def shutdown():
    logging.debug("Shutdown")
    if (_netAdapter):
        logging.debug("Closing socket...")
        _netAdapter.server_close()
    logging.debug("Removing PID file " + str(_pidfile))
    try:
        os.remove(_pidfile)
    except:
        pass
    sys.exit(0)

options()
signal.signal(signal.SIGINT, signal_handler)
signal.signal(signal.SIGTERM, signal_handler)
write_pid(_pidfile)
logging.debug("PORT " + str(_socket_port))

try:
    listen()
except Exception as e:
    logging.error("Fatal error: " + str(e))
    logging.info(traceback.format_exc())
    shutdown()

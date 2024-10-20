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


_socket_port = None
_pidfile = None
_apikey = None
_netAdapter = None

# ------------------------------------------------------------------------------------------------------
# Class HttpError
# ------------------------------------------------------------------------------------------------------
class HttpError(Exception):
    def __init__ (self, httpCode, Content):
        Exception.__init__(self,httpCode, content)
        self.httpCode = httpcode
        self.content = content

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
    def login(self, payload):
        login = payload['login']
        password = payload['password']
        session = requests.session()
        session.headers = {
            "authorization": "Basic aDRZZjBiOlU4WWtTYlZsNnh3c2c1WVFxWmZyZ1ZtSWFEcGhPc3kxUENhVXNpY1F0bzNUUjVrd2FKc2U0QVpkZ2ZJZmNMeXc=",
            "User-Agent": "vca-android/5.46.0",
            "Accept-Encoding": "gzip",
            "Content-Type": "aplication/json; charset=utf-8"
        }
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
        auth = session.get(OAUTH_AUTH_URL + url_params)

        if auth.status_code == 200:
            response = auth.json()
            auth_state = response['status']
   
            if auth_state == 'USERNAME_PASSWORD_REQUIRED':
                session.headers.update({"x-xsrf-header": "PingFederate"})
                url = response['_links']["checkUsernamePassword"]["href"] + "?action=checkUsernamePassword"
                body = {'username': login, 'password': password}
                auth = session.post(url, data=json.dumps(body))
                logging.debug(auth.status_code)
                logging.debug(auth.content)
                if auth.status_code == 200:
                    return auth.content
                elif auth.status_code == 400:
                    return auth.content
                else:
                    logging.error(f'HttpCode: {auth.status_code} for "checkUserNamePassword"')
                    raise HttpError(auth.status_code, auth.content)
            logging.error(f'Unknow State "{auth_state}" returnend by phase 1')
            raise StaterError (auth_state)
        else:
            logging.error(f'Httpcode: {auth_state} returnend by phase 1')
            raise HttpError(auth.status_code, auth.content)

    def handle(self):
        logging.info("Client connected to [%s:%d]" % self.client_address)
        payload = self.rfile.readline().decode('utf-8')
        logging.info(payload)
        logging.info("Message read from socket: " + str(payload.strip()))
        payload = json.loads(payload)
        if payload['apikey'] != _apikey:
            logging.error("Invalid apikey" )
            return
        try:
            if payload['action'] == 'login':
                response = self.login(payload)
                self.wfile.write(response)
        except HttpError as e:
            response = {
                "error" : "HttpCode",
                "HttpCode" : e.httpCode,
                "content" : e.content
            }
            self.wfile.write(json.dumps(response))
        except StateError as e:
            response = {
                "error" : "State",
                "state" : e.state,
                "content" : e.content
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

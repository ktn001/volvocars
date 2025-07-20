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
_logLevel =  'error'

_AUTH_URL = "https://volvoid.eu.volvocars.com/as/authorization.oauth2"
_CLIENT_ID = "dc-o1u00kgdad2fg0s9e2fcdvteq"
_CLIENT_SECRET = "ZGMtbzF1MDBrZ2RhZDJmZzBzOWUyZmNkdnRlcTpId3hvdVBWU2ZaMk1VemdCM1VSZXRsCg=="
_SCOPE= [
    "appointment",
    "appointment:write",
    "care_by_volvo:customer:identity",
    "care_by_volvo:financial_information:invoice:read",
    "care_by_volvo:financial_information:payment_method",
    "care_by_volvo:subscription:read",
    "carshare:guest",
    "carshare:owner",
    "conve:battery_charge_level",
    "conve:brake_status",
    "conve:climatization_start_stop",
    "conve:command_accessibility",
    "conve:commands",
    "conve:connectivity_status",
    "conve:diagnostics_engine_status",
    "conve:diagnostics_workshop",
    "conve:doors_status",
    "conve:engine_start_stop",
    "conve:engine_status",
    "conve:environment",
    "conve:fuel_status",
    "conve:honk_flash",
    "conve:lock",
    "conve:lock_status",
    "conve:navigation",
    "conve:odometer_status",
    "conve:recharge_status",
    "conve:trip_statistics",
    "conve:tyre_status",
    "conve:unlock",
    "conve:vehicle_relation",
    "conve:warnings",
    "conve:windows_status",
    "csb:all",
    "customer:attributes",
    "customer:attributes:write",
    "email",
    "energy:battery_charge_level",
    "energy:capability:read",
    "energy:charging_connection_status",
    "energy:charging_current_limit",
    "energy:charging_history:read",
    "energy:charging_property:read",
    "energy:charging_system_status",
    "energy:electric_range",
    "energy:estimated_charging_time",
    "energy:recharge_status",
    "energy:state:read",
    "energy:target_battery_level",
    "exve:brake_status",
    "exve:diagnostics_engine_status",
    "exve:diagnostics_workshop",
    "exve:doors_status",
    "exve:engine_status",
    "exve:fuel_status",
    "exve:lock_status",
    "exve:odometer_status",
    "exve:tyre_status",
    "exve:vehicle_statistics",
    "exve:warnings",
    "exve:windows_status",
    "location:read",
    "oidc.profile.read",
    "openid",
    "order:attributes",
    "payment:payment",
    "profile",
    "subscription:read",
    "subscription:write",
    "subscription_manager:read",
    "tsp_customer_api:all",
    "vehicle:attributes",
    "vehicle:attributes:write",
    "vehicle:brake_status",
    "vehicle:bulb_status",
    "vehicle:capabilities",
    "vehicle:climatization",
    "vehicle:climatization_calendar",
    "vehicle:climatization_calendar_status",
    "vehicle:climatization_status",
    "vehicle:connectivity_status",
    "vehicle:coolant_status",
    "vehicle:deliverToCar",
    "vehicle:doors_status",
    "vehicle:engine",
    "vehicle:engine_start",
    "vehicle:engine_status",
    "vehicle:fuel_status",
    "vehicle:honk_blink",
    "vehicle:ihu",
    "vehicle:location",
    "vehicle:lock",
    "vehicle:lock_status",
    "vehicle:maintenance_status",
    "vehicle:odometer_status",
    "vehicle:oil_status",
    "vehicle:orderToCar",
    "vehicle:parking",
    "vehicle:privacy",
    "vehicle:service_status",
    "vehicle:trip_status",
    "vehicle:trips",
    "vehicle:tyre_status",
    "vehicle:unlock",
    "vehicle:washer_status",
    "volvo_on_call:all "]


def options():
    global _socket_port
    global _pidfile
    global _apikey
    global _log_level

    parser = argparse.ArgumentParser()
    parser.add_argument("-l", "--logLevel", help="Le niveau logging", choices=["debug", "info", "notice", "warning", "error", "critical", "none"])
    parser.add_argument("-p", "--pidFile", help="fichier pid", type=str, required=True)
    parser.add_argument("-P", "--port", help="port pour communication avec le plugin", type=int, required=True)
    parser.add_argument("-a", "--apikey", help="Apikey", type=str, required=True)
    args = parser.parse_args()

    if args.logLevel:
        _log_level = args.logLevel
        set_log_level(args.logLevel)
    else:
        set_log_level()

    if args.pidFile:
        _pidfile = args.pidFile

    if args.port:
        _socket_port = args.port

    if args.apikey:
        _apikey = args.apikey


def logResponse(title, response):
    logging.debug("-DEBUT-------------" + title + "--------------------")
    logging.debug("  Request")
    logging.debug("    URL")
    logging.debug("      " + response.request.url)
    logging.debug("    Body")
    if response.request.body:
        if type(response.request.body) is bytes:
            logging.debug("      " + response.request.body.decode("utf-8"))
        else:
            logging.debug("      " + response.request.body)
    logging.debug("    Headers")
    for header in response.request.headers:
        logging.debug("      " + header + ": " + response.request.headers[header])
    logging.debug("  Response")
    logging.debug(f"    Code: {response.status_code}")
    logging.debug("    Body")
    logging.debug("      " + response.content.decode("utf-8"))
    logging.debug("-FIN---------------" + title + "--------------------")


# ------------------------------------------------------------------------------------------------------
# Exceptions
# ------------------------------------------------------------------------------------------------------
class myException(Exception):
    pass
class credentialException(myException):
    pass
# ------------------------------------------------------------------------------------------------------
# Class socket_handler
# ------------------------------------------------------------------------------------------------------
class socket_handler(StreamRequestHandler):

    sessions = dict()

    def handle(self):
        data = self.rfile.readline().strip().decode('utf-8')
        logging.info(f"data received: {data}")
        data = json.loads(data)
        try:
            if 'action' not in data:
                raise myException("Action non définie dans le message reçu par le daemon")
            if data['action'] == 'login':
                message = self.run(data)
            elif data['action'] == 'sendOTP':
                message = self.send_otp(data)
            else:
                raise myException(f"Action <{data['action']}> inconnue dans le daemon")
        except myException as e:
            logging.error(e.args[0])
            message = { "type" : "message",
                        "level": "error",
                        "code" : "unknow",
                        "message": e.args[0] }
        except credentionException as e:
            message = { "type" : "message",
                        "level": "warning",
                        "code" : "credential",
                        "message": e.args[0] }
        self.wfile.write(json.dumps(message).encode('utf-8'))

    def get_auth_session(self, id=None):
        if id != None and id in self.sessions:
            return self.sessions[id]

        auth_session = requests.session()
        auth_session.headers = {
            "authorization": "Basic aDRZZjBiOlU4WWtTYlZsNnh3c2c1WVFxWmZyZ1ZtSWFEcGhPc3kxUENhVXNpY1F0bzNUUjVrd2FKc2U0QVpkZ2ZJZmNMeXc=",
            "User-Agent": "vca-android/5.58.1",
            "Accept-Encoding": "gzip",
            "Content-Type": "application/json; charset=utf-8"
        }
        return auth_session

    def run(self, data):
        if data['action'] == 'login':
            auth_session = self.get_auth_session()
            response = self.login(auth_session, data)
            auth_session.headers.update({"x-xsrf-header": "PingFederate"})
        elif data['action'] == 'sendOTP':
            response = self.send_otp(data)
        else:
            return
        while 1:
            status = response["status"]

            if status == "USERNAME_PASSWORD_REQUIRED":
                if 'login' not in data:
                    raise myException("Login pas définini dans le message reçu par le deamon")
                if 'password' not in data:
                    raise myException("Password pas définini dans le message reçu par le deamon")
                response = self.check_username_password(auth_session, response, data['login'], data['password'])
                continue

            if status == "OTP_REQUIRED":
                return response
                continue

            if status == "OTP_VERIFIED":
                response = self.continue_auth(auth_session, response)
                continue
                
            if status == "COMPLETED":
                token = self.get_token(auth_session, response)
                return token

            raise myException(f"Status <{status}> inconnu")


    def login(self, auth_session, data):
        url_params = ("?client_id=h4Yf0b"
                      "&response_type=code"
                      "&acr_values=urn:volvoid:aal:bronze:2sv"
                      "&response_mode=pi.flow"
                      "&scope=openid email profile care_by_volvo:financial_information:invoice:read care_by_volvo:financial_information:payment_method care_by_volvo:subscription:read customer:attributes customer:attributes:write order:attributes vehicle:attributes tsp_customer_api:all conve:brake_status conve:climatization_start_stop conve:command_accessibility conve:commands conve:diagnostics_engine_status conve:diagnostics_workshop conve:doors_status conve:engine_status conve:environment conve:fuel_status conve:honk_flash conve:lock conve:lock_status conve:navigation conve:odometer_status conve:trip_statistics conve:tyre_status conve:unlock conve:vehicle_relation conve:warnings conve:windows_status energy:battery_charge_level energy:charging_connection_status energy:charging_system_status energy:electric_range energy:estimated_charging_time energy:recharge_status vehicle:attributes")

        auth = auth_session.get(OAUTH_AUTH_URL + url_params)
        logResponse("START LOGIN",auth)
        if auth.status_code != 200:
            message = auth.json()
            raise myException(message["details"][0]["userMessage"])
        response = auth.json()
        return response


    def check_username_password(self, auth_session, data, username, password):
        next_url = data["_links"]["checkUsernamePassword"]["href"].replace("http://", "https://") + "?action=checkUsernamePassword"
        body = {"username": username, "password": password}
        auth = auth_session.post(next_url, data=json.dumps(body))
        logResponse("CHECK USER PASSWORD", auth)
        if auth.status_code == 400:
            message = auth.json()
            if "code" in message and message['code'] == 'VALIDATION':
                if message['details'][0]['code'] == "CREDENTIAL_VALIDATION_FAILED":
                    raise credentialException("Nous n'avons pas reconnu le nom d'utilisateur ou le mot de passe que vous avez saisi. Veuillez réessayer.")
        if auth.status_code != 200:
            message = auth.json()
            raise myException(message["details"][0]["userMessage"])
        id = auth.json()['id']
        self.sessions[id] = auth_session
        return auth.json()


    def send_otp(self, data):
        logging.info(data)
        auth = json.loads(data['auth'])
        next_url = auth['_links']['checkOtp']['href'].replace("http://","https://") + "?action=checkOtp"
        body = {"otp": data['otp']}

        id = auth['id']
        auth_session = self.get_auth_session(id)
        auth_session.headers.update({"x-xsrf-header": "PingFederate"})

        auth = auth_session.post(next_url, data=json.dumps(body))
        logResponse("CHECK OTP", auth)
        if auth.status_code == 400:
            message = auth.json()
            if message['details'][0]['code'] == "INVALID_OTP":
                raise myException(message["details"][0]['userMessage'])
        if auth.status_code != 200:
            message = auth.json()
            raise myException(message["details"][0]["userMessage"])
        response = auth.json()
        if response['status'] != "OTP_VERIFIED":
            raise myException(f"Status <{response['status']}> inconnu après envoi de l'OTP")
        response = self.continue_auth(auth_session, response)
        return response


    def continue_auth(self, auth_session, data):
        next_url = data["_links"]["continueAuthentication"]["href"].replace("http://", "https://") + "?action=continueAuthentication"
        auth = auth_session.get(next_url)
        logResponse("CONTINUEAUTHENTICATION", auth)
        if auth.status_code != 200:
            message = auth.json()
            raise myException(message["details"][0]["userMessage"])
        response = auth.json()
        return response

    def get_token(self, auth_session, data):
        auth_session.headers.update({"content-type": "application/x-www-form-urlencoded"})
        body = {"code": data['authorizeResponse']['code'], "grant_type": "authorization_code"}
        auth = auth_session.post(OAUTH_TOKEN_URL, data=body)
        logResponse("GET_TOKEN", auth)
        if auth.status_code != 200:
            message = auth.json()
            raise myException(message["details"][0]["userMessage"])
        response = auth.json()
        return response


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


def listen():
    global _netAdapter
    socketserver.ThreadingTCPServer.allow_reuse_address = True
    _netAdapter = ThreadingTCPServer(("127.0.0.1", _socket_port), socket_handler)
    if _netAdapter:
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


def write_pid(path):
    pid = str(os.getpid())
    logging.info("Writing PID " + pid + " to " + str(path))
    open(path, "w").write("%s\n" % pid)


def shutdown():
    logging.debug("Shutdown")
    if _netAdapter:
        logging.debug("Closing socket...")
        _netAdapter.server_close()
    logging.debug("Removing PID file " + str(_pidfile))
    try:
        os.remove(_pidfile)
    except:
        pass
    sys.exit(0)


options()
logging.info('Start demond')
logging.info('├─Log level: %s', _log_level)
logging.info('├─Socket port: %s', _socket_port)
logging.info('├─PID file: %s', _pidfile)
logging.info('└─apikey: %s', _apikey)

signal.signal(signal.SIGINT, signal_handler)
signal.signal(signal.SIGTERM, signal_handler)
write_pid(_pidfile)

try:
    listen()
except Exception as e:
    logging.error("Fatal error: " + str(e))
    logging.info(traceback.format_exc())
    shutdown()

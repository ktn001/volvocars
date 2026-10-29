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

libDir = os.path.realpath(os.path.dirname(os.path.abspath(__file__)) + "/../lib")
sys.path.append(libDir)

import oauth
import util

OAUTH_AUTH_URL = "https://volvoid.eu.volvocars.com/as/authorization.oauth2"
OAUTH_TOKEN_URL = "https://volvoid.eu.volvocars.com/as/token.oauth2"

_socket_port = None
_pidfile = None
_apikey = None
_netAdapter = None
_logLevel =  'error'

_AUTH_URL = "https://volvoid.eu.volvocars.com/as/authorization.oauth2"

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


# ------------------------------------------------------------------------------------------------------
# Exceptions
# ------------------------------------------------------------------------------------------------------
class myException(Exception):
    pass
class credentialException(myException):
    pass
class otpException(Exception):
    pass
# ------------------------------------------------------------------------------------------------------
# Class socket_handler
# ------------------------------------------------------------------------------------------------------
class socket_handler(StreamRequestHandler):

    sessions = dict()
    otp_urls = dict()
    verifiers = dict()
    states = dict()

    def handle(self):
        data = self.rfile.readline().strip().decode('utf-8')
        logging.info(f"data received: {data}")
        data = json.loads(data)
        try:
            if 'action' not in data:
                raise myException("Action non définie dans le message reçu par le daemon")

            if data['action'] == 'login':
                if 'login' not in data:
                    raise myException("Login pas définini dans le message reçu par le deamon")
                if 'password' not in data:
                    raise myException("Password pas définini dans le message reçu par le deamon")
                self.login(data['account_id'], data['login'], data['password'])
                message = { "type" : "ok" }

            elif data['action'] == 'sendOTP':
                token_data = self.send_otp(data['otp'], data['account_id'])
                message = { "type" : "token",
                            "token_data" : token_data }

            else:
                raise myException(f"Action <{data['action']}> inconnue dans le daemon")

        except credentialException as e:
            message = { "type" : "message",
                        "level": "warning",
                        "code" : "credential",
                        "message": e.args[0] }
        except otpException as e:
            message = { "type" : "message",
                        "level": "warning",
                        "code" : "otp",
                        "message": e.args[0] }
        except myException as e:
            logging.error(e.args[0])
            message = { "type" : "message",
                        "level": "error",
                        "code" : "unknow",
                        "message": e.args[0] }
        self.wfile.write(json.dumps(message).encode('utf-8'))

    def login(self, account_id, login, password):
        logging.debug("start_login")
        socket_handler.sessions[account_id] = requests.session()

        user_agent = "vca-android/" + util.get_volvo_app_version()
        verifier, state, login_action = oauth.start_login(socket_handler.sessions[account_id], user_agent)
        socket_handler.verifiers[account_id] = verifier
        socket_handler.states[account_id] = state

        socket_handler.otp_urls[account_id] = oauth.submit_credentials(socket_handler.sessions[account_id], login_action, login, password)
        logging.info(socket_handler.otp_urls[account_id])
        return

    def send_otp(self,otp, account_id):
        logging.debug("send_otp")
        verifier = socket_handler.verifiers[account_id]
        state = socket_handler.states[account_id]

        callback_url = oauth.submit_otp(socket_handler.sessions[account_id], socket_handler.otp_urls[account_id], otp)
        token_data = oauth.exchange_code(socket_handler.sessions[account_id], callback_url, verifier, state)
        return token_data
        
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

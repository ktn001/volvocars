import logging
from google_play_scraper import app

def logResponse(title, response, options=None):
    if options is None:
        options = []

    logging.debug("-DEBUT-------------" + title + "--------------------")
    logging.debug("  Request")
    logging.debug("    URL")
    logging.debug("      " + response.request.url)
    logging.debug("    Headers")
    for header in response.request.headers:
        logging.debug("      " + header + ": " + response.request.headers[header])
    logging.debug("    Body")
    if 'noRequestBody' in options:
        logging.debug("      ...........")
    else:
        if response.request.body:
            if type(response.request.body) is bytes:
                logging.debug("      " + response.request.body.decode("utf-8"))
            else:
                logging.debug("      " + response.request.body)
    logging.debug("  Response")
    logging.debug(f"    Code: {response.status_code}")
    logging.debug("    Headers")
    for header in response.headers:
        logging.debug("      " + header + ": " + response.headers[header])
    logging.debug("    Body")
    if 'noResponseBody' in options:
        logging.debug("      ...........")
    else:
        logging.debug("      " + response.content.decode("utf-8"))
    logging.debug("-FIN---------------" + title + "--------------------")

def get_volvo_app_version():
    result = app('se.volvo.vcc', lang='en', country='us')
    if "version" in result:
        return result["version"]
    else:
        return "7.5.0"

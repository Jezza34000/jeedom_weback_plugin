# This file is part of Jeedom.
#
# Jeedom is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Jeedom is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Jeedom. If not, see <http://www.gnu.org/licenses/>.

import logging
import string
import sys
import os
import datetime
import traceback
import re
import signal
from optparse import OptionParser
from os.path import join
import argparse
import json
import threading
from queue import Queue
import time
import websocket

try:
    from jeedom.jeedom import *
except ImportError:
    print("Error: importing module jeedom.jeedom")
    sys.exit(1)

# Connexion var
wss_url = ""
jwt_token = ""
region_name = ""
authorization = "Basic KG51bGwpOihudWxsKQ=="

waiting_time = 0
awaiting_answer = False
pending_command = ""
socket_state = "CLOSE"
SOCKET_RECEIVE = Queue()
ws_obj = websocket

def read_socket():
    global JEEDOM_SOCKET_MESSAGE

    global waiting_time
    global awaiting_answer
    global pending_command
    global wss_url
    global jwt_token
    global region_name
    global socket_state

    # if pending_command != "" and not awaiting_answer and wsshandler.socket_state == "OPEN":
    #     logging.debug("Unanswered request : Retry to send it again...")
    #     SOCKET_SEND.put(pending_command)
    #     pending_command = ""
    #     awaiting_answer = True

    # Messages from Jeedom
    if not JEEDOM_SOCKET_MESSAGE.empty():

        # logging.debug("Message received in daemon JEEDOM_SOCKET_MESSAGE")
        message = JEEDOM_SOCKET_MESSAGE.get().decode('utf-8')
        message = json.loads(message)
        if message['apikey'] != _apikey:
            logging.error("Invalid apikey from socket : " + str(message))
            return
        try:
            # ============================================
            # Receive message for handle
            if message['action'] == "connect":
                logging.debug("WSS handler connection requested")
                # Store information
                wss_url = message['wss_url']
                jwt_token = message['jwt_token']
                region_name = message['region_name']
                # Check if re-connection is need
                if socket_state != "OPEN":
                    connect_wss()
            elif message['action'] == "update" or message['action'] == "action":
                logging.debug("Daemon receive ACTION for WSS")
                message = str(message['payload'])
                json_mess = message.replace("'", '"')
                if socket_state == "OPEN":
                    logging.debug("# state WSS OK (send mess)")
                    send_mess(json_mess)
                else:
                    if connect_wss():
                        logging.debug("# state WSS OK-RECO (send mess)")
                        send_mess(json_mess)
                    else:
                        logging.debug("# state WSS NOK (failed)")
                awaiting_answer = True
                waiting_time = 0
                pending_command = json_mess
            else:
                logging.error("# Daemon receive an unknown action type request")
        except Exception as e:
            logging.error('Received command to demon has encountered error : ' + str(e))

    if not SOCKET_RECEIVE.empty():
        awaiting_answer = False
        pending_command = ""
        message = SOCKET_RECEIVE.get()
        jeedom_cnx.send_change_immediate(json.loads(message))
        logging.debug("<< Message was sended OK")

    # if is_timed_out():
    #     logging.error("Request never receive answer, restarting connexion...")
    #     awaiting_answer = False
    #     SOCKET_SEND.put("STOP")
    #     time.sleep(0.5)
    #     check_wss_status()


def is_timed_out() -> bool:
    global awaiting_answer
    global waiting_time
    if awaiting_answer:
        if waiting_time <= 12:
            waiting_time += 1
            return False
        else:
            return True
    else:
        waiting_time = 0
        return False


def listen():
    jeedom_socket.open()
    try:
        while 1:
            time.sleep(0.5)
            read_socket()
    except KeyboardInterrupt:
        shutdown()


# ----------------------------------------------------------------------------
# Socket Handler
# ----------------------------------------------------------------------------
def connect_wss():
    global wss_url
    global jwt_token
    global region_name
    global authorization
    global socket_state
    global ws_obj
    global jeedom_cnx

    if not wss_url or not jwt_token or not region_name:
        logging.debug(f"> WssHandle missing credentials")
        message = '{"action":"getcredentials"}'
        jeedom_cnx.send_change_immediate(json.loads(message))
        return False

    logging.debug(f"> WssHandle start connexion ({wss_url})")
    # websocket.enableTrace(True)
    try:
        ws_obj = websocket.WebSocketApp(wss_url, header={"Authorization": authorization,
                                                         "region": region_name,
                                                         "token": jwt_token,
                                                         "Connection": "keep-alive, Upgrade",
                                                         "handshakeTimeout": "10000"},
                                        on_message=on_message,
                                        on_close=on_close,
                                        on_open=on_open,
                                        on_error=on_error)
        wst = threading.Thread(target=ws_obj.run_forever)
        wst.start()

        if wst.is_alive():
            logging.debug(f"> WssHandle thread started OK")
        else:
            logging.debug(f"> WssHandle thread NOK")

        for i in range(10):
            logging.debug(f"WSS awaiting connexion established... {i}")
            if socket_state == "OPEN":
                logging.debug(f"WSS connexion established OK")
                return True
            time.sleep(0.5)

        logging.debug(f"WSS awaiting failed")
        return False
    except Exception as e:
        socket_state = "ERROR"
        logging.debug(f"WSS ERROR while opening details={e}")
        return False


def send_mess(mess):
    global socket_state
    global ws_obj
    if socket_state == "OPEN":
        logging.debug(f">> WSS sending message={mess}")
        ws_obj.send(mess)
        return True
    else:
        logging.debug(f"WSS FAILED sending message={mess} status={socket_state}")
        return False


def on_message(ws, message):
    global SOCKET_RECEIVE
    logging.debug(f"<< WSS receiving message= {message}")
    SOCKET_RECEIVE.put(message)


def on_error(ws, error):
    global socket_state
    ws.close()
    socket_state = "ERROR"
    logging.debug(f"WSS ERROR details={error}")


def on_close(ws, close_status_code, close_msg):
    global socket_state
    ws.close()
    socket_state = "CLOSE"
    logging.debug(f"WSS CLOSE status_code={close_status_code} close_msg={close_msg}")


def on_open(ws):
    global socket_state
    socket_state = "OPEN"
    logging.debug(f"WSS OPEN is OK")


# ----------------------------------------------------------------------------
def handler(signum=None, frame=None):
    logging.debug("Signal %i caught, exiting..." % int(signum))
    shutdown()


def shutdown():
    logging.debug("Shutdown")
    logging.debug("Removing PID file " + str(_pidfile))
    try:
        os.remove(_pidfile)
    except (Exception,):
        pass
    try:
        jeedom_socket.close()
    except (Exception,):
        pass
    try:
        jeedom_serial.close()
    except (Exception,):
        pass
    logging.debug("Exit 0")
    sys.stdout.flush()
    os._exit(0)


# ----------------------------------------------------------------------------

_log_level = "error"
_socket_port = 33009
_socket_host = 'localhost'
_device = 'auto'
_pidfile = '/tmp/demond.pid'
_apikey = ''
_callback = ''
_cycle = 0.3

parser = argparse.ArgumentParser(description='Desmond Daemon for Jeedom plugin')
parser.add_argument("--device", help="Device", type=str)
parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
parser.add_argument("--callback", help="Callback", type=str)
parser.add_argument("--apikey", help="Apikey", type=str)
parser.add_argument("--cycle", help="Cycle to send event", type=str)
parser.add_argument("--pid", help="Pid file", type=str)
parser.add_argument("--socketport", help="Port for Zigbee server", type=str)
args = parser.parse_args()

if args.device:
    _device = args.device
if args.loglevel:
    _log_level = args.loglevel
if args.callback:
    _callback = args.callback
if args.apikey:
    _apikey = args.apikey
if args.pid:
    _pidfile = args.pid
if args.cycle:
    _cycle = float(args.cycle)
if args.socketport:
    _socketport = args.socketport

_socket_port = int(_socket_port)
jeedom_utils.set_log_level(_log_level)

jeedom_cnx = jeedom_com(_apikey, _callback)

logging.info('Start demond')
logging.info('Log level : ' + str(_log_level))
logging.info('Socket port : ' + str(_socket_port))
logging.info('Socket host : ' + str(_socket_host))
logging.info('PID file : ' + str(_pidfile))
logging.info('Apikey : ' + str(_apikey))
logging.info('Device : ' + str(_device))

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)

try:
    jeedom_utils.write_pid(str(_pidfile))
    jeedom_socket = jeedom_socket(port=_socket_port, address=_socket_host)
    listen()
except Exception as e:
    logging.error('Fatal error : ' + str(e))
    logging.info(traceback.format_exc())
    shutdown()

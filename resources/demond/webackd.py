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
import time
import datetime
import traceback
import re
import signal
from optparse import OptionParser
from os.path import join
import argparse
import json
import wsshandler

try:
    from jeedom.jeedom import *
except ImportError:
    print("Error: importing module jeedom.jeedom")
    sys.exit(1)

# Connexion var
wss_url = None
jwt_token = None
region_name = None
authorization = None
waiting_time = 0
awaiting_answer = False
pending_command = ""

def read_socket():
    global JEEDOM_SOCKET_MESSAGE

    global wss_url
    global jwt_token
    global region_name
    global authorization

    global waiting_time
    global awaiting_answer
    global pending_command

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
                authorization = "Basic KG51bGwpOihudWxsKQ=="
                # Check if re-connection is need
                check_wss_status()
            elif message['action'] == "update" or message['action'] == "action":
                logging.debug("Daemon receive ACTION for WSS")
                message = str(message['payload'])
                jsonmess = message.replace("'", '"')
                if check_wss_status():
                    logging.debug("# state OK (send mess)")
                    wss_cnx.send_mess(jsonmess)
                awaiting_answer = True
                waiting_time = 0
                pending_command = jsonmess
            else:
                logging.error("# Daemon receive an unknown action type request")
        except Exception as e:
            logging.error('Received command to demon has encountered error : ' + str(e))

    if not wss_cnx.SOCKET_RECEIVE.empty():
        awaiting_answer = False
        pending_command = ""
        message = wss_cnx.SOCKET_RECEIVE.get()
        s = jeedom_com(_apikey, _callback)
        s.send_change_immediate(json.loads(message))
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


def check_wss_status():
    global wss_url
    global jwt_token
    global region_name
    global authorization

    if wss_cnx.socket_state != "OPEN":
        logging.debug("# Connection initiation needed...")
        if wss_url or jwt_token or region_name:
            if wss_cnx.connect_wss(wss_url, jwt_token, region_name, authorization):
                logging.debug("# RET OK")
                return True
            else:
                logging.debug("# RET NOK")
                return False
        else:
            message = '{"action":"getcredentials"}'
            s = jeedom_com(_apikey, _callback)
            s.send_change_immediate(json.loads(message))
    else:
        # Connection is OK
        logging.debug("# Connection OK (still alive)")
        return True


def listen():
    jeedom_socket.open()
    try:
        while 1:
            time.sleep(0.5)
            read_socket()
    except KeyboardInterrupt:
        shutdown()

# ----------------------------------------------------------------------------


def handler(signum=None, frame=None):
    logging.debug("Signal %i caught, exiting..." % int(signum))
    shutdown()


def shutdown():
    logging.debug("Shutdown")
    logging.debug("Removing PID file " + str(_pidfile))
    try:
        os.remove(_pidfile)
    except:
        pass
    try:
        jeedom_socket.close()
    except:
        pass
    try:
        jeedom_serial.close()
    except:
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

wss_cnx = wsshandler.WssHandle()

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

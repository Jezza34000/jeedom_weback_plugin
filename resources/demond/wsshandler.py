import websocket
import logging
from threading import Thread
from queue import Queue

SOCKET_SEND = Queue()
SOCKET_RECEIVE = Queue()
socket_state = None

class WssHandle:
    def __init__(self, wss_url, jwt_token, region, authorization):
        global SOCKET_RECEIVE
        global SOCKET_SEND
        global socket_state
        logging.debug(f"> WssHandle init connexion ({wss_url})")
        # websocket.enableTrace(True)
        try:
            self.ws = websocket.WebSocket()
            self.ws.connect(wss_url, header={"Authorization": authorization,
                                             "region": region,
                                             "token": jwt_token,
                                             "Connection": "keep-alive, Upgrade",
                                             "handshakeTimeout": "10000"})
        except Exception as e:
            socket_state = "ERROR"
            logging.debug(f"> WSS Error : {e}")

        if self.ws.connected:
            logging.debug(f"> WSS connected OK")
            wss_inbound = Thread(target=self.recep_msg, args=[self.ws])
            wss_outbound = Thread(target=self.send_msg, args=[self.ws])

            wss_inbound.start()
            wss_outbound.start()

            if wss_inbound.is_alive() and wss_outbound.is_alive():
                socket_state = "OPEN"

    def __del__(self):
        # Close
        self.ws.close()
        logging.debug(f"> WSS handler terminated")

    def recep_msg(self, socket):
        global socket_state
        global SOCKET_RECEIVE
        while self.ws.connected:
            message = socket.recv()
            logging.debug(f"<< WSS receiving message = {message}")
            SOCKET_RECEIVE.put(message)
        socket_state = "CLOSE"
        logging.debug(f"> WSS closed")

    def send_msg(self, socket):
        global socket_state
        global SOCKET_SEND
        while self.ws.connected:
            if not SOCKET_SEND.empty():
                message = SOCKET_SEND.get()
                if message == "STOP":
                    logging.debug(f"> WSS closing connexion (stop signal)")
                    self.ws.close()
                else:
                    logging.debug(f">> WSS sending message = {message}")
                    socket.send(message)
        socket_state = "CLOSE"
        logging.debug(f"> WSS closed")


import websocket
import logging
import threading
from queue import Queue
import time

class WssHandle:
    def __init__(self):
        self.__wst = None
        self.ws = None
        self.socket_state = "CLOSE"
        self.SOCKET_RECEIVE = Queue()
        logging.debug(f"WSS handler LOADED")

    def __del__(self):
        # Class unload
        logging.debug(f"WSS handler UNLOADED")
        logging.debug(f"WSS handler thread_state={self.__wst.is_alive()}")

    def connect_wss(self, wss_url, jwt_token, region, authorization):
        logging.debug(f"> WssHandle start connexion ({wss_url})")
        # websocket.enableTrace(True)
        try:
            self.ws = websocket.WebSocketApp(wss_url, header={"Authorization": authorization,
                                             "region": region,
                                             "token": jwt_token,
                                             "Connection": "keep-alive, Upgrade",
                                             "handshakeTimeout": "10000"},
                                             on_message=self.on_message,
                                             on_close=self.on_close,
                                             on_open=self.on_open,
                                             on_error=self.on_error)
            self.__wst = threading.Thread(target=self.ws.run_forever)
            self.__wst.start()
            if self.__wst.is_alive():
                logging.debug(f"> WssHandle thread started OK")
            else:
                logging.debug(f"> WssHandle thread NOK")

            for i in range(10):
                logging.debug(f"WSS awaiting connexion etablished... {i}")
                if self.socket_state == "OPEN":
                    logging.debug(f"WSS connexion etablished OK")
                    return True
                time.sleep(0.5)

            logging.debug(f"WSS awaiting failed")
            return False

        except Exception as e:
            self.socket_state = "ERROR"
            logging.debug(f"WSS ERROR while opening details={e}")
            return False

    def send_mess(self, mess):
        if self.socket_state == "OPEN":
            logging.debug(f">> WSS sending message={mess}")
            self.ws.send(mess)
            return True
        else:
            logging.debug(f"WSS FAILED sending message={mess} status={self.socket_state}")
            return False

    def close_cnx(self):
        self.ws.close()
        logging.debug(f"WSS close request")
        return True

    def on_message(self, ws, message):
        logging.debug(f"<< WSS receiving message= {message}")
        self.SOCKET_RECEIVE.put(message)

    def on_error(self, ws, error):
        self.ws.close()
        self.socket_state = "ERROR"
        logging.debug(f"WSS ERROR details={error}")

    def on_close(self, ws, close_status_code, close_msg):
        self.socket_state = "CLOSE"
        logging.debug(f"WSS CLOSE status_code={close_status_code} close_msg={close_msg}")

    def on_open(self, ws):
        self.socket_state = "OPEN"
        logging.debug(f"WSS OPEN is OK")


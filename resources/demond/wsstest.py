import websocket
import json
import threading
from time import sleep



data1 = {
      'topic_name': '$aws/things/neatsvor-x600-20-4e-f6-9e-f2-a1/shadow/update',
      'opt': 'send_to_device',
      'sub_type': 'neatsvor-x600',
      'topic_payload': {'state': {'working_status': 'AutoClean'}},
      'thing_name': 'neatsvor-x600-20-4e-f6-9e-f2-a1'
    }

data2 = {
    "opt": "thing_status_get",
    "sub_type": "neatsvor-x600",
    "thing_name": "neatsvor-x600-20-4e-f6-9e-f2-a1"
}


json_string1 = json.dumps(data1)
json_string2 = json.dumps(data2)
ze_token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJleHAiOjE2NDYzMTM2MzQsImlhdCI6MTY0NjIyNzIzNCwiaXNzIjoiY29tLmFpY2xvdWQueXVnb25nIiwiZGF0YSI6eyJjYWxsaW5nX2NvZGUiOiIwMDMzIiwiYWNjb3VudCI6InRla3YzZnJtQGdtYWlsLmNvbSIsImFwcF9uYW1lIjoiV2VCYWNrIiwiYXBpX3ZlcnNpb24iOiIxLjAiLCJyZWdpb25fbmFtZSI6ImV1LWNlbnRyYWwtMSIsImxhbmd1YWdlIjoiZnIifX0.uUHknclWvKSIv4v9n3AUxGF2h7nlxTyiFcWgxTnZ-DE"

def on_message(ws, message):
    print(f"Message={message}")


def on_ping(ws, message):
    print("Got a ping! A pong reply has already been automatically sent.")


def on_pong(ws, message):
    print("Got a pong! No need to respond")


def on_close(ws):
    print
    "### closed ###"


"""
ws = websocket.WebSocketApp("wss://user.grit-cloud.com/wss",
           header={"Authorization": "Basic KG51bGwpOihudWxsKQ==", "region": "eu-central-1", "token": ze_token, "Connection": "keep-alive, Upgrade", "handshakeTimeout": "10000"},
                            on_message=on_message, on_ping=on_ping, on_pong=on_pong)
ws.run_forever(ping_interval=60, ping_timeout=10)



ws = websocket.WebSocket()
ws.connect("wss://user.grit-cloud.com/wss",
           header={"Authorization": "Basic KG51bGwpOihudWxsKQ==", "region": "eu-central-1", "token": ze_token, "Connection": "keep-alive, Upgrade", "handshakeTimeout": "10000"})
ws.send(json_string2)
print(ws.recv())
ws.close()
"""

if __name__ == "__main__":
    websocket.enableTrace(True)
    ws = websocket.WebSocketApp("wss://user.grit-cloud.com/wss",
                                header={"Authorization": "Basic KG51bGwpOihudWxsKQ==", "region": "eu-central-1",
                                        "token": ze_token, "Connection": "keep-alive, Upgrade",
                                        "handshakeTimeout": "10000"},
                                on_message=on_message, on_ping=on_ping, on_pong=on_pong)

    wst = threading.Thread(target=ws.run_forever)
    wst.daemon = True
    wst.start()

    conn_timeout = 5
    while not ws.sock.connected and conn_timeout:
        sleep(1)
        conn_timeout -= 1

    while ws.sock.connected:
        print(">> Send Request")
        ws.send(json_string1)
        sleep(600)
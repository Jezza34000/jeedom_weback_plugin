import wsshandler
import time
import json
from wsshandler import *


message = '{"action":"getcredentials"}'
print(json.loads(message))


token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJleHAiOjE2NDY1MDk5MTcsImlhdCI6MTY0NjQyMzUxNywiaXNzIjoiY29tLmFpY2xvdWQueXVnb25nIiwiZGF0YSI6eyJjYWxsaW5nX2NvZGUiOiIwMDMzIiwiYWNjb3VudCI6InRla3YzZnJtQGdtYWlsLmNvbSIsImFwcF9uYW1lIjoiV2VCYWNrIiwiYXBpX3ZlcnNpb24iOiIxLjAiLCJyZWdpb25fbmFtZSI6ImV1LWNlbnRyYWwtMSIsImxhbmd1YWdlIjoiZnIifX0.fxhW1CXqNYPlm41mSOWhSxjUifiGm491o3_d2WOpzAQ"

data2 = {
    "opt": "thing_status_get",
    "sub_type": "neatsvor-x600",
    "thing_name": "neatsvor-x600-20-4e-f6-9e-f2-a1"
}
json_string2 = json.dumps(data2)

cnx = wsshandler.WssHandle("ws://echo.websocket.events/.ws", token, "eu-central-1")



print("Running")
while True:
    time.sleep(1)
    print(f"STATE={wsshandler.socket_state}")
    if not wsshandler.SOCKET_RECEIVE.empty():
        print(f"Receive return={wsshandler.SOCKET_RECEIVE.get()}")
    print("Send mess")
    wsshandler.SOCKET_SEND.put("json_string2")


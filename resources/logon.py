import sys
from weback_unofficial.client import WebackApi
login = "+"+sys.argv[1]+"-"+sys.argv[2]
password = sys.argv[3]
client = WebackApi(login, password)
devices = client.device_list()
for device in devices:
    print(f"DEVICEFOUND={device['Thing_Name']}")
    description = client.get_device_description(device["Thing_Name"])
    print(f"DEVICETYPE={description.get('thingTypeName')}")


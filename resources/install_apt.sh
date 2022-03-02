PROGRESS_FILE=/tmp/jeedom/weback/dependency
if [ ! -z $1 ]; then
    PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "*************************************"
echo "*   Launch install of dependencies  *"
echo "*************************************"
echo $(date)
echo 5 > ${PROGRESS_FILE}
apt-get clean
echo 10 > ${PROGRESS_FILE}
apt-get update
echo 20 > ${PROGRESS_FILE}

echo "*****************************"
echo "Install modules using apt-get"
echo "*****************************"
apt-get install -y python3 python3-requests python3-pip
echo 60 > ${PROGRESS_FILE}

echo "*************************************"
echo "Install the required python libraries"
echo "*************************************"
python3 -m pip install "websocket" "asyncio"
echo 80 > ${PROGRESS_FILE}

echo 100 > ${PROGRESS_FILE}
echo $(date)
echo "***************************"
echo "*      Install ended      *"
echo "***************************"
rm ${PROGRESS_FILE}

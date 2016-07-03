#!/bin/bash
# Start capturing

#捕获间隔

sleepTime="100"

#当前时间
currentTime=$(date +%s)

#即将写入的文件名
filename=`expr $currentTime + $sleepTime`
echo $(date +%s)
echo $filename

# 写入起始时间
date +%s | awk '{print "start_timestamp:" $1 }' > ./Timestamp/$filename

tcpdump -i p3p1 tcp[20:2]=0x4745 or tcp[20:2]=0x504f -w tcp.cap -s 512 2>&1 &

# 捕获间隔
sleep $sleepTime

strings tcp.cap | grep -E "Host:"|awk -F ' ' '{print $2 }' >>  url.txt

echo "Stringfy Ok"

#strings tcp.cap | grep -E "GET /|POST /|Host:" | grep --no-group-separator -B 1 "Host:" | grep --no-group-separator -A 1 -E "GET /|POST /" | awk '{url=$2;getline;host=$2;printf ("%s\n",host""url)}' > /tmp/url.txt

date +%s | awk '{print "end_timestamp:" $1 }' >> ./Timestamp/$(date +%s)

grep -v -i -E "^$" url.txt | awk -F '/' '{print $1}' | sort | uniq -c | sort -nr >> ./Timestamp/$(date +%s)

echo "Decode OK"
cat Timestamp/$(date +%s)

kill `ps aux | grep tcpdump | grep -v grep | awk '{print $2}'`
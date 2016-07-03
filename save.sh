date +%s | awk '{print "timestamp:" $1 }' > timestamp 
grep -v -i -E "^$" url.txt | awk -F '/' '{print $1}' | sort | uniq -c | sort -nr >> timestamp

#!/bin/bash
 grep -v -i -E "\.(gif|png|jpg|jpeg|ico|swf|css)" url.txt | awk -F '/' '{print $1}' | sort | uniq -c | sort -nr

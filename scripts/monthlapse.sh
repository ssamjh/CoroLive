#!/bin/sh
if [ -z "$3" ]; then
    DATE_ARG="2 days ago"
else
    DATE_ARG="$3"
fi

MON=`date --date="$DATE_ARG" "+%b"`
MONTH=`date --date="$DATE_ARG" "+%B"`
YEAR=`date --date="$DATE_ARG" "+%Y"`
CAM=$1
CAMERA=$2
DAY=1

mkdir -p /tmp/$CAM-monthlapse/$YEAR-$MON
cd /tmp/$CAM-monthlapse/$YEAR-$MON
touch /tmp/$CAM-monthlapse/$YEAR-$MON/files.txt

while [[ $DAY -le 31 ]]
do
    echo "Downloading $DAY $MON $YEAR from $CAMERA's archives."
    curl --connect-timeout 2 --retry 4 --retry-delay 1 -s -S -f -o "$DAY.webm" https://api.corolive.nz/$CAM/archive/$YEAR/$MON/"$(printf %02d $DAY)"/animation.webm && echo "file '$DAY'.webm" >> /tmp/$CAM-monthlapse/$YEAR-$MON/files.txt
    (( DAY++ ))
done

echo "Archive downloading complete. Starting merge of all days into one big file."

ffmpeg -loglevel error -stats -f concat -safe 0 -i files.txt -c copy merge.webm

echo "Merge complete. Re-rendering merged file at higher speed."

ffmpeg -loglevel error -stats -i merge.webm -c:v libvpx-vp9 -b:v 0 -crf 30 -deadline good -cpu-used 5 -vf "setpts=0.08*PTS" -an -r 30 "timelapse-$YEAR-$MON.webm"

echo "Re-render complete."

cp "/tmp/$CAM-monthlapse/$YEAR-$MON/timelapse-$YEAR-$MON.webm" "/var/www/corolive.nz/api/$CAM/archive/$YEAR/$MON/monthlapse.webm"

rm -rf /tmp/$CAM-monthlapse/$YEAR-$MON

#!/bin/sh
if [ -z "$2" ]; then
    DATE_ARG="2 days ago"
else
    DATE_ARG="$2"
fi

YEAR=`date --date="$DATE_ARG" "+%Y"`
CAM=$1

mkdir -p /tmp/$CAM-yearlapse/$YEAR
cd /tmp/$CAM-yearlapse/$YEAR
touch /tmp/$CAM-yearlapse/$YEAR/files.txt

MONTH=1

while [[ $MONTH -le 12 ]]
do
    # Convert the month number into its abbreviated form
    MON_ABBR=$(date -d "$YEAR-$MONTH-01" '+%b')
    echo "Downloading $MON_ABBR of $YEAR from $CAM's archives."
    curl --connect-timeout 2 --retry 4 --retry-delay 1 -s -S -f -o "$MONTH.webm" https://api.corolive.nz/$CAM/archive/$YEAR/$MON_ABBR/monthlapse.webm && echo "file '$MONTH'.webm" >> /tmp/$CAM-yearlapse/$YEAR/files.txt
    (( MONTH++ ))
done

echo "Archive downloading complete. Starting merge of all months into one big file."

ffmpeg -loglevel error -stats -f concat -safe 0 -i files.txt -c copy merge.webm

echo "Merge complete. Re-rendering merged file at higher speed."

ffmpeg -loglevel error -stats -i merge.webm -c:v libvpx-vp9 -b:v 0 -crf 30 -deadline good -cpu-used 5 -vf "setpts=0.20*PTS" -an -r 30 "timelapse-$YEAR.webm"

echo "Re-render complete."

cp "/tmp/$CAM-yearlapse/$YEAR/timelapse-$YEAR.webm" "/var/www/corolive.nz/api/$CAM/archive/$YEAR/yearlapse.webm"

rm -rf /tmp/$CAM-yearlapse/$YEAR

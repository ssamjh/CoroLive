#!/bin/sh

MON=`date --date="2 days ago" "+%b"`
MONTH=`date --date="2 days ago" "+%B"`
YEAR=`date --date="2 days ago" "+%Y"`
CAM=$1
CAMERA=$2

mkdir -p /tmp/$CAM-monthlapse/$YEAR-$MON

cd /tmp/$CAM-monthlapse/$YEAR-$MON

touch /tmp/$CAM-monthlapse/$YEAR-$MON/files.txt

DAY=1

while [[ $DAY -le 31 ]]
do
 echo "Downloading $DAY $MON $YEAR from $CAMERA's archives."
 curl --connect-timeout 2 --retry 4 --retry-delay 1 -s -S -f -o "$DAY.webm" https://api.corolive.nz/$CAM/archive/$YEAR/$MON/"$(printf %02d $DAY)"/animation-fast.webm && echo "file '$DAY'.webm" >> /tmp/$CAM-monthlapse/$YEAR-$MON/files.txt
 (( DAY++ ))
done

echo "Archive downloading complete. Starting merge of all days into one big file."

/opt/ffmpeg-git-20220108-amd64-static/ffmpeg -loglevel error -stats -f concat -safe 0 -i files.txt -c copy merge.webm

echo "Merge complete. Re-rendering merged file at higher speed."

/opt/ffmpeg-git-20220108-amd64-static/ffmpeg -loglevel error -stats -i merge.webm -c:v libvpx-vp9 -b:v 0 -crf 30 -deadline good -cpu-used 5 -vf "setpts=0.20*PTS" -an "timelapse-$YEAR-$MON.webm"

echo "Re-render complete. Uploading finished timelapse of $CAMERA to Facebook page."

curl -X POST \
  "https://graph-video.facebook.com/305641893452131/videos" \
  -F "access_token=***REMOVED***" \
  -F "source=@/tmp/$CAM-monthlapse/$YEAR-$MON/timelapse-$YEAR-$MON.webm" \
  -F "description=$CAMERA Monthlapse: $MONTH $YEAR"
  
echo "Facebook upload complete. Now copying timelapse to correct location and cleaning up working folder."

cp "/tmp/$CAM-monthlapse/$YEAR-$MON/timelapse-$YEAR-$MON.webm" "/var/www/html/corolive.nz/api/$CAM/archive/$YEAR/$MON/monthlapse.webm"

rm -rf /tmp/$CAM-monthlapse/$YEAR-$MON
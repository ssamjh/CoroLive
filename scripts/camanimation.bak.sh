# Remove any existing folder from last animation.
rm -rf /tmp/$1-animation
#
# Create a fresh new folder for this animation.
mkdir /tmp/$1-animation
#
# Copy all images from today to our temporary folder.
#
#cp /var/www/html/corolive.nz/api/$1/archive/latest/*.jpg /tmp/$1-animation/
cp /var/www/html/corolive.nz/api/$1/archive/latest/*.webp /tmp/$1-animation/
#
# Move to the temp folder.
cd /tmp/$1-animation/
#
# Rename all images from 1.jpg onwards. With 1.jpg being the first image.
#ls | cat -n | while read n f; do mv "$f" `printf "%d.jpg" $n`; done
ls | cat -n | while read n f; do mv "$f" `printf "%d.webp" $n`; done
#
# Create the normal speed animation. (crf was 28)
/opt/ffmpeg-git-20220108-amd64-static/ffmpeg -r 12 -i %d.webp -c:v libx264 -crf 30 -vf "fps=12,format=yuv420p" animation.mp4
/opt/ffmpeg-git-20220108-amd64-static/ffmpeg -r 12 -i %d.webp -c:v libvpx-vp9 -b:v 0 -crf 38 -deadline good -cpu-used 5 -vf "fps=12" -pass 1 -an -f null /dev/null && \
/opt/ffmpeg-git-20220108-amd64-static/ffmpeg -r 12 -i %d.webp -c:v libvpx-vp9 -b:v 0 -crf 38 -deadline good -cpu-used 5 -vf "fps=12" -pass 2 -an animation.webm
#
# Create the faster speed animation.
/opt/ffmpeg-git-20220108-amd64-static/ffmpeg -r 30 -i %d.webp -c:v libx264 -crf 30 -vf "fps=30,format=yuv420p" animation-fast.mp4
/opt/ffmpeg-git-20220108-amd64-static/ffmpeg -r 30 -i %d.webp -c:v libvpx-vp9 -b:v 0 -crf 42 -deadline good -cpu-used 5 -vf "fps=30" -pass 1 -an -f null /dev/null && \
/opt/ffmpeg-git-20220108-amd64-static/ffmpeg -r 30 -i %d.webp -c:v libvpx-vp9 -b:v 0 -crf 42 -deadline good -cpu-used 5 -vf "fps=30" -pass 2 -an animation-fast.webm
#
# Cleanup the old animation files.
rm /var/www/html/corolive.nz/api/$1/archive/latest/animation.mp4
rm /var/www/html/corolive.nz/api/$1/archive/latest/animation-fast.mp4
rm /var/www/html/corolive.nz/api/$1/archive/latest/animation.webm
rm /var/www/html/corolive.nz/api/$1/archive/latest/animation-fast.webm
#
# Copy the new animations to today's folder.
cp /tmp/$1-animation/animation.mp4 /var/www/html/corolive.nz/api/$1/archive/latest/animation.mp4
cp /tmp/$1-animation/animation-fast.mp4 /var/www/html/corolive.nz/api/$1/archive/latest/animation-fast.mp4
cp /tmp/$1-animation/animation.webm /var/www/html/corolive.nz/api/$1/archive/latest/animation.webm
cp /tmp/$1-animation/animation-fast.webm /var/www/html/corolive.nz/api/$1/archive/latest/animation-fast.webm
#
# Clean up the api animation files.
rm /var/www/html/corolive.nz/api/$1/animation.mp4
rm /var/www/html/corolive.nz/api/$1/animation-fast.mp4
rm /var/www/html/corolive.nz/api/$1/animation.webm
rm /var/www/html/corolive.nz/api/$1/animation-fast.webm
#
# Copy the animations to the api folder.
cp /tmp/$1-animation/animation.mp4 /var/www/html/corolive.nz/api/$1/animation.mp4
cp /tmp/$1-animation/animation-fast.mp4 /var/www/html/corolive.nz/api/$1/animation-fast.mp4
cp /tmp/$1-animation/animation.webm /var/www/html/corolive.nz/api/$1/animation.webm
cp /tmp/$1-animation/animation-fast.webm /var/www/html/corolive.nz/api/$1/animation-fast.webm

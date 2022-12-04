# Create the normal speed animation. (crf was 28)
cd /tmp/$1-animation/webp
ffmpeg -r 12 -i %d.webp -c:v libvpx-vp9 -b:v 0 -crf 38 -deadline good -cpu-used 5 -vf "fps=12,format=yuv420p" -pass 1 -an -f null /dev/null && \
ffmpeg -r 12 -i %d.webp -c:v libvpx-vp9 -b:v 0 -crf 38 -deadline good -cpu-used 5 -vf "fps=12,format=yuv420p" -pass 2 -an animation.webm
#
# Create the faster speed animation.
cd /tmp/$1-animation/webp
ffmpeg -r 30 -i %d.webp -c:v libvpx-vp9 -b:v 0 -crf 42 -deadline good -cpu-used 5 -vf "fps=30,format=yuv420p" -pass 1 -an -f null /dev/null && \
ffmpeg -r 30 -i %d.webp -c:v libvpx-vp9 -b:v 0 -crf 42 -deadline good -cpu-used 5 -vf "fps=30,format=yuv420p" -pass 2 -an animation-fast.webm
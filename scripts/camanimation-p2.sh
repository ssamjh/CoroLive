# Create the normal speed animation.
cd /tmp/$1-animation/webp
/opt/ffmpeg-git-20220108-amd64-static/ffmpeg -r 12 -i %d.webp -c:v libvpx-vp9 -b:v 0 -crf 38 -deadline good -cpu-used 5 -vf "fps=12" -pass 1 -an -f null /dev/null && \
/opt/ffmpeg-git-20220108-amd64-static/ffmpeg -r 12 -i %d.webp -c:v libvpx-vp9 -b:v 0 -crf 38 -deadline good -cpu-used 5 -vf "fps=12" -pass 2 -an animation.webm
#
# Create the faster speed animation.
cd /tmp/$1-animation/webp
/opt/ffmpeg-git-20220108-amd64-static/ffmpeg -r 30 -i %d.webp -c:v libvpx-vp9 -b:v 0 -crf 42 -deadline good -cpu-used 5 -vf "fps=30" -pass 1 -an -f null /dev/null && \
/opt/ffmpeg-git-20220108-amd64-static/ffmpeg -r 30 -i %d.webp -c:v libvpx-vp9 -b:v 0 -crf 42 -deadline good -cpu-used 5 -vf "fps=30" -pass 2 -an animation-fast.webm
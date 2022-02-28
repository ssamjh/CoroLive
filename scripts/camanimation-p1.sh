# Remove any existing folder from last animation.
rm -rf /tmp/$1-animation
#
# Create a fresh new folder for this animation.
mkdir -p /tmp/$1-animation/webp
#
# Copy all images from today to our temporary folder.
#
cp /var/www/html/corolive.nz/api/$1/archive/latest/*.webp /tmp/$1-animation/webp/
#
# Move to the jpg folder.
cd /tmp/$1-animation/webp
#
# Rename all images from 1.webp onwards. With 1.jpg being the first image.
ls | cat -n | while read n f; do mv "$f" `printf "%d.webp" $n`; done
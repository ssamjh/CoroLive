# Remove any existing folder from last animation.
rm -rf /tmp/$1-animation
#
# Create a fresh new folder for this animation.
mkdir /tmp/$1-animation
mkdir /tmp/$1-animation/jpg
mkdir /tmp/$1-animation/webp
#
# Copy all images from today to our temporary folder.
#
cp /var/www/html/corolive.nz/api/$1/archive/latest/*.jpg /tmp/$1-animation/jpg/
cp /var/www/html/corolive.nz/api/$1/archive/latest/*.webp /tmp/$1-animation/webp/
#
# Move to the jpg folder.
cd /tmp/$1-animation/jpg
#
# Rename all images from 1.jpg onwards. With 1.jpg being the first image.
ls | cat -n | while read n f; do mv "$f" `printf "%d.jpg" $n`; done
#
# Move to the jpg folder.
cd /tmp/$1-animation/webp
#
# Rename all images from 1.webp onwards. With 1.jpg being the first image.
ls | cat -n | while read n f; do mv "$f" `printf "%d.webp" $n`; done
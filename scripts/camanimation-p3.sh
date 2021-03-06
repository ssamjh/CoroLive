# Cleanup the old animation files.
rm /var/www/html/corolive.nz/api/$1/archive/latest/animation.webm
rm /var/www/html/corolive.nz/api/$1/archive/latest/animation-fast.webm
#
# Copy the new animations to today's folder.
cp /tmp/$1-animation/webp/animation.webm /var/www/html/corolive.nz/api/$1/archive/latest/animation.webm
cp /tmp/$1-animation/webp/animation-fast.webm /var/www/html/corolive.nz/api/$1/archive/latest/animation-fast.webm
#
# Clean up the api animation files.
rm /var/www/html/corolive.nz/api/$1/animation.webm
rm /var/www/html/corolive.nz/api/$1/animation-fast.webm
#
# Copy the animations to the api folder.
cp /tmp/$1-animation/webp/animation.webm /var/www/html/corolive.nz/api/$1/animation.webm
cp /tmp/$1-animation/webp/animation-fast.webm /var/www/html/corolive.nz/api/$1/animation-fast.webm

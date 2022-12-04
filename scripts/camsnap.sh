# Grab a still from camera.
curl --connect-timeout 2 --retry 4 --retry-delay 1 -s -S -f -o /tmp/$1-camsnap.jpg $2 \
&& cwebp /tmp/$1-camsnap.jpg -quiet -preset photo -q 50 -m 6 -resize 1280 720 -metadata none -o /tmp/$1-camsnap-optimised.webp \
&& mv /tmp/$1-camsnap-optimised.webp "/var/www/html/corolive.nz/api/$1/archive/latest/snap-$(date +%R).webp"
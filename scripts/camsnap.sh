# Grab a still from camera.
curl --connect-timeout 2 --retry 4 --retry-delay 1 -s -S -f -o /tmp/$1-camsnap.jpg $2 \
&& cp /tmp/$1-camsnap.jpg /tmp/$1-camsnap-optim.jpg \
&& mogrify -resize 1280x720 /tmp/$1-camsnap-optim.jpg \
&& jpegoptim --max=45 --strip-all /tmp/$1-camsnap-optim.jpg \
&& mv /tmp/$1-camsnap-optim.jpg "/var/www/html/corolive.nz/api/$1/archive/latest/snap-$(date +%R).jpg" \
&& cwebp /tmp/$1-camsnap.jpg -quiet -preset photo -q 50 -resize 1280 720 -metadata none -o /tmp/$1-camsnap-optimised.webp \
&& mv /tmp/$1-camsnap-optimised.webp "/var/www/html/corolive.nz/api/$1/archive/latest/snap-$(date +%R).webp"


#&& cwebp /tmp/$1-camsnap.jpg -quiet -m 6 -q 65 -psnr 37 -af -sharp_yuv -metadata none -o /tmp/$1-camsnap-optimised.webp

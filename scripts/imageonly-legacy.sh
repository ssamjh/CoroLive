#Grab at :00
curl --connect-timeout 2 --retry 4 --retry-delay 1 -s -S -f -o /tmp/$1-imageonly.jpg $2 \
&& cp /tmp/$1-imageonly.jpg "/var/www/html/corolive.nz/api/$1/snap.jpg" \
&& cwebp /tmp/$1-imageonly.jpg -o /tmp/$1-imageonly.webp \
&& mv /tmp/$1-imageonly.webp "/var/www/html/corolive.nz/api/$1/snap.webp"
sleep 15
#Grab at :15
curl --connect-timeout 2 --retry 4 --retry-delay 1 -s -S -f -o /tmp/$1-imageonly.jpg $2 \
&& cp /tmp/$1-imageonly.jpg "/var/www/html/corolive.nz/api/$1/snap.jpg" \
&& cwebp /tmp/$1-imageonly.jpg -o /tmp/$1-imageonly.webp \
&& mv /tmp/$1-imageonly.webp "/var/www/html/corolive.nz/api/$1/snap.webp"
sleep 15
#Grab at :30
curl --connect-timeout 2 --retry 4 --retry-delay 1 -s -S -f -o /tmp/$1-imageonly.jpg $2 \
&& cp /tmp/$1-imageonly.jpg "/var/www/html/corolive.nz/api/$1/snap.jpg" \
&& cwebp /tmp/$1-imageonly.jpg -o /tmp/$1-imageonly.webp \
&& mv /tmp/$1-imageonly.webp "/var/www/html/corolive.nz/api/$1/snap.webp"
sleep 15
#Grab at :45
curl --connect-timeout 2 --retry 4 --retry-delay 1 -s -S -f -o /tmp/$1-imageonly.jpg $2 \
&& cp /tmp/$1-imageonly.jpg "/var/www/html/corolive.nz/api/$1/snap.jpg" \
&& cwebp /tmp/$1-imageonly.jpg -o /tmp/$1-imageonly.webp \
&& mv /tmp/$1-imageonly.webp "/var/www/html/corolive.nz/api/$1/snap.webp"
i=0
while ((i < 4)); do
        curl --connect-timeout 2 --retry 4 --retry-delay 1 -s -S -f -o /tmp/$1-imageonly.jpg $2 \
        && cwebp /tmp/$1-imageonly.jpg -quiet -preset photo -resize 1920 1080 -o /tmp/$1-imageonly.webp \
        && mv /tmp/$1-imageonly.webp "/var/www/html/corolive.nz/api/$1/snap.webp"
        sleep 15
    ((i++));
done
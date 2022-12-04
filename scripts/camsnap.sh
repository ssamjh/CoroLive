# Download and convert the image in a single command.
curl --connect-timeout 2 --retry 4 --retry-delay 1 -s -S -f "$2" | cwebp -quiet -preset photo -q 50 -m 6 -resize 1280 720 -metadata none -o "/var/www/html/corolive.nz/api/$1/archive/latest/snap-$(date +%R).webp"

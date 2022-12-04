# Remove the old animation files.
rm /var/www/html/corolive.nz/api/$1/{archive/latest,}/{animation.webm,animation-fast.webm}

# Copy the new animations to today's folder and the api folder.
cp /tmp/$1-animation/webp/*.webm /var/www/html/corolive.nz/api/$1/{archive/latest,}/
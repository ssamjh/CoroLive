# Remove the old animation folder.
rm -rf /tmp/$1-animation

# Create a new folder for the animation.
mkdir -p /tmp/$1-animation/webp

# Copy today's images to the temporary folder.
cp /var/www/html/corolive.nz/api/$1/archive/latest/*.webp /tmp/$1-animation/webp/

# Move to the webp folder.
cd /tmp/$1-animation/webp

# Rename all images using a numeric sequence starting at 1.
n=1
for file in *.webp; do
    mv "$file" "$((n++)).webp"
done

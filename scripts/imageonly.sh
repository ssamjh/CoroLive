# Set the initial value of the loop counter.
i=0

# Loop until the counter reaches 4.
while ((i < 4)); do
    # Download and convert the image in a single command.
    curl --connect-timeout 2 --retry 4 --retry-delay 1 -s -S -f "$2" | cwebp -quiet -preset photo -resize 1920 1080 -o "/var/www/html/corolive.nz/api/$1/snap.webp"

    # Wait for 15 seconds before repeating the loop.
    sleep 15

    # Increment the loop counter.
    ((i++));
done

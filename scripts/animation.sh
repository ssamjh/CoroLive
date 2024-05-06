#!/bin/bash

# Set $camera.
camera=$1

# Get the folder path for today's images.
today_folder_path="/var/www/corolive.nz/api/$camera/archive/$(date +%Y/%b/%d)"

# Create the tmp folder varible.
tmp_folder="/run/animation-$camera"

# Set the output file name and format.
output_file="$tmp_folder/animation.webm"

# Set the api folder path.
api_folder="/var/www/corolive.nz/api"



# Remove the old animation folder if it exists.
if [ -d "$tmp_folder" ]; then
    rm -rf "$tmp_folder"
fi

# Create a new folder for the animation.
mkdir -p "$tmp_folder"

# Calculate the number of minutes since the last top of the hour.
minutes_since_last_top_of_hour=$(date +%-M)

# Calculate the timestamp for the last top of the hour.
last_top_of_hour_timestamp=$(date -d "-$minutes_since_last_top_of_hour minutes" '+%Y-%m-%d %H:%M')

# Copy today's images to the temporary folder using find and the -newermt option.
find "$today_folder_path" -name "*.webp" ! -newermt "$last_top_of_hour_timestamp" -exec cp {} "$tmp_folder/" \;

# Move to the webp folder.
cd "$tmp_folder/"

# Rename all images using a numeric sequence starting at 1.
n=1
for file in *.webp; do
    mv "$file" "$((n++)).webp"
done

# Create the animation using ffmpeg in two-pass mode.
ffmpeg -r 12 -i "$tmp_folder/%d.webp" -c:v libvpx-vp9 -b:v 0 -crf 38 -deadline good -cpu-used 5 -vf "format=yuv420p" -pass 1 -an -f null /dev/null \
&& ffmpeg -r 12 -i "$tmp_folder/%d.webp" -c:v libvpx-vp9 -b:v 0 -crf 38 -deadline good -cpu-used 5 -vf "format=yuv420p" -pass 2 -an "$output_file"

# Move the new animation to the api folder.
cp "$output_file" "$api_folder/$camera/animation.webm"

# Remove the animation folder.
rm -rf "$tmp_folder"
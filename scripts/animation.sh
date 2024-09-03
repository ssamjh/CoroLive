#!/bin/bash

# Set $camera.
camera=$1

# Get the folder path for today's images.
today_folder_path="/var/www/corolive.nz/api/$camera/archive/$(date +%Y/%b/%d)"

# Create the tmp folder variable.
tmp_folder="/run/animation-$camera"

# Set the output file name and format.
output_file="$tmp_folder/animation.webm"

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
find "$today_folder_path" -name "*.avif" ! -newermt "$last_top_of_hour_timestamp" -exec cp {} "$tmp_folder/" \;

# Move to the temporary folder.
cd "$tmp_folder/"

# Rename all images using a numeric sequence starting at 1 and create a file list.
n=1
> file_list.txt
for file in *.avif; do
    new_name="$((n++)).avif"
    mv "$file" "$new_name"
    echo "file '$PWD/$new_name'" >> file_list.txt
done

# Create the animation using ffmpeg in two-pass mode with the file list.
ffmpeg -loglevel error -r 12 -f concat -safe 0 -i file_list.txt -c:v libvpx-vp9 -b:v 0 -crf 38 -deadline good -cpu-used 5 -vf "format=yuv420p" -pass 1 -an -f null /dev/null \
&& ffmpeg -loglevel error -r 12 -f concat -safe 0 -i file_list.txt -c:v libvpx-vp9 -b:v 0 -crf 38 -deadline good -cpu-used 5 -vf "format=yuv420p" -pass 2 -an "$output_file"

# Move the new animation to the api folder.
cp "$output_file" "$today_folder_path/animation.webm"

# Remove the animation folder.
rm -rf "$tmp_folder"
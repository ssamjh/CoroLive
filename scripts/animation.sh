#!/bin/bash

# Get the folder path for today's images.
today_folder_path="/var/www/html/corolive.nz/api/$1/archive/$(date +%Y/%b/%d)"

# Create the tmp folder varible
tmp_folder="/tmp/$1-animation"

# Set the output file name and format
output_file="$tmp_folder/animation.webm"

# Set the fast output file name and format
output_file_fast="$tmp_folder/animation-fast.webm"

# Set the api folder path
api_folder="/var/www/html/corolive.nz/api"



# Remove the old animation folder if it exists
if [ -d "$tmp_folder" ]; then
    rm -rf "$tmp_folder"
fi

# Create a new folder for the animation.
mkdir -p "$tmp_folder"

# Copy today's images to the temporary folder.
find "$today_folder_path" -name "*.webp" -exec cp {} "$tmp_folder/" \;

# Move to the webp folder.
cd "$tmp_folder/"

# Rename all images using a numeric sequence starting at 1.
n=1
for file in *.webp; do
    mv "$file" "$((n++)).webp"
done

# Create the animation using ffmpeg in two-pass mode
ffmpeg -r 12 -i "$tmp_folder/%d.webp" -c:v libvpx-vp9 -b:v 0 -crf 38 -deadline good -cpu-used 5 -vf "format=yuv420p" -pass 1 -an -f null /dev/null && \
ffmpeg -r 12 -i "$tmp_folder/%d.webp" -c:v libvpx-vp9 -b:v 0 -crf 38 -deadline good -cpu-used 5 -vf "format=yuv420p" -pass 2 -an "$output_file"

# Create the fast animation using ffmpeg in two-pass mode
ffmpeg -r 30 -i "$tmp_folder/%d.webp" -c:v libvpx-vp9 -b:v 0 -crf 42 -deadline good -cpu-used 5 -vf "format=yuv420p" -pass 1 -an -f null /dev/null && \
ffmpeg -r 30 -i "$tmp_folder/%d.webp" -c:v libvpx-vp9 -b:v 0 -crf 42 -deadline good -cpu-used 5 -vf "format=yuv420p" -pass 2 -an "$output_file_fast"

# Move the new animations to today's folder and api folder.
mv "$output_file" "$today_folder_path/animation.webm" "$api_folder/$1/animation.webm"
mv "$output_file_fast" "$today_folder_path/animation-fast.webm" "$api_folder/$1/animation-fast.webm"

# Remove the animation folder.
rm -rf /tmp/$1-animation
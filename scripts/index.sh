#!/bin/bash

# Set the base directory
base_dir="/var/www/corolive.nz/api"

# Set the camera variable from the command-line argument
camera=$1

# Get the current date and format it
current_date=$(date +%Y/%b/%d)

# Set the full directory path
dir_path="${base_dir}/${camera}/archive/${current_date}"

# Change to the specified directory
cd "$dir_path" || exit

# Create an empty array to store the file names
files=()

# Loop through all the .webp files in the directory and add them to the array
for file in *.webp; do
    files+=("$file")
done

# Sort the array in ascending order
IFS=$'\n' sorted_files=($(sort <<<"${files[*]}"))

# Create the index.json file
echo "[" > index.json
for i in "${!sorted_files[@]}"; do
  file="${sorted_files[$i]}"
  echo "  \"$file\"$([ $i -lt $((${#sorted_files[@]} - 1)) ] && echo ',')" >> index.json
done
echo "]" >> index.json
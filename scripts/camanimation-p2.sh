#!/bin/bash

# Set the directory that contains the files to be animated
input_dir="/tmp/$1-animation/webp"

# Set the output file name and format
output_file="/tmp/$1-animation/webp/animation.webm"

# Set the fast output file name and format
output_file_fast="/tmp/$1-animation/webp/animation-fast.webm"

# Create the animation using ffmpeg in two-pass mode
ffmpeg -r 12 -i "$input_dir/%d.webp" -c:v libvpx-vp9 -b:v 0 -crf 38 -deadline good -cpu-used 5 -vf "format=yuv420p" -pass 1 -an -f null /dev/null && \
ffmpeg -r 12 -i "$input_dir/%d.webp" -c:v libvpx-vp9 -b:v 0 -crf 38 -deadline good -cpu-used 5 -vf "format=yuv420p" -pass 2 -an "$output_file"

# Create the fast animation using ffmpeg in two-pass mode
ffmpeg -r 30 -i "$input_dir/%d.webp" -c:v libvpx-vp9 -b:v 0 -crf 42 -deadline good -cpu-used 5 -vf "format=yuv420p" -pass 1 -an -f null /dev/null && \
ffmpeg -r 30 -i "$input_dir/%d.webp" -c:v libvpx-vp9 -b:v 0 -crf 42 -deadline good -cpu-used 5 -vf "format=yuv420p" -pass 2 -an "$output_file_fast"
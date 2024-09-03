#!/bin/bash

# Create varibles.
mode=$1
camera=$2
url=$3

if [ "$mode" == "snap" ]; then
    
    # Set i to 0.
    i=0
    
    # Loop 2 times to attempt to update every 15s.
    while ((i < 2)); do
        curl --connect-timeout 2 --retry 4 --retry-delay 1 -s -S -f -o /run/$camera-snap.jpg $url \
        && cwebp /run/$camera-snap.jpg -quiet -preset photo -resize 1920 1080 -o /run/$camera-snap.webp \
        && mv /run/$camera-snap.webp "/var/www/corolive.nz/api/$camera/snap.webp"
        
        # Remove tmp file.
        rm "/run/$camera-snap.jpg"
        
        # Wait 30 seconds.
        sleep 30
        
        # Increase i by 1.
        ((i++));
    done
    
    
    elif [ "$mode" == "api" ]; then
    
    # Create varible.
    today_folder_path="/var/www/corolive.nz/api/$camera/archive/$(date +%Y/%b/%d)"
    
    #Create the folder if it doesn't exist.
    if [ ! -d "$today_folder_path" ]; then
        mkdir -p "$today_folder_path"
    fi
    
    curl --connect-timeout 2 --retry 4 --retry-delay 1 -s -S -f -o /run/$camera-api.jpg $url \
    && convert /run/$camera-api.jpg -resize '1280x720>' -quality 53 -define avif:compression-level=4 -define avif:speed=0 -define avif:tiling=1 /run/$camera-api-optimised.avif \
    && mv /run/$camera-api-optimised.avif "$today_folder_path/snap-$(date +%R).avif"
    
    #Remove tmp file.
    rm /run/$camera-api.jpg
    rm /run/$camera-api-resized.jpg
    
else
    exit 1
fi

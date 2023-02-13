#!/bin/bash

# Create varibles.
mode=$1
camera=$2
url=$3

if [ "$mode" == "snap" ]; then
    
    # Set i to 0.
    i=0
    
    # Loop 4 times to attempt to update every 15s.
    while ((i < 4)); do
        curl --connect-timeout 2 --retry 4 --retry-delay 1 -s -S -f -o /run/$camera-snap.jpg $url \
        && cwebp /run/$camera-snap.jpg -quiet -preset photo -resize 1920 1080 -o /run/$camera-snap.webp \
        && mv /run/$camera-snap.webp "/var/www/html/corolive.nz/api/$camera/snap.webp"
        
        # Remove tmp file.
        rm "/run/$camera-snap.jpg"

        # Wait 15 seconds.
        sleep 15

        # Increase i by 1.
        ((i++));
    done
    
    
    elif [ "$mode" == "api" ]; then
    
    # Create varible.
    today_folder_path="/var/www/html/corolive.nz/api/$camera/archive/$(date +%Y/%b/%d)"
    
    #Create the folder if it doesn't exist.
    if [ ! -d "$today_folder_path" ]; then
        mkdir -p "$today_folder_path"
    fi
    
    #Grab a still from camera.
    curl --connect-timeout 2 --retry 4 --retry-delay 1 -s -S -f -o /run/$camera-api.jpg $url \
    && cwebp /run/$camera-api.jpg -quiet -preset photo -q 50 -resize 1280 720 -metadata none -o /run/$camera-api-optimised.webp \
    && mv /run/$camera-api-optimised.webp "$today_folder_path/snap-$(date +%R).webp"
    
    #Remove tmp file.
    rm /run/$camera-api.jpg
    
else
    exit 1
fi

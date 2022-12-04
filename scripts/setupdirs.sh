# Make a directory with today's date.
today=$(date +%Y/%b/%d)
mkdir -p /var/www/html/corolive.nz/api/$1/archive/$today

# Remove the current "latest" folder.
rm -rf /var/www/html/corolive.nz/api/$1/archive/latest

# Recreate the "latest" folder linking to today's date.
ln -s /var/www/html/corolive.nz/api/$1/archive/$today /var/www/html/corolive.nz/api/$1/archive/latest
# Make a directory with todays date.
mkdir -p /var/www/html/corolive.nz/api/$1/archive/$(date +%Y)/$(date +%b)/$(date +%d)
#
# Remove the current "latest" folder.
rm /var/www/html/corolive.nz/api/$1/archive/latest
#
# Recreate the "latest" folder linking to todays date.
ln -s /var/www/html/corolive.nz/api/$1/archive/$(date +%Y)/$(date +%b)/$(date +%d)/ /var/www/html/corolive.nz/api/$1/archive/latest
#
# Setup redirect
#echo -e "return 302 https://\x24host/$1/archive/$(date +%Y)/$(date +%b)/$(date +%d)/;" > /etc/nginx/snippets/$1-redir.conf
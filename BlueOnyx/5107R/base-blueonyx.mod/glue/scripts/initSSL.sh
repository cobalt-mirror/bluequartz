#!/bin/sh

# remove default ssl settings from ssl.conf
target=/etc/httpd/conf.d/ssl.conf
tmpfile=$target.temp
cat $target | \
  sed -e 's|^Listen 443|#Listen 443|g' \
      -e '/^<VirtualHost _default_:443>$/,/^<\/VirtualHost>$/d' \
  > $tmpfile
if test -s $tmpfile
then
  \cp -f $tmpfile $target
fi
rm -f $tmpfile


#!/bin/sh

perl -pi.bak -e 'if (/^AddDefaultCharset/) { s/AddDefaultCharset/#AddDefaultCharset/ }' /etc/httpd/conf/httpd.conf

perl -pi.bak -e 's/LogFormat "%h %l %u %t \\"%r\\" %>s %b \\"%{Referer}i\\" \\"%{User-Agent}i\\"" combined/LogFormat "%v %h %l %u %t \\"%r\\" %>s %b \\"%{Referer}i\\" \\"%{User-Agent}i\\"" combined/g' /etc/httpd/conf/httpd.conf

# disable error alias
perl -pi.bak -e 's|^Alias /error/|#Alias /error/|g' /etc/httpd/conf/httpd.conf

# disable ScriptAlias
perl -pi.bak -e 's/^ScriptAlias/#ScriptAlias/g' /etc/httpd/conf/httpd.conf


#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: set_httpd_alias_perms.pl Sun 10 Apr 2011 07:40:35 AM EDT mstauber $
#
# This constructor sets the GID and permissions on the databases in /etc/httpd/alias/
# to the new requested standards as required by the mod_nss introduced by CentOS-5.6:

# Fix GID and permissions one /etc/httpd/alias/ for new mod_nss:
system('/usr/bin/find /etc/httpd/alias -user root -name "*.db" -exec /bin/chgrp apache {} \;');
system('/usr/bin/find /etc/httpd/alias -user root -name "*.db" -exec /bin/chmod g+r {} \;');

exit(0);


#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: set_httpd_alias_perms.pl Mon 18 Apr 2011 12:32:28 AM EDT mstauber $
#
# This constructor sets the GID and permissions on the databases in /etc/httpd/alias/
# to the new requested standards as required by the mod_nss introduced by CentOS-5.6:

# Fix GID and permissions one /etc/httpd/alias/ for new mod_nss:
if (-e "/etc/httpd/alias/cert8.db") {
        system('/usr/bin/find /etc/httpd/alias -user root -name "*.db" -exec /bin/chgrp apache {} \;');
        system('/usr/bin/find /etc/httpd/alias -user root -name "*.db" -exec /bin/chmod g+r {} \;');   
}

# While we are at it, delete the default CentOS welcome page:
if (-e "/etc/httpd/conf.d/welcome.conf") {
	system('/bin/rm -f /etc/httpd/conf.d/welcome.conf');
}

# A lot of BX servers have ImageMagick installed, which in turn installs and activates the avahi-daemon.
# This daemon is not really needed and certainly should not be running. Hence we stop it and turn it off:
if (-e "/etc/init.d/avahi-daemon") {
	system('/etc/init.d/avahi-daemon stop >/dev/null 2>&1');
	system('/sbin/chkconfig --level 2345 avahi-daemon off');
}

exit(0);


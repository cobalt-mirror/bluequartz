#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: squirrelmail_apache.pl v1.0.1-1 
# Copyright 2006-2014 Solarspeed.net All rights reserved.

# Location of AdmServ config:
$admserv_conf = "/etc/admserv/conf.d/squirrelmail.conf";

# Location of Apache config:
$apache_conf = "/etc/httpd/conf.d/squirrelmail.conf";

#
#### No configureable options below!
#

use CCE;
use Sauce::Service;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';
$cce->connectuds();

# Check for squirrelmail.conf:
if ( -f $apache_conf )  {
    system("/bin/rm -f /etc/admserv/conf.d/squirrelmail.conf");
}

# Several fixes to help with the transition of AdmServ's UID/GID:
system("/bin/chown -R admserv:admserv /var/lib/squirrelmail/prefs");
system("/bin/chown -R root:admserv /etc/squirrelmail/config_local.php");
system("/bin/chown -R root:admserv /etc/squirrelmail/config.php");
system("/bin/chown -R root:admserv /etc/squirrelmail/default_pref");
system("/bin/chown -R root:admserv /etc/squirrelmail/sqspell_config.php");

$cce->bye('SUCCESS');
exit(0);


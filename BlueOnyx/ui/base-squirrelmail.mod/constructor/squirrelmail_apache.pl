#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: squirrelmail_apache.pl v1.0.0.0 Sun 18 Jan 2009 09:50:09 PM EST mstauber Exp $
# Copyright 2006-2009 Solarspeed Ltd. All rights reserved.

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

# Parse squirrelmail.conf to see if it still has the open_basedir directive in it:
if ( -f $apache_conf )  {
    $CHECK_CONFIG = `/bin/cat /etc/httpd/conf.d/squirrelmail.conf | /bin/grep open_basedir | /usr/bin/wc -l`; 
    chomp($CHECK_CONFIG);
    $CHECK_CONFIG_AGAIN = `/bin/cat /etc/httpd/conf.d/squirrelmail.conf | /bin/grep safe_mode_include_dir | /usr/bin/wc -l`; 
    chomp($CHECK_CONFIG_AGAIN);
}

# On bad results copy the "good" squirrelmail.conf from AdmServ over to the public Apache:
unless (($CHECK_CONFIG == "2") && ($CHECK_CONFIG_AGAIN == "2")) {
    if ( -e $admserv_conf ) {
	# Copy config over:
	system("/bin/cp $admserv_conf $apache_conf");

	# Reload Apache:
	Sauce::Service::service_run_init('httpd', 'reload');
    }
}

$cce->bye('SUCCESS');
exit(0);


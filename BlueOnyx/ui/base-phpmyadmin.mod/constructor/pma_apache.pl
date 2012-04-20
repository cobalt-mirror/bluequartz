#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: pma_apache.pl v1.0.0.0 Fri 20 Apr 2012 06:37:17 AM EDT mstauber Exp $
# Copyright 2006-2012 Team Blueonyx. All rights reserved.

# Location of sample phpMyAdmin.conf:
$sample_conf = "/usr/sausalito/configs/phpmyadmin/phpMyAdmin.conf";

# Location of AdmServ config:
$admserv_conf = "/etc/admserv/conf.d/phpMyAdmin.conf";

# Location of Apache config:
$apache_conf = "/etc/httpd/conf.d/phpMyAdmin.conf";

#
#### No configureable options below!
#

use CCE;
use Sauce::Service;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';
$cce->connectuds();

if ( -f $sample_conf )  {
    # Parse phpMyAdmin.conf to see if it still has the open_basedir directive in it:
    if ( -f $apache_conf )  {
        $CHECK_CONFIG = `/bin/cat /etc/admserv/conf.d/phpMyAdmin.conf | /bin/grep open_basedir | /usr/bin/wc -l`; 
        chomp($CHECK_CONFIG);
        if ( -e $apache_conf )  {
                $CHECK_CONFIG_AGAIN = `/bin/cat /etc/httpd/conf.d/phpMyAdmin.conf | /bin/grep safe_mode_include_dir | /usr/bin/wc -l`; 
                chomp($CHECK_CONFIG_AGAIN);
        }
        else {
                $CHECK_CONFIG_AGAIN = "1";
        }
    }

print "1: $CHECK_CONFIG - $CHECK_CONFIG_AGAIN \n";

    # On bad results copy the "good" squirrelmail.conf from AdmServ over to the public Apache:
    unless (($CHECK_CONFIG == "1") && ($CHECK_CONFIG_AGAIN == "1")) {
        if ( -e $admserv_conf ) {
            # Copy config over:
            system("/bin/cp $sample_conf $admserv_conf");
            if ( -f $apache_conf )  {
                system("/bin/cp $sample_conf $apache_conf");
                # Reload Apache:
                Sauce::Service::service_run_init('httpd', 'reload');
            }
        }
    }
}

$cce->bye('SUCCESS');
exit(0);


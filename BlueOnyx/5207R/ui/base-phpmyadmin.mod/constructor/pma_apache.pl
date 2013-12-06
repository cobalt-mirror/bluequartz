#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: pma_apache.pl 
# Copyright 2006-2013 Team Blueonyx. All rights reserved.

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

# 
# Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without modification, 
# are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation and/or 
# other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
# 

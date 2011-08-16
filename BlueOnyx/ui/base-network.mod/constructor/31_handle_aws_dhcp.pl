#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: 31_handle_aws_dhcp.pl, v1.0.0 Sun Aug 14 2011 09:08:46 AM EDT mstauber Exp $
# Copyright 2006-2011 Team BlueOnyx. All rights reserved.
#
# On a regular BlueOnyx install this script does nothing and exits almost right away.
# It is only needed on AWS Clouds or in an environment where the network settings
# are gathered from DHCP.
#
# If DHCP is used, it will do the following:
#
# 1.) It will read in the currently used IP for eth0 and will store it for usage in the "Virtual Site Template".
# 2.) It will reconfigure all Vsites to run on that IP.
# 3.) It will update the Proftpd config file to use the new IP.

# Debugging switch:
$DEBUG = "0";

# Are we using DHCP?
if (!-f "/etc/is_aws") {
    # Apparently not. Great!
    exit(0);
}

use CCE;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';
$cce->connectuds();

#
#### No configureable options below!
#

# Get eth0 IP:

($sys_oid) = $cce->find('Network', {'device' => 'eth0'});
($ok, $eth0) = $cce->get($sys_oid);
$ip = $eth0->{'ipaddr'};

if ($ip) {

    # Update IP in Virtual Site Template:
    ($sys_oid) = $cce->find('System');
    ($ok, $sys) = $cce->get($sys_oid);
    ($ok) = $cce->set($sys_oid, 'VsiteDefaults',{ 'ipaddr' => $ip });

    # Find all Vsites:
    my @vhosts = ();
    my (@vhosts) = $cce->findx('Vsite');
 
    # Walk through all Vsites and update their IP's if necessary:
    for my $vsite (@vhosts) {
	($ok, my $my_vsite) = $cce->get($vsite);
	if ($my_vsite->{'ipaddr'} ne $ip) {
	    ($ok) = $cce->set($vsite, '', { 'ipaddr' => $ip });
	}
    }

}

$cce->bye('SUCCESS');
exit(0);


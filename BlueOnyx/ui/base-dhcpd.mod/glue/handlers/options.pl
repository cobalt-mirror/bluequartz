#!/usr/bin/perl  -I /usr/sausalito/perl
#
# Copyright (c) 2000 Cobalt Networks
# Author: asun@cobalt.com
#
# mangle options that the dhcp server will spit out.
# this doesn't use the event object as it can be called from a 
# bunch of different objects.

use lib '/usr/sausalito/handlers/base/dhcpd/';
use CCE;
use Dhcpd;

my $cce = new CCE;
$cce->connectfd();

# some setup
my @oids = $cce->find('System');

my @netoids = ();
if ( -e "/proc/user_beancounters") {
     @netoids = $cce->find('Network', { 'device' => 'venet0:0' });
}
else {
    @netoids = $cce->find('Network', { 'device' => 'eth0' });
}
my ($ok, $net) = $cce->get($netoids[0], '');
my $ipaddr = $net->{ipaddr};

my %options;

# email options:
my ($ok, $obj) = $cce->get($oids[0], 'Email');
$options{'smtp-server'} = $obj->{enableSMTP} ? $ipaddr : '';
$options{'pop-server'} =  $obj->{enablePop} ? $ipaddr : '';

# ntp:
($ok, $obj) = $cce->get($oids[0], 'Time');
$options{'ntp-servers'} = $obj->{ntpEnabled} ? $ipaddr : '';

# wins
($ok, $obj) = $cce->get($oids[0], 'WinNetwork');
if ($obj->{winsSetting} eq 'self') {
	$options{'netbios-name-servers'} = $ipaddr;
} elsif ($obj->{winsSetting} eq 'others') {
	$options{'netbios-name-servers'} = $obj->{winsIpAddress};
} else {
	$options{'netbios-name-servers'} = '';
}

# web server
$options{'www-server'} = $ipaddr;

Dhcpd::dhcpd_create_conf();
Dhcpd::dhcpd_set_options(%options);

@oids = $cce->find('DhcpParam');
($ok, $obj) = $cce->get($oids[0]);
Dhcpd::dhcpd_hup() if $obj->{enabled};
$cce->bye('SUCCESS');
exit 0;
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without 
# modification, are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, 
# this list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation  
# and/or other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.

#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: networker.pl,v 1.10.2.2 2002/02/26 03:18:19 bservies Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

use strict;

use CCE;
use Sauce::Util;
use Sauce::Service;
use Sauce::Validators;

my $DEBUG = 0;

# declares that should probably go elsewhere:
my $service		= "cobalt-networker";
my $confFile		= "/nsr/res/servers";
my $confBlockStart	= "# Legato NetWorker Config Start"; 
my $confBlockStop	= "# Legato NetWorker Config Stop";
my $port_cmd		= '/usr/bin/nsrports';
my $ok;
my $state;
my $count;

my $cce = new CCE (Namespace => "NetWorker");
$cce->connectfd();

my $obj			= $cce->event_object();
my @lgNetWorkerHost	= $cce->scalar_to_array($obj->{'lgNetWorkerHost'});
my $enabled		= $obj->{'enabled'};

$DEBUG && warn 'NSR: enabled: ' . $enabled . "\n";
if ($enabled && !@lgNetWorkerHost) {
	$DEBUG && warn 'NSR: failed checking for host list data.' . "\n";
	$cce->bye('FAIL', '[[legato-networker.enabledButNoData]]');
	exit 1;
}

my $hostname = `hostname`;
chomp($hostname);
if ($enabled && !checkDNSLookup($hostname)) {
	$cce->bye('FAIL',
	    '[[legato-networker.lgNetWorkerLocalHost_LookupFailed]]');
	exit 1;
}

$DEBUG && warn 'NSR: Checking host list.' . "\n";
foreach my $server (@lgNetWorkerHost) {
	$DEBUG && warn 'NSR: Checking host: ' . $server . "\n";
	if ($enabled && !checkDNSLookup($server)) {
		$cce->bye('FAIL',
		    '[[legato-networker.lgNetWorkerHostField_LookupFailed]]');
		exit 1;
	}
}

# Add hostnames to /nsr/res/servers
$DEBUG && warn 'NSR: Adding host list to configuration file.' . "\n";
my $data = join "\n", @lgNetWorkerHost;
if(! -e $confFile){
	system('/bin/mkdir','-p','/nsr/res');
}
Sauce::Util::replaceblock($confFile, $confBlockStart, $data,
    $confBlockStop, 0600);

# Always stop the service.  Then we will turn it on again if that is desired.
$ok = Sauce::Service::service_toggle_init($service, 0, 'nobg');
$state = Sauce::Service::service_get_init($service);
$count = 0;
while (($count < 10) && ($state != $enabled)) {
	#
	# For some reason, the first time this comes though the state is not
	# correctly set.  So, itterate a few times until the correct state
	# is achieved.
	#
	$DEBUG &&
	    warn "NSR: calling service_toggle_init($service, $enabled, nobg)\n";
	$ok = Sauce::Service::service_toggle_init($service, $enabled, 'nobg');
	$state = Sauce::Service::service_get_init($service);
	$count++;
}
if (! $ok) {
        if ($enabled) {
                $cce->bye('FAIL',
                    '[[legato-networker.cannotStartLegatoServer]]');
        } else {
                $cce->bye('FAIL',
                    '[[legato-networker.cannotStopLegatoServer]]');
        }
        exit 1;
}

if ($enabled) {
	#
        # see if the ports need to be massaged.  NetWorker must be running for
	# this to happen, hence the enabled check.
	#
        my $new_ports = $cce->event_new();
        if ($new_ports->{enabled} || $new_ports->{servicePortLow}
                || $new_ports->{servicePortHigh}) {
                if (system ($port_cmd, '-S', $obj->{servicePortLow} .
                        '-' . $obj->{servicePortHigh})) {
                        $cce->warn('[[legato-networker.cantChangeServPorts]]');
                        $cce->bye('FAIL');
                        exit 1;
                }
        }

        if ($new_ports->{enabled} || $new_ports->{connPortLow} ||
            $new_ports->{connPortHigh}) {
                if (system ($port_cmd, '-C', $obj->{connPortLow} .
                        '-' . $obj->{connPortHigh})) {
                        $cce->warn('[[legato-networker.cantChangeConnPorts]]');
                        $cce->bye('FAIL');
                        exit 1;
                }
        }
}
	
$cce->bye('SUCCESS');
exit 0;

# The basic algorithm is this:
# hostname => ip addresses
# for each address, ipAddress => hostname2 => ipAddress2
# make sure ipAddress == ipAddress2. If they are all equal, good. If not, bad.
# Here are 2 examples:
# --------------------
# www.yahoo.com => 204.71.202.160, 216.115.105.2, 204.71.200.74,
#                  204.71.200.75, 204.71.200.67, 204.71.200.68
# 204.71.202.160 => w1.yahoo.com => 204.71.202.160
#
# They match. Good.
# --------------------
# yahoo.com => 204.71.200.243, 204.71.200.245
# 204.71.200.243 => UNKNOWN.yahoo.com => does not exist
# 204.71.200.245 => UNKNOWN.yahoo.com => does not exist
# They don't exist. Bad
sub checkDNSLookup {
    use Socket;
    my $host = shift; 
    my ($name,$aliases,$addrtype,$length,@addrs) = gethostbyname($host) or return 0;
    my $ipaddr;
    foreach $ipaddr (@addrs) {
	my $revHost = gethostbyaddr($ipaddr, AF_INET) or return 0;
	my $revHostToIP =  gethostbyname($revHost) or return 0;
	if ($ipaddr != $revHostToIP) {
	    return 0;
	}
    }
    return 1;
}
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

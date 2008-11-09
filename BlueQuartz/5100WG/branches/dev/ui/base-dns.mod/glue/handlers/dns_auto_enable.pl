#!/usr/bin/perl -w
# $Id: dns_auto_enable.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# Detects self-reference in DNS servers, enables named accordingly

my $DEBUG = 0;
$DEBUG && open(STDERR, ">>/tmp/dns_auto_enable");
$DEBUG && warn `date` .' '. $0;

use lib qw( /usr/sausalito/perl );
use CCE;
$cce = new CCE;
$cce->connectfd();

my ($sysoid) = $cce->find('System');
my ($ok, $obj) = $cce->get($sysoid, 'DNS');

# Do nothing if bind is already enabled
if($obj->{enabled})
{
	$DEBUG && warn "named already enabled, exiting\n";
	$cce->bye('SUCCESS');
	exit 0;
}

my ($sok, $system) = $cce->get($sysoid, "");
$DEBUG && warn 'system DNS servers: '.$system->{dns}."\n";
my $enable_named = 0;

# This special case is caught by the setup wizard
$enable_named = 1 if ($system->{dns} =~ /&127.0.0.1&/);

my (@netoids) = $cce->find('Network');
foreach my $oid (@netoids)
{
	last if ($enable_named);

	my ($ok, $net) = $cce->get($oid);
	my $search = '&'.$net->{ipaddr}.'&';
	$DEBUG && warn "Per-network match: $search\n";
	if($system->{dns} =~ /$search/)
	{
		$enable_named = 1;
	}
}

if($enable_named)
{
	$DEBUG && warn "Enabling bind...\n";
	$cce->set($sysoid, 'DNS', {'enabled' => 1});
}

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

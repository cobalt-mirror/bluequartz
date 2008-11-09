#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: servicequota_usage.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# Update service quotas 

use CCE;
use Quota;

my $DEBUG = 0;
$DEBUG && open(STDERR, ">>/tmp/servicequota");
$DEBUG && warn `date`."$0\n";

my $cce = new CCE;
$cce->connectfd();

# return error string, null for success
my $err;

# find ServiceQuota objects
foreach my $oid ($cce->find('ServiceQuota'))
{
	$DEBUG && warn "Found ServiceQuota oid $oid\n";

	my($ok, $service) = $cce->get($oid);
	next unless ($ok);

	my($aok, $site) = $cce->get( 
		($cce->find('Vsite', {'name' => $service->{site}}))[0]
		);
	next unless ($aok);

	my $used = 0;
	if($service->{isgroup})
	{
		my $gid = getgrnam($service->{account});
		($used) = Quota::query(Quota::getqcarg($site->{volume}), 
			$gid, 1);
		$DEBUG && warn "Found group $gid usage $used for volume $site->{volume}\n";
	}
	else
	{
		my $pid = getpwnam($service->{account});
		($used) = Quota::query(Quota::getqcarg($site->{volume}), 
			$pid);
		$DEBUG && warn "Found user $pid usage $used for volume $site->{volume}\n";
	}

	$used ||= 0;
	($ok) = $cce->set($oid, '', {'used' => $used}) if ($used =~ /^\d+$/);
}

if($err) 
{
    $cce->bye('FAIL', $err);
    exit(1);
}

$cce->bye('SUCCESS');
exit(0);

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

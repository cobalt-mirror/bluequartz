#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: service_quota.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# manages the per-site anonymous FTP ServiceQuota objects for site-level
# disk usage stats

use CCE;
use Data::Dumper;

my $DEBUG = 0;
$DEBUG && open STDERR, ">/tmp/service_quota";
$DEBUG && warn `date`."$0\n";

my $cce = new CCE;
$cce->connectfd();

my $err; # Global error message/return state

# Just the facts
my $oid = $cce->event_oid();
my $obj = $cce->event_object();
my $obj_new = $cce->event_new();
my $obj_old = $cce->event_old();

# Find matching ServiceQuota objects
my $sitegroup = $obj_old->{anonymousOwner};
$sitegroup ||= $obj->{anonymousOwner};
my @oids = $cce->find('ServiceQuota', {
	'site' => $sitegroup,
	'label' => '[[base-ftp.anonServiceQuota]]',
	}); 

if($cce->event_is_destroy() || !$obj->{anonymous})
{
	# destroy the associated ServiceQuota object
	foreach my $i (@oids)
	{
		my ($ret, @info) = $cce->destroy($i);
		$err .= '[[base-ftp.couldNotClearFtpQuotaMon]]' 
			unless ($ret);
	}
} 
elsif ($obj_new->{anonymous})
{
	# create a ServiceQuota object
	$owner = $obj->{anonymousOwner};
	$owner =~ tr/[a-z]/[A-Z]/;

	my($ret) = $cce->create('ServiceQuota', { 
		'label' => '[[base-ftp.anonServiceQuota]]',
		'site' => $obj->{anonymousOwner},
		'account' => $owner,
		'isgroup' => 0,
		'quota' => $obj->{quota},
		'used' => 0,
		});
		
	$err .= '[[base-ftp.couldNotSetFtpQuotaMon]]' 
		unless ($ret);
}
elsif ($obj_new->{quota})
{
	foreach my $i (@oids)
	{
		my ($ret) = $cce->set($i, '', {
			'quota' => $obj_new->{quota}
			});
		$err .= '[[base-ftp.couldNotUpdateFtpQuotaMon]]' 
			unless ($ret);
	}
} 

if($err)
{
	$cce->bye('FAIL', $err);
	exit 1;
}
else
{
	$cce->bye('SUCCESS');
	exit 0;
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

#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: bandwidth.pl,v 1.20 2001/11/06 07:09:47 uzi Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# Author: Joshua Uziel

use CCE;
use Sauce::Util;
use Sauce::Service;

$bw_setip_prog = '/sbin/bw_setip';

my $cce = new CCE;
$cce->connectfd();

# Get the bandwidth object
my ($bw_ok, $bwoid) = $cce->get($cce->event_oid());
if (not $bw_ok) {
	$cce->bye('FAIL');
	exit(1);
}

if ($bwoid->{bwlimit} >= 10) {
	bw_setip($bwoid->{ipaddr}, $bwoid->{bwlimit});
} else {
	$cce->bye('FAIL', '[[base-bandwidth.valuelow]]');
	exit(1);
}

$cce->bye('SUCCESS');
exit(0);

#--------------------------------------------------------------------------#
# Subroutines
#--------------------------------------------------------------------------#

sub bw_setip
{
	my ($ipaddr, $bwlimit)  = @_;

	my $limit = $bwlimit * 128;	# 1024/8 = 128

	my $direction = 'w';
	my $protocol  = 'tcp';

	# Set port if needed.
	my $protport = "-p $protocol";
	$protport .= "/$port" if ($port);

	my @cmdline = ("$bw_setip_prog", "-i $ipaddr", "-b $limit");
	@cmdline = (@cmdline, $protport);
	@cmdline = (@cmdline, " -t $direction") if ($direction);
#ROLLBACK SPECIAL
	my $ret = system("@cmdline > /dev/null 1>&2");
	if ($ret) {
		$cce->bye('FAIL', '[[base-bandwidth.setlimitfail]]');
		exit 1;
	}

	# Write info to the file... this is for when the system starts up.
	my %settings;

	# CCE limit is in kbps, we want bps.
	$settings{'BWLIMIT'} = $limit;
	$settings{'IP'} = $ipaddr;

	$ret = Sauce::Util::editfile("/etc/sysconfig/bwmgmt/$ipaddr.conf",
		*Sauce::Util::keyvalue_edit_fcn, '#', '=', undef, %settings);
	unless ($ret) {
		$cce->bye('FAIL', '[[base-bandwidth.cantsetipfile]]');
		exit 1;
	}

	return $ret;
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

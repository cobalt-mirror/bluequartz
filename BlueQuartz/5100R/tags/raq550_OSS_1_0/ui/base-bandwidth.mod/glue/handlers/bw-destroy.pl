#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: bw-destroy.pl,v 1.11 2001/09/28 08:01:59 uzi Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# Author: Joshua Uziel

use CCE;
use Sauce::Util;
use Sauce::Service;

$bw_setip_prog = '/sbin/bw_setip';

my $cce = new CCE;
$cce->connectfd();

# Get the IP address we're workin' with.
my $bwobj = $cce->event_old();

# Regardless, we get rid of that interface's limit.
bw_delip($bwobj->{ipaddr});

$cce->bye('SUCCESS');
exit(0);

#--------------------------------------------------------------------------#
# Subroutines
#--------------------------------------------------------------------------#

sub bw_delip
{
	my $ipaddr = shift;

	my $protocol = 'tcp';

	my @cmdline = ("$bw_setip_prog", "-i $ipaddr", "-p $protocol", "-d");
	my $ret = system("@cmdline > /dev/null 1>&2");
	if ($ret) {
		$cce->bye('FAIL', '[[base-bandwidth.dellimitfail]]');
		exit 1;
	}

	# Kill the config of this interface for startup
	Sauce::Util::unlinkfile("/etc/sysconfig/bwmgmt/$ipaddr.conf");

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

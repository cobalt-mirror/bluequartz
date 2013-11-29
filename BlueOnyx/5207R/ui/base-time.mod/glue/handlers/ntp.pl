#!/usr/bin/perl -w -I/usr/sausalito/perl
#
# $Id: ntp.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#
# Configure and start/stop ntpd.
#

use CCE;
use Sauce::Service;
use Sauce::Util;
use Sauce::Validators;

# defines that should probably go elsewhere
my $NTPconf = '/etc/ntp.conf';
my $ntpd = 'ntpd';
my $tickers = '/etc/ntp/step-tickers';

my $cce = new CCE(Namespace => "Time");
$cce->connectfd();

my $time_obj = $cce->event_object();

# set the ntp Address and toggle the ntp server based upon its existence
my $ntpAddress = $time_obj->{ntpAddress};
if ($ntpAddress) {
	# ntp server defined
	if (!Sauce::Validators::netaddr($ntpAddress)) {
		$cce->baddata(0, 'ntpAddress', "[[base-time.ntpAddress_invalid]]");
		$cce->bye('FAIL');
		exit(1);
	} else {
		# NTP address is good, update the conf file	
		if (!Sauce::Util::editfile($NTPconf, *update_ntp_conf,
					    $ntpAddress)) { 
			$cce->warn("[[base-time.errorWritingConfFile]]");
		}

		# set the time on system startup
		`echo '$ntpAddress' > $tickers`;
	}  
}
Sauce::Service::service_toggle_init($ntpd, $ntpAddress);

# this is used by other objects
my @oids = $cce->find('System');
if ($#oids > -1) {
	my $new = $ntpAddress ? 1 : 0;
	my $old = $time_obj->{ntpEnabled} ? 1 : 0;
	if ($old != $new) {
		$cce->set($oids[0], 'Time', { 'ntpEnabled' => $new });
	}
}
$cce->bye('SUCCESS');
exit 0;

sub update_ntp_conf 
{
	my ($fin, $fout, $ntpAddress) = @_;

	my $begin_servers = '# begin Cobalt Section';
	my $end_servers = '# end Cobalt Section';
	my $mcast_help = '# Uncomment the following line to use ntpd as a multicast client';

	my $found_mcast_help = 0;
	
	while (<$fin>) {
		if (/^$begin_servers$/ .. /^$end_servers$/) {
			# skip the servers section and re-add below
			next;
		} elsif (/^$mcast_help$/) {
			$found_mcast_help = 1;
		} elsif (/^#*\s*multicastclient/) {
			if ($found_mcast_help) {
				# if the help msg is there, leave it as is
				print $fout $mcast_help, "\n";
				print $fout $_;
			} else {
				# add multi cast help message
				print $fout $mcast_help, "\n";

				# comment out multicastclient if not already
				if (!/^#/) {
					print $fout '# ', $_;
				}
			}
		} else {
			# some other line, leave it there
			print $fout $_;
		}
	}

	# add servers section
	print $fout $begin_servers, "\n";
	if ($ntpAddress) {
		print $fout "server    $ntpAddress\n";
	}
	print $fout $end_servers, "\n";

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

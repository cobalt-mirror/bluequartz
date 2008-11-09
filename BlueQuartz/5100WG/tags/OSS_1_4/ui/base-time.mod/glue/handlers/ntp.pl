#!/usr/bin/perl -w -I/usr/sausalito/perl

use CCE;
use Sauce::Service;
use Sauce::Util;
use Sauce::Validators;

sub update_ntp_conf {
	my ($fin, $fout, $ntpAddress) = @_;
	print $fout "server    $ntpAddress\n";
	return 0;
}

# defines that should probably go elsewhere
my $NTPconf = '/etc/ntp.conf';
my $ntpd = 'ntpd';
my $tickers = '/etc/ntp/step-tickers';

my $cce = new CCE ( Namespace => "Time" );
$cce->connectfd();

my $time_obj = $cce->event_object();

# set the ntp Address and toggle the ntp server based upon its existence
my $ntpAddress = $time_obj->{ntpAddress};
if ($ntpAddress) {
	# ntp server defined
	if ( ! Sauce::Validators::netaddr ( $ntpAddress )) {
		$cce->baddata(0, 'ntpAddress', "[[base-time.ntpAddress_invalid]]");
	} else {
		# NTP address is good,    update the conf file	
		Sauce::Util::editblock( $NTPconf, *update_ntp_conf,
					'# begin Cobalt Section',
					'# end Cobalt Section',
					$ntpAddress) && 
			$cce->warn("[[base-time.errorWritingConfFile]]");

		# set the time on system startup
		`echo '$ntpAddress' > $tickers`;
	}  
}
Sauce::Service::service_toggle_init($ntpd, $ntpAddress);

# this is used by other objects
my @oids = $cce->find('System');
if ($#oids > -1) {
	my $new = $ntpAddress ? 't' : '';
	my $old = $time_obj->{ntpEnabled} ? 't' : '';
	$cce->set($oids[0], 'Time', 
		 { 'ntpEnabled' => $new }) if ($old ne $new);
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

#!/usr/bin/perl -w -I/usr/sausalito/perl -I. -I/usr/sausalito/handlers/base/network
# $Id: istatd_fix_conf.pl
# Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT

use strict;
use Sauce::Util;
use Sauce::Config;
use Sauce::Service;
use CCE;
use Network;

my $DEBUG = 0;

my $cce = new CCE;
$cce->connectuds();

# If we're NOT on OpenVZ, we can exit right away:
if (!-f "/proc/user_beancounters") {
    $cce->bye('SUCCESS');
    exit(0);
}
else {

	# Find out if istatd is enabled at the moment:
	my @oids = $cce->find('System');
	if (not @oids) {
		$cce->bye('FAIL');
		exit 1;
	}
	my ($ok, $obj) = $cce->get($oids[0], 'iStat');
	unless ($ok and $obj) {
		$cce->bye('FAIL');
		exit 1;
	}

	# Change monitored network interface to 'venet0':
	my %settings;
	$settings{'monitor_net'} = '( venet0 )';
	Sauce::Util::editblock('/etc/istat.conf',
			       *Sauce::Util::keyvalue_edit_fcn,
			       '# Note: Only support for one network interface, limited by client.',
			       '# Array of disks to monitor. Specify mount path or device name.',
			       '#', ' ', '', %settings);

	# Restart istatd, but only if it's currently enabled:
	if ($obj->{enabled} == "1") {
		Sauce::Service::service_send_signal('istatd', 'HUP');
	}

	# Cleanup:
	system("/bin/rm -f /etc/istat.conf*back*");

}

$cce->bye('SUCCESS');
exit(0);

#
# Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
# 
# Redistribution and use in source and binary forms, with or without modification, 
# are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation and/or 
# other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
# 

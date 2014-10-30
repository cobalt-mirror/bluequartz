#!/usr/bin/perl -I. -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/ftp
# $Id: syncFTP.pl Tue 13 Jan 2009 08:15:26 PM CET mstauber $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

use Sauce::Util;
use Sauce::Config;
use Sauce::Service;
use ftp;
use CCE;

my $cce = new CCE;
$cce->connectuds();

my @oids = $cce->find('System');
if (not @oids) {
	$cce->bye('FAIL');
	exit 1;
}

my ($ok, $obj) = $cce->get($oids[0], 'Ftp');
unless ($ok and $obj) {
	$cce->bye('FAIL');
	exit 1;
}

# get connect rate
my $connectRate = $obj->{connectRate};

# handle connections
my %settings;
$settings{'MaxClients'} = $obj->{maxConnections};
$settings{'UseReverseDNS'} = $obj->{useReverseDNS} ? 'on' : 'off';
$settings{'IdentLookups'} = 'off';
Sauce::Util::editblock('/etc/proftpd.conf',
		*Sauce::Util::keyvalue_edit_fcn,
		'# begin global -- do not delete',
		'# end global -- do not delete',
		'#', ' ', undef, %settings);

Sauce::Util::editblock('/etc/proftpds.conf',
		*Sauce::Util::keyvalue_edit_fcn,
		'# begin global -- do not delete',
		'# end global -- do not delete',
		'#', ' ', undef, %settings);

# Force IdentLookups off to be included in the Global section.
my $data = "AllowOverwrite		yes
  <Limit ALL SITE_CHMOD>
    AllowAll
  </Limit>
  # Restrict the range of ports from which the server will select when sent the
  # PASV command from a client. Use IANA-registered ephemeral port range of
  # 49152-65534
  PassivePorts 49152 65534
  IdentLookups 			off";
Sauce::Util::replaceblock('/etc/proftpd.conf',
			  '<Global>',
			  $data,
			  '</Global>');

Sauce::Util::replaceblock('/etc/proftpds.conf',
			  '<Global>',
			  $data,
			  '</Global>');

# handle guest
my $err = Sauce::Util::editblock(ftp::ftp_getconf, *ftp::edit_anon,
				 '# begin anonymous -- do not delete',
				 '# end anonymous -- do not delete',
				 $fobj->{guestEnabled}, 
				 $fobj->{guestUser}, $fobj->{guestGroup},
				 $fobj->{guestWorkGroup});

my $err = Sauce::Util::editblock(ftp::ftps_getconf, *ftp::edit_anon,
				 '# begin anonymous -- do not delete',
				 '# end anonymous -- do not delete',
				 $fobj->{guestEnabled}, 
				 $fobj->{guestUser}, $fobj->{guestGroup},
				 $fobj->{guestWorkGroup});

# handle enabled
my $old = Sauce::Service::service_get_xinetd('proftpd') ? 'on' : 'off';
my $new = $obj->{enabled} ? 'on' : 'off';
Sauce::Service::service_set_xinetd('proftpd', $new, $connectRate);

# See: "[BlueOnyx:00144] SLOW FTP  -- a partial solution"
# Or: http://www.wains.be/index.php/2006/01/24/slow-logins-under-proftpd-using-xinetd/
system("/usr/bin/perl -pi -e 's|DURATION USERID|DURATION|g' /etc/xinetd.d/proftpd");

Sauce::Service::service_send_signal('xinetd', 'HUP');
system('rm -f /etc/xinetd.d/proftpd.backup.*');
system('rm -f /etc/proftpd.conf.backup.*');
system('rm -f /etc/proftpd.conf.middle.backup.*');
system('rm -f /etc/proftpd.conf.start');
system('rm -f /etc/proftpd.conf.middle');
system('rm -f /etc/proftpd.conf.end');

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

#!/usr/bin/perl -I. -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/ftp
# $Id: syncFTP.pl

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
  IdentLookups 		off";

# Actually we don't want top modify the <GLOBAL></GLOBAL> Container anymore.
# At least not at the present time.
#Sauce::Util::replaceblock('/etc/proftpd.conf', '<Global>', $data, '</Global>');
#Sauce::Util::replaceblock('/etc/proftpds.conf', '<Global>', $data, '</Global>');

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
system('rm -f /etc/xinetd.d/proftpds.backup.*');
system('rm -f /etc/proftpd.conf.backup.*');
system('rm -f /etc/proftpd.conf.middle.backup.*');
system('rm -f /etc/proftpd.conf.start');
system('rm -f /etc/proftpd.conf.middle');
system('rm -f /etc/proftpd.conf.end');
system('rm -f /etc/proftpds.conf.backup.*');
system('rm -f /etc/proftpds.conf.middle.backup.*');
system('rm -f /etc/proftpds.conf.start');
system('rm -f /etc/proftpds.conf.middle');
system('rm -f /etc/proftpds.conf.end');

$cce->bye('SUCCESS');
exit 0;

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#	 notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#	 notice, this list of conditions and the following disclaimer in 
#	 the documentation and/or other materials provided with the 
#	 distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#	 contributors may be used to endorse or promote products derived 
#	 from this software without specific prior written permission.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
# COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
# POSSIBILITY OF SUCH DAMAGE.
# 
# You acknowledge that this software is not designed or intended for 
# use in the design, construction, operation or maintenance of any 
# nuclear facility.
# 
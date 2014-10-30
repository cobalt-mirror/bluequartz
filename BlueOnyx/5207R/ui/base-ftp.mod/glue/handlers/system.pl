#!/usr/bin/perl -I/usr/sausalito/handlers/base/ftp -I/usr/sausalito/perl
# $Id: system.pl 
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# handle ftp access
#
use CCE;
use Sauce::Util;
use Sauce::Service;
use ftp;

my $cce = new CCE(Namespace => "Ftp");
$cce->connectfd();

my $obj = $cce->event_object();
my $old = $cce->event_old();

my %settings;

# maximum number of users. always set this.
$settings{'MaxClients'} = $obj->{maxConnections};
$settings{'UseReverseDNS'} = $obj->{useReverseDNS} ? 'on' : 'off';
my $ok = Sauce::Util::editblock(ftp::ftp_getconf, 
				 *Sauce::Util::keyvalue_edit_fcn,
				 '# begin global -- do not delete',
				 '# end global -- do not delete',
				 '#', ' ', undef, %settings);
if (not $ok) {
    $cce->bye('FAIL', '[[base-ftp.cantConfigFile]]');
    exit 1;
}

my $ok = Sauce::Util::editblock(ftp::ftps_getconf, 
         *Sauce::Util::keyvalue_edit_fcn,
         '# begin global -- do not delete',
         '# end global -- do not delete',
         '#', ' ', undef, %settings);
if (not $ok) {
    $cce->bye('FAIL', '[[base-ftp.cantConfigFile]]');
    exit 1;
}

# Manage xinetd.d/proftpd
$lastRate = $old->{connectRate}; # Pun intended...
$firstRate = $obj->{connectRate};
$firstRate ||= 80;

# proftpd:
$old = Sauce::Service::service_get_xinetd('proftpd') ? 'on' : 'off';
my $new = $obj->{enabled} ? 'on' : 'off';
if (($old ne $new) || ($lastRate ne $firstRate)) {
  Sauce::Service::service_set_xinetd('proftpd', $new, $firstRate);
  Sauce::Service::service_send_signal('xinetd', 'HUP');
}

# proftpds:
$old = Sauce::Service::service_get_xinetd('proftpds') ? 'on' : 'off';
my $new = $obj->{ftpsEnabled} ? 'on' : 'off';
if (($old ne $new) || ($lastRate ne $firstRate)) {
  Sauce::Service::service_set_xinetd('proftpds', $new, $firstRate);
  Sauce::Service::service_send_signal('xinetd', 'HUP');
}

$cce->bye('SUCCESS');
exit 0;

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#   notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#   notice, this list of conditions and the following disclaimer in 
#   the documentation and/or other materials provided with the 
#   distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#   contributors may be used to endorse or promote products derived 
#   from this software without specific prior written permission.
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
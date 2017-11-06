#!/usr/bin/perl -I/usr/sausalito/perl
# Authors: Brian N. Smith and Michael Stauber
# $Id: update_mysqlPass.pl
#

use CCE;
use Sauce::Util;
use Sauce::Service;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG) {
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $cce = new CCE;
$cce->connectuds();

$firstboot = "0";
my @oids = $cce->find('System');
if (!defined($oids[0])) {
    $cce->bye('SUCCESS');
    exit 0;
}
else {
    ($ok, $obj) = $cce->get($oids[0]);
    if ($obj->{'isLicenseAccepted'} == "0") {
        $firstboot = "1";
    }
}
my ($ok, $Netata) = $cce->get($oids[0], "Netdata");
my @moids = $cce->find('MySQL');
if (!defined($moids[0])) {
    $cce->bye('SUCCESS');
    exit 0;
}

my ($ok, $MySQL) = $cce->get($moids[0], "");
$pass = $MySQL->{'sql_rootpassword'};

&debug_msg("Updating pass: " . $new . "\n");
$cmdline = 'sed -i -e "s@pass     :.*@pass     : ' . "'" . $pass . "'" . '@" /etc/netdata/python.d/mysql.conf';
&debug_msg($cmdline . "\n");
system($cmdline);
service_toggle_init('netdata', $Netata->{enabled});

$cce->bye('SUCCESS');
exit 0;

sub debug_msg {
    if ($DEBUG) {
        my $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
}

# 
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
# Copyright (C) 2006, NuOnce Networks, Inc. All rights reserved.
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#    notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#    notice, this list of conditions and the following disclaimer in 
#    the documentation and/or other materials provided with the 
#    distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#    contributors may be used to endorse or promote products derived 
#    from this software without specific prior written permission.
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

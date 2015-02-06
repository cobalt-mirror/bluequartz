#!/usr/bin/perl -I/usr/sausalito/perl
# Authors: Brian N. Smith and Michael Stauber
# $Id: mysql-changePass.pl
#

use CCE;
use Sauce::Util;
use Sauce::Service;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();

my @oids = $cce->find('System');
if (!defined($oids[0])) {
	exit 0;
}

my ($ok, $nuMySQL) = $cce->get($oids[0], "mysql");

my @moids = $cce->find('MySQL');
if (!defined($moids[0])) {
        exit 0;
}

my ($ok, $MySQL) = $cce->get($moids[0], "");

$host = $MySQL->{'sql_host'};
$port = $MySQL->{'sql_port'};

$user = $nuMySQL->{'mysqluser'};
$old = $nuMySQL->{'oldpass'};
$new = $nuMySQL->{'newpass'};

&debug_msg("user: " . $user . "\n");
&debug_msg("old: " . $old . "\n");
&debug_msg("new: " . $new . "\n");

if (( $old eq "-1" ) && ($new ne "")) {
	&debug_msg("Setting new: " . $new . "\n");
	$cmdline = "/usr/bin/mysqladmin --user=$user --host=$host --port=$port password " . '"' . $new . '"';
    system($cmdline);
    &debug_msg($cmdline . "\n");
} 
elsif (($old != "") && ($new == "")) {
	$cmdline = "/usr/bin/mysqladmin --user=$user --host=$host --port=$port --password=" . '"' . $old . '"' . " password \"\"";
    system($cmdline);
    &debug_msg($cmdline . "\n");
}
else {
	&debug_msg("Setting new: " . $new . "\n");
	$cmdline = "/usr/bin/mysqladmin --user=$user --host=$host --port=$port --password=" . '"' . $old . '"' . " password " . '"' . $new . '"';
    system($cmdline);
    &debug_msg($cmdline . "\n");
}

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
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# Copyright (C) 2006, NuOnce Networks, Inc. All rights reserved.
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
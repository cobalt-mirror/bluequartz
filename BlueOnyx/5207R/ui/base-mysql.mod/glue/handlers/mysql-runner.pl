#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: mysql-runner.pl
#

use CCE;
use Sauce::Util;
use Sauce::Service;

my $cce = new CCE;
$cce->connectfd();

$oid = $cce->event_oid();
$obj = $cce->event_object();

my @oids = $cce->find('System');
if (!defined($oids[0])) {
        exit 0;
}

my ($ok, $mysql) = $cce->get($oids[0], "mysql");

$enable = $mysql->{'enabled'};

# Get status of MySQL:
$pid = `/sbin/pidof mysqld`;
chomp($pid);

# Check if we have a 5209R with MariaDB ("1") or MySQL ("0"):
$cmd_chk_build = `cat /etc/build|grep 5209R|wc -l`;
$cmd_mysqld = chomp($cmd_chk_build);

if ( $enable eq "1" ) {
	if ( ! $pid ) {
		$tmp = Sauce::Service::service_run_init('mysqld', 'start');
		if ($cmd_mysqld eq "1") {
			# 5209R with MariaDB:
			$tmp = Sauce::Service::service_set_init('mariadb', 'on', '345');
		}
		else {
			# Anything else with MySQL:
			$tmp = Sauce::Service::service_set_init('mysqld', 'on', '345');
		}
	}
} else {
	if ( $pid ) {
		$tmp = Sauce::Service::service_run_init('mysqld', 'stop');
		
		if ($cmd_mysqld eq "1") {
			# 5209R with MariaDB:
			$tmp = Sauce::Service::service_set_init('mariadb', 'on', '345');
		}
		else {
			# Anything else with MySQL:
			$tmp = Sauce::Service::service_set_init('mysqld', 'off', '345');
		}
	}
}

sleep 5;

$cce->bye('SUCCESS');
exit 0;

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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
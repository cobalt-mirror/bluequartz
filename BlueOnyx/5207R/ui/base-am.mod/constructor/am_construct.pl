#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: am_construct.pl

use strict;
use CCE;

my ($cce, @oids);
my $oid = 0;
my $success = 0;

$cce = new CCE;
$cce->connectuds();

# check if AM exists already
@oids = $cce->find("ActiveMonitor");
if ($#oids >= 0) {
	# check if it is exactly 1 
	if ($#oids > 0) {
		#FIXME: cleanup in the case of a screwup
		print STDERR "We already have " . scalar(@oids) . " ActiveMonitor object(s)\n";
	}
	$success = 1;
	$oid = $oids[0];
} else {
	# add
	($success) = $cce->create("ActiveMonitor", 
		{ alertEmailList => "admin",
		  enabled => 1});

	if ($success) {
		@oids = $cce->find("ActiveMonitor");
		if ($#oids >= 0) {
			$oid = $oids[0];
			$success = 1;
		} else {
			$success = 0;
		}
	}
}

# failed?
if(!$success) {
  print STDERR "Constructor for ActiveMonitor failed\n";
  $cce->bye();
  exit 1;
}

# clear old data on EVERY reboot 
my @names;
($success, @names) = $cce->names($oid);
foreach $_ (@names) {
	$cce->set($oid, $_, {
		currentState => "N",
		currentMessage => "",
		lastChange => time()});
}

$cce->bye();

exit 0;

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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
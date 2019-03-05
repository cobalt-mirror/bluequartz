#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: gen_adm_cert.pl
#
# Make sure an ssl cert exists for the admin server.

use strict;
use lib qw(/usr/sausalito/perl);
use CCE;

if (-e '/etc/admserv/certs/certificate') {
	exit(0);
}

my $cce = new CCE;
$cce->connectuds();

# find the system object
my ($sys_oid) = $cce->find('System');
my ($ok, $sys) = $cce->get($sys_oid);

#
# FIXME: This checks to make sure the number of days valid for the default
# 	 cert doesn't push past the y2038 bug barrier (rollover because time
#	 is only 4 bytes in C).  This should be removed if/when the y2038 bug
#	 is fixed.  This will occur in about 9 years or so as of 1/14/2002, and
#	 more than likely this script will no longer be in use.  Hey, I was
#	 bored.
# FIXED: This actually removes current time from bug_time in y2038 and subtracts 20 days
#        to avoid any strange race conditions. I REALLY HOPE WE DON'T USE 32-bit Linux
#        machines with the y2038 present when the time comes... PBaltz was wrong thouch
#        to hope for a quick fix! :) / Rickard Osser <rickard.osser@bluapp.com>
#
my $current_time = time();
my $bug_time = (2 ** 31) - 100;  # T-100 seconds to avoid race conditions
my $difference = $bug_time - $current_time;
my $days_valid = int($difference / 86400)- 20; # T - 20 days to avoid race conditions.

# if the clock in the server by any chance is set to something that is past, hardcode 9995 days as that won't break anything.
if ( $days_valid > 9995 ) {
    $days_valid = 9995;
}
if ($difference < 0) { 
        # need to correct days valid 
        $days_valid += int($difference / (24 * 60 * 60)); 
} 

# set up ssl
($ok) = $cce->set($sys_oid, 'SSL',
		  {
			'createCert' => time(),
			'daysValid' => $days_valid,
			'country' => 'SS',
			'state' => 'SS',
			'city' => 'Generic',
			'orgName' => 'Project BlueOnyx'
		  });
$cce->bye();
exit(0);

# 
# Copyright (c) 2017 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2017 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
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
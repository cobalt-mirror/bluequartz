#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: am_support.pl
#
# This constructor is run at the end of Swatch.
#
# It checks if a Support-Account is present and if so, it checks the 
# access_epoch on it. If the time is past the access_epoch, it deletes
# the support account.
#

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

#
#### No configureable options below!
#

use CCE;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';

$cce->connectuds();

&debug_msg("Checking Expiry of Support Account.");

# Get the System / Support Object:
@sysoids = $cce->find('System');
($ok, $Support) = $cce->get($sysoids[0], 'Support');

# Get current Epoch:
$epoch_now = time();

# Identify the Support-Account:
$support_account = $Support->{'support_account'};
$access_epoch = $Support->{'access_epoch'};

# Get the Epoch of the SOS-Report:
$sos_epoch = $Support->{'sos_epoch'};

# Handle deletion of the SOS report once it is older than 72 hours:
$sos_epoch_expiry = $sos_epoch*24*60*60;
if (($Support->{'sos_present'} == '1') && ($epoch_now > $sos_epoch_expiry)) {
	&debug_msg("SOS Report is older than three days. Deleting it.");
	($success) = $cce->set($sysoids[0], 'Support', { 'sos_delete' => $epoch_now });
}

# If the 'access_epoch' is '0', then we do NOT expire the Support-Account and can exit right here:
if ($access_epoch == "0") {
	&debug_msg("Support Account is set to never expire.");
	$cce->bye('SUCCESS');
	exit(0);
}

if ($access_epoch < $epoch_now) {
	&debug_msg("Support Account is past its expiry.");

	# Find out if the Support-Account exists on the system:
	(@oids) = $cce->find('User', { "capLevels" => 'adminUser', 'name' => $support_account });
	if ($#oids >= 0) {
		($ok, $USER) = $cce->get($oids[0]);
		&debug_msg("OID of Support-Account: $oids[0]");

		&debug_msg("Disabling Shell Access for Support-Account");
        ($success) = $cce->set($oids[0], 'Shell', { 'enabled' => '0' });

		&debug_msg("Deleting Support-Account");
        ($success) = $cce->destroy($oids[0]);

        # Check twice that it's really gone, as deletion of 'adminUsers' has its quirks:
       	(@oids) = $cce->find('User', { "capLevels" => 'adminUser', 'name' => $support_account });
		if ($#oids >= 0) {
    	    ($success) = $cce->destroy($oids[0]);
    	}
       	(@oids) = $cce->find('User', { "capLevels" => 'adminUser', 'name' => $support_account });
		if ($#oids >= 0) {
    	    ($success) = $cce->destroy($oids[0]);
    	}
		$cce->bye('SUCCESS');
		exit(0);
	}
	else {
		&debug_msg("Support-Account: Not present.");
		$cce->bye('SUCCESS');
		exit(0);
	}
}
else {
	&debug_msg("Support Account is NOT YET past its expiry.");
	$cce->bye('SUCCESS');
	exit(0);
}

$cce->bye('SUCCESS');
exit(0);

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

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#     notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#     notice, this list of conditions and the following disclaimer in 
#     the documentation and/or other materials provided with the 
#     distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#     contributors may be used to endorse or promote products derived 
#     from this software without specific prior written permission.
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

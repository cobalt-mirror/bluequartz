#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: update_pam_abl_settings.pl

# This handler is run whenever pam_abl is modified through the GUI.

# Debugging switch:
$DEBUG = "0";

# Location of pam_abl_config:
$pam_abl_config = "/etc/security/pam_abl.conf";

#
#### No configureable options below!
#

use CCE;
use Data::Dumper;
use Sauce::Util;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';

$cce->connectfd();

# Get our events from the event handler stack:
$oid = $cce->event_oid();
$obj = $cce->event_object();

$old = $cce->event_old();
$new = $cce->event_new();

# Get Object pam_abl_settings for from CODB:
($ok, $abl_settings) = $cce->get($oid);
$PAM_ABL_OID = $oid;

# We're creating or modifying the pam_abl_settings object:
if ((($cce->event_is_create()) || ($cce->event_is_modify())) && ($PAM_ABL_OID eq $oid)) {
    # Someone used the GUI to edit some parameters. Update
    # the existing config file:
    if (-f $pam_abl_config) {

	# Variable cleanup:
	$host_purge = $abl_settings->{"host_purge"};
	$user_purge = $abl_settings->{"user_purge"};
	$host_rule = $abl_settings->{"host_rule"};
	$user_rule = $abl_settings->{"user_rule"};

	# Edit config:
        if (!Sauce::Util::editfile($pam_abl_config, *edit_pam_abl_config, $host_purge, $user_purge, $host_rule, $user_rule)) {
                $cce->bye('FAIL', "Cannot edit $pam_abl_config");
                exit(1);
        }
    }
    else {
	# Ok, we have a problem: No config found.
	# So we just weep silently and exit. 
	$cce->bye('FAIL', "$pam_abl_config not found!");
	exit(1);
    }
}

$cce->bye('SUCCESS');
exit(0);

sub edit_pam_abl_config {

        my($in, $out, $xhost_purge, $xuser_purge, $xhost_rule, $xuser_rule) = @_;
        my($new_config) = <<EOF;
# /etc/security/pam_abl.conf
# debug
host_db=/var/lib/abl/hosts.db
host_purge=$xhost_purge
host_rule=$xhost_rule
user_db=/var/lib/abl/users.db
user_purge=$xuser_purge
user_rule=$xuser_rule
EOF
        print $out $new_config;
        return 1;
}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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
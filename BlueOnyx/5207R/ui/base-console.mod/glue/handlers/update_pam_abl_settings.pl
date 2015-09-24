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
    # Someone used the GUI to edit some parameters. Update the existing config file:
    if (-f $pam_abl_config) {

        # Variable cleanup:
        $host_purge = $abl_settings->{"host_purge"};
        $host_rule = $abl_settings->{"host_rule"};
        @host_whitelist_array = $cce->scalar_to_array($abl_settings->{"host_whitelist"});

        $host_whitelist = '';
        foreach $wl_ip (@host_whitelist_array) {
            $host_whitelist .= $wl_ip . ';';
        }
        $host_whitelist .= '';


        # Edit config:
        if (!Sauce::Util::editfile($pam_abl_config, *edit_pam_abl_config, $host_purge, $host_rule, $host_whitelist)) {
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

    my($in, $out, $xhost_purge, $xhost_rule, $xhost_whitelist) = @_;
    my($new_config) = <<EOF;
db_home=/var/lib/pam_abl
host_db=/var/lib/pam_abl/hosts.db
host_purge=$xhost_purge
host_rule=$xhost_rule
#user_db=/var/lib/pam_abl/users.db
user_purge=1d
user_rule=*:3/1h
host_clear_cmd=[logger] [clear] [host] [%h]
host_block_cmd=[logger] [block] [host] [%h]
user_clear_cmd=[logger] [clear] [user] [%u]
user_block_cmd=[logger] [block] [user] [%u]
limits=1000-1200
host_whitelist=$xhost_whitelist
user_whitelist=admin
EOF
    print $out $new_config;
    return 1;
}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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
#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: sshd_handler.pl
#
# This handler is run whenever SSHd is modified through the GUI.

# Debugging switch:
$DEBUG = "0";

# Location of sshd_config:
$sshd_config = "/etc/ssh/sshd_config";

# Location of root's .bashrc:
$bashrc = "/root/.bashrc";

#
#### No configureable options below!
#

use CCE;
use Data::Dumper;
use Sauce::Service;
use Sauce::Util;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';

$cce->connectfd();

# Get our events from the event handler stack:
$oid = $cce->event_oid();
$obj = $cce->event_object();

$old = $cce->event_old();
$new = $cce->event_new();

# Get Object SSH for from CODB:
($ok, $sshd_settings) = $cce->get($oid, "SSH");
$SSH_server_OID = $oid;

# We're creating or modifying the SSH object:
if ((($cce->event_is_create()) || ($cce->event_is_modify())) && ($SSH_server_OID eq $oid)) {

    # Someone used the GUI to edit some parameters. Update
    # the existing config and restart the daemon:
    if (-f $sshd_config) {
    # Edit config:
    &ini_read;
    &edit_sshd_config;


    # Restart daemon:   
    &restart_sshd;
    }
    else {
    # Ok, we have a problem: No config found.
    # So we just weep silently and exit. 
    $cce->bye('FAIL', "sshd_config not found!");
    exit(1);
    }

    # If we have a /root/.bashrc we edit it if needed:
    if (-f $bashrc) {
        # Edit /root/.bashrc as needed:
        &handle_bashrc;
    }

}

$cce->bye('SUCCESS');
exit(0);

sub restart_sshd {
    # Restarts SSHd - hard restart - if services is enabled:
    if ($sshd_settings->{"enabled"} eq "1") {
    service_run_init('sshd', 'restart');
    }
}

sub edit_sshd_config {

    # Convert CODB values to output values:
    if ($sshd_settings->{"PermitRootLogin"} eq "0") {
    $sshd_settings->{"PermitRootLogin"} = "no";
    }
    else {
    $sshd_settings->{"PermitRootLogin"} = "yes";
    }

    if (($sshd_settings->{"Protocol"} ne "2") && ($sshd_settings->{"Protocol"} ne "2,1") && ($sshd_settings->{"Protocol"} ne "1,2") && ($sshd_settings->{"Protocol"} ne "1")) {
    $sshd_settings->{"Protocol"} = "2";
    }

    &portlist;
    $portfound = "0";
    foreach $port (@portlist) {
    if ($sshd_settings->{"Port"} eq $port) {
        $portfound = "1";
    }
    }
    if ($portfound ne "1") {
    if ($CONFIG{'Port'}) {
        $sshd_settings->{"Port"} = $CONFIG{'Port'};
    }
    else {
        $sshd_settings->{"Port"} = "22";
    }
    }

    # Build output hash:
    $server_sshd_settings_writeoff = { 
    'PermitRootLogin' => $sshd_settings->{"PermitRootLogin"}, 
    'Port' => $sshd_settings->{"Port"}, 
    'Protocol' => $sshd_settings->{"Protocol"},
    'X11Forwarding' => 'no',
    'AllowTcpForwarding' => 'no'
    };

    # Write changes to config file using Sauce::Util::hash_edit_function. The really GREAT thing
    # about this function is that it replaces existing values and appends those new ones that 
    # are missing in the output file. And it does it for ALL values in our hash in one go.

    $ok = Sauce::Util::editfile(
        $sshd_config,
        *Sauce::Util::hash_edit_function,
        '#',
        { 're' => '(\s*)', 'val' => ' ' },
        $server_sshd_settings_writeoff);

    # Error handling:
    unless ($ok) {
        $cce->bye('FAIL', "Error while editing sshd_config!");
        exit(1);
    }
}

sub portlist {
    # List of ports that we allow SSHd to run on:
    @portlist = (
    '22',
    '222',
    '303',
    '333',
    '691',
    '768',
    '769',
    '900',
    '901',
    '902',
    '903',
    '904',
    '905',
    '906',
    '907',
    '908',
    '909',
    '909',
    '2002'
        );
}

sub handle_bashrc {
    $ret =  (Sauce::Util::editfile(
        $bashrc,
        *bashrc_edit,
        $sshd_settings->{"PermitRootLogin"})
        );

    # Error handling:
    unless ($ret) {
        $cce->bye('FAIL', "Error while editing $bashrc!");
        exit(1);
    }
}

sub bashrc_edit {

    # If PermitRootLogin is allowed, we need to remove the /bin/echo lines
    # from /root/.bashrc. Otherwise RSYNC and SCP as root will not work.
    # Under Aventurin{e} in a BlueOnyx template we will not append the
    # echo lines in any case, as /root/network_settings.sh doesn't exist.

        my($in, $out, $enabled) = @_;
        my $codeBase = <<EOF;
/bin/echo ""
/bin/echo "To change your network settings from the command line, run"
/bin/echo "the command /root/network_settings.sh"
/bin/echo ""
/bin/echo "To remove this notice, edit /root/.bashrc"
/bin/echo ""
EOF
        my $found = 0;

        while(<$in>) {
                if(/^\/bin\/echo(.*)$/) {
                        $DEBUG && warn "WITHIN codeBase, neato: $_";
                        $found = 1;
                        if (($enabled eq "no") && (! -e "/proc/user_beancounters")) {
                            print $out $_;
                        }
                }
                else {
                        print $out $_;
                }
        }
        $DEBUG && warn "Found in block? $found\n";

    if (($enabled eq "no") && (!$found) && (! -e "/proc/user_beancounters")) {
        print $out $codeBase;
    }
        return 1;
}

# Read and parse config:
sub ini_read {
    open (F, $sshd_config) || die "Could not open $sshd_config: $!";

    while ($line = <F>) {
        chomp($line);
        next if $line =~ /^\s*$/;                       # skip blank lines
        next if $line =~ /^\#*$/;                       # skip comment lines
        if ($line =~ /^([A-Za-z_\.]\w*)/) {
            $line =~s/\#(.*)$//g;                       # Remove trailing comments in lines
            $line =~s/\"//g;                            # Remove double quotation marks

            @row = split (/ /, $line);                  # Split row at the delimiter
            $CONFIG{$row[0]} = $row[1];                 # Hash the splitted row elements
        }
    }
    close(F);

    # At this point we have all switches from the config cleanly in a hash, split in key / value pairs.
    # To read to which value "key" is set we query $CONFIG{'key'} for example.

}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
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
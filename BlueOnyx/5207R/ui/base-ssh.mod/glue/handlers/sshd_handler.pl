#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: sshd_handler.pl
#
# This handler is run whenever SSHd is modified through the GUI.

# Debugging switch:
$DEBUG = "0";
if ($DEBUG) {
    use Sys::Syslog qw( :DEFAULT setlogsock);
    &debug_msg("Debug enabled.\n");
}

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

# Get 'System' Object:
@sysoids = $cce->find('System');
($ok, $System) = $cce->get($sysoids[0]);

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

    # Convert selected CODB bool values (0|1) to config file values (No|no|Yes|yes):
    if ($System->{isLicenseAccepted} eq "1") {
        # Only modify the 'PermitRootLogin' settings if the initial setup has been completed.
        if ($sshd_settings->{"PermitRootLogin"} eq "0") {
            $sshd_settings->{"PermitRootLogin"} = "no";
        }
        else {
            $sshd_settings->{"PermitRootLogin"} = "yes";
        }
    }

    if ($sshd_settings->{"XPasswordAuthentication"} eq "0") {
        $sshd_settings->{"XPasswordAuthentication"} = "no";
    }
    else {
        $sshd_settings->{"XPasswordAuthentication"} = "yes";
    }
    
    if ($sshd_settings->{"RSAAuthentication"} eq "0") {
        $sshd_settings->{"RSAAuthentication"} = "no";
    }
    else {
        $sshd_settings->{"RSAAuthentication"} = "yes";
    }

    if ($sshd_settings->{"PubkeyAuthentication"} eq "0") {
        $sshd_settings->{"PubkeyAuthentication"} = "no";
    }
    else {
        $sshd_settings->{"PubkeyAuthentication"} = "yes";
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
        'PasswordAuthentication' => $sshd_settings->{"XPasswordAuthentication"}, 
        'RSAAuthentication' => $sshd_settings->{"RSAAuthentication"}, 
        'PubkeyAuthentication' => $sshd_settings->{"PubkeyAuthentication"}, 
        'Port' => $sshd_settings->{"Port"}, 
        'Protocol' => $sshd_settings->{"Protocol"},
        'X11Forwarding' => 'no',
        'StrictModes' => 'no',
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

    # The kickstart scripts or the installer add echo lines to /root/.bashrc
    # that explain how to change the network settings. These lines must be 
    # removed if PermitRootLogin is allowed. Otherwise RSYNC and SCP as root 
    # will not work. Additionally: Once removed, these lines should not really
    # come back on their own. So we only add them under the following conditions:
    #
    # - $sshd_settings->{"PermitRootLogin"} is still set to '0'
    # - $System->{isLicenseAccepted} is still set to '0'
    # - /root/network_settings.sh actually (still) exists.
    #

    &debug_msg("Editing /root/.bashrc - PermitRootLogin is set to: " . $sshd_settings->{"PermitRootLogin"} . "\n");

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
            $found = 1;
            &debug_msg("Found echo lines, removing them.\n");
        }
        else {
            &debug_msg("Printing $_. \n");
            print $out $_;
        }
    }

    #if (($enabled eq "no") && (!$found) && (! -e "/proc/user_beancounters")) {
    if (($sshd_settings->{"PermitRootLogin"} eq "0") && ($System->{isLicenseAccepted} eq "0") && (-f "/root/network_settings.sh")) {
        &debug_msg("PermitRootLogin is set to '0', 'isLicenseAccepted' is set to '0': Adding network settings line to /root/.bashrc. \n");
        print $out $codeBase;
    }
    else {
        &debug_msg("System is past initial setup, PermitRootLogin is not '0' or /root/network_settings.sh missing. Not adding network settings line to /root/.bashrc \n");
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

sub in_array {
    my ($arr,$search_for) = @_;
    my %items = map {$_ => 1} @$arr; # create a hash out of the array values
    return (exists($items{$search_for}))?1:0;
}

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
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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

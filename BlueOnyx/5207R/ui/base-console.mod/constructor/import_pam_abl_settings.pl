#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: import_pam_abl_settings.pl

# This script parses /etc/security/pam_abl.conf and brings CODB up to date on how pam_abl is configured.
# It also sets up PAM to use PAM_ABL by copying the right PAM config files in place.

# Debugging switch:
$DEBUG = "0";

# Uncomment correct type:
$whatami = "constructor";
#$whatami = "handler";

# Location of /etc/security/pam_abl.conf:
$pam_abl_conf = "/etc/security/pam_abl.conf";

#
#### No configureable options below!
#

use CCE;
use Data::Dumper;

my $cce = new CCE;
if ($whatami eq "handler") {
    $cce->connectfd();
}
else {
    $cce->connectuds();
}

#
## Set up PAM:
#
# Figure out platform:
my ($fullbuild) = `cat /etc/build`;
chomp($fullbuild);
my ($build, $model, $lang) = ($fullbuild =~ m/^build (\S+) for a (\S+) in (\S+)/);

# Copy the right PAM config files into the right places:
if (($model eq "5107R") || ($model eq "5207R")) {
    if (-d "/usr/sausalito/configs/pam.d/el6") {
       system("/bin/cp /usr/sausalito/configs/pam.d/el6/* /etc/pam.d/");
    }
}
if (($model eq "5108R") || ($model eq "5208R")) {
    if (-d "/usr/sausalito/configs/pam.d/el6.64") {
       system("/bin/cp /usr/sausalito/configs/pam.d/el6.64/* /etc/pam.d/");
    }
}
if ($model eq "5106R") {
    if (-d "/usr/sausalito/configs/pam.d/el5") {
       system("/bin/cp /usr/sausalito/configs/pam.d/el5/* /etc/pam.d/");
    }
}
if ($model eq "5209R") {
    if (-d "/usr/sausalito/configs/pam.d/el7") {
       system("/bin/cp /usr/sausalito/configs/pam.d/el7/* /etc/pam.d/");
    }
}

# Config file present?
if (-f $pam_abl_conf) {

    # Array of config switches that we want to update in CCE:
    &items_of_interest;

    # Read, parse and hash pam_abl.conf:
    &ini_read;
        
    # Verify input and set defaults if needed:
    &verify;
        
    # Shove ouput into CCE:
    &feedthemonster;
}
else {
    # Ok, we have a problem: No pam_abl.conf found.
    # So we just weep silently and exit. 
    $cce->bye('FAIL', "$pam_abl_conf not found!");
    exit(1);
}

$cce->bye('SUCCESS');
exit(0);

# Read and parse pam_abl.conf:
sub ini_read {
    open (F, $pam_abl_conf) || die "Could not open $pam_abl_conf: $!";

    while ($line = <F>) {
        chomp($line);
        next if $line =~ /^\s*$/;           # skip blank lines
        next if $line =~ /^\#*$/;           # skip comment lines
        if ($line =~ /^([A-Za-z_\.]\w*)/) {     
            $line =~s/\s//g;                # Remove spaces
            $line =~s/#(.*)$//g;            # Remove trailing comments in lines
            $line =~s/\"//g;                # Remove double quotation marks

            @row = split (/=/, $line);      # Split row at the equal sign. Unfortunately if there are more than one
                                            # equal signs in a line we get multiple parts that we need to join again.
            @temprow = @row;
            @sectemprow = ();
            delete @temprow[0];             # Delete first entry in the array, which contains the key, leaving only the values.
            $trnums = @temprow;             # Count number of entries in array
            if ($trnums == "1") {
                $CONFIG{$row[0]} = $temprow[0];
            }
            elsif ($trnums == "2") { 
                $CONFIG{$row[0]} = $temprow[0] . $temprow[1];
            }
            elsif ($trnums == "3") { 
                $CONFIG{$row[0]} = $temprow[0] . $temprow[1] . "=" . $temprow[2];
            }
            elsif ($trnums > "3") { 
                @sectemprow = @temprow;
                delete @sectemprow[0];
                delete @sectemprow[1];
                delete @sectemprow[2];
                $the_value = join("=", @sectemprow);
                $CONFIG{$row[0]} = $temprow[0] . $temprow[1] . "=" . $temprow[2] . $the_value;
            }
        }
    }
    close(F);

    # At this point we have all switches from pam_abl.conf cleanly in a hash, split in key / value pairs.
    # To read how "user_rule" is set we query $CONFIG{'user_rule'} for example. 

    # For debugging only:
    if ($DEBUG > "1") {
        while (my($k,$v) = each %CONFIG) {
            print "$k => $v\n";
        }
    }

    # For debugging only:
    if ($DEBUG == "1") {
        print "user_rule: "      . $CONFIG{'user_rule'} . "\n";
        print "host_rule: "      . $CONFIG{'host_rule'} . "\n";
        print "host_whitelist: " . $CONFIG{'host_whitelist'} . "\n";
    }
}

sub verify {

    # Go through list of config switches we're interested in:
    foreach $entry (@whatweneed) {
        if (!$CONFIG{"$entry"}) {
            # Found key without value - setting defaults for those that need it:
            if ($entry eq "host_purge") {
                $CONFIG{"$entry"} = "1d";
            }
            if ($entry eq "host_rule") {
                $CONFIG{"$entry"} = "*:30/1h";
            }
            if ($entry eq "host_whitelist") {
                $CONFIG{"$entry"} = "127.0.0.1/32";
            }
        }

        $oldstyle_config_found = '0';
        if ($CONFIG{"user_db"} eq "/var/lib/abl/users.db")  {
            # Found an old style config file. Need to update!
            $oldstyle_config_found = '1';
        }

        if ($CONFIG{"host_whitelist"})  {
            if ($CONFIG{"host_whitelist"} =~ /;/) {
                @hwl = split (/;/, $CONFIG{"host_whitelist"});
                $CONFIG{"host_whitelist"} = $cce->array_to_scalar(@hwl);
                $CONFIG{"host_whitelist"} =~s/%2F/\//g;
            }
        }

        # For debugging only:
        if ($DEBUG == "1") {
            print $entry . " = " . $CONFIG{"$entry"} . "\n";
        }
    }

}

sub feedthemonster {
    @oids = $cce->find('pam_abl_settings');
    if ($#oids < 0) {
        # Object not yet in CCE. Creating new one:
        ($ok) = $cce->create('pam_abl_settings', {
            'host_purge' => $CONFIG{"host_purge"},  
            'host_rule' => $CONFIG{"host_rule"},  
            'host_whitelist' => $CONFIG{"host_whitelist"}
            });
    }
    else {
        # Object already present in CCE. Updating it, NOT forcing a rewrite of pam_abl.conf.
        ($sys_oid) = $cce->find('pam_abl_settings');
        ($ok, $sys) = $cce->get($sys_oid);
        ($ok) = $cce->set($sys_oid, '',{
            'host_purge' => $CONFIG{"host_purge"},  
            'host_rule' => $CONFIG{"host_rule"},  
            'host_whitelist' => $CONFIG{"host_whitelist"}
        });
    }

    # If we found an old style config file we force an update of it:
    if ($oldstyle_config_found eq "1") {
        ($sys_oid) = $cce->find('pam_abl_settings');
        ($ok, $sys) = $cce->get($sys_oid);
        ($ok) = $cce->set($sys_oid, '',{
            'update_config' => time(),
            'force_update' => time(),
            });
    }
}

sub items_of_interest {
    # List of config switches that we're interested in:
    @whatweneed = ( 
        'host_purge', 
        'host_rule',
        'host_whitelist'
    );
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
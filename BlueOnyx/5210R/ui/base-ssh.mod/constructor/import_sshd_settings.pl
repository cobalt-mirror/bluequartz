#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: import_sshd_settings.pl

# This script parses /etc/ssh/sshd_config and brings CODB up to date on how SSH is configured.

# Debugging switch:
$DEBUG = "0";
if ($DEBUG) {
    use Sys::Syslog qw( :DEFAULT setlogsock);
    &debug_msg("Debug enabled.\n");
}

# Uncomment correct type:
$whatami = "constructor";
#$whatami = "handler";

# Location of sshd_config:
$sshd_config = "/etc/ssh/sshd_config";

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

# Array setup:
@yes = ('Yes', 'yes', '1');
@boolKeys = ('PermitRootLogin', 'PasswordAuthentication', 'RSAAuthentication', 'PubkeyAuthentication');

# Config file present?
if (-f $sshd_config) {

	# Array of config switches that we want to update in CCE:
	&items_of_interest;

	# Read, parse and hash config:
    &ini_read;
        
    # Verify input and set defaults if needed:
    &verify;
        
    # Shove ouput into CCE:
    &feedthemonster;
}
else {
	# Ok, we have a problem: No config file found.
	# So we just weep silently and exit. 
	$cce->bye('FAIL', "$sshd_config not found!");
	exit(1);
}

$cce->bye('SUCCESS');
exit(0);

# Read and parse config:
sub ini_read {
    open (F, $sshd_config) || die "Could not open $sshd_config: $!";

    while ($line = <F>) {
        chomp($line);
        next if $line =~ /^\s*$/;               	# skip blank lines
        next if $line =~ /^\#*$/;               	# skip comment lines
        if ($line =~ /^([A-Za-z_\.]\w*)/) {		
			$line =~s/\#(.*)$//g; 					# Remove trailing comments in lines
			$line =~s/\"//g; 						# Remove double quotation marks

            @row = split (/ /, $line);				# Split row at the delimiter
            &debug_msg("Reading: $row[0] - $row[1] \n");
    	    $CONFIG{$row[0]} = $row[1];				# Hash the splitted row elements
        }
    }
    close(F);

    # At this point we have all switches from the config cleanly in a hash, split in key / value pairs.
    # To read to which value "key" is set we query $CONFIG{'key'} for example. 

}

sub verify {

    # Find out if we have ever run before:
    @oid = $cce->find('System');
    ($ok, $sshd_settings) = $cce->get($oid, "SSH");

    if ($#oids < 0) {
		$first_run = "1";
    }
    else {
		if ($sshd_settings{'force_update'} eq "") {
		    $first_run = "1";
		}
		else {
		    $first_run = "0";
		}
    }

    # Go through list of config switches we're interested in:
    foreach $entry (@whatweneed) {
		if (!$CONFIG{"$entry"}) {
		    # Found key without value - setting defaults for those that need it:
		    if ($entry eq "PermitRootLogin") {
		    	&debug_msg("Defaulting: $entry - $CONFIG{$entry}\n");
				$CONFIG{"$entry"} = "0";
		    }
		    if ($entry eq "Protocol") {
		    	&debug_msg("Defaulting: $entry - $CONFIG{$entry}\n");
				$CONFIG{"$entry"} = "2";
		    }
		    if ($entry eq "Port") {
		    	&debug_msg("Defaulting: $entry - $CONFIG{$entry}\n");
				$CONFIG{"$entry"} = "22";
		    }
		    if ($entry eq "PasswordAuthentication") {
		    	&debug_msg("Defaulting: $entry - $CONFIG{$entry}\n");
				$CONFIG{"$entry"} = "yes";
		    }
		    if ($entry eq "RSAAuthentication") {
		    	&debug_msg("Defaulting: $entry - $CONFIG{$entry}\n");
				$CONFIG{"$entry"} = "no";
		    }
		    if ($entry eq "PubkeyAuthentication") {
		    	&debug_msg("Defaulting: $entry - $CONFIG{$entry}\n");
				$CONFIG{"$entry"} = "yes";
		    }
		}

		if ($CONFIG{Protocol} eq "2") {
			$CONFIG{RSAAuthentication} = "0";
		}

		# Convert selected config file values (No|no|Yes|yes) to bool (0|1) for CODB:
		if (in_array(\@boolKeys, $entry)) {
			if (in_array(\@yes, $CONFIG{$entry})) {
				$CONFIG{$entry} = '1';
			}
			else {
				$CONFIG{$entry} = '0';
			}
		}

		# For debugging only:
        if ($DEBUG == "1") {
		    print $entry . " = " . $CONFIG{"$entry"} . "\n";
		}
		&debug_msg("Post-Verify: $entry - $CONFIG{$entry}\n");
    }
}

sub feedthemonster {

	if ($DEBUG == "1") {
	    foreach $entry (@whatweneed) {
			print $entry . " = " . $CONFIG{"$entry"} . "\n";
	    }
	}

    @oid = $cce->find('System');
    ($ok, $sshd_settings) = $cce->get($oid);

        # Object already present in CCE. Updating it.
        ($sys_oid) = $cce->find('System');
        ($ok, $sys) = $cce->get($sys_oid);
        ($ok) = $cce->update($sys_oid, 'SSH',{
		    'Port' => $CONFIG{"Port"},  
		    'Protocol' => $CONFIG{"Protocol"},   
		    'PermitRootLogin' => $CONFIG{"PermitRootLogin"},
		    'XPasswordAuthentication' => $CONFIG{"PasswordAuthentication"},
		    'RSAAuthentication' => $CONFIG{"RSAAuthentication"},
		    'PubkeyAuthentication' => $CONFIG{"PubkeyAuthentication"},
		    'force_update' => time()  
        });
    

}

sub items_of_interest {
    # List of config switches that we're interested in:
    @whatweneed = ( 
		'PermitRootLogin', 
		'Protocol', 
		'Port',
		'PasswordAuthentication',
		'RSAAuthentication',
		'PubkeyAuthentication'
	);
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
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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
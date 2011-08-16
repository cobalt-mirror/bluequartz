#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: import_sshd_settings.pl, v1.1.0.0 Thu Dec 04 02:39:45 2008 Exp $
# Copyright 2006-2008 Solarspeed Ltd. All rights reserved.

# This script parses /etc/ssh/sshd_config and brings CODB up to date on how SSH is configured.

# Debugging switch:
$DEBUG = "0";

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
my $conf = '/var/lib/cobalt';

if ($whatami eq "handler") {
    $cce->connectfd();
}
else {
    $cce->connectuds();
}

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
	    $line =~s/\#(.*)$//g; 			# Remove trailing comments in lines
	    $line =~s/\"//g; 				# Remove double quotation marks

            @row = split (/ /, $line);			# Split row at the delimiter
    	    $CONFIG{$row[0]} = $row[1];			# Hash the splitted row elements
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
		$CONFIG{"$entry"} = "0";
	    }
	    if ($entry eq "Protocol") {
		$CONFIG{"$entry"} = "2";
	    }
	    if ($entry eq "Port") {
		$CONFIG{"$entry"} = "22";
	    }
	}
	# Convert to schema format:
	if (($CONFIG{"PermitRootLogin"} eq "No") || ($CONFIG{"PermitRootLogin"} eq "no")) {
		$CONFIG{"PermitRootLogin"} = "0";
	}
	if (($CONFIG{"PermitRootLogin"} eq "Yes") || ($CONFIG{"PermitRootLogin"} eq "yes")) {
		$CONFIG{"PermitRootLogin"} = "1";
	}
	# For debugging only:
        if ($DEBUG == "1") {
	    print $entry . " = " . $CONFIG{"$entry"} . "\n";
	}
    }
}

sub feedthemonster {

    @oid = $cce->find('System');
    ($ok, $sshd_settings) = $cce->get($oid);

        # Object already present in CCE. Updating it.
        ($sys_oid) = $cce->find('System');
        ($ok, $sys) = $cce->get($sys_oid);
        ($ok) = $cce->set($sys_oid, 'SSH',{
	    'Port' => $CONFIG{"Port"},  
	    'Protocol' => $CONFIG{"Protocol"},   
	    'PermitRootLogin' => $CONFIG{"PermitRootLogin"},   
	    'force_update' => time()  
        });
    
}

sub items_of_interest {
    # List of config switches that we're interested in:
    @whatweneed = ( 
	'PermitRootLogin', 
	'Protocol', 
	'Port' 
	);
}

$cce->bye('SUCCESS');
exit(0);


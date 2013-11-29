#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: import_pam_abl_settings.pl, v1.0.0-2 Thu 28 Jun 2011 11:24:369 AM CEST mstauber Exp $
# Copyright 2006-2009 Solarspeed Ltd. All rights reserved.
# Copyright 2009-2011 Team BlueOnyx. All rights reserved.

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
my $conf = '/var/lib/cobalt';

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

# Copy the right config file in place:
if (($model eq "5107R") || ($model eq "5108R")) {
    if (-e "/etc/pam.d/password-auth-ac.5107R") {
	system("/bin/cp /etc/pam.d/password-auth-ac.5107R /etc/pam.d/password-auth-ac");
    }
}
if ($model eq "5106R") {
    if (-e "/etc/pam.d/system-auth.5106R") {
	system("/bin/cp /etc/pam.d/system-auth.5106R /etc/pam.d/system-auth");
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
        next if $line =~ /^\s*$/;               	# skip blank lines
        next if $line =~ /^\#*$/;               	# skip comment lines
        if ($line =~ /^([A-Za-z_\.]\w*)/) {		
	    $line =~s/\s//g; 				# Remove spaces
	    $line =~s/#(.*)$//g; 			# Remove trailing comments in lines
	    $line =~s/\"//g; 				# Remove double quotation marks

            @row = split (/=/, $line);			# Split row at the equal sign. Unfortunately if there are more than one
        						# equal signs in a line we get multiple parts that we need to join again.
    	    @temprow = @row;
    	    @sectemprow = ();
    	    delete @temprow[0];				# Delete first entry in the array, which contains the key, leaving only the values.
    	    $trnums = @temprow;				# Count number of entries in array
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
	print "user_rule: " . $CONFIG{'user_rule'} . "\n";
	print "host_rule: " . $CONFIG{'host_rule'} . "\n";
	print "user_purge: " . $CONFIG{'user_purge'} . "\n";
	print "host_purge: " . $CONFIG{'host_purge'} . "\n";
    }

}

sub verify {

    # Go through list of config switches we're interested in:
    foreach $entry (@whatweneed) {
	if (!$CONFIG{"$entry"}) {
	    # Found key without value - setting defaults for those that need it:
	    if ($entry eq "host_purge") {
		$CONFIG{"$entry"} = "2d";
	    }
	    if ($entry eq "user_purge") {
		$CONFIG{"$entry"} = "2d";
	    }
	    if ($entry eq "host_rule") {
		$CONFIG{"$entry"} = "*=30/1h";
	    }
	    if ($entry eq "user_rule") {
		$CONFIG{"$entry"} = "!admin/cced=10000/1h,50000/1m";
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
	    'user_purge' => $CONFIG{"user_purge"},  
	    'user_rule' => $CONFIG{"user_rule"}
        });
    }
    else {
        # Object already present in CCE. Updating it, NOT forcing a rewrite of pam_abl.conf.
        ($sys_oid) = $cce->find('pam_abl_settings');
        ($ok, $sys) = $cce->get($sys_oid);
        ($ok) = $cce->set($sys_oid, '',{
	    'host_purge' => $CONFIG{"host_purge"},  
	    'host_rule' => $CONFIG{"host_rule"},  
	    'user_purge' => $CONFIG{"user_purge"},  
	    'user_rule' => $CONFIG{"user_rule"}
        });
    }
}

sub items_of_interest {
    # List of config switches that we're interested in:
    @whatweneed = ( 
	'host_purge', 
	'host_rule',
	'user_purge',
	'user_rule'
	);
}

$cce->bye('SUCCESS');
exit(0);


#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: pam_abl_import.pl, v1.0.0-3 Wed 12 Aug 2009 05:04:49 PM CEST mstauber Exp $
# Copyright 2006-2009 Solarspeed Ltd. All rights reserved.
# Copyright 2009 Team BlueOnyx. All rights reserved.

# This handler is run whenever the GUI needs to know the contends of the pam_abl user and host database.

# Debugging switch:
$DEBUG = "0";

#
#### No configureable options below!
#

use CCE;
use Socket;
use Sys::Hostname;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';

if ($DEBUG == "0") {
    $cce->connectfd();
}
else {
    $cce->connectuds();
}

# Dumpfile:
$dumpfile = "/tmp/.pamablstats";

# Destroy all existing 'fail_users' objects:
@fail_users = $cce->findx('fail_users');
for $entry (@fail_users) {
	($ok) = $cce->destroy($entry);
}

# Destroy all existing 'fail_hosts' objects:
@fail_hosts = $cce->findx('fail_hosts');
for $entry (@fail_hosts) {
	($ok) = $cce->destroy($entry);
}

# Generate a pam_abl dump with the CLI for parsing:
system("/bin/rm -f $dumpfile");
system("/usr/sausalito/bin/pam_abl_gui > $dumpfile");

# Pull the vzlist-dump into a Matrix:
open (F, $dumpfile) || die "Could not open $dumpfile $!";

$a = 0;
@USERNAMES = ();
@HOSTNAMES = ();
while ($line = <F>) {
    chomp($line);
    next if $line =~ /^\s*$/;               		# skip blank lines
    if ($line =~ /^Failed users/) {
    	$matrix_name = 'USERS';				# While passing through "Failed users" set matrix name to 'USERS'.
	next;
    }
    if ($line =~ /^Failed hosts/) {
	$matrix_name = 'HOSTS';				# While passing through "Failed hosts" set matrix name to 'HOSTS'.
	next;
    }
    if ($line =~ /^   <none>/) { 
	next; 
    }
    my (@row) = split (/\s+/, $line); 			# Splits at spaces. Not really desireable.
    shift(@row);					# Delete first entry from array, as it's garbage.
    $txn[0] = shift(@row);				# Get username or Host/IP.
    $txn[1] = shift(@row);				# Get failcount.

    if ($txn[1] =~ /\((.*)\)/) {			# Get just the number and ignore the brackets.
        $mtxn[1] = $1;
    }

    $txn[2] = join(" ", @row);				# Get rest joined back together.
    if ($txn[2] =~ m/Not blocking/i) {			
	$mtxn[2] = "0";					# Turn the blocking status into an integer (0 = not blocked, 1 = blocked).
    }
    else {
	$mtxn[2] = "1";
    }
	
    @combined_access = ();					# Bring the trash out
    push(@combined_access, ($txn[0], $mtxn[1], $mtxn[2]));	# Join pices back together.
    push (@{$matrix_name}, \@combined_access);		# Populate Matrix.

    #  Matrix legend:
    #  ===============
    #
    #  Users:				       	IPs:
    #  ------				       	----
    #  $USERS[$a][0]	<- Username / Host->   	$HOSTS[$a][0]
    #  $USERS[$a][1]	<- Failcount      ->   	$HOSTS[$a][1]
    #  $USERS[$a][2]	<- Block Status   ->   	$HOSTS[$a][2]

    # Store the USERS data in CODB:
    if (($matrix_name eq "USERS") && ($USERS[$a][0])) {
        ($ok) = $cce->create('fail_users', {
    	'username' => $USERS[$a][0],
	'failcnt' => $USERS[$a][1],
	'blocking' => $USERS[$a][2]
        });
    }

    # Prepare HOSTS data for storage in CODB:
    $host_or_ip = $HOSTS[$a][0];
    $host = "";
    $ip = "";
    $fc = $HOSTS[$a][1];
    $bs = $HOSTS[$a][2];

    if (($matrix_name eq "HOSTS") && ($host_or_ip)) {

        if ($host_or_ip) {
	    # Make sure we got an IP and not a FQDN:
    	    $ip_is_valid = silent_is_ip($host_or_ip);

	    if ($ip_is_valid ne "-1") {
		# Valid IP obtained.
		$ip = $host_or_ip;
		# Now get the matching hostname for it:
		$host = convert_to_ip($ip);
		# Check if it's still an IP:
		$host_valid = silent_is_ip($host);
		if ($host_valid = "-1") {
		    # It's still an IP. Forget it then and set a default:
		    $host = " -n/a- ";
		}
	    }
	    else {
		# We have a FQDN:
		$host = $host_or_ip;
		# Now get the matching IP for it:
		$ip = convert_to_ip($host_or_ip);
	    }

	    # Store host data in CODB:
	    ($ok) = $cce->create('fail_hosts', {
	        'host_fqdn' => $host,
	        'host_ip' => $ip,
	        'failcnt' => $fc,
	        'blocking' => $bs
	        });
	}
    }
    $a++;
}
close(F);
system("/bin/rm -f $dumpfile");

# Test to determine if the input is a valid IP-address:
sub convert_to_ip {
    my $convert_ip = $_[0];
    if ($convert_ip =~ /^(([0-9])|([1-9][0-9])|(1[0-9][0-9])|2[0-4][0-9]|25[0-5])\.(([0-9])|([1-9][0-9])|(1[0-9][0-9])|2[0-4][0-9]|25[0-5])\.(([0-9])|([1-9][0-9])|(1[0-9][0-9])|2[0-4][0-9]|25[0-5])\.(([0-9])|([1-9][0-9])|(1[0-9][0-9])|2[0-4][0-9]|25[0-5])$/) {
        # It's already and IP! Return IP:
        return $convert_ip;
    }
    else {
        # Look up the IP for the hostname in question and return that:
	$addr  = gethostbyname($convert_ip);
	$ip_of_hostname = inet_ntoa($addr);
        return $ip_of_hostname;
    }
}

sub silent_is_ip {
    my $check_ip_sil = $_[0];
    if ($check_ip_sil =~ /^(([0-9])|([1-9][0-9])|(1[0-9][0-9])|2[0-4][0-9]|25[0-5])\.(([0-9])|([1-9][0-9])|(1[0-9][0-9])|2[0-4][0-9]|25[0-5])\.(([0-9])|([1-9][0-9])|(1[0-9][0-9])|2[0-4][0-9]|25[0-5])\.(([0-9])|([1-9][0-9])|(1[0-9][0-9])|2[0-4][0-9]|25[0-5])$/) {                                                                                                                                    
        # return valid IP:
        return $check_ip_sil;
    }
    else {
        # return -1 instead:
        return "-1";
    }                                                                                                                                                                                                                                                                                                                                                                                                     
}
 
$cce->bye('SUCCESS');
exit(0);


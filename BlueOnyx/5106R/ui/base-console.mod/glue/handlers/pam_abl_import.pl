#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: pam_abl_import.pl, v1.0.0-1 Thu 06 Aug 2009 01:48:37 AM CEST mstauber Exp $
# Copyright 2006-2009 Solarspeed Ltd. All rights reserved.
# Copyright 2009 Team BlueOnyx. All rights reserved.

# This handler is run whenever the GUI needs to know the contends of the pam_abl user and host database.

# Debugging switch:
$DEBUG = "0";

#
#### No configureable options below!
#

use CCE;

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
	shift(@row);						# Delete first entry from array, as it's garbage.
	$txn[0] = shift(@row);					# Get username or IP.
	$txn[1] = shift(@row);					# Get failcount.

        if ($txn[1] =~ /\((.*)\)/) {				# Get just the number and ignore the brackets.
            $mtxn[1] = $1;
        }

	$txn[2] = join(" ", @row);				# Get rest joined back together.
	if ($txn[2] =~ m/Not blocking/i) {			
		$mtxn[2] = "0";					# Turn the blocking status into an integer (0 = not blocked, 1 = blocked).
	}
	else {
		$mtxn[2] = "1";
	}
	
	@combined_access = ();					# Bring the trash out.
	push(@combined_access, ($txn[0], $mtxn[1], $mtxn[2]));	# Join pices back together.
	
        push (@{$matrix_name}, \@combined_access);		# Populate Matrix.

	#  Matrix legend:
	#  ===============
	#
	#  Users:				       	IPs:
	#  ------				       	----
	#  $USERS[$a][0]	<- Username / IP  ->   	$HOSTS[$a][0]
	#  $USERS[$a][1]	<- Failcount      ->   	$HOSTS[$a][1]
	#  $USERS[$a][2]	<- Block Status   ->   	$HOSTS[$a][2]

	# Store the USERS data in CODB:
    	if (($matrix_name eq "USERS") && ($USERS[$a][0])){
	    ($ok) = $cce->create('fail_users', {
		'username' => $USERS[$a][0],
		'failcnt' => $USERS[$a][1],
		'blocking' => $USERS[$a][2]
	    });
	}

	# Store the HOSTS data in CODB:
	$ip = $HOSTS[$a][0];
	$fc = $HOSTS[$a][1];
	$bs = $HOSTS[$a][2];
    	if (($matrix_name eq "HOSTS") && ($ip) && ($ip ne "localhost")) {
	    ($ok) = $cce->create('fail_hosts', {
		'host' => $ip,
		'failcnt' => $fc,
		'blocking' => $bs
	    });
	}

        $a++;
    }
close(F);
system("/bin/rm -f $dumpfile");

$cce->bye('SUCCESS');
exit(0);

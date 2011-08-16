#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
# Copyright 2008-2009 Team BlueOnyx. All rights reserved.
# $Id: fix_user_suspension.pl,v 1.0.0.0 Jun 23 22:07:13 2009 mstauber Exp $
#
# User accounts MUST be locked or unlocked. Otherwise suspended users can still use SMTP-Auth.
# This script walks through all Vsites and checks if the Vsite is suspended (or unsuspended).
# If a Vsite is suspended, this script will user 'usermod -L <username>' to lock the account.
# If a Vsite is NOT suspended, this script will user 'usermod -U <username>' to unlock the account.
#
# You can use this script to repair the lock status of accounts. Just run it from the command line
# without any parameters. 
#

use CCE;
my $cce = new CCE;
$cce->connectuds();

# Root check:
my $id = `id -u`;
chomp($id);
if ($id ne "0") {
    print "$0 must be run by user 'root'!\n";

    $cce->bye('FAIL');
    exit(1);
}

# Find all Vsites:
my @vhosts = ();
my (@vhosts) = $cce->findx('Vsite');

# Walk through all Vsites:
for my $vsite (@vhosts) {
    ($ok, my $my_vsite) = $cce->get($vsite);

	if ($my_vsite->{suspend} == "0") {
		$site_status = "Not suspended";
	}
	else {
		$site_status = "Suspended";
	}

    print "\nChecking Site $my_vsite->{fqdn} - Site status: $site_status \n";

    # Start with an empty and predefined array:
    my @users = ();
	
    # Get all users of this Vsite:
    @users = $cce->findx('User', { 'site' => $my_vsite->{name} });

    # Walk through all users of that Vsite:
    for my $user (@users) {

        # Get the username:
        ($ok, my $myuser) = $cce->get($user);

        if (($my_vsite->{suspend} == "0") && ($myuser->{enabled} == "1")) {
	    # Unlock account:
    	    system("/usr/sbin/usermod -U $myuser->{name}");
	    print "User $myuser->{name} is not suspended. Running '/usr/sbin/usermod -U $myuser->{name}' to unlock the account.\n";
    	}
	else {
    	    # Lock account:
    	    system("/usr/sbin/usermod -L $myuser->{name}");
	    print "User $myuser->{name} should be suspended. Running '/usr/sbin/usermod -L $myuser->{name}' to lock the account.\n";
	}
    }
}

$cce->bye('SUCCESS');
exit(0);


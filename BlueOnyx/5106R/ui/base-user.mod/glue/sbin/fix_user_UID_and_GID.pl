#!/usr/bin/perl
#
# $Id: fix_user_UID_and_GID.pl, v 1.0.0.3 Tue 23 Jun 2009 06:06:08 PM EDT mstauber Exp $
# Copyright 2006-2009 Solarspeed Ltd. and NuOnce Networks, Inc. All rights reserved.
#
# This script fixes the UID and GID of all Vsite users. Also fixed ownership of Vsite logs.
#
# Please note: This script does NOT change UID or GID of files in the webspace of a Vsite!
#
# Usage:
#
# Just run this script without any parameters. You can run it as often as you like
# without causing any problems.

use Unix::PasswdFile;
use Unix::GroupFile;

# Root check
my $id = `id -u`;
chomp($id);
if ($id ne "0") {
    print "$0 must be run by user 'root'!.\n";

    $cce->bye('FAIL');
    exit(1);
}

# Make backup copy of /etc/passwd - just in case:
system("/bin/cp /etc/passwd /etc/.passwd.pre-uid-fixing");

# Give brief startup info:
print "Processing UID/GID fix for all users.\n";

$pw = new Unix::PasswdFile "/etc/passwd";
$found_one = "0";

foreach $user ($pw->users) {
    undef(@groupworkaround);
    undef $home;
    $uid = $pw->uid($user);
    $gid = $pw->gid($user);
    $home = $pw->home($user);
    @groupworkaround = split(/\//, $home);

    if (($uid >= "500") && ($groupworkaround[2] eq ".sites")) {

	# Determine GID of user by parsing /etc/group:
	$grp = new Unix::GroupFile "/etc/group";
	$real_gid_of_user = $grp->gid($groupworkaround[4]);
	undef $grp;

	# Fixing GID of everything in the users home directory:
	if (-d "$home") {
		system("/bin/chown -R $uid:$real_gid_of_user $home");
	}

	$found_one++;
    }

    # Fix UID / GID of logfiles:
    if (($uid >= "500") && ($groupworkaround[5] eq "logs")) {
        # This is a SITExx-logs user
        if (-d "$home") {

            # Get GID:
            $grp = new Unix::GroupFile "/etc/group";
            $real_gid_of_user = $grp->gid($groupworkaround[4]);
            undef $grp;

            # Do the chown:
            print "Setting UID and GID of $home to $user ($uid) and GID $groupworkaround[4] ($real_gid_of_user) \n";
            system("/bin/chown -R $user:$groupworkaround[4] $home");
            $found_one++;
        }
    }
}

undef $pw;

if ($found_one gt "0") {
    print "Everything done. All users are fixed.\n";
}
else {
    print "Nothing to do.\n";
}

exit(0);

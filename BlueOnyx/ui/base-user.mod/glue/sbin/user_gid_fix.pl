#!/usr/bin/perl
#
# $Id: user_gid_fix.pl, v 1.0.0.3 Tue 23 Jun 2009 06:17:01 PM EDT mstauber Exp $
# Copyright 2006-2009 Solarspeed Ltd. and NuOnce Networks, Inc. All rights reserved.
#
# During the mandatory PAM to shadow conversion we discovered that there were cases
# where users were added to /etc/passwd with the default group "users". Which can 
# cause problems with email forwarding and auto-responders. Aside from that all
# users SHOULD be in the right group anyway.
# 
# Those problems during user creation have now been fixed. But this script allows
# you to manually fix all existing users which still belong to group "users" 
# (numerical GID = 100), but should NOT belong to it.
#
# Please note:
#
# User "admin" and server administrators rightfully belong to group "users". So this
# script will NOT modify those, as they're fine.
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
system("/bin/cp /etc/passwd /etc/.passwd.pre-uid-fix");

# Give brief startup info:
print "Checking for users with incorrect gid.\n";

$pw = new Unix::PasswdFile "/etc/passwd";
$found_one = "0";

foreach $user ($pw->users) {
    undef(@groupworkaround);
    undef $home;
    $uid = $pw->uid($user);
    $gid = $pw->gid($user);
    $home = $pw->home($user);
    @groupworkaround = split(/\//, $home);

    if (($uid >= "500") && ($gid == "100") && ($groupworkaround[2] eq ".sites")) {

	# If this is not a system user AND he has a GID of 100 AND is not a serverAdmin,
	# then this GID is most likely wrong. Determine real GID from parsing /etc/group:
	$grp = new Unix::GroupFile "/etc/group";
	$real_gid_of_user = $grp->gid($groupworkaround[4]);
	undef $grp;

	# Fix all users with incorrect GID:
	print "Fixing GID of $user ($uid), GID is: $gid, GID should be: $real_gid_of_user \n";
	$pw->gid($user, $real_gid_of_user);
	$pw->commit();

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

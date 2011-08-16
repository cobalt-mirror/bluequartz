#!/usr/bin/perl

# This script is a work around for a small mystery.
# mbox files of users that never received email may end up with the UID of "root"
# instead of the UID of the user who should own the mbox file.
#
# This script checks the ownership of all mbox files under /home/.sites
# If it finds an mbox file that's owned by "root", it will chown it to the
# UID of the user who should own it.

$USERLIST=`/usr/bin/find /home/.sites -name mbox -user root -exec ls -la \\{} \\; | /bin/grep -v "/logs/" | /bin/awk \'{print \$9}\'`;
@mboxes = split(/\n/, $USERLIST);
foreach $user_mbox (@mboxes) {
    @owner_list = split(/\//, $user_mbox);
    if (($owner_list[7]) && ($user_mbox)) {
        system("/bin/chown $owner_list[7] $user_mbox");
    }
}

exit(0);

#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: kill_user.pl v1.1.0.0 Fri 03 Jul 2009 09:53:51 AM CEST mstauber Exp $
# Copyright 2006-2009 Solarspeed Ltd. All rights reserved.
# Copyright 2009 Team BlueOnyx. All rights reserved.

# This handler kills of logins that the user wants to boot from the system.

use CCE;
my $cce = new CCE;
my $conf = '/var/lib/cobalt';

$DEBUG = 0;

my $cce = new CCE('Domain' => 'base-console');

if ($DEBUG == 0) {
    $cce->connectfd();
}
else {
    $cce->connectuds(); # only for debugging
}

if ($DEBUG == 0) {
    ($sys_oidnode) = $cce->find('SOL_Console');
    ($ok, $MainNode) = $cce->get($sys_oidnode);
}

$console = $MainNode->{user_kill_console};
$username = $MainNode->{user_kill_user};
$pid = $MainNode->{user_kill_pid};

if (($console == "ftpd") && ($pid ne "0")) {
	system("/bin/kill -9 $pid");
}
else {
	system("/bin/ps axf | /bin/grep $console | /bin/grep -v /bin/grep | /bin/awk '{printf \$1 \" \"}' | /usr/bin/xargs kill -9 ");
} 

$cce->bye('SUCCESS');
exit(0);

# /bin/ps axf | /bin/grep "pts/3" | /bin/grep -v /bin/grep |  /bin/awk '{printf $1 " "}' | /usr/bin/xargs kill -9

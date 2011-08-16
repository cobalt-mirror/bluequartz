#!/usr/bin/perl -I/usr/sausalito/perl
#
# $Id: fix_302_httpd_redirects.pl, v 1.0.0.1 Oct 15 2009 05:32:01 AM EDT mstauber Exp $
# Copyright 2006-2009 Team BlueOnyx. All rights reserved.
#
# This script walks through all /etc/httpd/conf/vhosts/site* files and changes the old 302
# httpd redirects to more search engine friendly 301 redirects.
#
# It makes a copy of the include files in /tmp/vhosts/ prior to making these changes.
#
# Usage:
#
# Simply run this script once. Running it multiple times will do no harm, though.

# Root check:
my $id = `id -u`;
chomp($id);
if ($id ne "0") {
    print "$0 must be run by user 'root'!\n";

    $cce->bye('FAIL');
    exit(1);
}

use CCE;
use Sauce::Service;

my $cce = new CCE;
$cce->connectuds();

# tell cce everything is okay
$cce->bye('SUCCESS');

if (!-d "/tmp/vhosts") {
	system("mkdir /tmp/vhosts");
}
system("cp /etc/httpd/conf/vhosts/site* /tmp/vhosts/");
system("find /etc/httpd/conf/vhosts/site*|grep -v .include|xargs perl -pi -e 's/\\[L,R\\]/\\[L,R=301\\]/g'");

#
# now wait a while and then restart httpd
# this is okay, because cce doesn't wait for the handler to actually exit
#

sleep(3);
service_run_init('httpd', 'restart');

exit(0);

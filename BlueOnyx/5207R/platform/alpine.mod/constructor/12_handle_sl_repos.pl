#!/usr/bin/perl -I/usr/sausalito/perl
# Copyright 2012 Team BlueOnyx.  All rights reserved.
# $Id: 12_handle_sl_repos.pl Mo 13 Aug 2012 04:46:14 CEST mstauber  Exp $
#
# Check if this is Scientific Linux and set the YUM repository files to use 6x instead of the hardwired one from sl-release:
# In an ideal world this would go into base-blueonyx.mod. But if we update that, the mailing list will be screaming again.

use CCE;
use I18n;

my $cce = new CCE;
$cce->connectuds();

# Is YUM running? (0 = not running)
$yum = `ps axf|grep yum|grep -v grep|wc -l`;

if (-e "/etc/yum.repos.d/sl.repo") {
	# Do we have $releasever in sl.repo? 
	$ver = `cat /etc/yum.repos.d/sl.repo |grep releasever|wc -l`;
}
else {
	# No sl.repo found:
	$ver = "0";
}

# Execute if YUM is not running:
if ($yum == "0") {
	# But only if we have an sl.repo with $releasever in it:
	if ($ver ne "0") {
		# Clean YUM cache:
		system("yum clean all >/dev/null 2>&1");
	}
}

# Fix sl.repo:
if (-e "/etc/yum.repos.d/sl.repo") {
	system("sed -i 's/\$releasever/6x/g' /etc/yum.repos.d/sl.repo");
}
# Fix sl-other.repo:
if (-e "/etc/yum.repos.d/sl-other.repo") {
	system("sed -i 's/\$releasever/6x/g' /etc/yum.repos.d/sl-other.repo");
}

$cce->bye('SUCCESS');
exit(0);

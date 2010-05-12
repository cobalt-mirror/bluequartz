#!/usr/bin/perl
#
# setup_mysql_data.pl - Solarspeed.net's mysql GUI
# By: Michael Stauber <mstauber@solarspeed.net>
# Copyright 2006-2008 Solarspeed Ltd. All rights reserved.

# Perl libraries, all Sausalito
use lib qw(/usr/sausalito/perl);
use CCE;

$cce = new CCE;
$cce->connectuds();

# Create main MySQL access settings if not already present:
@mysql_main = $cce->find('MySQL');
if (!defined($mysql_main[0])) {
    ($ok) = $cce->create('MySQL', {
	'sql_host' => "localhost",
	'sql_port' => "3306",
	'sql_root' => "root",
	'sql_rootpassword' => "",
	'timestamp' => time(),
	'savechanges' => time()
    });
}

$cce->bye('SUCCESS');
exit 0;

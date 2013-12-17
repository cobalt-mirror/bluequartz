#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
#
# mod-mysql.pl - Solarspeed.net's mysql GUI
# By: Michael Stauber <mstauber@solarspeed.net>
# Copyright 2006-2008 Solarspeed Ltd. All rights reserved.

use CCE;

my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();

my $mysql_ini = "/etc/my.cnf";
my $mysql_tmp = "/tmp/my.cnf";
my $mysql_temp = "/tmp/my.cnf.clean";

my @oids = $cce->find('System');
if (!defined($oids[0])) {
	print STDERR "mysql: Sorry, no System object in CCE found!\n";
	exit 0;
}

my ($ok, $mysqlValues) = $cce->get($oids[0], "MYSQLUSERS_DEFAULTS");

if ($mysqlValues->{soltab} eq "two") {
    if ($ok) {
	mysqlWriteConf();
	mysqlReloadMYSQL();
    }
}

$cce->bye('SUCCESS');
exit(0);

##
## Reload Apache
##

sub mysqlReloadMYSQL {
    system("/bin/egrep -q '(4100R|4...WG)' /etc/build");
    if ( $? eq 0 ) {
		system("/etc/init.d/mysqld stop > /dev/null 2>&1");
		system("sleep 8");
		system("/etc/init.d/mysqld start > /dev/null 2>&1");
    }
    else {
	    	system("/etc/rc.d/init.d/mysqld restart > /dev/null 2>&1");
    }
}

##
## mysqlWriteConf
## Saves mysql configuration into /etc/mysql.conf
##

sub mysqlWriteConf {
    system("/bin/sed 's/\r//' $mysql_tmp > $mysql_temp");
    system("/bin/cp $mysql_temp $mysql_ini");
    system("/bin/rm -f $mysql_tmp");
    system("/bin/rm -f $mysql_temp");
}


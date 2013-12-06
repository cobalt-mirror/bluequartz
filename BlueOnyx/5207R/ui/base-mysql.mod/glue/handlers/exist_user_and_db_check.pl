#!/usr/bin/perl -I/usr/sausalito/perl
#
# exist_user_and_db_check.pl
# Hideki Oride <oride@gachapom.jp>

use CCE;
use POSIX qw(isalpha);
use DBI;

my $DEBUG = 0;
$DEBUG && open(STDERR, ">>/tmp/validate-mysql");
$DEBUG && warn $0.' '.`date`;

$cce = new CCE;

$cce->connectfd();

my $vsite     = $cce->event_object();
my $vsite_oid = $cce->event_oid();
my $vsite_new = $cce->event_new();
my $vsite_old = $cce->event_old();

# Get System Information
my @oids = $cce->find("System");
$DEBUG && print STDERR Dumper(@oids);
my ($ok, $system) = $cce->get($oids[0]);

# Get MySQL Information
my @oids = $cce->find("MySQL");
$DEBUG && print STDERR Dumper(@oids);
my ($ok, $mysql) = $cce->get($oids[0]);

# Get 'MYSQL_Vsite' details:
($ok, $vsite_mysql) = $cce->get($vsite_oid, 'MYSQL_Vsite');
$DEBUG && print STDERR Dumper($system, $mysql, $vsite, $vsite_mysql);
$DEBUG && warn "DB: $vsite_mysql->{DB}  \n";

# Enable and Disable Check
if($vsite_mysql->{enabled} != 1)
{
    $cce->bye('SUCCESS');
    exit(0);
}


## MySQL Server Connection Check
$dbh = DBI->connect(
        "DBI:mysql:mysql:$mysql->{sql_host}:$mysql->{port}",
        $mysql->{sql_root}, $mysql->{sql_rootpassword},
        {
            RaiseError => 0,
            PrintError => 0
        }
); 
$DEBUG && print STDERR Dumper($dbh);

if(!$dbh)
{
    $message .= '[[base-mysql.amNotAccepting]]';
    $db_check_fail = 1;
    $cce->bye('FAIL', $message);
    exit(1);
}

# User Check
my $siteMySQLUser = $dbh->quote($vsite_mysql->{username});
$DEBUG && warn "SQL: SELECT COUNT(*) as rowCount FROM `user` WHERE User = $siteMySQLUser  \n";
@row = $dbh->selectrow_array("SELECT COUNT(*) as rowCount FROM `user` WHERE User = $siteMySQLUser");
$DEBUG && warn "row[0]:$row[0]  \n";
$DEBUG && print STDERR Dumper(@row);
if($row[0] != 0)
{
    $message .= '[[base-mysql.CreateFailUserExists]]';
    $db_check_fail = 1;
}

# DB Check
my $siteMySQLDb = $dbh->quote($vsite_mysql->{DB});
$DEBUG && warn "SQL: SELECT COUNT(*) as rowCount FROM `db` WHERE User = $siteMySQLUser AND Db = $siteMySQLDb \n";
@row = $dbh->selectrow_array("SELECT COUNT(*) as rowCount FROM `db` WHERE User = $siteMySQLUser AND Db = $siteMySQLDb");
$DEBUG && warn "row[0]:$row[0]  \n";
$DEBUG && print STDERR Dumper(@row);
if($row[0] != 0)
{
    $message .= '[[base-mysql.CreateFailDBExists]]';
    $db_check_fail = 1;
}

$dbh->disconnect;

## Database Connection Check
$dbh = DBI->connect(
        "DBI:mysql:$vsite_mysql->{DB}:$mysql->{sql_host}:$mysql->{port}",
        $mysql->{sql_root}, $mysql->{sql_rootpassword},
        {
            RaiseError => 0,
            PrintError => 0
        }
); 
$DEBUG && print STDERR Dumper($dbh);
if($dbh)
{
    # $dbh->diconnect;
    $message .= '[[base-mysql.CreateFailDatabaseExists]]';
    $db_check_fail = 1;
}


if($db_check_fail) 
{
    $cce->bye('FAIL', $message);
    exit(1);
}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2010 Hideki Oride <oride@gachapom.jp>
# Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without modification, 
# are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation and/or 
# other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
# 

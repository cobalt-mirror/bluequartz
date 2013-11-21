#!/usr/bin/perl -I/usr/sausalito/perl

# exist_user_and_db_check.pl
# Hideki Oride <oride@gachapom.jp>
# Thu, 27 Aug 2010

use CCE;
use POSIX qw(isalpha);
use DBI;

my $DEBUG = 0;
$DEBUG && open(STDERR, ">>/tmp/validate-mysql");
$DEBUG && warn $0.' '.`date`;

#my $cce = new CCE('Domain' => 'base-vsite');
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
    $message .= "Can not Connect MySQL Server\n";
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
    $message .= "Exist User on `user` table\n";
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
    $message .= "Exist DB on `Db` table\n";
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
    $message .= "Exist database\n";
    $db_check_fail = 1;
}


if($db_check_fail) 
{
    $cce->bye('FAIL', $message);
    exit(1);
}


$cce->bye('SUCCESS');
exit(0);

#!/usr/bin/perl
#
# destroy_mysql_data.pl 
# Hideki Oride <oride@gachapom.jp>
# Thu, 27 Aug 2010

# Global Paths & Config
$namespace = 'MYSQL_Vsite';

# Perl libraries, all Sausalito
use lib qw(/usr/sausalito/perl);
use DBI;
use CCE;
use Sauce::Util;

# Debug, Will-style
$DEBUG = 0;
$DEBUG && open(STDERR, ">>/tmp/destroy_mysql-vsite");
$DEBUG && warn $0.' '.`date`;

$cce = new CCE('Namespace' => "MYSQL_Vsite");
$cce->connectfd();
#$cce->connectuds(); # Only for debugging

$event_object = $cce->event_object();
$event_new = $cce->event_new();
$event_old = $cce->event_old();
$event_oid = $cce->event_oid();
$event_is_create = $cce->event_is_create();
$event_is_destroy = $cce->event_is_destroy();
$event_is_modify = $cce->event_is_modify();

$DEBUG && warn "event_object: $event_object  \n";
$DEBUG && warn "event_new: $event_new  \n";
$DEBUG && warn "event_old: $event_old  \n";
$DEBUG && warn "event_oid: $event_oid  \n";
$DEBUG && warn "event_is_create: $event_is_create  \n";
$DEBUG && warn "event_is_destroy: $event_is_destroy  \n";
$DEBUG && warn "event_is_modify: $event_is_modify  \n";

# Get 'MYSQL_Vsite' details:
($ok, $mysql_info) = $cce->get($event_oid, 'MYSQL_Vsite');
($ok, $old_mysql_info) = $cce->get($event_old->{OID}, 'MYSQL_Vsite');
$DEBUG && print STDERR Dumper($event_object, $old_mysql_info, $event_new, $event_old, $event_oid, $event_is_create, $event_is_destroy, $event_is_modify, $mysql_info);
$DEBUG && warn "DB: $mysql_info->{DB}  \n";

# Get 'System' details:
@system_main = $cce->find('System');
if (!defined($system_main[0])) {
    print STDERR "Sorry, no 'System' object found in CCE!\n";
    $cce->bye('FAIL', "Unable to find 'System' Object in CCE.");
    exit(1);
}
else {
    # Build FQDN:
    ($ok, $my_system_main) = $cce->get($system_main[0]);
    $my_host = $my_system_main->{'hostname'};
    $my_domain = $my_system_main->{'domainname'};
    $my_fqdn = $my_host . "." . $my_domain; 
}
$DEBUG && warn "system_main[0]: $system_main[0] \n";

# Check if MySQLd is running. If not, we can stop right here and now:
&check_mysqld_status;
$DEBUG && warn "MySQLd status: $sts_mysqld \n";
if ($sts_mysqld ne "RUNNING") {
    $cce->bye('FAIL', "MySQL Server is not running");
    exit(1);
}


## Get Vsite's MySQL from 'MYSQL_Vsite':
$siteMysql_user = $event_old->{'username'};
$siteMysql_pass = $event_old->{'pass'}; # Do not Use.
$siteMysql_host = $event_old->{'host'};
$siteMysql_port = $event_old->{'port'};
$siteMysql_db = $event_old->{'DB'};

    $DEBUG && warn "User: $siteMysql_user - DB: $siteMysql_db \n";

# Get main MySQL access settings out of 'MySQL':
@mysql_main = $cce->find('MySQL');
if (!defined($mysql_main[0])) {
        print STDERR "Sorry, no 'Solarspeed_MySQL' object found in CCE!\n";
        print STDERR "Unable to fetch MySQL 'root' access details for MySQL.\n";
	$cce->bye('FAIL', "Unable to fetch MySQL 'root' access details for MySQL from CCE. Please configure them under 'Network Settings', 'MySQL'.");
        exit(1);
}
else {
	($ok, $mysql_main) = $cce->get($mysql_main[0]);
	$sql_root = $mysql_main->{'sql_root'};
	$root_pass = $mysql_main->{'sql_rootpassword'};
	$sql_host = $mysql_main->{'sql_host'};
	$sql_port = $mysql_main->{'sql_port'};
}

    $DEBUG && warn "mysql_main: $mysql_main \n";
    $DEBUG && print STDERR Dumper($mysql_main);
    $DEBUG && print STDERR Dumper($sql_root, $root_pass, $sql_host, $sql_port);

# Make sure we have all MySQL access details that we need (password is optional here):
unless (($sql_root ne "") && ($sql_host ne "") && ($sql_port ne "")) {
        print STDERR "Unable to fetch MySQL 'root' access details for MySQL from CCE.\n";
	$cce->bye('FAIL', "Unable to fetch MySQL 'root' access details for MySQL from CCE. Please configure them under 'Network Settings', 'MySQL'.");
        exit(1);
}
else {
	$mysql_host=$sql_host . ":" . $sql_port;
}

if($cce->event_is_destroy())
{
    $DEBUG && warn "================= Destroy Vsite ==============  \n";
    # Site deletion, not to be confused with MySQL disable
    if($event_old->{'enabled'} == 1)
    {
        &remove_db_and_user;
    }
}

$cce->bye('SUCCESS');
exit 0;

### Subs

sub remove_db_and_user {
    
    ## MySQL Server Connection Check
    $dbh = DBI->connect(
                    "DBI:mysql:$siteMysql_db:$siteMysql_host:$siteMysql_port",
                    $sql_root, $root_pass,
                    {
                        RaiseError => 0,
                        PrintError => 0
                    }
    );
    $DEBUG && print STDERR Dumper($siteMysql_db, $siteMysql_host, $siteMysql_port, $sql_root, $root_pass);
    $DEBUG && print STDERR Dumper($dbh);
    
    if(!$dbh)
    {
        $message .= "Can not Connect MySQL Server\n";
        $db_check_fail = 1;
        $cce->bye('FAIL', $message);
        exit(1);
    }
    
    $query = "DROP DATABASE IF EXISTS '$siteMysql_db'";
    $DEBUG && print STDERR Dumper($query);
    #$return = $dbh->do($query);
    $DEBUG && print STDERR Dumper($return);
    
    $dbh->disconnect;
    
    #
    ## Revoke privileges (Step #1):
    #
    $dbh = DBI->connect(
                    "DBI:mysql:mysql:$sql_host:$sql_port}",
                    $sql_root, $root_pass,
                    {
                        RaiseError => 0,
                        PrintError => 0
                    }
    );
    $DEBUG && print STDERR Dumper($sql_host, $sql_port, $sql_root, $root_pass);
    $DEBUG && print STDERR Dumper($dbh);
    
    if(!$dbh)
    {
        $message .= "Can not Connect MySQL Server\n";
        $db_check_fail = 1;
        $cce->bye('FAIL', $message);
        exit(1);
    }

    $query = "REVOKE ALL PRIVILEGES ON * . * FROM '$siteMysql_user'\@'$siteMysql_host';\n";
    $DEBUG && print STDERR Dumper($query);
    $return = $dbh->do($query);
    $DEBUG && print STDERR Dumper($return);

    #
    ## Revoke privileges (Step #2):
    #
    $query = "REVOKE ALL PRIVILEGES ON `$siteMysql_db` . * FROM '$siteMysql_user'\@'$siteMysql_host';\n";
    $DEBUG && print STDERR Dumper($query);
    $return = $dbh->do($query);
    $DEBUG && print STDERR Dumper($return);
    
    #
    ## Revoke privileges (Step #3):
    #
    $qeury = "REVOKE GRANT OPTION ON * . * FROM '$siteMysql_user'\@'$siteMysql_host';\n";
    $DEBUG && print STDERR Dumper($query);
    $return = $dbh->do($query);
    $DEBUG && print STDERR Dumper($return);

    #
    ## Delete the sites MySQL user: 
    #
    $query = "DROP USER '$siteMysql_user'\@'$siteMysql_host';\n";
    $DEBUG && print STDERR Dumper($query);
    $return = $dbh->do($query);
    $DEBUG && print STDERR Dumper($return);
    
    #
    ## Flush privileges:
    #
    $query = "FLUSH PRIVILEGES;";
    $DEBUG && print STDERR Dumper($query);
    $return = $dbh->do($query);
    $DEBUG && print STDERR Dumper($return);
    
    $dbh->disconnect;
    
    
    # Delete Symbolic Link
    system("rm -f /var/lib/mysql/$siteMysql_db");
    
}

sub check_mysqld_status {
    # Check if MySQLd is running:
    $cmd_mysqld = '/etc/init.d/mysqld';
    $sts_mysqld = "UNKNOWN";
    $status_tempfile = '/tmp/.ststmp';

    # MySQLd:
    $rtn_mysqld = system("$cmd_mysqld status > $status_tempfile");
    open (F, $status_tempfile) || die "Could not open $status_tempfile: $!";
    while ($line = <F>) {
        chomp($line);
        next if $line =~ /^\s*$/;
        if ($line =~ /[0-9]/) {
                $sts_mysqld = "RUNNING";
        }
    }
    close(F);
    system("/bin/rm -f $status_tempfile");
}

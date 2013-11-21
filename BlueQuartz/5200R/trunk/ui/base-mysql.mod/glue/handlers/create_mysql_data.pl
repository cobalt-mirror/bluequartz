#!/usr/bin/perl
#
# create_mysql_data.pl 
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
$DEBUG && open(STDERR, ">>/tmp/create_mysql-vsite");
$DEBUG && warn $0.' '.`date`;

$cce = new CCE();
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
$siteMysql_user = $mysql_info->{'username'};
$siteMysql_pass = $mysql_info->{'pass'};
$siteMysql_port = $mysql_info->{'port'};
$siteMysql_host = $mysql_info->{'host'};
$siteMysql_db = $mysql_info->{'DB'};

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

# Make sure we have all MySQL access details that we need (password is optional here):
unless (($sql_root ne "") && ($sql_host ne "") && ($sql_port ne "")) {
        print STDERR "Unable to fetch MySQL 'root' access details for MySQL from CCE.\n";
	$cce->bye('FAIL', "Unable to fetch MySQL 'root' access details for MySQL from CCE. Please configure them under 'Network Settings', 'MySQL'.");
        exit(1);
}
else {
	$mysql_host=$sql_host . ":" . $sql_port;
}

if ($mysql_info->{'enabled'} == 1)
{
    $DEBUG && warn "=================== Create db ==============  \n";
    &create_db_and_user;
}
elsif ($mysql_info->{'enabled'} == 0)
{
    &remove_db_and_user;
}

$cce->bye('SUCCESS');
exit 0;

### Subs

sub create_db_and_user {
    
    ## Get default DB rights from 'System' object 'solmysql':
    my ($ok, $db_rights) = $cce->get($system_main[0], "MYSQLUSERS_DEFAULTS");
    
    my $MAX_QUERIES_PER_HOUR = $db_rights->{'MAX_QUERIES_PER_HOUR'};
    my $MAX_CONNECTIONS_PER_HOUR = $db_rights->{'MAX_CONNECTIONS_PER_HOUR'};
    my $MAX_UPDATES_PER_HOUR = $db_rights->{'MAX_UPDATES_PER_HOUR'};
    
    @the_user_rights =();
    if($db_rights->{'SELECT'} == 1)
    {
        push @the_user_rights, "SELECT";
    }
    if($db_rights->{'INSERT'} == 1)
    {
        push @the_user_rights, "INSERT";
    }
    if($db_rights->{'UPDATE'} == 1)
    {
        push @the_user_rights, "UPDATE";
    }
    if($db_rights->{'DELETE'} == 1)
    {
        push @the_user_rights, "DELETE";
    }
    if($db_rights->{'FILE'} == 1)
    {
        push @the_user_rights, "FILE";
    }
    if($db_rights->{'CREATE'} == 1)
    {
        push @the_user_rights, "CREATE";
    }
    if($db_rights->{'DROP'} == 1)
    {
        push @the_user_rights, "DROP";
    }
    if($db_rights->{'INDEX'} == 1)
    {
        push @the_user_rights, "INDEX";
    }
    if($db_rights->{'ALTER'} == 1)
    {
        push @the_user_rights, "ALTER";
    }
    if($db_rights->{'TEMPORARY'} == 1)
    {
        push @the_user_rights, "CREATE TEMPORARY TABLES";
    }
    
    if($db_rights->{'GRANT'} == 1)
    {
        push @the_user_rights, "GRANT OPTION";
    }
    if($db_rights->{'REFERENCE'} == 1)
    {
        push @the_user_rights, "REFERENCES";
    }
    if($db_rights->{'LOCK'} == 1)
    {
        push @the_user_rights, "LOCK TABLES";
    }
    if($db_rights->{'CREATE_VIEW'} == 1)
    {
        push @the_user_rights, "CREATE VIEW";
    }
    if($db_rights->{'SHOW_VIEW'} == 1)
    {
        push @the_user_rights, "SHOW VIEW";
    }
    if($db_rights->{'CREATE_ROUTINE'} == 1)
    {
        push @the_user_rights, "CREATE ROUTINE";
    }
    if($db_rights->{'ALTER_ROUTINE'} == 1)
    {
        push @the_user_rights, "ALTER ROUTINE";
    }
    if($db_rights->{'EXECUTE'} == 1)
    {
        push @the_user_rights, "EXECUTE";
    }
    
    # SQL create
    $the_user_rights_count = @the_user_rights; 
    $DEBUG && warn "the_user_rights_count:$the_user_rights_count  \n";
    
    for ( my $x = 0; $x < $the_user_rights_count; $x++)
    {
        if($x < $the_user_rights_count - 1)
        {
            $my_user_rights .= $the_user_rights[$x] . ", ";
        }
        else
        {
            $my_user_rights .= $the_user_rights[$x] . "";
        }
    }
    
    $DEBUG && warn "my_user_rights:$my_user_rights\n";
    
    my $db_check_fail = 0;
    my $message = "";
    $DEBUG && print STDERR Dumper($sql_host, $sql_port, $sql_root, $root_pass);
    
    ## MySQL Server Connection Check
    $dbh = DBI->connect(
            "DBI:mysql:mysql:$sql_host:$sql_port",
            $sql_root, $root_pass,
            {
                RaiseError => 0,
                PrintError => 0
            }
    ); 
    $DEBUG && print STDERR Dumper($dbh);
    if(!$dbh)
    {
        $cce->bye('FAIL', 'Can not connect Database');
        exit(1);
    }
    
    # Create Database
    $query = "CREATE DATABASE `$siteMysql_db`;\n";
    $DEBUG && print STDERR Dumper($query);
    $return = $dbh->do($query);
    $DEBUG && print STDERR Dumper($return);
    
    
    # Create User
    $query = "GRANT $my_user_rights ON `$siteMysql_db`.* TO `$siteMysql_user`@`$siteMysql_host`;\n";
    $DEBUG && print STDERR Dumper($query);
    $return = $dbh->do($query);
    $DEBUG && print STDERR Dumper($return);
    
    # 
    $query = "GRANT USAGE ON * . * TO `$siteMysql_user`@`$siteMysql_host` IDENTIFIED BY '$siteMysql_pass' WITH MAX_QUERIES_PER_HOUR $MAX_QUERIES_PER_HOUR MAX_CONNECTIONS_PER_HOUR $MAX_CONNECTIONS_PER_HOUR MAX_UPDATES_PER_HOUR $MAX_UPDATES_PER_HOUR;\n";
    $DEBUG && print STDERR Dumper($query);
    $return = $dbh->do($query);
    $DEBUG && print STDERR Dumper($return);
    
    # FLUSH PRIVILEGES
    $query = "FLUSH PRIVILEGES;\n";
    $DEBUG && print STDERR Dumper($query);
    $return = $dbh->do($query);
    $DEBUG && print STDERR Dumper($return);
    
    $dbh->disconnect;
    
    #
    ## Database Directory Move
    #
    
    $vsite_db_path = $event_object->{'basedir'}."/.database";
    $DEBUG && print STDERR Dumper($vsite_db_path);
    
    #
    if(!-e $vsite_db_path)
    {
        mkdir $vsite_db_path;
        system "chmod 2770 $vsite_db_path";
        system "chown -R  mysql:$event_object->{'name'} $vsite_db_path";
    }
    
    
    #
    system "cp -dpR /var/lib/mysql/$siteMysql_db $vsite_db_path/$siteMysql_db";
    system "chown -R mysql:$event_object->{'name'} $vsite_db_path/$siteMysql_db";
    system "chmod 2660 $vsite_db_path/$siteMysql_db/*";
    
    #
    system "rm -rf /var/lib/mysql/$siteMysql_db";
    system "ln -s $vsite_db_path/$siteMysql_db /var/lib/mysql/$siteMysql_db";
    
    
    
}

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
    $DEBUG && print STDERR Dumper($dbh);
    
    if(!$dbh)
    {
        $message .= "Can not Connect MySQL Server\n";
        $db_check_fail = 1;
        $cce->bye('FAIL', $message);
        exit(1);
    }
    
    $query = "DROP DATABASE IF EXISTS $siteMysql_db";
    $DEBUG && print STDERR Dumper($query);
    $return = $dbh->do($query);
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

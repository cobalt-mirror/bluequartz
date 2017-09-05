#!/usr/bin/perl
#
# create_mysql_data.pl 

# Global Paths & Config
$namespace = 'MYSQL_Vsite';

# Perl libraries, all Sausalito
use lib qw(/usr/sausalito/perl);
use DBI;
use CCE;
use Sauce::Util;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

$cce = new CCE();
$cce->connectfd();

$event_object = $cce->event_object();
$event_new = $cce->event_new();
$event_old = $cce->event_old();
$event_oid = $cce->event_oid();
$event_is_create = $cce->event_is_create();
$event_is_destroy = $cce->event_is_destroy();
$event_is_modify = $cce->event_is_modify();

&debug_msg("event_object: $event_object  \n");
&debug_msg("event_new: $event_new  \n");
&debug_msg("event_old: $event_old  \n");
&debug_msg("event_oid: $event_oid  \n");
&debug_msg("event_is_create: $event_is_create  \n");
&debug_msg("event_is_destroy: $event_is_destroy  \n");
&debug_msg("event_is_modify: $event_is_modify  \n");

# Get 'MYSQL_Vsite' details:
($ok, $mysql_info) = $cce->get($event_oid, 'MYSQL_Vsite');
($ok, $old_mysql_info) = $cce->get($event_old->{OID}, 'MYSQL_Vsite');
&debug_msg("Dumper: " . Dumper($event_object, $old_mysql_info, $event_new, $event_old, $event_oid, $event_is_create, $event_is_destroy, $event_is_modify, $mysql_info) . "\n");
&debug_msg("DB: $mysql_info->{DB}  \n");

# Get 'System' details:
@system_main = $cce->find('System');
if (!defined($system_main[0])) {
    &debug_msg("Sorry, no 'System' object found in CCE!\n");
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
&debug_msg("system_main[0]: $system_main[0]\n");

# Check if MySQLd is running. If not, we can stop right here and now:
&check_mysqld_status;
&debug_msg("MySQLd status: $sts_mysqld \n");
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

&debug_msg("User: $siteMysql_user - DB: $siteMysql_db\n");

# Get main MySQL access settings out of 'MySQL':
@mysql_main = $cce->find('MySQL');
if (!defined($mysql_main[0])) {
    &debug_msg("Sorry, no 'MySQL' object found in CCE!\n");
    &debug_msg("Unable to fetch MySQL 'root' access details for MySQL.\n");
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
    &debug_msg("Unable to fetch MySQL 'root' access details for MySQL.\n");
    $cce->bye('FAIL', "Unable to fetch MySQL 'root' access details for MySQL from CCE. Please configure them under 'Network Settings', 'MySQL'.");
    exit(1);
}
else {
    $mysql_host=$sql_host . ":" . $sql_port;
}

if ($mysql_info->{'enabled'} == 1) {
    &debug_msg("=================== Create db ==============  \n");
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
    if ($db_rights->{'SELECT'} == 1) {
        push @the_user_rights, "SELECT";
    }
    if ($db_rights->{'INSERT'} == 1) {
        push @the_user_rights, "INSERT";
    }
    if ($db_rights->{'UPDATE'} == 1) {
        push @the_user_rights, "UPDATE";
    }
    if ($db_rights->{'DELETE'} == 1) {
        push @the_user_rights, "DELETE";
    }
    # FILE is a global privilege and cannot be granted individually for a DB:
    #if ($db_rights->{'FILE'} == 1) {
    #   push @the_user_rights, "FILE";
    #}
    if ($db_rights->{'CREATE'} == 1) {
        push @the_user_rights, "CREATE";
    }
    if ($db_rights->{'DROP'} == 1) {
        push @the_user_rights, "DROP";
    }
    if ($db_rights->{'INDEX'} == 1) {
        push @the_user_rights, "INDEX";
    }
    if ($db_rights->{'ALTER'} == 1) {
        push @the_user_rights, "ALTER";
    }
    if ($db_rights->{'TEMPORARY'} == 1) {
        push @the_user_rights, "CREATE TEMPORARY TABLES";
    }
    if ($db_rights->{'GRANT'} == 1) {
        push @the_user_rights, "GRANT OPTION";
    }
    if ($db_rights->{'REFERENCE'} == 1) {
        push @the_user_rights, "REFERENCES";
    }
    if ($db_rights->{'LOCK'} == 1) {
        push @the_user_rights, "LOCK TABLES";
    }
    if ($db_rights->{'CREATE_VIEW'} == 1) {
        push @the_user_rights, "CREATE VIEW";
    }
    if ($db_rights->{'SHOW_VIEW'} == 1) {
        push @the_user_rights, "SHOW VIEW";
    }
    if ($db_rights->{'CREATE_ROUTINE'} == 1) {
        push @the_user_rights, "CREATE ROUTINE";
    }
    if ($db_rights->{'ALTER_ROUTINE'} == 1) {
        push @the_user_rights, "ALTER ROUTINE";
    }
    if ($db_rights->{'EXECUTE'} == 1) {
        push @the_user_rights, "EXECUTE";
    }

    # Start: New rights:
    if ($db_rights->{'EVENT'} == 1) {
        push @the_user_rights, "EVENT";
    }
    if ($db_rights->{'TRIGGER'} == 1) {
        push @the_user_rights, "TRIGGER";
    }
    if ($db_rights->{'LOCK_TABLES'} == 1) {
        push @the_user_rights, "LOCK TABLES";
    }
    if ($db_rights->{'REFERENCES'} == 1) {
        push @the_user_rights, "REFERENCES";
    }
    # End: New rights
    
    # SQL create
    $the_user_rights_count = @the_user_rights;
    &debug_msg("the_user_rights_count:$the_user_rights_count  \n");
    
    for ( my $x = 0; $x < $the_user_rights_count; $x++) {
        if ($x < $the_user_rights_count - 1) {
            $my_user_rights .= $the_user_rights[$x] . ", ";
        }
        else {
            $my_user_rights .= $the_user_rights[$x] . "";
        }
    }

    &debug_msg("my_user_rights:$my_user_rights\n");
    
    my $db_check_fail = 0;
    my $message = "";
    &debug_msg("Dumper: " . Dumper($sql_host, $sql_port, $sql_root, $root_pass) . "\n");
    
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
    if (!$dbh)
    {
        $cce->bye('FAIL', 'Can not connect Database');
        exit(1);
    }
    
    # Create Database
    $query = "CREATE DATABASE `$siteMysql_db`;\n";
    &debug_msg("Dumper: " . Dumper($query) . "\n");
    $return = $dbh->do($query);
    &debug_msg("Dumper: " . Dumper($return) . "\n");
    if ($dbh->err) {
         &debug_msg("Dumper: " . Dumper($dbh->err) . " ErrMsg: " . $dbh->errstr . "\n");
    }

    # Create User
    # Note to self: In MariaDB-10.1 the behavior changed. Now we need to explicitly create the user first before we can GRANT him any rights.
    # In the past a GRANT on a non-existing User would create the User instead. Fun and games!
    $query = "CREATE USER `$siteMysql_user`@`$siteMysql_host`;\n";
    &debug_msg("Dumper: " . Dumper($query) . "\n");
    $return = $dbh->do($query);
    &debug_msg("Dumper: " . Dumper($return) . "\n");
    if ($dbh->err) {
         &debug_msg("Dumper: " . Dumper($dbh->err) . " ErrMsg: " . $dbh->errstr . "\n");
    }

    # Grant basic rights to User:
    $query = "GRANT $my_user_rights ON `$siteMysql_db`.* TO `$siteMysql_user`@`$siteMysql_host`;\n";
    &debug_msg("Dumper: " . Dumper($query) . "\n");
    $return = $dbh->do($query);
    &debug_msg("Dumper: " . Dumper($return) . "\n");
    if ($dbh->err) {
         &debug_msg("Dumper: " . Dumper($dbh->err) . " ErrMsg: " . $dbh->errstr . "\n");
    }
    
    # Grant DB-rights to User:
    $query = "GRANT USAGE ON * . * TO `$siteMysql_user`@`$siteMysql_host` IDENTIFIED BY '$siteMysql_pass' WITH MAX_QUERIES_PER_HOUR $MAX_QUERIES_PER_HOUR MAX_CONNECTIONS_PER_HOUR $MAX_CONNECTIONS_PER_HOUR MAX_UPDATES_PER_HOUR $MAX_UPDATES_PER_HOUR;\n";
    &debug_msg("Dumper: " . Dumper($query) . "\n");
    $return = $dbh->do($query);
    &debug_msg("Dumper: " . Dumper($return) . "\n");
    if ($dbh->err) {
         &debug_msg("Dumper: " . Dumper($dbh->err) . " ErrMsg: " . $dbh->errstr . "\n");
    }
    
    # FLUSH PRIVILEGES
    $query = "FLUSH PRIVILEGES;\n";
    &debug_msg("Dumper: " . Dumper($query) . "\n");
    $return = $dbh->do($query);
    &debug_msg("Dumper: " . Dumper($return) . "\n");
    
    $dbh->disconnect;
    
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
    &debug_msg("Dumper: " . Dumper($dbh) . "\n");
    
    if (!$dbh) {
        $message .= "Can not Connect MySQL Server\n";
        $db_check_fail = 1;
        $cce->bye('FAIL', $message);
        exit(1);
    }
    
    $query = "DROP DATABASE IF EXISTS $siteMysql_db";
    &debug_msg("Dumper: " . Dumper($query) . "\n");
    $return = $dbh->do($query);
    &debug_msg("Dumper: " . Dumper($return) . "\n");
    
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
    &debug_msg("Dumper: " . Dumper($dbh) . "\n");
    
    if (!$dbh) {
        $message .= "Can not Connect MySQL Server\n";
        $db_check_fail = 1;
        $cce->bye('FAIL', $message);
        exit(1);
    }

    $query = "REVOKE ALL PRIVILEGES ON * . * FROM '$siteMysql_user'\@'$siteMysql_host';\n";
    &debug_msg("Dumper: " . Dumper($query) . "\n");
    $return = $dbh->do($query);
    &debug_msg("Dumper: " . Dumper($return) . "\n");

    #
    ## Revoke privileges (Step #2):
    #
    $query = "REVOKE ALL PRIVILEGES ON `$siteMysql_db` . * FROM '$siteMysql_user'\@'$siteMysql_host';\n";
    &debug_msg("Dumper: " . Dumper($query) . "\n");
    $return = $dbh->do($query);
    &debug_msg("Dumper: " . Dumper($return) . "\n");
    
    #
    ## Revoke privileges (Step #3):
    #
    $qeury = "REVOKE GRANT OPTION ON * . * FROM '$siteMysql_user'\@'$siteMysql_host';\n";
    &debug_msg("Dumper: " . Dumper($query) . "\n");
    $return = $dbh->do($query);
    &debug_msg("Dumper: " . Dumper($return) . "\n");

    #
    ## Delete the sites MySQL user: 
    #
    $query = "DROP USER '$siteMysql_user'\@'$siteMysql_host';\n";
    &debug_msg("Dumper: " . Dumper($query) . "\n");
    $return = $dbh->do($query);
    &debug_msg("Dumper: " . Dumper($return) . "\n");
    
    #
    ## Flush privileges:
    #
    $query = "FLUSH PRIVILEGES;";
    &debug_msg("Dumper: " . Dumper($query) . "\n");
    $return = $dbh->do($query);
    &debug_msg("Dumper: " . Dumper($return) . "\n");
    
    $dbh->disconnect;
    
}

sub check_mysqld_status {
    # Check if MySQLd is running:
    $sts_mysqld = "UNKNOWN";
    $cmd_mysqld = `pidof mysqld|wc -l`;
    $cmd_mysqld = chomp($cmd_mysqld);
    &debug_msg("MySQL status: " . $cmd_mysqld . "\n");
    if ($cmd_mysqld eq "1") {
        $sts_mysqld = "RUNNING";
    }
}

sub debug_msg {
    if ($DEBUG) {
        my $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
}

# 
# Copyright (c) 2010 Hideki Oride <oride@gachapom.jp>
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#     notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#     notice, this list of conditions and the following disclaimer in 
#     the documentation and/or other materials provided with the 
#     distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#     contributors may be used to endorse or promote products derived 
#     from this software without specific prior written permission.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
# COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
# POSSIBILITY OF SUCH DAMAGE.
# 
# You acknowledge that this software is not designed or intended for 
# use in the design, construction, operation or maintenance of any 
# nuclear facility.
# 
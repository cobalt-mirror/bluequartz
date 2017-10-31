#!/usr/bin/perl
#
# mod_userpermsUpdate.pl

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
&debug_msg("DB: $mysql_info->{DBmulti}  \n");

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
    &debug_msg("=================== Modify User MySQL permissions ==============  \n");
    &mod_user_perms;
}

$cce->bye('SUCCESS');
exit 0;

### Subs

sub mod_user_perms {
    &debug_msg("Updating Permissions for user: $siteMysql_user \n");

    my $MAX_QUERIES_PER_HOUR = $mysql_info->{'MAX_QUERIES_PER_HOUR'};
    my $MAX_CONNECTIONS_PER_HOUR = $mysql_info->{'MAX_CONNECTIONS_PER_HOUR'};
    my $MAX_UPDATES_PER_HOUR = $mysql_info->{'MAX_UPDATES_PER_HOUR'};
    
    @the_user_rights =();
    if ($mysql_info->{'SELECT'} == 1) {
        push @the_user_rights, "SELECT";
    }
    if ($mysql_info->{'INSERT'} == 1) {
        push @the_user_rights, "INSERT";
    }
    if ($mysql_info->{'UPDATE'} == 1) {
        push @the_user_rights, "UPDATE";
    }
    if ($mysql_info->{'DELETE'} == 1) {
        push @the_user_rights, "DELETE";
    }
    # FILE is a global privilege and cannot be granted individually for a DB:
    #if ($mysql_info->{'FILE'} == 1) {
    #    push @the_user_rights, "FILE";
    #}
    if ($mysql_info->{'CREATE'} == 1) {
        push @the_user_rights, "CREATE";
    }
    if ($mysql_info->{'DROP'} == 1) {
        push @the_user_rights, "DROP";
    }
    if ($mysql_info->{'INDEX'} == 1) {
        push @the_user_rights, "INDEX";
    }
    if ($mysql_info->{'ALTER'} == 1) {
        push @the_user_rights, "ALTER";
    }
    if ($mysql_info->{'TEMPORARY'} == 1) {
        push @the_user_rights, "CREATE TEMPORARY TABLES";
    }
    if ($mysql_info->{'GRANT'} == 1) {
        push @the_user_rights, "GRANT OPTION";
    }
    if ($mysql_info->{'REFERENCE'} == 1) {
        push @the_user_rights, "REFERENCES";
    }
    if ($mysql_info->{'LOCK'} == 1) {
        push @the_user_rights, "LOCK TABLES";
    }
    if ($mysql_info->{'CREATE_VIEW'} == 1) {
        push @the_user_rights, "CREATE VIEW";
    }
    if ($mysql_info->{'SHOW_VIEW'} == 1) {
        push @the_user_rights, "SHOW VIEW";
    }
    if ($mysql_info->{'CREATE_ROUTINE'} == 1) {
        push @the_user_rights, "CREATE ROUTINE";
    }
    if ($mysql_info->{'ALTER_ROUTINE'} == 1) {
        push @the_user_rights, "ALTER ROUTINE";
    }
    if ($mysql_info->{'EXECUTE'} == 1) {
        push @the_user_rights, "EXECUTE";
    }

    # Start: New rights:
    if ($mysql_info->{'EVENT'} == 1) {
        push @the_user_rights, "EVENT";
    }
    if ($mysql_info->{'TRIGGER'} == 1) {
        push @the_user_rights, "TRIGGER";
    }
    if ($mysql_info->{'LOCK_TABLES'} == 1) {
        push @the_user_rights, "LOCK TABLES";
    }
    if ($mysql_info->{'REFERENCES'} == 1) {
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

    # Revoke Permissions:
    $query = "REVOKE ALL PRIVILEGES ON `$siteMysql_db`.* FROM `$siteMysql_user`@`$siteMysql_host`;\n";
    &debug_msg("Dumper: " . Dumper($query) . "\n");
    $return = $dbh->do($query);
    &debug_msg("Dumper: " . Dumper($return) . "\n");    

    # Set Permissions:
    $query = "GRANT $my_user_rights ON `$siteMysql_db`.* TO `$siteMysql_user`@`$siteMysql_host`;\n";
    &debug_msg("Dumper: " . Dumper($query) . "\n");
    $return = $dbh->do($query);
    &debug_msg("Dumper: " . Dumper($return) . "\n");

    @ExtraDBs = $cce->scalar_to_array($mysql_info->{DBmulti});
    foreach my $extraDB (@ExtraDBs) {
        # Revoke Permissions:
        $query = "REVOKE ALL PRIVILEGES ON `$extraDB`.* FROM `$siteMysql_user`@`$siteMysql_host`;\n";
        &debug_msg("Dumper: " . Dumper($query) . "\n");
        $return = $dbh->do($query);
        &debug_msg("Dumper: " . Dumper($return) . "\n");    

        # Set Permissions:
        $query = "GRANT $my_user_rights ON `$extraDB`.* TO `$siteMysql_user`@`$siteMysql_host`;\n";
        &debug_msg("Dumper: " . Dumper($query) . "\n");
        $return = $dbh->do($query);
        &debug_msg("Dumper: " . Dumper($return) . "\n");
    }

    # Grant Usage:
    $query = "GRANT USAGE ON * . * TO `$siteMysql_user`@`$siteMysql_host` IDENTIFIED BY '$siteMysql_pass' WITH MAX_QUERIES_PER_HOUR $MAX_QUERIES_PER_HOUR MAX_CONNECTIONS_PER_HOUR $MAX_CONNECTIONS_PER_HOUR MAX_UPDATES_PER_HOUR $MAX_UPDATES_PER_HOUR;\n";
    &debug_msg("Dumper: " . Dumper($query) . "\n");
    $return = $dbh->do($query);
    &debug_msg("Dumper: " . Dumper($return) . "\n");
    
    # FLUSH PRIVILEGES
    $query = "FLUSH PRIVILEGES;\n";
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
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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
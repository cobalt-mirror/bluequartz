#!/usr/bin/perl
#
# destroy_mysql_data.pl 

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

$cce = new CCE('Namespace' => "MYSQL_Vsite");
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
&debug_msg("DB: $event_old->{DB}  \n");
&debug_msg("DBmulti_old: $event_old->{DBmulti} \n");

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
$siteMysql_user = $event_old->{'username'};
$siteMysql_pass = $event_old->{'pass'}; # Do not Use.
$siteMysql_host = $event_old->{'host'};
$siteMysql_port = $event_old->{'port'};
$siteMysql_db = $event_old->{'DB'};

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

&debug_msg("mysql_main: $mysql_main \n");
&debug_msg("Dumper: " . Dumper($mysql_main) . "\n");
&debug_msg("Dumper: " . Dumper($sql_root, $root_pass, $sql_host, $sql_port) . "\n");

# Make sure we have all MySQL access details that we need (password is optional here):
unless (($sql_root ne "") && ($sql_host ne "") && ($sql_port ne "")) {
    &debug_msg("Unable to fetch MySQL 'root' access details for MySQL from CCE.\n");
    $cce->bye('FAIL', "Unable to fetch MySQL 'root' access details for MySQL from CCE. Please configure them under 'Network Settings', 'MySQL'.");
    exit(1);
}
else {
    $mysql_host=$sql_host . ":" . $sql_port;
}

if ($cce->event_is_destroy()) {
    &debug_msg("=================== Destroy Vsite ==============  \n");
    # Site deletion, not to be confused with MySQL disable
    &debug_msg("event_is_destroy \n");
    if ($event_old->{'enabled'} == 1) {
        &debug_msg("DB for this site is enabled. Destroying. \n");
        &remove_db_and_user;
    }
}

if ($cce->event_is_modify()) {
    &debug_msg("=================== Modify Vsite ==============  \n");
    # Vsite Modification. Check if MySQL is set to be disabled:
    &debug_msg("event_new: " . $event_new->{'enabled'} . "\n");
    if ($event_new->{'enabled'} == 0) {
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
    &debug_msg("Dumper: " . Dumper($siteMysql_db, $siteMysql_host, $siteMysql_port, $sql_root, $root_pass) . "\n");
    &debug_msg("Dumper: " . Dumper($dbh) . "\n");
    
    if (!$dbh) {
        # In that case the DB might be gone already. But as this is what we wanted,
        # we still exit with a 'SUCCESS' message and by not raising an error:
        $db_check_fail = 1;
        $cce->bye('SUCCESS');
        exit(0);
    }
    
    $query = "DROP DATABASE IF EXISTS `$siteMysql_db`;";
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
    &debug_msg("Dumper: " . Dumper($sql_host, $sql_port, $sql_root, $root_pass) . "\n");
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
    ## Remove any extra-DB that might be around:
    #
    @ExtraDBs = $cce->scalar_to_array($event_old->{DBmulti});
    foreach $extraDB (@ExtraDBs) {
        #
        ## Revoke privileges on extra-DB (Step #1):
        #
        &debug_msg("Revoking privileges on $extraDB for user $siteMysql_user:\n");
        $query = "REVOKE ALL PRIVILEGES ON `$extraDB` . * FROM " . $siteMysql_user . '@' . $siteMysql_host . ";\n";
        &debug_msg("Dumper: " . Dumper($query) . "\n");
        $return = $dbh->do($query);
        &debug_msg("Dumper: " . Dumper($return) . "\n");

        #
        ## Drop extra-DB (Step #2):
        #
        &debug_msg("Deleting DB $extraDB\n");
        $query = "DROP DATABASE IF EXISTS `$extraDB`;";
        &debug_msg("Dumper: " . Dumper($query) . "\n");
        $return = $dbh->do($query);
        &debug_msg("Dumper: " . Dumper($return) . "\n");
        $dbh->disconnect;

        #
        ### Updating DBdel:
        #
        ($ok) = $cce->set($event_oid, 'MYSQL_Vsite',{ 'DBdel' => '', 'DBmulti' => '' });

    }

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
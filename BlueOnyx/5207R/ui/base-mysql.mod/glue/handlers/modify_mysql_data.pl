#!/usr/bin/perl
#
# modify_mysql_data.pl

# Global Paths & Config
$namespace = 'MYSQL_Vsite';
$global_namespace = 'MYSQL_DBs';

# Perl libraries, all Sausalito
use lib qw(/usr/sausalito/perl);
use DBI;
use CCE;
use Sauce::Util;

# Debug, Will-style
$DEBUG = 0;
$DEBUG && open(STDERR, ">>/tmp/mod_mysql-vsite");
$DEBUG && warn $0.' '.`date`;

$cce = new CCE;
$cce->connectfd();
#$cce->connectuds(); # Only for debugging

$object = $cce->event_object();
$new = $cce->event_new();
$old = $cce->event_old();

$vsite_name = $old->{fqdn};
$DEBUG && warn "Vsite Name: $vsite_name  \n";

#$cce->bye('SUCCESS');
#exit(0);

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

# Check if MySQLd is running. If not, we can stop right here and now:
&check_mysqld_status;
$DEBUG && warn "MySQLd status: $sts_mysqld \n";
if ($sts_mysqld ne "RUNNING") {
    $cce->bye('SUCCESS');
    exit(0);
}

# Get Vsite's MySQL data out of 'MYSQL_DBs':
@mysql_vsite_extra = $cce->find('MYSQL_DBs', { 'fqdn' => $vsite_name} );
if (!defined($mysql_vsite_extra[0])) {
        print STDERR "Sorry, this Vsite has no 'MYSQL_DBs' object in CCE!\n";
	# We exit cleanly nonetheless, as this site might never have had a MySQL database.
	$cce->bye('SUCCESS');
	exit(0);
}
else {
	($ok, $site_mysql_extra) = $cce->get($mysql_vsite_extra[0]);
	$site_mysql_user = $site_mysql_extra->{username};
	$db = $site_mysql_extra->{DB};
	if ($site_mysql_extra->{enabled} == "1") {
	    $mysqlsite = "1";
	}
	else {
	    $mysqlsite = "0";
	}
	$DEBUG && warn "User: $site_mysql_user - DB: $db \n";
}

$fqdn = $vsite->{fqdn};
$fqdn = $new->{fqdn} if ($new->{fqdn});

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
	$new_sql_host=$sql_host . ":" . $sql_port;
}

if($cce->event_is_destroy() && $old->{fqdn}) {
	# Site deletion, not to be confused with MySQL disable
	&checkalldetails;
	if ($mysql_vsite_enabled == "1") {
	    # Only perform action if this site 
	    # has a MySQL database or user:
	    &remove_db_and_user;
	    &remove_extra;
	}
}
elsif ($mysqlsite == "1") {
    # Enable MySQL for the site

}

elsif ($mysqlsite == "0") {
    # disable for a site, not to be confused with site deletion
}

$cce->bye('SUCCESS');
exit 0;

### Subs

sub remove_db_and_user {

	#
	## Now we reset $sql_host with $my_fqdn on an as needed basis:
	#
	
	if ($sql_host ne 'localhost') {
	    $sql_host = $my_fqdn;
	}

	#
	## Delete the sites MySQL database: 
	#
	# Now caveat emptor! If we try to drop a database that (no longer?) exists (for whatever reasons!),
	# then DBI connect will bitch six days til Sunday. Now if it fails during site deletion, then site
	# deletion fails as well! We don't want that at all! Hence we wrap it into an eval and then figure 
	# out if it went through or not:
	$we_failed = "0";
	eval { 
	    $dbh = DBI->connect(
	    "DBI:mysql:$db;host=$new_sql_host",
	    $sql_root, $root_pass,
    	    {
        	RaiseError => 1,
    		PrintError => 0
    		}
	    ); 
	};
	if ($@) {
	    $we_failed = "1";
	}
        print STDERR "Retval: " . $we_failed . "\n";
	print STDERR "Dropping $db failed: $@\n" if $@;

	# And of course we only disconnect from the database if the connection was successful. Otherwise it
	# bitches again. The eval in there for 'drop database' could be outside, as we already made it
	# fool proof with the 'if exists' addition. But meh! That's fine:
	if ($we_failed eq "0") {
	    eval { $dbh->do("DROP DATABASE IF EXISTS $db") };
	    $dbh->disconnect();
	}

	#
        ## Revoke privileges (Step #1):
        #
        $dbh = DBI->connect("DBI:mysql:mysql;host=$new_sql_host", $sql_root, $root_pass) || die "Database connection not possible: $DBI::errstr";
	$userstring = "REVOKE ALL PRIVILEGES ON * . * FROM '$site_mysql_user'\@'$sql_host';\n";
        print STDERR "Userstring: " . $userstring . "\n";
        eval { $dbh->do($userstring) };
        print STDERR "Revoking privileges failed at step 1 for user $site_mysql_user at $sql_host $@\n" if $@;
        $dbh->disconnect();

	#
        ## Revoke privileges (Step #2):
        #
        $dbh = DBI->connect("DBI:mysql:mysql;host=$new_sql_host", $sql_root, $root_pass) || die "Database connection not possible: $DBI::errstr";
	$userstring = "REVOKE ALL PRIVILEGES ON `" . $db . "` . * FROM '$site_mysql_user'\@'$sql_host';\n";
        print STDERR "Userstring: " . $userstring . "\n";
        eval { $dbh->do($userstring) };
        print STDERR "Revoking privileges failed at step 2 for user $site_mysql_user at $sql_host $@\n" if $@;
        $dbh->disconnect();

	#
        ## Revoke privileges (Step #3):
        #
        $dbh = DBI->connect("DBI:mysql:mysql;host=$new_sql_host", $sql_root, $root_pass) || die "Database connection not possible: $DBI::errstr";
	$userstring = "REVOKE GRANT OPTION ON * . * FROM '$site_mysql_user'\@'$sql_host';\n";
        print STDERR "Userstring: " . $userstring . "\n";
        eval { $dbh->do($userstring) };
        print STDERR "Revoking privileges failed at step 3 for user $site_mysql_user at $sql_host $@\n" if $@;
        $dbh->disconnect();

	#
	## Delete the sites MySQL user: 
	#
	$dbh = DBI->connect("DBI:mysql:mysql;host=$new_sql_host", $sql_root, $root_pass) || die "Database connection not possible: $DBI::errstr";
	print STDERR "Trying to drop MySQL user $site_mysql_user at $sql_host \n";
	$userstring = "DROP USER '$site_mysql_user'\@'$sql_host';\n";
	print STDERR "Userstring: " . $userstring . "\n";
	eval { $dbh->do($userstring) };
	print STDERR "MySQL user removal failed for user $site_mysql_user at $sql_host $@\n" if $@;
	$dbh->disconnect();

	#
	## Flush privileges:
	#
	$dbh = DBI->connect("DBI:mysql:mysql;host=$new_sql_host", $sql_root, $root_pass) || die "Database connection not possible: $DBI::errstr";
	$userstring = "FLUSH PRIVILEGES;";
	eval { $dbh->do($userstring) };
	print STDERR "Flushing privileges failed: $@\n" if $@;
	$dbh->disconnect();

}

sub checkalldetails {
    # Make sure we know what user and DB to delete:
    if (($site_mysql_user eq "") || ($db eq "")) {
	# OK, this site doesn't have a MySQL database or user:
	$mysql_vsite_enabled = "0";
    }
    else {
	# OK, this site has a MySQL database or user:
	$mysql_vsite_enabled = "1";
    }
}

sub remove_extra {
    ($ok) = $cce->destroy($mysql_vsite_extra[0]);
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

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#	 notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#	 notice, this list of conditions and the following disclaimer in 
#	 the documentation and/or other materials provided with the 
#	 distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#	 contributors may be used to endorse or promote products derived 
#	 from this software without specific prior written permission.
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
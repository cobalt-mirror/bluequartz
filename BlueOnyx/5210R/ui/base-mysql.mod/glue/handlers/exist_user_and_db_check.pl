#!/usr/bin/perl -I/usr/sausalito/perl
#
# exist_user_and_db_check.pl

use CCE;
use POSIX qw(isalpha);
use DBI;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

$cce = new CCE;

$cce->connectfd();

my $vsite     = $cce->event_object();
my $vsite_oid = $cce->event_oid();
my $vsite_new = $cce->event_new();
my $vsite_old = $cce->event_old();

# Get System Information
my @oids = $cce->find("System");
my ($ok, $system) = $cce->get($oids[0]);

# Get MySQL Information
my @oids = $cce->find("MySQL");
my ($ok, $mysql) = $cce->get($oids[0]);

# Get 'MYSQL_Vsite' details:
($ok, $vsite_mysql) = $cce->get($vsite_oid, 'MYSQL_Vsite');

&debug_msg("DB: $vsite_mysql->{DB} \n");

# Enable and Disable Check
if ($vsite_mysql->{enabled} != 1) {
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

if (!$dbh) {
    $message .= '[[base-mysql.amNotAccepting]]';
    $db_check_fail = 1;
    &debug_msg("FAIL: $message \n");
    $cce->bye('FAIL', $message);
    exit(1);
}

# User Check
my $siteMySQLUser = $dbh->quote($vsite_mysql->{username});
&debug_msg("SQL: SELECT COUNT(*) as rowCount FROM `user` WHERE User = $siteMySQLUser \n");
@row = $dbh->selectrow_array("SELECT COUNT(*) as rowCount FROM `user` WHERE User = $siteMySQLUser");
&debug_msg("row[0]:$row[0] \n");

if ($row[0] != 0) {
    $message .= '[[base-mysql.CreateFailUserExists]]';
    $db_check_fail = 1;
}

# DB Check
my $siteMySQLDb = $dbh->quote($vsite_mysql->{DB});
&debug_msg("SQL: SELECT COUNT(*) as rowCount FROM `db` WHERE User = $siteMySQLUser AND Db = $siteMySQLDb \n");
@row = $dbh->selectrow_array("SELECT COUNT(*) as rowCount FROM `db` WHERE User = $siteMySQLUser AND Db = $siteMySQLDb");
&debug_msg("row[0]:$row[0] \n");

if ($row[0] != 0) {
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
&debug_msg("Dumper" . Dumper($dbh) . "\n"); 

if ($dbh) {
    # $dbh->diconnect;
    $message .= '[[base-mysql.CreateFailDatabaseExists]]';
    $db_check_fail = 1;
}

if ($db_check_fail)  {
    &debug_msg("FAIL: $message \n");
    $cce->bye('FAIL', $message);
    exit(1);
}

$cce->bye('SUCCESS');
exit(0);

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
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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
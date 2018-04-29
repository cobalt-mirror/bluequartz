#!/usr/bin/perl -I /usr/sausalito/perl
#
# $Id: purge_avspam.pl
# 
# If 'System' . 'Sitestats' . 'avspam' is updated we connect to MySQL and
# in the AV-SPAM database we prune 'milter_usage' to cull entries older 
# than our retention period for records.
#

# Debugging flag: Set to 1 to turn on logging to /var/log/messages
my $DEBUG = 0;
if ($DEBUG) 
{ 
    use Data::Dumper; 
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

use CCE;
use DBI;

my $cce = new CCE;
$cce->connectfd();

my @sysoids = $cce->find('System');
my ($ok, $sitestats) = $cce->get($sysoids[0], 'Sitestats');

# Early exit if pruning is disabled or AV-SPAM doesn't use SQL:
if (($sitestats->{avspam} eq "0") || (! -f '/etc/mail/spamassassin/sql.cf') || ($sitestats->{purge} eq "0")) {
  &debug_msg("Early exit as we're either not using SQL or don't want pruning.\n");
  $cce->bye('SUCCESS');
  exit(0);    
}

# Get "System" Object MySQL:
my ($ok, $sysMySQL) = $cce->get($sysoids[0], 'mysql');
unless ($ok and $sysMySQL) {
  $cce->bye('FAIL');
  exit 1;
}

# Get "AVSPAM_Settings" from CODB:
($ok, $avspam) = $cce->get($sysoids[0], 'AVSPAM_Settings');
unless ($ok and $avspam) {
  $cce->bye('FAIL');
  exit 1;
}

# Get MySQL connection details:
@myoids = $cce->find('MySQL');
($ok, $sys_sql_config) = $cce->get($myoids[0], "");
unless ($ok and $sys_sql_config) {
  $cce->bye('FAIL');
  exit 1;
}

# Debug output:
&debug_msg("Debug: MySQL enabled: " . $sysMySQL->{'enabled'}  . "\n");
&debug_msg("Debug: avs_dbname: " . $avspam->{'avs_dbname'}  . "\n");

# AV-SPAM SQL is enabled:
if ($sysMySQL->{'enabled'} eq "1") {

  # Prune 'milter_usage' database:
  $MySQLError = '0';
  $dbh = DBI->connect("DBI:mysql:" . $avspam->{'avs_dbname'} . ":host=$sys_sql_config->{'sql_host'}", $sys_sql_config->{'sql_root'}, $sys_sql_config->{'sql_rootpassword'}, { PrintError => 0, RaiseError => 1, HandleError => \&handle_error });            
  &debug_msg("Debug: MySQL Root Connection result: $MySQLError \n");
  if ($MySQLError eq "0") {
      $sql = "DELETE FROM `milter_usage` WHERE `date` < DATE_SUB(NOW() , INTERVAL " . $sitestats->{'purge'} . " DAY);\n";
      &debug_msg("Debug: Running Query: $sql \n");
      $sth = $dbh->prepare($sql);
      $sth->execute();
  }

  if ($MySQLError eq "0") {
      &debug_msg("Debug: Pruning reported no error. \n");
  }
  else {
    &debug_msg("Debug: Pruning reported error. \n");
  }

}

$cce->bye('SUCCESS');
exit(0);

#
### Subs:
#

sub debug_msg {
  if ($DEBUG) {
    my $msg = shift;
    $DEBUG && print STDERR "$ARGV[0]: ", $msg, "\n";

    $user = $ENV{'USER'};
    setlogsock('unix');
    openlog($0,'','user');
    syslog('info', "$ARGV[0]: $msg");
    closelog;
  }
}

sub handle_error {
    my $message = shift;
    #write error message wherever you want
    &debug_msg("Debug: MySQL-Error: " . $message . "\n");
    $MySQLError = '1';
}

# 
# Copyright (c) 2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2018 Team BlueOnyx, BLUEONYX.IT
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
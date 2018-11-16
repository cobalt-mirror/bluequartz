#!/usr/bin/perl -I /usr/sausalito/perl
#
# $Id: logrotate.pl
# 
# Parses /etc/logrotate.conf to see what values logrotate is set to and
# stores that value into CODB.
#

# Debugging flag: Set to 1 to turn on logging to /var/log/messages and console:
my $DEBUG = 0;
if ($DEBUG) 
{ 
    use Data::Dumper; 
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

use CCE;

my $cce = new CCE;
$cce->connectuds();

my @sysoids = $cce->find('System');
my ($ok, $sitestats) = $cce->get($sysoids[0], 'Sitestats');

if (-f '/etc/logrotate.conf') {
  $daily = `cat /etc/logrotate.conf |grep ^daily|wc -l`;
  chomp($daily);
  $weekly = `cat /etc/logrotate.conf |grep ^weekly|wc -l`;
  chomp($weekly);
  $monthly = `cat /etc/logrotate.conf |grep ^monthly|wc -l`;
  chomp($monthly);
  $yearly = `cat /etc/logrotate.conf |grep ^yearly|wc -l`;
  chomp($yearly);

  $period = 'daily';
  if ($daily eq '1') {
    $period = 'daily';
  }
  if ($weekly eq '1') {
    $period = 'weekly';
  }
  if ($monthly eq '1') {
    $period = 'monthly';
  }
  if ($yearly eq '1') {
    $period = 'yearly';
  }

  $rotate = `cat /etc/logrotate.conf |grep ^rotate|sed "s#^rotate ##g"`;
  chomp($rotate);

  $mult = '1';
  if ($period eq 'daily') {
    $mult = '1';
  }
  elsif ($period eq 'weekly') {
    $mult = '7';
  }
  elsif ($period eq 'monthly') {
    $mult = '30';
  }
  elsif ($period eq 'yearly') {
    $mult = '365';
  }

  # Build output value:
  $rotate_final = $rotate * $mult;

  if ($rotate_final eq '0') {
    $rotate_final = '14';
  }

  # If values are not identical, update CODB:
  if (($sitestats->{'rotate'} ne $rotate_final) || ($weekly eq '1')) {
    &debug_msg("Updating CODB with logrotate interval of $rotate_final.\n");
    my ($ok) = $cce->set($sysoids[0], 'Sitestats', { 'rotate' => $rotate_final});
    if (!$ok) {
      $cce->bye('FAIL');
      exit(1);
    }
  }
  else {
    &debug_msg("CODB update not neccessary.\n");
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
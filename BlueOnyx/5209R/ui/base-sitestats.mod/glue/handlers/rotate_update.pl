#!/usr/bin/perl -I /usr/sausalito/perl
#
# $Id: rotate_update.pl
# 
# If 'System' . 'Sitestats' . 'rotate' is updated, we update
# the relevant logrotate config files to reflect the changes.
#

# Debugging flag: Set to 1 to turn on logging to /var/log/messages
my $DEBUG = 0;
use Data::Dumper;
if ($DEBUG) 
{ 
    use Data::Dumper;
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

use CCE;
use Sauce::Util;

my $cce = new CCE;
$cce->connectfd();

my @sysoids = $cce->find('System');
my ($ok, $sitestats) = $cce->get($sysoids[0], 'Sitestats');

$rot_replace = $sitestats->{'rotate'};

#
### Deal with /etc/logrotate.conf:
#

&debug_msg("Logrotate interval is set to: " . $rot_replace . " \n");
if (!Sauce::Util::editfile('/etc/logrotate.conf', *edit_logrotate, $rot_replace))
{
    $cce->bye('FAIL', '[[base-sitestats.cantEnableLogrotate]]');
    exit(1);
}

#
### Deal with /etc/logrotate.d/apache:
#

&debug_msg("Running: " . 'sed -i -e "s|rotate \([0-9]\+\)|rotate ' . $rot_replace . '|g" /etc/logrotate.d/apache' . " \n");
system('sed -i -e "s|rotate \([0-9]\+\)|rotate ' . $rot_replace . '|g" /etc/logrotate.d/apache');

#
### Deal with /etc/logrotate.d/admserv:
#

&debug_msg("Running: " . 'sed -i -e "s|rotate \([0-9]\+\)|rotate ' . $rot_replace . '|g" /etc/logrotate.d/admserv' . " \n");
system('sed -i -e "s|rotate \([0-9]\+\)|rotate ' . $rot_replace . '|g" /etc/logrotate.d/admserv');

#
### Deal with /etc/logrotate.d/nginx:
#

if (-f '/etc/logrotate.d/nginx') {
  &debug_msg("Running: " . 'sed -i -e "s|rotate \([0-9]\+\)|rotate ' . $rot_replace . '|g" /etc/logrotate.d/nginx' . " \n");
  system('sed -i -e "s|rotate \([0-9]\+\)|rotate ' . $rot_replace . '|g" /etc/logrotate.d/nginx');
}


$cce->bye('SUCCESS');
exit(0);

#
### Subs:
#

sub edit_logrotate
{
    my($in, $out, $rotate) = @_;

    if (($rotate eq "") || ($rotate lt '0')) {
      $rotate = '14';
    }

    my($logrotate) = <<EOF;
# see "man logrotate" for details
# rotate log files daily
daily

# keep $rotate days worth of backlogs
rotate $rotate

# create new (empty) log files after rotating old ones
create

# use date as a suffix of the rotated file
dateext

# uncomment this if you want your log files compressed
compress

# RPM packages drop log rotation information into this directory
include /etc/logrotate.d

# no packages own wtmp and btmp -- we'll rotate them here
/var/log/wtmp {
    monthly
    create 0664 root utmp
        minsize 1M
    rotate 1
}

/var/log/btmp {
    missingok
    monthly
    create 0600 root utmp
    rotate 1
}

# system-specific logs may be also be configured here
EOF

    print $out $logrotate;

    return 1;
}

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
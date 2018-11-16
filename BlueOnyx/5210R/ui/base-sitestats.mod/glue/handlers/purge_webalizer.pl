#!/usr/bin/perl -I /usr/sausalito/perl
#
# $Id: purge_webalizer.pl
# 
# If 'System' . 'Sitestats' . 'webalizer' is updated we run through all
# Vsites and remove the static files for Webalizer.
#

# Debugging flag: Set to 1 to turn on logging to /var/log/messages
my $DEBUG = 0;
if ($DEBUG) 
{ 
    use Data::Dumper; 
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

use CCE;

my $cce = new CCE;
$cce->connectfd();

my @sysoids = $cce->find('System');
my ($ok, $sitestats) = $cce->get($sysoids[0], 'Sitestats');

# Early exit if no reset is wanted:
if ($sitestats->{webalizer} eq "0") {
    $cce->bye('SUCCESS');
    exit(0);    
}

# Remove server-stats:
if (-d '/var/www/usage') {
  system("rm -f /var/www/usage/*.*");
  system("rm -f /var/www/usage/*.*");
}

# /var/www/usage

# Find all Vsites:
my @vhosts = ();
my (@vhosts) = $cce->findx('Vsite');

# Walk through all Vsites:
for my $vsite_oid (@vhosts) {
  ($ok, my $vsite) = $cce->get($vsite_oid);
  $webalizer_int = $vsite->{basedir} . '/webalizer';
  $webalizer_int_files = $vsite->{basedir} . '/webalizer/*.*';

  if (-d $webalizer_int) {
    &debug_msg("Vsite " . $vsite->{name} . " deleting $webalizer_int_files\n");
    system("rm -f $webalizer_int_files");
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
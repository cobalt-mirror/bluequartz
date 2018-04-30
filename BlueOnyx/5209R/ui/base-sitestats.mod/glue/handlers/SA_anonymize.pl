#!/usr/bin/perl -I /usr/sausalito/perl
#
# $Id: SA_anonymize.pl
# 
# If 'System' . 'Sitestats' . 'SA_anonymize' is updated we update
# /etc/sendmailanalyzer.conf and restart SendmailAnalyzer.
#

# Debugging flag: Set to 1 to turn on logging to /var/log/messages
my $DEBUG = 0;
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

if (-f '/etc/sendmailanalyzer.conf') {
  if (!Sauce::Util::editfile(('/etc/sendmailanalyzer.conf'),*edit_blueonyx, $sitestats->{'SA_anonymize'})) {
      $cce->bye('FAIL', '[[base-sitestats.cantEdit_sendmailanalyzer_conf]]');
      exit(1);
  }
  Sauce::Service::service_run_init('sendmailanalyzer', 'restart');
}

$cce->bye('SUCCESS');
exit(0);

#&debug_msg("Running: cp /home/sendmailanalyzer/sa_to_php.pl /root/sa_to_php.pl\n");

# Anonymize reports. This remove sender and recipient adresses from reports.

#
### Subs:
#

sub edit_blueonyx {
    my ($in, $out, $webdata) = @_;
    my $begin = '# Anonymize reports. This remove sender and recipient adresses from reports.';
    my $script_conf = '';


    $out_options = join(" ", @Options);
    $out_AllowOverride = join(" ", @AllowOverride);
    $script_conf = $begin . "\n";
    $script_conf .= "ANONYMIZE     $webdata\n";

    my $last;
    while(<$in>) {
        if(/^$begin$/) {
            while(<$in>) {
                if(/^ANONYMIZE(.*)$/) { last; }
            }
            print $out $script_conf;
        }
        else {
            print $out $_;
        }
    }
    print $out $last;

    # preserve the remainder of the config file
    while(<$in>) {
        print $out $_;
    }

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
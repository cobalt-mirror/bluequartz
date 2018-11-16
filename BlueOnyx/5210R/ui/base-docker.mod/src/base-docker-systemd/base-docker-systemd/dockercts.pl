#!/usr/bin/perl -I/usr/sausalito/perl

use Getopt::Long;
use CCE;
use Sauce::Util;
use Sauce::Config;
use Sauce::Service;

# System name of the service:
$service = 'dockercts.service';

my $cce = new CCE;

# Debugging switch (0|1|2):
# 0 = off
# 1 = log to syslog
# 2 = log to screen
#
$DEBUG = "2";
if ($DEBUG) {
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $cce = new CCE;
$cce->connectuds();

# Get the System Object:
@sysoids = $cce->find('System');
($ok, $ServiceSettings) = $cce->get($sysoids[0], 'Docker');
if (!$ok) {
    &debug_msg("Service $service: Unspecified fail. \n");
    $cce->bye('FAIL');
}

#
### Command line option handling
#

GetOptions ('stop' => sub { $action = "stop" },
            'start' => sub { $action = "start" },
            'restart' => sub { $action = "start" },
            'help|h'  => sub { HelpMessage() });

if (($action eq "") || ($ServiceSettings->{'enabled'} eq "0")) {
    &HelpMessage;
    $cce->bye('SUCCESS');
    exit(0);
}

if ($ServiceSettings->{'enabled'} eq "0") {
    &debug_msg("Service Docker is not enabled. Cannot manage any CTs at this time.\n");
    $cce->bye('SUCCESS');
    exit(0);
}


#
## Handle Action: stop
#

if ($action eq "stop") {
    # Check for Containers with enabled AutoStart:
    $rawAutoStartCTs = `/usr/bin/ls -k1 /var/lib/docker/containers/*/autostart|/usr/bin/cut -d / -f6`;
    chomp($rawAutoStartCTs);
    @AutoStartCTs = split /\n/, $rawAutoStartCTs;
    my $CTstate = '0';
    foreach my $x (@AutoStartCTs) {
        $CTstate = `/usr/bin/docker inspect $x |grep Running|grep false|wc -l`;
        chomp($CTstate);
        &debug_msg("Stopping Docker CT $x.\n");
        system("/usr/bin/docker stop $x >/dev/null 2>&1 || :");
    }
}

if ($action eq "start") {
    # Check for Containers with enabled AutoStart:
    $rawAutoStartCTs = `/usr/bin/ls -k1 /var/lib/docker/containers/*/autostart|/usr/bin/cut -d / -f6`;
    chomp($rawAutoStartCTs);
    @AutoStartCTs = split /\n/, $rawAutoStartCTs;
    my $CTstate = '0';
    foreach my $x (@AutoStartCTs) {
        $CTstate = `/usr/bin/docker inspect $x |grep Running|grep false|wc -l`;
        chomp($CTstate);
        &debug_msg("Restarting Docker CT $x.\n");
        system("/usr/bin/docker restart $x >/dev/null 2>&1 || :");
    }
}

#
### End:
#
$cce->bye('SUCCESS');
exit(0);

##
# Subs:
##

# Debug:
sub debug_msg {
    if ($DEBUG) {
        my $msg = shift;
        if ($DEBUG eq "1") {
            $user = $ENV{'USER'};
            setlogsock('unix');
            openlog($0,'','user');
            syslog('info', "$ARGV[0]: $msg");
            closelog;
        }
        else {
            print $msg;
        }
    }
}

sub HelpMessage {
    print "Command line options: \n";
    print " -stop \n";
    print " -start \n";
    print " -restart \n";
}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2018 Michael Stauber, SOLARSPEED.NET
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
#!/usr/bin/perl -I/usr/sausalito/perl

# System name of the service:
$service = 'docker';

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

use AM::Util;
use Sauce::Service;
use CCE;

my $cce = new CCE;
$cce->connectuds();

my %am_states = am_get_statecodes();

# Get the System Object:
@sysoids = $cce->find('System');
($ok, $ServiceSettings) = $cce->get($sysoids[0], 'Docker');
if (!$ok) {
    &debug_msg("Service $service: Unspecified fail. \n");
    $cce->bye('FAIL');
    exit $am_states{AM_STATE_NOINFO};
}

# Check if the service is enabled:
if ($ServiceSettings->{'enabled'} eq "1") {
    # Service is enabled:
    &debug_msg("Service $service is enabled in the GUI. Checking status. \n");

    # Check if the System service is enabled:
    $enabled = Sauce::Service::service_get_init($service);
    &debug_msg("Service $service is enabled on the System level. \n");

    if ($enabled eq "0") {
        Sauce::Service::service_set_init($service, 'on');
        Sauce::Service::service_run_init($service, 'restart');
        &debug_msg("Service $service was not enabled. But it is enabled now and has been started.\n");
    }

    # Check Status:
    $status = `pidof /usr/bin/dockerd|wc -l`;
    chomp($status);
    &debug_msg("Service $service status check: $status.\n");
    if ($status gt "0") {
        # Service is working:
        &debug_msg("Service $service is working. \n");
        print $ENV{greenMsg};
        $cce->bye('SUCCESS');
        exit $am_states{AM_STATE_GREEN};
    }
    else {
        # Service failed. Attempting a restart:
        &debug_msg("Service $service has failed and we are restarting it. \n");
        Sauce::Service::service_run_init($service, 'restart');
        sleep 3;
        # Retest if service is working now:
        $status = `pidof /usr/bin/dockerd|wc -l`;
        chomp($status);
        if ($status eq "0") {
            &debug_msg("Service $service has failed. \n");
            print $ENV{redMsg};
            $cce->bye('SUCCESS');
            exit $am_states{AM_STATE_RED};
        }
    }
}
else {
    # Service is disabled:
    &debug_msg("Service $service is disabled. \n");
    $cce->bye('SUCCESS');
    exit $am_states{AM_STATE_NOINFO};
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

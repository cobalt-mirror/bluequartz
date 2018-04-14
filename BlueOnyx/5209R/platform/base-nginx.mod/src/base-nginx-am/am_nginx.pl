#!/usr/bin/perl -I/usr/sausalito/perl

use AM::Util;
use Sauce::Service;
use CCE;
use LWP::UserAgent; 
use HTTP::Request::Common qw{ POST };

my $cce = new CCE;
$cce->connectuds();

my ($oid) = $cce->find('System');
my ($AMoid) = $cce->find('ActiveMonitor');
my ($ok, $System) = $cce->get($oid);
my ($ok, $objWeb) = $cce->get($oid, 'Web');
my ($ok, $Nginx) = $cce->get($oid, 'Nginx');
my ($ok, $NginxAM) = $cce->get($AMoid, 'Nginx');

my %am_states = am_get_statecodes();

$fqdn = $System->{hostname} . '.' . $System->{domainname};
$sslPort = $objWeb->{sslPort};
$check_url = "https://$fqdn:$sslPort";

if ($Nginx->{enabled} eq "1") {
    # Make sure nginx is enabled:
    $nginx_status = Sauce::Service::service_get_init("nginx");
    if ($nginx_status == "0") {        
        Sauce::Service::service_set_init('nginx', 'on');
    }
}
else {
    Sauce::Service::service_set_init('nginx', 'off');
    ($ok) = $cce->update($AMoid, 'Nginx',{
        'enabled' => '0',
        'monitor' => '0'
    });
}

$nginx_status = &check_ssl_nginx;
if ($nginx_status eq "0") {
    # Service not running:

    # Perform action:
    if (-f "/usr/bin/systemctl") { 
        # Got Systemd: 
        # Please note: For nginx we do not use systemctl with the --no-block option to
        # enqueue the call. We issue it directly and wait for the result.
        `/usr/bin/systemctl --job-mode=flush restart nginx.service`; 
    } 
    else { 
        # Thank God, no Systemd: 
        `/sbin/service nginx restart`;
    }
    # Now check again:
    $nginx_status = &check_ssl_nginx;
    if ($nginx_status eq "0") {
        print $ENV{redMsg};
        $cce->bye('SUCCESS'); 
        exit $am_states{AM_STATE_RED};
    }
    else {
        print $ENV{greenMsg};
        $cce->bye('SUCCESS');
        exit $am_states{AM_STATE_GREEN};
    }
}
else {
    print $ENV{greenMsg};
    $cce->bye('SUCCESS');
    exit $am_states{AM_STATE_GREEN};
}

$cce->bye('SUCCESS');
exit $am_states{AM_STATE_NOINFO};

#
### Subs
#

sub check_ssl_nginx {
    $ua       = LWP::UserAgent->new(ssl_opts => { verify_hostname => 0 });
    $ua->agent('ActiveMonitor/1.0');
    $response = $ua->head($check_url);
    if ($response->is_success) {
        #print "Response from $check_url was: SUCCESS " . $response->status_line . "\n";
        return 1;
    }
    else {
        #print "Response from $check_url was: FAIL " . $response->status_line . "\n";
        return 0;
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
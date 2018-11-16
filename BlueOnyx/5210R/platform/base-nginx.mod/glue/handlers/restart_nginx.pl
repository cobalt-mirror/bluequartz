#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: restart_nginx.pl

# Debugging switch:
$DEBUG = "1";
if ($DEBUG)
{
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

use CCE;
use Sauce::Service;

my $cce = new CCE;
$cce->connectfd();

@SysOID = $cce->find('System');
($ok, $Nginx) = $cce->get($SysOID[0], 'Nginx');

if ($Nginx->{enabled} eq "1") {
    &debug_msg("Restarting Nginx\n");
    Sauce::Service::service_set_init('nginx', 'on');
    Sauce::Service::service_run_init('httpd', 'restart');
    Sauce::Service::service_run_init('nginx', 'restart');
}
else {
    &debug_msg("Stopping and disabling Nginx\n");
    Sauce::Service::service_set_init('nginx', 'off');
    Sauce::Service::service_run_init('nginx', 'stop');
    Sauce::Service::service_run_init('httpd', 'restart');
}

$cce->bye('SUCCESS');
exit 0;

#
### Subroutines:
#

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
#    notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#    notice, this list of conditions and the following disclaimer in 
#    the documentation and/or other materials provided with the 
#    distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#    contributors may be used to endorse or promote products derived 
#    from this software without specific prior written permission.
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
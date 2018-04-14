#!/usr/bin/perl -I/usr/sausalito/perl

use AM::Util;
use Sauce::Service;
use CCE;

my $cce = new CCE;
$cce->connectuds();

if (-f "/usr/bin/systemctl") { 
    $awk = '/usr/bin/awk';
    $kill = '/usr/bin/kill';
    $grep = '/usr/bin/grep';
    $ps = '/usr/bin/ps';
    $wc = '/usr/bin/wc';
}
else {
    $awk = '/bin/awk';
    $kill = '/bin/kill';
    $grep = '/bin/grep';
    $ps = '/bin/ps';
    $wc = '/usr/bin/wc';
}

# See how Apache behaves currently:
$checker = &_check_apache_state;

# Act on it:
if ($checker ne "1") {
    # Service is not running right! Fix it!
    &_kill_and_restart_apache;
}

# Make sure httpd is enabled:
$apache_status = service_get_init("httpd");
if ($apache_status == "0") {
    # It is not? Turn it on:
    service_set_init("httpd", "on");
}

$cce->bye('SUCCESS');
exit(0);

#
### Subs
#

sub _check_apache_state {
    # Check how many Apache processes are currently attached around as 
    # primaries and not as children. There should be only one:
    $apache_state = `$ps axf|$grep /usr/sbin/httpd|$grep -v adm|$grep -v '\_'|$wc -l`;
    chomp($apache_state);

    ## Legend:
    #   0   Apache dead
    #   1   Apache probably running OK
    #  >1   Childs have detached (bad)
    return $apache_state;
}

sub _kill_and_restart_apache {

    # See how Apache behaves currently:
    $xchecker = &_check_apache_state;
    ## Legend:
    #   0   Apache dead
    #   1   Apache probably running OK
    #  >1   Childs have detached (bad)

    if ($xchecker > "1") {
        # Apache-Childs have detached from the master-process. Which is bad.
        # Kill httpd (but not AdmServ!):
        `$ps axf|$grep /usr/sbin/httpd|$grep -v adm|$grep -v grep|$grep -v '\_'|$awk -F ' ' '{print \$1}'|/usr/bin/xargs $kill -9 >&/dev/null`;
    }

    # Perform action:
    if (-f "/usr/bin/systemctl") { 
        # Got Systemd: 
        # Please note: For httpd we do not use systemctl with the --no-block option to
        # enqueue the call. We issue it directly and wait for the result.
        `/usr/bin/systemctl --job-mode=flush restart httpd.service`; 
    } 
    else { 
        # Thank God, no Systemd: 
        `/sbin/service httpd restart`;
    }
}

# 
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
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
#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: servicequota_usage.pl
# Update service quotas 

use CCE;
use Quota;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG) {
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $cce = new CCE;
$cce->connectfd();

# return error string, null for success
my $err;

# find ServiceQuota objects
foreach my $oid ($cce->find('ServiceQuota')) {
    &debug_msg("Found ServiceQuota oid $oid\n");

    my($ok, $service) = $cce->get($oid);
    next unless ($ok);

    my ($aok, $site) = $cce->get(($cce->find('Vsite', {'name' => $service->{site}}))[0]);
    next unless ($aok);

    my $used = 0;
    if ($service->{isgroup}) {
        my $gid = getgrnam($service->{account});
        ($used) = Quota::query(Quota::getqcarg($site->{volume}), $gid, 1);
        &debug_msg("Found $service->{account} (group $gid) usage $used for volume $site->{volume}\n");
    }
    else {
        my $pid = getpwnam($service->{account});
        ($used) = Quota::query(Quota::getqcarg($site->{volume}), $pid);
        &debug_msg("Found user $service->{account} ($pid) usage $used for volume $site->{volume}\n");
    }

    $used ||= 0;
    ($ok) = $cce->set($oid, '', {'used' => $used}) if ($used =~ /^\d+$/);
}

if ($err) {
    $cce->bye('FAIL', $err);
    exit(1);
}

$cce->bye('SUCCESS');
exit(0);

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
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
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
#!/usr/bin/perl
# $Id: addSWUpdateServer.pl
# author: mchan@cobalt.com

use lib '/usr/sausalito/perl';
use Sauce::Config;
use CCE;

my $cce = new CCE;
$cce->connectuds();

my $package_dir = '/home/packages';

# Fix perms on /var/log/yum.log:
if (! -f '/var/log/yum.log') {
    system("touch /var/log/yum.log");
}
system("/bin/chmod 644 /var/log/yum.log");

# Disable RHEL6 yum-autoupdater if present:
if (-e "/etc/sysconfig/yum-autoupdate") {
    system("/usr/bin/perl -pi -e 's|^ENABLED=\"true\"|ENABLED=\"false\"|g' /etc/sysconfig/yum-autoupdate");
}

my @OIDS = $cce->find('SWUpdateServer');

# Check if SWUpdateServer object has the right data. If it has wrong data, fix it.
# If it's missing, create it.
if ($OIDS[0] ne "") {
    my ($ok, $SWUpdateServer) = $cce->get($OIDS[0]);
    if ($SWUpdateServer->{location} ne 'newlinq.blueonyx.it') {
        $cce->update($OIDS[0], '', { 'location' => 'newlinq.blueonyx.it', 'name' => 'default', 'enabled' => '1' });
    }
}
else {
    foreach $oid (@OIDS) {
        my ($ok, $obj) = $cce->destroy($oid);
    }

    # create SWUpdateServer object
    $cce->create('SWUpdateServer', {
        'name'        => 'default',
        'enabled'     => 1,
        'location'    => 'newlinq.blueonyx.it',
    });
}

mkdir($package_dir, 0755) unless -d ($package_dir);
chown((getpwnam(Sauce::Config::groupdir_owner))[2,3], $package_dir);

# sync up system with cce
@OIDS = $cce->find('System');
if (@OIDS) {
    my ($ok, $obj) = $cce->get($OIDS[0], 'SWUpdate');
    my $interval = $obj->{updateInterval};

    # If Update-Interval is 'Never' (no longer supported), then change it to 'Daily':
    if ($interval eq "Never") {
        $interval = 'Daily';
        $cce->update($OIDS[0], 'SWUpdate', { updateInterval => $interval });
    }
}

$cce->bye('SUCCESS');
exit 0;

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
#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
# $Id: suspend.pl
#
# Handle most of the site related stuff that needs to happen when a site 
# is suspended.

use CCE;
use Vsite;

my $cce = new CCE;
$cce->connectfd();

# things to do on site suspend
# 1. (maybe) disable mailing lists or maybe just maillist server.
# 2. disable VirtualHost
# 3. suspend all site users - and lock their accounts.
# 4. disable the site preview (if it was enabled), because otherwise it's still
#    possible to see (and use!) it through the site preview. 

my $vsite = $cce->event_object();

# deal with mailing lists when they actually somewhat work

# disable my VirtualHost and reset site_preview:
my ($vhost) = $cce->findx('VirtualHost', { 'name' => $vsite->{name} });
my ($ok) = $cce->set($vhost, '', { 
        'enabled' => ($vsite->{suspend} ? 0 : 1)
    });

if (not $ok) {
    $cce->bye('FAIL', '[[base-vsite.cantDisableVhost]]');
    exit(1);
}

# Change permissions on Vsite homedir on suspend/unsuspend:
if ($vsite->{suspend} == "1") {
    vsite_disable($cce, $vsite->{name});
}
else {
    vsite_enable($cce, $vsite->{name});
}

# suspend/unsuspend all site users
my @users = ();
if ($vsite->{suspend}) {
    #
    # site being suspended, so find all site members that are currently
    # enabled
    #
    @users = $cce->findx('User',
            { 'site' => $vsite->{name}, 'enabled' => 1 });

    # Disable site_preview:
    my ($ok) = $cce->set($vhost, '', { 'site_preview' => '0' });
} 
else {
    #
    # site being unsuspended, so find all site members who
    # should be reenabled
    #
    @users = $cce->findx('User',
            { 'site' => $vsite->{name}, 'ui_enabled' => 1 });

    # Enable site_preview again if it was active for the site:
    if ($vsite->{site_preview} == "1") {
        my ($ok) = $cce->set($vhost, '', { 'site_preview' => '1' });
    }
}

for my $user (@users) {
    ($ok) = $cce->set($user, '', { 'enabled' => ($vsite->{suspend} ? 0 : 1) });

    #
    ### User accounts MUST be locked or unlocked. Otherwise suspended users can still use SMTP-Auth:
    #

    # Get the username:
    ($ok, my $myuser) = $cce->get($user);

    if ($vsite->{suspend} == "0") {
        # Unlock account:
        system("/usr/sbin/usermod -U $myuser->{name}");
        }
    else {
        # Lock account:
        system("/usr/sbin/usermod -L $myuser->{name}");
    }

    if (not $ok) {
        $cce->bye('FAIL', '[[base-vsite.cantSuspendUsers]]');
        exit(1);
    }
}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
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
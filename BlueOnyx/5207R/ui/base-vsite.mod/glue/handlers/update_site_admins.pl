#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: update_site_admins.pl
#
# keep all site admins capabilities in sync as admin users give and
# take away features from a virtual site

use CCE;
use Base::User::Capabilities;

my $cce = new CCE;
$cce->connectfd();

my $vsite = $cce->event_object();
my $vsite_old = {};

my @site_admins = ();

# handle site admin creation and modifying the siteAdminCaps property
if ($vsite->{CLASS} eq 'Vsite')
{
    $vsite_old = $cce->event_old();
    @site_admins = $cce->findx('User', 
                    { 'site' => $vsite->{name}, 'capLevels' => 'siteAdmin' },
                    {}, '', '');
}
else
{
    # must be a user
    if ($vsite->{capLevels} !~ /siteAdmin/)
    {
        $cce->bye('SUCCESS');
        exit(0);
    }

    push @site_admins, $cce->event_oid();
    
    # need to get the vsite info if this is a user
    my ($oid) = $cce->findx('Vsite', { 'name' => $vsite->{site} });
    (my $ok, $vsite, $vsite_old) = $cce->get($oid);

    if (!$ok)
    {
        $cce->bye('FAIL', '[[base-vsite.cantReadVsite]]');
        exit(1);
    }
}

# setup lists of caps for user updates
my @old_caps = $cce->scalar_to_array($vsite_old->{siteAdminCaps});
my @new_caps = $cce->scalar_to_array($vsite->{siteAdminCaps});

# get the caps class
my $capabilities = new Base::User::Capabilities($cce);

# go through and reset all site admins' capLevels
for my $admin (@site_admins)
{
    if (!$capabilities->modUserCaps($admin, \@old_caps, \@new_caps))
    {
        $cce->bye('FAIL', '[[base-vsite.cantUpdateSiteAdminCaps]]');
        exit(0);
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
#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/ftp
# $Id: vsite_suspend.pl
# 
# Bring down anonymous ftp and also ftp for the vsite's ip address, if
# it is the only site using the ip, when a Vsite is suspended.

use CCE;

my $cce = new CCE('Domain' => 'base-ftp');
$cce->connectfd();

my $vsite = $cce->event_object();
my ($ftp_site) = $cce->find('FtpSite', { 'ipaddr' => $vsite->{ipaddr} });
my ($ok, $anon_ftp) = $cce->get($cce->event_oid(), 'AnonFtp');

# get current ftp settings
($ok, my $ftp_settings) = $cce->get($ftp_site);

my $new_settings = {};

# disable ftp on suspend
if ($vsite->{suspend})
{
    # check if anonymous ftp needs to be disabled
    if ($anon_ftp->{enabled})
    {
        $new_settings->{anonymous} = 0;
    }

    ($ok) = $cce->set($ftp_site, '', $new_settings);
    if (not $ok)
    {
        $cce->bye('FAIL', 'cantSuspendFtp');
        exit(0);
    }

}
else # must be unsuspending a site
{
    # figure out what to do with anonymous ftp
    if ($anon_ftp->{enabled})
    {
        # make sure someone else hasn't taken over
        if ($ftp_settings->{anonymous} &&
            ($ftp_settings->{anonymousOwner} ne $vsite->{name}))
        {
            # disable anonymous for this site and issue a warning
            $cce->set($cce->event_oid(), 'AnonFtp', { 'enabled' => 0 });
            $cce->info('unsuspendAnonFtpInUse', { 'ipaddr' => $vsite->{ipaddr} });
        }
        else # reclaim anonymous ftp
        {
            $new_settings->{anonymous} = 1;
            $new_settings->{anonymousOwner} = $vsite->{name};
            $new_settings->{maxConnections} = $anon_ftp->{maxConnections};
            $new_settings->{quota} = $anon_ftp->{quota};
        }
    }

    # restore settings
    ($ok) = $cce->set($ftp_site, '', $new_settings);

    if (not $ok)
    {
        $cce->bye('FAIL', 'cantUnsuspendFtp');
        exit(0);
    }
}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2017 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2017 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
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
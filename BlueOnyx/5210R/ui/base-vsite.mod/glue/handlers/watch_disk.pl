#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: watch_disk.pl
#
# make sure that additional storage that sites are located on cannot
# be modified or removed so that the sites become unusable

use CCE;

my $cce = new CCE('Domain' => 'base-vsite');
$cce->connectfd();

my $disk = $cce->event_object();
if ($cce->event_is_destroy())
{
    $disk = $cce->event_old();
}

# just succeed for the /home partition
if ($disk->{mountPoint} eq '/home')
{
    $cce->bye('SUCCESS');
    exit(0);
}

my @sites = $cce->findx('Vsite', 
                    { 'volume' => $disk->{mountPoint} });

if (scalar(@sites))
{
    my @site_names = ();
    for my $site (@sites)
    {
        my ($ok, $site_info) = $cce->get($site);
        push @site_names, $site_info->{fqdn};
    }
    
    $cce->bye('FAIL', 'diskUsedBySites', { 'sites' => (join(', ', @site_names)) });
    exit(1);
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
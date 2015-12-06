#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/ssl
# $Id: vsite_ssl.pl
# update siteAdminCaps property when enabling/disabling ssl

my $DEBUG = 0;

use File::Copy;
use CCE;
use Base::Vsite qw(vsite_update_site_admin_caps);

my $cce = new CCE('Domain' => 'base-ssl');
$cce->connectfd();

# get vsite and ssl information for the vsite
my $vsite = $cce->event_object();
my $oid = $cce->event_oid();
my ($ok, $ssl_info) = $cce->get($oid, 'SSL');
$DEBUG && print STDERR Dumper($vsite, $ssl_info);

## make sure two sites with the same IP address don't both get SSL enabled
#my @other_sites = $cce->findx('Vsite', { 'ipaddr' => $vsite->{ipaddr}, 'SSL.enabled' => 1 });
#
#if ($ssl_info->{enabled} && scalar(@other_sites) > 1) {
#    my $oid_to_get = $other_sites[0] != $oid ? $other_sites[0] : $other_sites[1];
#    ($ok, my $other_site) = $cce->get($oid_to_get);
#                                
#    $cce->bye('FAIL', 'sslAlreadyUsedByIP', 
#                { 
#                    'fqdn' => $other_site->{fqdn}, 
#                    'ipaddr' => $other_site->{ipaddr} 
#                });
#    exit(1);
#}

# set VirtualHost.ssl 
my ($void) = $cce->find('VirtualHost', {'name' => $vsite->{name}}); 
$cce->set($void, '', {'ssl' => $ssl_info->{enabled}}); 

# update siteAdminCaps in Vsite
if (!$cce->event_is_destroy()) {
    vsite_update_site_admin_caps($cce, $vsite, 'siteSSL', $ssl_info->{enabled});
}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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
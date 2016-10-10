#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: LE_SSL_fixer.pl
#
# This constructor walks through all SSL enabled Vsites that are using
# Let's Encrypt certificates. 
#
# It then adds all 'webAliases' of that Vsite to the list of domain
# aliases that Let's Encrypt will request cert validity for during
# the next renewal.
#
# This script is used to transition the CODB data from a prior 
# implementation of the SSL GUI to a newer one. You should not run it
# manually unless you know what you do.
#

use CCE;
$cce = new CCE;
$cce->connectuds();

# Root check:
$id = `id -u`;
chomp($id);
if ($id ne "0") {
    print "$0 must be run by user 'root'!\n";

    $cce->bye('FAIL');
    exit(1);
}

# Find all Vsites:
@vhosts = ();
(@vhosts) = $cce->findx('Vsite');

# Walk through all Vsites:
for $vsite (@vhosts) {

  ($ok, $my_vsite) = $cce->get($vsite);
  ($ok, $xvsite_SSL) = $cce->get($vsite, 'SSL');

  if (($xvsite_SSL->{'enabled'} == "1") && ($xvsite_SSL->{'uses_letsencrypt'} == "1") && ($xvsite_SSL->{'LEwantedAliases'} eq "") && ($my_vsite->{'webAliases'} ne "")) {
    print "Vsite $my_vsite->{fqdn} has SSL enabled and uses a Let's Encrypt SSL certificate. Adding all 'webAliases'.\n";
    ($ok) = $cce->set($vsite, 'SSL', { 'LEwantedAliases' => $my_vsite->{'webAliases'} });
  }
}

# tell cce everything is okay
$cce->bye('SUCCESS');
exit(0);

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

#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: SSL_fixer.pl
#
# This constructor handles the transition from the old SSL implementation 
# (5106R/5107R/5108R) to the new one used on 5207R/5208R/5209R.
#
# In the old implementation we were using /etc/httpd/conf.d/ssl_perl.conf 
# and Apache2::PerlSections() to dynamically create the SSL-enabled Vhost
# containers for Apache. While that was pretty slick: It's difficult to do
# SNI with that (SNI = allow multiple SSL enabled Vsites on the same IP).
# At least I couldn't find a way to make it happen that way.
#
# So instead I went back and borrowed Hisao Shibuya's 5200R code to include
# the SSL Vhost containers in the siteX include files.
#
# This creates a transitional problem if someone upgrades an existing box
# from 5107R/5108R to 5207R/5208R. In that case the SSL-Vhost containers
# need to be created, as ssl_perl.conf is no longer dynamically doing that.
#
# Hence this script: It checks if Vsites have SSL enabled. If so, it toggles
# SSL off and back on. That forces the GUI handlers to do their magic and 
# they will then write out the missing SSL-Vhost into the respective
# siteX include file(s).
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
    ($ok, $xvsite_PHP) = $cce->get($vsite, 'PHP');

    if ($xvsite_SSL->{'enabled'} == "1") {
        print "Vsite $my_vsite->{fqdn} has SSL enabled. Toggling it off and back on.\n";
        ($ok) = $cce->set($vsite, 'SSL', { 'enabled' => '0' });
        ($ok) = $cce->set($vsite, 'SSL', { 'enabled' => '1' });
        ($ok) = $cce->set($vsite, 'PHPVsite',{ 'force_update' => time()});
        ($ok) = $cce->set($vsite, '',{
            'webAliases' => ''
           });
        ($ok) = $cce->set($vsite, '',{
            'webAliases' => $my_vsite->{webAliases}
           });

        ($ok) = $cce->set($vsite, '',{
            'mailAliases' => ''
           });
        ($ok) = $cce->set($vsite, '',{
            'mailAliases' => $my_vsite->{mailAliases}
           });
        ($ok) = $cce->set($vsite, 'PHP',{
            'enabled' => '0'
           });
        ($ok) = $cce->set($vsite, 'PHP',{
            'enabled' => $xvsite_PHP->{enabled}
           });
    }
    if ($xvsite_SSL->{'enabled'} == "0") {
        print "Vsite $my_vsite->{fqdn} does not have SSL enabled. Toggling it on and back off.\n";
        ($ok) = $cce->set($vsite, 'SSL', { 'enabled' => '1' });
        ($ok) = $cce->set($vsite, 'SSL', { 'enabled' => '0' });
        ($ok) = $cce->set($vsite, 'PHPVsite',{ 'force_update' => time()});
        ($ok) = $cce->set($vsite, '',{
            'webAliases' => ''
           });
        ($ok) = $cce->set($vsite, '',{
            'webAliases' => $my_vsite->{webAliases}
           });

        ($ok) = $cce->set($vsite, '',{
            'mailAliases' => ''
           });
        ($ok) = $cce->set($vsite, '',{
            'mailAliases' => $my_vsite->{mailAliases}
           });
        ($ok) = $cce->set($vsite, 'PHP',{
            'enabled' => '0'
           });
        ($ok) = $cce->set($vsite, 'PHP',{
            'enabled' => $xvsite_PHP->{enabled}
           });
    }
}

# tell cce everything is okay
$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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

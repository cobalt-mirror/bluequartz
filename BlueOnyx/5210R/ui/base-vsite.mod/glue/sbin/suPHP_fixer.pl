#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: suPHP_fixer.pl
#
# This constructor handles the transition from the old suPHP to the new one, which
# allows for custom php.ini config files in the homedir of Vsites.
#
# This constructor runs through all sites and checks if suPHP is enabled.
#
# If it is, it checks if a custom php.ini is already present. If not, it will
# disable suPHP briefly and will immediately re-enabled it. That then triggers
# the creation of the custom php.ini config file in the Vsites home directory.

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

# Start sane:
$found = "0";

# Walk through all Vsites:
for $vsite (@vhosts) {

    # Start sane:
    $custom_php_ini = "";

    ($ok, $my_vsite) = $cce->get($vsite);
    ($ok, $xvsite_php) = $cce->get($vsite, 'PHP');

    # Custom php.ini location:
    $custom_php_ini = $my_vsite->{'basedir'} . "/php.ini";

    if (($xvsite_php->{'suPHP_enabled'} == "1") &&  (!-f $custom_php_ini)) {
    if ($found == "0") {
        print "Going through all suPHP enabled Vsites to make sure they all have a custom php.ini: \n";
        $found++;
    }
    print "Vsite $my_vsite->{fqdn} has suPHP enabled, but is missing a custom php.ini. Fixing it!\n";
    ($ok) = $cce->set($vsite, 'PHP', { 'suPHP_enabled' => '0' });
    ($ok) = $cce->set($vsite, 'PHP', { 'suPHP_enabled' => '1' });
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
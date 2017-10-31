#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: 11_configureSystem.pl
#
# setup things in the raqish state in the system object

use strict;
use CCE;
use I18n;

my $cce = new CCE;
$cce->connectuds();

my $i18n = new I18n;
$i18n->setLocale(I18n::i18n_getSystemLocale($cce));

# find the system object
my ($oid) = $cce->find('System');
if (!$oid)
{
    $cce->bye('FAIL', '[[base-alpine.cantFindSystem]]');
    exit(1);
}

# setup stuff, don't bother with failure, because messages should be
# propagated up, and, well, this is a constructor so what can we do?
my ($ok) = $cce->set($oid, '', { 'productName' => $i18n->interpolate('[[base-product.productName]]') });

# set Telnet access appropriately for raqs
my ($ok) = $cce->set($oid, 'Telnet', { 'access' => 'reg' });

# turn on console access, this should probably be removed before shipping
($ok) = $cce->set($oid, '', { 'console' => 1 });

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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
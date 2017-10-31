#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: free_admin_alias.pl
#
# The email alias 'admin' used to be a reserved (protected) email alias.
# We just changed that. However, to free it up for usage on existing Vsites,
# one needs to run this script first. The post-install script of the 
# base-vsite RPMs will run it automatically during the transition.

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

# Find all ProtectedEmailAlias that have the 'alias' of 'admin':
@ProtectedEmailAlias = ();
(@ProtectedEmailAlias) = $cce->findx('ProtectedEmailAlias', { 'alias' => 'admin'});

# Start sane:
$found = "0";

# Walk through all results and destroy them:
for $aliasOID (@ProtectedEmailAlias) {
    ($ok, $xvsite_php) = $cce->destroy($aliasOID);
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
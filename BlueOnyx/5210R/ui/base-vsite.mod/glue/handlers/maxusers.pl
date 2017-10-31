#!/usr/bin/perl -I /usr/sausalito/perl
# $Id: maxusers.pl
#
# enforce the maxusers requirement for vsites

use CCE;

my $cce = new CCE('Domain' => 'base-vsite');
$cce->connectfd();

my $user_new = $cce->event_new();

# special case, if the site property of the user is not set assume it's a global user
if ($user_new->{site} eq '')
{
    $cce->bye('SUCCESS');
    exit(0);
}

# get the vsite info
my ($ok, $vsite) = $cce->get(($cce->find("Vsite", { 'name' => $user_new->{site} }))[0]);
if (not $ok)
{
    $cce->bye('FAIL', '[[base-vsite.cantReadVsite]]');
    exit(1);
}

# get current number of users and check to see if this user
# will put the site over the limit
# note: the user being created will be found in this search 
#       so it's actually current users + 1
my $num_users = scalar($cce->find('User', { 'site' => $user_new->{site} }));

if ($num_users > $vsite->{maxusers})
{
    $cce->bye('FAIL', 'overUserLimit', { 'fqdn' => $vsite->{fqdn} });
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
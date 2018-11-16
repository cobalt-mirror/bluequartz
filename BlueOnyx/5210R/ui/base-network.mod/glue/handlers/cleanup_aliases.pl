#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: cleanup_aliases.pl
#
# if a real interface has just changed IP addresses to that of an alias
# cleanup the alias

use CCE;

my $cce = new CCE;
$cce->connectfd();

my $network = $cce->event_object();

# if this Network object is not real or not enabled, just succeed
if ((!$network->{ipaddr} || !$network->{ipaddr_IPv6}) || !$network->{real} || !$network->{enabled}) 
{
    $cce->bye('SUCCESS');
    exit(0);
}

# check for aliases
my @aliases = $cce->find('Network',
                {
                    'ipaddr' => $network->{ipaddr},
                    'ipaddr_IPv6' => $network->{ipaddr_IPv6}, 
                    'real' => 0,
                    'enabled' => 1
                });

for (my $i = 0; $i < scalar(@aliases); $i++)
{
    my ($ok) = $cce->set($aliases[$i], '', { 'enabled' => 0 });
    ($ok) = $cce->destroy($aliases[$i]);
    if (!$ok)
    {
        $cce->bye('FAIL');
        exit(1);
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
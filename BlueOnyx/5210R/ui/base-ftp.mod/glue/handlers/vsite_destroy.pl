#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: vsite_destroy.pl
# clear anonymous ftp settings if a Vsite gets destroyed and they have
# anon ftp enabled

use CCE;

my $cce = new CCE;

$cce->connectfd();

# only old settings available on DESTROY
my $old_vsite = $cce->event_old();

my (@oids) = $cce->findx('FtpSite', { 'anonymousOwner' => $old_vsite->{name} });

for my $oid (@oids)
{
    my ($ok) = $cce->set($oid, '', { 'anonymousOwner' => '', 'anonymous' => 0 });

    if (!$ok)
    {
        $cce->warn('[[base-ftp.cantDisableAnonVsite]]');
    }
}

# check for affected FtpSite entries by IP
my (@ftpsite_oids) = $cce->findx('FtpSite', { 'ipaddr' => $old_vsite->{ipaddr} });
for my $oid (@ftpsite_oids)
{
    my ($ok) = $cce->set($oid, '', { 'commit' => time() });
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
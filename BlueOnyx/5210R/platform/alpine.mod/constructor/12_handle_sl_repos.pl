#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: 12_handle_sl_repos.pl
#
# Check if this is Scientific Linux and set the YUM repository files to use 6x instead of the hardwired one from sl-release:
# In an ideal world this would go into base-blueonyx.mod. But if we update that, the mailing list will be screaming again.

use CCE;
use I18n;

my $cce = new CCE;
$cce->connectuds();

# Is YUM running? (0 = not running)
$yum = `ps axf|grep yum|grep -v grep|wc -l`;

if (-e "/etc/yum.repos.d/sl.repo") {
    # Do we have $releasever in sl.repo? 
    $ver = `cat /etc/yum.repos.d/sl.repo |grep releasever|wc -l`;
    chomp($ver);
}
else {
    # No sl.repo found:
    $cce->bye('SUCCESS');
    exit(0);    
}

# Execute if YUM is not running:
if ($yum == "0") {
    # But only if we have an sl.repo with $releasever in it:
    if ($ver ne "0") {
        # Clean YUM cache:
        system("yum clean all >/dev/null 2>&1");
    }
}

# Fix sl.repo:
if (-e "/etc/yum.repos.d/sl.repo") {
    system("sed -i 's/\$releasever/6x/g' /etc/yum.repos.d/sl.repo");
}
# Fix sl-other.repo:
if (-e "/etc/yum.repos.d/sl-other.repo") {
    system("sed -i 's/\$releasever/6x/g' /etc/yum.repos.d/sl-other.repo");
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
#!/usr/bin/perl -I/usr/sausalito/perl -I.
# $Id: set_httpd_alias_perms.pl
#
# This constructor sets the GID and permissions on the databases in /etc/httpd/alias/
# to the new requested standards as required by the mod_nss introduced by CentOS-5.6:

use Sauce::Service;
use CCE;

my $cce = new CCE;
$cce->connectuds();

# Fix GID and permissions one /etc/httpd/alias/ for new mod_nss:
if (-e "/etc/httpd/alias/cert8.db") {
        system('find /etc/httpd/alias -user root -name "*.db" -exec /bin/chgrp apache {} \;');
        system('find /etc/httpd/alias -user root -name "*.db" -exec /bin/chmod g+r {} \;');   
}

# While we are at it, delete the default CentOS welcome page:
if (-e "/etc/httpd/conf.d/welcome.conf") {
    system('/bin/rm -f /etc/httpd/conf.d/welcome.conf');
}

# Also delete /etc/httpd/conf.d/manual.conf:
if (-e "/etc/httpd/conf.d/manual.conf") {
    system('/bin/rm -f /etc/httpd/conf.d/manual.conf');
}

# Also remove server.conf
if (-e "/etc/httpd/conf.d/server.conf") {
    system('/bin/rm -f /etc/httpd/conf.d/server.conf');
}

# Also remove userdir.conf
if (-e "/etc/httpd/conf.d/userdir.conf") {
    system('/bin/rm -f /etc/httpd/conf.d/userdir.conf');
}

# A lot of BX servers have ImageMagick installed, which in turn installs and activates the avahi-daemon.
# This daemon is not really needed and certainly should not be running. Hence we stop it and turn it off:
if ((-f "/etc/avahi/avahi-daemon.conf") && (Sauce::Service::service_get_init('avahi-daemon') eq "1")) {
    Sauce::Service::service_run_init('avahi-daemon', 'stop');
    Sauce::Service::service_set_init('avahi-daemon', 0);
}

$cce->bye('SUCCESS');
exit 0;

# 
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
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

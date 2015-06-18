#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: vhost_addrem.pl
#
# Add or remove the include line for a vhost when creating or destroying,
#

use CCE;
use Sauce::Util;
use Base::Httpd qw(httpd_add_include httpd_remove_include);
use Sauce::Service;

my $cce = new CCE;

$cce->connectfd();

my ($ok, $vhost);
my $vhost_new = $cce->event_new();
if ($cce->event_is_create()) { 
    $vhost = $cce->event_object();
    #Sauce::Util::addrollbackcommand("/sbin/service httpd restart >/dev/null 2>&1 &");
    $ok = httpd_add_include("$Base::Httpd::vhost_dir/$vhost->{name}");

} elsif ($cce->event_is_destroy()) { 
    $vhost = $cce->event_old();
    #Sauce::Util::addrollbackcommand("/sbin/service httpd restart >/dev/null 2>&1 &");
    $ok = httpd_remove_include("$Base::Httpd::vhost_dir/$vhost->{name}");
    # don't remove the file here to avoid a race condition
}

if (not $ok) {
    $cce->bye('FAIL', '[[base-apache.cantEditHttpdConf]]');
    exit(1);
}

# perview config moves to last line
$ok = httpd_remove_include("$Base::Httpd::vhost_dir/preview");
$ok = httpd_add_include("$Base::Httpd::vhost_dir/preview");
if (not $ok) {
        $cce->bye('FAIL', '[[base-apache.cantEditHttpdConf]]');
        exit(1);
}

# always register a reload, to make sure apache knows the file is gone
service_run_init('httpd', 'reload');

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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
#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: product_language.pl
# Sets locale auto-negotiation order
#
# Depends on:
#		System.productLanguage
#
# MPBug fixed.

use strict;
use Sauce::Config;
use FileHandle;
use File::Copy;
use CCE;

my $cce = new CCE;
$cce->connectfd();

my ($oid) = $cce->find("System");
my ($ok, $obj) = $cce->get($oid);

my $locale = $obj->{productLanguage};

# Fix admin's locale
my ($uoid) = $cce->find("User", {name => 'admin'});

my ($uok, $uobj) = $cce->get($uoid);
if($locale ne $uobj->{localePreference}) {
	$cce->set($uoid, '', {localePreference => $locale});
	$cce->commit();
}

$cce->bye("SUCCESS");

umask(0077);

# legacy and modern Cobalt locale stamps
my $stage;

my $real = "/etc/cobalt/locale";
Sauce::Util::modifyfile($real);
$stage = $real.'~';
Sauce::Util::unlinkfile($stage);
sysopen(STAGE, $stage, 1|O_CREAT|O_EXCL, 0600) || die;
print STAGE $locale."\n";
close(STAGE);

chmod(0644, $stage);
if(-s $stage) {
  move($stage,$real);
  chmod(0644,$real); # paranoia
} 

$real = "/usr/sausalito/locale";
Sauce::Util::modifyfile($real);
$stage = $real.'~';
unlink($stage);
sysopen(STAGE, $stage, 1|O_CREAT|O_EXCL, 0600) || die;
print STAGE $locale."\n";
close(STAGE);

chmod(0644, $stage);
if(-s $stage) {
  move($stage,$real);
  chmod(0644,$real); # paranoia
} 

my @fall_back_html = ('/usr/sausalito/ui/web/error/authorizationRequired.html',
                      '/usr/sausalito/ui/web/error/fileNotFound.html',
                      '/usr/sausalito/ui/web/error/forbidden.html',
                      '/usr/sausalito/ui/web/error/internalServerError.html');
my($page);
foreach $page (@fall_back_html) {
  my $fall_back = $page.'.'.$locale;
  next unless (-r $fall_back);
  Sauce::Util::unlinkfile($page);
  Sauce::Util::copyfile($fall_back, $page);
  Sauce::Util::chmodfile(0644,$page);
}
  
exit(0);

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#   notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#   notice, this list of conditions and the following disclaimer in 
#   the documentation and/or other materials provided with the 
#   distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#   contributors may be used to endorse or promote products derived 
#   from this software without specific prior written permission.
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
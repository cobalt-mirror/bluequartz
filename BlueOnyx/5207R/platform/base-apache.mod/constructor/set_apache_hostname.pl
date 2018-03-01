#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: set_apache_hostname.pl
#
# This constructor sets the ServerAdmin and ServerName in the Apache config of
# both the public Apache and AdmServ. It does NOT restart both services by intent,
# so the changes will only take effect during the next restart of these services.
#
# Depends on:
#   System.hostname
#   System.domainname

my $confdir = '/etc/httpd/conf';
my $aconfdir = '/etc/admserv/conf';

use Sauce::Config;
use Sauce::Util;
use FileHandle;
use File::Copy;
use CCE;

my $cce = new CCE;
$cce->connectuds();

my ($oid) = $cce->find("System");
my ($ok, $obj) = $cce->get($oid);
my ($status, $web) = $cce->get($oid, "Web");

my $hostname = $obj->{hostname};
my $domain = $obj->{domainname};
$hostname =~ s/\.${domain}$//;
my $fqdn = $hostname . '.' . $domain;
my $httpPort = $web->{'httpPort'};

if ($httpPort eq "") {
  $httpPort = '80';
}

umask(0077);
my $stage = "$confdir/httpd.conf~";
open(HTTPD, "$confdir/httpd.conf");
unlink($stage);
sysopen(STAGE, $stage, 1|O_CREAT|O_EXCL, 0600) || die;
while(<HTTPD>) {
  s|^ServerTokens OS|ServerTokens ProductOnly|g;
  s/^ServerAdmin\s.+$/ServerAdmin admin\@$fqdn/;
  s/^ServerName\s.+$/ServerName $fqdn/;
  s/^#ServerName\s.+$/ServerName $fqdn/;
  s/^Listen\s.+$/Listen $httpPort/;
  
  print STAGE;
}
close(STAGE);
close(HTTPD);

chmod(0644, $stage);
if(-s $stage) {
  move($stage,"$confdir/httpd.conf");
  chmod(0644, "$confdir/httpd.conf"); # paranoia
  $apache_ok = "1";
} else {
  $apache_ok = "0";
}

# Handle admserv:
umask(0077);
my $astage = "$aconfdir/httpd.conf~";
open(HTTPD, "$aconfdir/httpd.conf");
unlink($astage);
sysopen(STAGE, $astage, 1|O_CREAT|O_EXCL, 0600) || die;
while(<HTTPD>) {
  s|^ServerTokens OS|ServerTokens ProductOnly|g;
  s/^ServerAdmin\s.+$/ServerAdmin admin\@$fqdn/;
  s/^ServerName\s.+$/ServerName $fqdn/;
  s/^#ServerName\s.+$/ServerName $fqdn/;
  s|^AddDefaultCharset\s.+$|AddDefaultCharset UTF-8|g;

  print STAGE;
}
close(STAGE);
close(HTTPD);

chmod(0644, $astage);
if(-s $astage) {
  move($astage,"$aconfdir/httpd.conf");
  chmod(0644, "$aconfdir/httpd.conf"); # paranoia
  $admserv_ok = "1";
} else {
  $admserv_ok = "0";
}

if (($apache_ok == "1") && ($admserv_ok == "1")) {
  $cce->bye("SUCCESS");
}
else {
  $cce->bye("FAILURE");
}

# Add PerlConfigRequire line to httpd.conf:
$cfg_block = 'PerlConfigRequire /etc/httpd/conf.perl/00-default-vsite.pl';

system("cp /etc/httpd/conf/httpd.conf /etc/httpd/conf/httpd.conf.bak")
$ok = Sauce::Util::replaceblock("$confdir/httpd.conf", '# VirtualHost example:', $cfg_block, '# Almost any Apache directive may go into a VirtualHost container.');
# Error handling:
unless ($ok) {
    $cce->bye('FAIL', "Error while editing $confdir/httpd.conf!");
    exit(1);
}

system("rm -f /etc/httpd/conf/httpd.conf.backup.*");

# Fix GID and permissions one /etc/httpd/alias/ for new mod_nss:
if ( -d "/etc/httpd/alias" ) {
  system('find /etc/httpd/alias -user root -name "*.db" -exec /bin/chgrp apache {} \;');
  system('find /etc/httpd/alias -user root -name "*.db" -exec /bin/chmod g+r {} \;');
}

exit(0);

# 
# Copyright (c) 2015-2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015-2018 Team BlueOnyx, BLUEONYX.IT
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
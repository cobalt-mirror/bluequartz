#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: set_apache_hostname.pl
#
# This constructor sets the ServerAdmin and ServerName in the Apache config of
# both the public Apache and AdmServ. It does NOT restart both services by intent,
# so the changes will only take effect during the next restart of these services.
#
# Depends on:
#		System.hostname
#		System.domainname

my $confdir = '/etc/httpd/conf';
my $aconfdir = '/etc/admserv/conf';

use Sauce::Config;
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

# Fix GID and permissions one /etc/httpd/alias/ for new mod_nss:
if ( -d "/etc/httpd/alias" ) {
	system('find /etc/httpd/alias -user root -name "*.db" -exec /bin/chgrp apache {} \;');
	system('find /etc/httpd/alias -user root -name "*.db" -exec /bin/chmod g+r {} \;');
}

exit(0);

# 
# Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
# 
# Redistribution and use in source and binary forms, with or without modification, 
# are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation and/or 
# other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
# 
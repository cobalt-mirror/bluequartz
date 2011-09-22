#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: set_apache_hostname.pl Fri 12 Jun 2009 12:33:22 PM CEST mstauber $
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
system('find /etc/httpd/alias -user root -name "*.db" -exec /bin/chgrp apache {} \;');
system('find /etc/httpd/alias -user root -name "*.db" -exec /bin/chmod g+r {} \;');

exit(0);

# Set ServerTokens in httpd.conf to reveal very little info (i.e.: just 'Apache'):
#/usr/bin/perl -pi -e 's|^ServerTokens OS|ServerTokens ProductOnly|g' /etc/httpd/conf/httpd.conf

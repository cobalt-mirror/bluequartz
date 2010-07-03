#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: fix_php_conf. Sat Jul 2010 06:42:28 PM CEST mstauber $
#
# This constructor takes a look at /etc/httpd/conf.d/php.conf and trims it down to
# BlueOnyx's requirements. After all we only want PHP enabled on sites where we 
# manually turn it on ... and not everywhere.
#
# Depends on:
#		System.hostname
#		System.domainname

my $confdir = '/etc/httpd/conf.d';
my $aconfdir = '/etc/admserv/conf';

use Sauce::Config;
use FileHandle;
use File::Copy;
use CCE;

my $cce = new CCE;
$cce->connectuds();

umask(0077);
my $stage = "$confdir/php.conf~";
open(HTTPD, "$confdir/php.conf");
unlink($stage);
sysopen(STAGE, $stage, 1|O_CREAT|O_EXCL, 0600) || die;
while(<HTTPD>) {
  s/^AddHandler\s/#AddHandler /g;
  s/^AddType\s/#AddType /g;
  s/^DirectoryIndex\s/#DirectoryIndex /g;
  
  print STAGE;
}
close(STAGE);
close(HTTPD);

chmod(0644, $stage);
if(-s $stage) {
  move($stage,"$confdir/php.conf");
  chmod(0644, "$confdir/php.conf"); # paranoia
  $apache_ok = "1";
} else {
  $apache_ok = "0";
}

if ($apache_ok == "1") {
  $cce->bye("SUCCESS");
}
else {
  $cce->bye("FAILURE");
}

exit(0);


#!/usr/bin/perl -I/usr/sausalito/perl
# Author: Brian N. Smith
# Copyright 2006, NuOnce Networks.  All rights reserved.
# $Id: vsite-add.pl,v 1.0 2006/01/26 10:41:00 Exp $

use CCE;
use Sauce::Util;
use Sauce::Service;

umask(002);

$cce = new CCE;
$cce->connectfd();

$oid = $cce->event_oid();
($ok, $subdomain) = $cce->get($oid);

## Lets search existing Subdomains to verify this is unquie.
@oids = $cce->find('Subdomains', { 'group' => $subdomain->{'group'},  'hostname' => $subdomain->{'hostname'}});
($ok, $sd) = $cce->get($oids[0]);

open(FH, ">/tmp/bsmith");
print FH $subdomain->{'group'};
print FH $subdomain->{'hostname'};
print FH $subdomain->{'isUser'};
print FH $subdomain->{'webpath'};
close(FH);



$size = @oids;

if ( $size > 1 ) {
  ## Duplicate Entry
  $cce->warn('[[base-subdomains.duplicateEntry]]');
  $cce->bye('FAIL');
  exit(1);
}

$subdomain_config_dir = "/etc/httpd/conf/vhosts/subdomains";
if ( ! -e $subdomain_config_dir ) {
  mkdir($subdomain_config_dir, 0755);
  Sauce::Util::chmodfile(02775, "$subdomain_config_dir");
}

$subdomain_config_file = $subdomain_config_dir . "/" . $subdomain->{'group'} . "." . $subdomain->{'hostname'} . ".conf";

@oids = $cce->find('Vsite', { 'name' => $subdomain->{'group'} });
($ok, $vsite) = $cce->get($oids[0]);

$web_dir = $subdomain->{'webpath'};
if ( ! -e $web_dir ) {
  system("/bin/mkdir -p -m 775 $web_dir");
  Sauce::Util::chmodfile(02775, "$web_dir");
  system("/bin/cp -R /etc/skel/vsite/en/web/* $web_dir");
  Sauce::Util::chmodfile(02775, "$web_dir/error");
  Sauce::Util::chmodfile(02775, "$web_dir/index.html");
}

$index_file = $web_dir . "/index.html";
$ipadd = $vsite->{'ipaddr'};
$fqdn = $subdomain->{'hostname'} . "." . $vsite->{'domain'};

$site_config = "NameVirtualHost $ipadd:80
ServerRoot /etc/httpd

<VirtualHost $ipadd:80>
  ServerName  $fqdn
  ServerAdmin admin
  DocumentRoot $web_dir
</VirtualHost>";


open(FO, ">$subdomain_config_file");
print FO $site_config;
close(FO);
Sauce::Util::chmodfile(00544, "$subdomain_config_file");

$index = "";
open(FI, "<$index_file");
while ( <FI> ) {
	chomp;
	s/\[DOMAIN\]/$fqdn/;
	$index .= $_ . "\n";
}
close(FI);

open(FO,">$index_file");
print FO $index;
close(FO);

$tmp = Sauce::Service::service_run_init('httpd', 'reload');

$cce->bye('SUCCESS');
exit(0);


#### OLD Stuff, need to redo.

($php, $cgi, $ssi) = split(//, $subdomain->{'props'});

if ( $php ) {
	$php_config  = "AddType application/x-httpd-php .php\n";
	$php_config .= "AddType application/x-httpd-php .php4\n";
} else { $php_config = ""; }

if ( $cgi ) {
	$cgi_config  = "AddHandler cgi-wrapper .pl\n";
	$cgi_config .= "AddHandler cgi-wrapper .cgi\n";
	$cgi_config .= "ScriptAlias /cgi-bin/ /home/nuonce/cgiwrap/cgiwrap/\n";
	$cgi_config .= "Action cgi-wrapper /cgi-bin\n";
} else { $cgi_config = ""; }

if ( $ssi ) {
	$ssi_config  = "AddHandler server-parsed .shtml\n";
	$ssi_config .= "AddType text/html .shtml\n";
} else { $ssi_config = ""; }




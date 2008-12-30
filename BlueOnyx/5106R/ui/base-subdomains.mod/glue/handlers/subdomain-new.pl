#!/usr/bin/perl -I/usr/sausalito/perl
# Author: Brian N. Smith
# Copyright 2008, NuOnce Networks, Inc.  All rights reserved.
# $Id: subdomain-new.pl, v2.0 2008/12/30 13:17:00 Exp $

use CCE;
use Sauce::Util;
use Switch;

umask(002);

$cce = new CCE;
$cce->connectfd();

$oid = $cce->event_oid();
($ok, $subdomain) = $cce->get($oid);

## Lets search existing Subdomains to verify this is unquie.
@oids = $cce->find('Subdomains', { 'group' => $subdomain->{'group'},  'hostname' => $subdomain->{'hostname'}});
($ok, $sd) = $cce->get($oids[0]);

$size = @oids;

if ( $size > 1 ) {
  ## Duplicate Entry
  $cce->warn('[[base-subdomains.duplicateEntry]]');
  $cce->bye('FAIL');
  exit(1);
}

$master_config = "/etc/httpd/conf.d/subdomains.conf";
if ( ! -e $master_config ) {
  open(OUT, ">$master_config");
  print OUT "Include /etc/httpd/conf.d/subdomains/*.conf";
  close(OUT);
}


$subdomain_config_dir = "/etc/httpd/conf.d/subdomains";
if ( ! -e $subdomain_config_dir ) {
  mkdir($subdomain_config_dir, 0755);
  Sauce::Util::chmodfile(02775, "$subdomain_config_dir");
}

$subdomain_config_file = $subdomain_config_dir . "/" . $subdomain->{'group'} . "." . $subdomain->{'hostname'} . ".conf";

@oids = $cce->find('Vsite', { 'name' => $subdomain->{'group'} });
($ok, $vsite) = $cce->get($oids[0]);
$vsiteOID = $oids[0];

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

my @services = ("PHP", "SSI", "CGI");
foreach $service (@services) {
  ($ok, $$service) = $cce->get($vsiteOID, $service);
  switch ($service) {
    case "PHP" {
      if ( $$service->{'enabled'} ) {
        $serviceCFG .= "  AddType application/x-httpd-php .php\n";
        $serviceCFG .= "  AddType application/x-httpd-php .php4\n";
      }
    }

    case "CGI" {
      if ( $$service->{'enabled'} ) {
        $serviceCFG .= "  AddHandler cgi-wrapper .pl\n";
        $serviceCFG .= "  AddHandler cgi-wrapper .cgi\n";
        $serviceCFG .= "  ScriptAlias /cgi-bin/ /home/nuonce/cgiwrap/cgiwrap/\n";
        $serviceCFG .= "  Action cgi-wrapper /cgi-bin\n";
      }
    }

    case "SSI" {
      if ( $$service->{'enabled'} ) {
        $serviceCFG .= "  AddHandler server-parsed .shtml\n";
        $serviceCFG .= "  AddType text/html .shtml\n";
      }
    }
  }
}

$site_config = "NameVirtualHost $ipadd:80
ServerRoot /etc/httpd

<VirtualHost $ipadd:80>
  ServerName  $fqdn
  ServerAdmin admin
  DocumentRoot $web_dir
  # BEGIN WebScripting SECTION.  DO NOT EDIT MARKS OR IN BETWEEN.
$serviceCFG
  # END WebScripting SECTION.  DO NOT EDIT MARKS OR IN BETWEEN.
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

$cce->bye('SUCCESS');
exit(0);

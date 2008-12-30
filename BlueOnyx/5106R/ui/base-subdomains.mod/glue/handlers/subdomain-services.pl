#!/usr/bin/perl -I/usr/sausalito/perl
# Author: Brian N. Smith
# Copyright 2008, NuOnce Networks, Inc.  All rights reserved.
# $Id: subdomain-services.pl, v2.0 2008/12/30 13:14:00 Exp $

use CCE;
use Switch;
use Sauce::Util;

my $cce = new CCE;
$cce->connectfd();

my $vsite = $cce->event_object();

$serviceCFG = "  ## Web Services\n";

my @services = ("PHP", "SSI", "CGI");
foreach $service (@services) {
  ($ok, $$service) = $cce->get($cce->event_oid(), $service);
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

$subdomain_config_dir = "/etc/httpd/conf.d/subdomains";
@oids = $cce->find('Subdomains', { 'group' => $vsite->{'name'} });

$open  = "  # BEGIN WebScripting SECTION.  DO NOT EDIT MARKS OR IN BETWEEN.";
$close = "  # END WebScripting SECTION.  DO NOT EDIT MARKS OR IN BETWEEN.";

foreach $oid (@oids) {
  my ($ok, $subdomain) = $cce->get($oid);
  $config_file = $subdomain_config_dir . "/" . $subdomain->{'group'} . "." . $subdomain->{'hostname'} . ".conf";
  Sauce::Util::replaceblock($config_file, $open, $serviceCFG, $close);
}

$cce->bye('SUCCESS');
exit(0);

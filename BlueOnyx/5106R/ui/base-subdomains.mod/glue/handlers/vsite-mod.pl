#!/usr/bin/perl -I/usr/sausalito/perl
# Author: Brian N. Smith
# Copyright 2006, NuOnce Networks.  All rights reserved.
# $Id: vsite-mod.pl,v 1.0 2006/01/26 10:41:00 Exp $

use CCE;
use Sauce::Util;
use Sauce::Service;

umask(002);

$cce = new CCE;
$cce->connectfd();

$oid = $cce->event_oid();
$obj = $cce->event_object();

($ok, $vsite) = $cce->get($oid);

$name = $vsite->{'name'};
@oids = $cce->find('Vsite', { 'name' => $name });
($ok, $subdomains) = $cce->get($oids[0], "subdomains");

open(FO, ">/tmp/hehehe");
print FO $subdomains->{'props'};
close(FO);

$base_dir = $vsite->{'basedir'} . "/vsites";
$vsite_dir = $base_dir . "/" . $subdomains->{'hostname'};
$web_dir = $vsite_dir . "/web";

$site_config_file = $vsite_dir . "/.web.config";
$extension_config_file = $vsite_dir . "/.site.config";

$ipadd = $vsite->{'ipaddr'};
$fqdn = $subdomains->{'hostname'} . "." . $vsite->{'domain'};

($php, $cgi, $ssi) = split(//, $subdomains->{'props'});

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


$site_config = "NameVirtualHost $ipadd:80
ServerRoot /etc/httpd
<VirtualHost $ipadd:80>
ServerName  $fqdn
ServerAdmin admin
DocumentRoot $web_dir
ErrorDocument 401 /error/401-authorization.html
ErrorDocument 403 /error/403-forbidden.html
ErrorDocument 404 /error/404-file-not-found.html
ErrorDocument 500 /error/500-internal-server-error.html
$php_config
$cgi_config
$ssi_config
</VirtualHost>";

open(FO, ">$site_config_file");
print FO $site_config;
close(FO);
Sauce::Util::chmodfile(00544, "$site_config_file");

open(FO, ">$extension_config_file");
print FO $subdomains->{'props'};
close(FO);
Sauce::Util::chmodfile(00544, "$extension_config_file");

$tmp = Sauce::Service::service_run_init('httpd', 'reload');

$cce->bye('SUCCESS');
exit(0);

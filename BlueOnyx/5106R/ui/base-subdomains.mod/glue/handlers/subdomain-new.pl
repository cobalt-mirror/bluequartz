#!/usr/bin/perl -I/usr/sausalito/perl
# Author: Brian N. Smith
# Copyright 2008, NuOnce Networks, Inc.  All rights reserved.
# Copyright 2010, Team BlueOnyx. All rights reserved.
# $Id: subdomain-new.pl, v2.0 2008/12/30 13:17:00 Exp $

use CCE;
use Sauce::Util;
use Switch;
use Sauce::Service;

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

##

	    # Get Object PHP from CODB to find out which PHP version we use:
	    @sysoids = $cce->find('PHP');
	    ($ok, $mySystem) = $cce->get($sysoids[0]);
	    $platform = $mySystem->{'PHP_version'};
	    if ($platform >= "5.3") {
    		# More modern PHP found:
    	    $legacy_php = "0";
	    }
	    else {
    		# Older PHP found:
    		$legacy_php = "1";
	    }

	    # Get PHP:
	    $vgroup = $subdomain->{'group'};
	    @vsiteoid = $cce->find('Vsite', { 'name' => $vgroup });
	    ($ok, $vsite_php) = $cce->get($vsiteoid[0], "PHP");
    
	    # Get PHPVsite:
	    ($ok, $vsite_php_settings) = $cce->get($vsiteoid[0], "PHPVsite");

	    $serviceCFG .= "# created by subdomain-new.pl\n";

            if ( $$service->{'suPHP_enabled'} ) {
                $serviceCFG .= "  suPHP_Engine on\n";
                $serviceCFG .= "  suPHP_ConfigPath $web_dir\n";
                $serviceCFG .= "  suPHP_AddHandler x-httpd-suphp\n";
                $serviceCFG .= "  AddHandler x-httpd-suphp .php\n";
            }
            else {
                $serviceCFG .= "  AddType application/x-httpd-php .php\n";
                $serviceCFG .= "  AddType application/x-httpd-php .php4\n";
                $serviceCFG .= "  AddType application/x-httpd-php .php5\n";
            }

    	    if ($legacy_php == "1") {
                # These options only apply to PHP versions prior to PHP-5.3:
                if ($vsite_php_settings->{"safe_mode"} ne "") {
                    $serviceCFG .= 'php_admin_flag safe_mode ' . $vsite_php_settings->{"safe_mode"} . "\n";
                }
                if ($vsite_php_settings->{"safe_mode_gid"} ne "") {
                    $serviceCFG .= 'php_admin_flag safe_mode_gid ' . $vsite_php_settings->{"safe_mode_gid"} . "\n";
                }
                if ($vsite_php_settings->{"safe_mode_allowed_env_vars"} ne "") {
                    $serviceCFG .= 'php_admin_value safe_mode_allowed_env_vars ' . $vsite_php_settings->{"safe_mode_allowed_env_vars"} . "\n";
                }
                if ($vsite_php_settings->{"safe_mode_exec_dir"} ne "") {
                    $serviceCFG .= 'php_admin_value safe_mode_exec_dir ' . $vsite_php_settings->{"safe_mode_exec_dir"} . "\n";
                }
                if ($vsite_php_settings->{"safe_mode_include_dir"} ne "") {
                    $serviceCFG .= 'php_admin_value safe_mode_include_dir ' . $vsite_php_settings->{"safe_mode_include_dir"} . "\n";
                }
                if ($vsite_php_settings->{"safe_mode_protected_env_vars"} ne "") {
                    $serviceCFG .= 'php_admin_value safe_mode_protected_env_vars ' . $vsite_php_settings->{"safe_mode_protected_env_vars"} . "\n";
                }
            }
            if ($vsite_php_settings->{"register_globals"} ne "") {
                $serviceCFG .= 'php_admin_flag register_globals ' . $vsite_php_settings->{"register_globals"} . "\n";
            }
            if ($vsite_php_settings->{"allow_url_fopen"} ne "") {
                $serviceCFG .= 'php_admin_flag allow_url_fopen ' . $vsite_php_settings->{"allow_url_fopen"} . "\n";
            }
            if ($vsite_php_settings->{"allow_url_include"} ne "") {
                $serviceCFG .= 'php_admin_flag allow_url_include ' . $vsite_php_settings->{"allow_url_include"} . "\n";
            }

            # Some BX users apparently want open_basedir to be empty. Security wise this is a bad idea,
            # but if they really want to let their pants down that far ... <sigh>. New provision to check
            # if 'open_basedir' is empty in the GUI. We act a bit later on based on this switch:
            $empty_open_basedir = "0";
            if ($vsite_php_settings->{"open_basedir"} eq "") {
                $empty_open_basedir = "1";
            }

            # We need to remove any site path references from open_basedir, because they could be from the wrong site,
            # like during a cmuImport, when it inherited the path it had on the server it was exported from.

            @vsite_php_settings_temp = split(":", $vsite_php_settings->{"open_basedir"});
            foreach $entry (@vsite_php_settings_temp) {
                #system("echo $entry >> /tmp/debug.ms");
                $entry =~ s/\/home\/.sites\/(.*)\/(.*)\///;
                if ($entry ne "") {
                    push(@vsite_php_settings_new, $entry);
                }
            }
            if ($vsite_php_settings->{"open_basedir"} ne "") {
                $vsite_php_settings->{"open_basedir"} = join(":", @vsite_php_settings_new);
            }
            # Decision if we write 'open_basedir' to the site include file or not. We do NOT
            # write an empty open_basedir. So if it is empty, we simply skip this step:
            if ($empty_open_basedir != "1") {
                # Decide if we need to add the sites homedir to open_basedir or not:
                if ($vsite_php_settings->{"open_basedir"} =~ m/$vsite->{"basedir"}\//) {
                    # If the site's basedir path is already present, we use whatever paths open_basedir currently has:
                    $serviceCFG .= 'php_admin_value open_basedir ' . $vsite_php_settings->{"open_basedir"} . "\n";
                }
                else {
                    # If the sites path to it's homedir is missing, we add it here:
                    $serviceCFG .= 'php_admin_value open_basedir ' . $vsite_php_settings->{"open_basedir"} . ':' . $vsite->{"basedir"} . '/' . "\n";
                }
            }

            if ($vsite_php_settings->{"post_max_size"} ne "") {
                $serviceCFG .= 'php_admin_value post_max_size ' . $vsite_php_settings->{"post_max_size"} . "\n";
            }
            if ($vsite_php_settings->{"upload_max_filesize"} ne "") {
                $serviceCFG .= 'php_admin_value upload_max_filesize ' . $vsite_php_settings->{"upload_max_filesize"} . "\n";
            }
            if ($vsite_php_settings->{"max_execution_time"} ne "") {
                $serviceCFG .= 'php_admin_value max_execution_time ' . $vsite_php_settings->{"max_execution_time"} . "\n";
            }
            if ($vsite_php_settings->{"max_input_time"} ne "") {
                $serviceCFG .= 'php_admin_value max_input_time ' . $vsite_php_settings->{"max_input_time"} . "\n";
            }
            if ($vsite_php_settings->{"memory_limit"} ne "") {
                $serviceCFG .= 'php_admin_value memory_limit ' . $vsite_php_settings->{"memory_limit"} . "\n";
            }
##
      }
    }

    case "CGI" {
      if ( $$service->{'enabled'} ) {
        $serviceCFG .= "  AddHandler cgi-wrapper .pl\n";
        $serviceCFG .= "  AddHandler cgi-wrapper .cgi\n";
        $serviceCFG .= "  ScriptAlias /cgi-bin/ /usr/local/blueonyx/cgiwrap/cgiwrap/\n";
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

service_run_init('httpd', 'reload');

$cce->bye('SUCCESS');
exit(0);

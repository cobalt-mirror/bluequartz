#!/usr/bin/perl -I/usr/sausalito/perl
# Author: Brian N. Smith
# Copyright 2008, NuOnce Networks, Inc.  All rights reserved.
# Copyright 2010, Team BlueOnyx. All rights reserved.
# $Id: subdomain-services.pl, v2.0 2008/12/30 13:14:00 Exp $

use CCE;
use Switch;
use Sauce::Util;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $cce = new CCE;
$cce->connectfd();

my $vsite = $cce->event_object();

&debug_msg("Vsite1: $vsite->{'fqdn'} \n");

$serviceCFG = "  ## Web Services\n";
if (!$subdomain->{'group'}) {
    #$subdomain->{'group'} = $vsite->{'name'};
    @oids = $cce->find('Subdomains', { 'group' => $vsite->{'name'} });
    ($ok, $subdomain) = $cce->get($oids[0]);
}
&debug_msg("Group: $subdomain->{'group'} \n");
@oids = $cce->find('Vsite', { 'name' => $subdomain->{'group'} });
($ok, $vsite) = $cce->get($oids[0]);
&debug_msg("Vsite2: $vsite->{'fqdn'} \n");

$vsiteOID = $oids[0];
$web_dir = $subdomain->{'webpath'};

my @services = ("PHP", "SSI", "CGI");
foreach $service (@services) {
  ($ok, $$service) = $cce->get($cce->event_oid(), $service);
  switch ($service) {
    case "PHP" {
      if (( $$service->{'enabled'} ) || ( $$service->{'force_update'} )) {
	    &debug_msg("Case: PHP \n");

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
	    &debug_msg("1: vsiteoid[0]: $vsiteoid[0] \n");

            # Get PHPVsite:
            ($ok, $vsite_php_settings) = $cce->get($vsiteoid[0], "PHPVsite");

	    &debug_msg("2: vsiteoid[0]: $vsiteoid[0] \n");

            $serviceCFG .= "# created by subdomain-services.pl\n";

	    &debug_msg("suPHP_enabled: $vsite_php->{'suPHP_enabled'} \n");

            if ($vsite_php->{'suPHP_enabled'} == "1") {
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

            # Making sure 'safe_mode_include_dir' has the bare minimum defaults:
            @smi_temporary = split(":", $vsite_php_settings->{"safe_mode_include_dir"});
            @smi_baremetal_minimums = ('/usr/sausalito/configs/php/', '.');
            @smi_temp_joined = (@smi_temporary, @smi_baremetal_minimums);
        
            # Remove duplicates:
            foreach my $var ( @smi_temp_joined ){
                if ( ! grep( /$var/, @safe_mode_include_dir ) ){
                    push(@safe_mode_include_dir, $var );
                }
            }
            $vsite_php_settings->{"safe_mode_include_dir"} = join(":", @safe_mode_include_dir);
 
            # Making sure 'safe_mode_allowed_env_vars' has the bare minimum defaults:
            @smaev_temporary = split(",", $vsite_php_settings->{"safe_mode_allowed_env_vars"});
            @smi_baremetal_minimums = ('PHP_','_HTTP_HOST','_SCRIPT_NAME','_SCRIPT_FILENAME','_DOCUMENT_ROOT','_REMOTE_ADDR','_SOWNER');
            @smaev_temp_joined = (@smaev_temporary, @smi_baremetal_minimums);

            # Remove duplicates:
            foreach my $var ( @smaev_temp_joined ){
                if ( ! grep( /$var/, @safe_mode_allowed_env_vars ) ){
                    push(@safe_mode_allowed_env_vars, $var );
                }
            }
            $vsite_php_settings->{"safe_mode_allowed_env_vars"} = join(",", @safe_mode_allowed_env_vars);

            # Make sure that the path to the prepend file directory is allowed, too:
            unless ($vsite_php_settings->{"open_basedir"} =~ m/\/usr\/sausalito\/configs\/php\//) {
                $vsite_php_settings->{"open_basedir"} .= $vsite_php_settings->{"open_basedir"} . ':/usr/sausalito/configs/php/';
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

            # Email related:
            $serviceCFG .= 'php_admin_flag mail.add_x_header On' . "\n";
            $serviceCFG .= 'php_admin_value sendmail_path /usr/sausalito/sbin/phpsendmail' . "\n";
            $serviceCFG .= 'php_admin_value auto_prepend_file /usr/sausalito/configs/php/set_php_headers.php' . "\n";

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

$subdomain_config_dir = "/etc/httpd/conf.d/subdomains";
@oids = $cce->find('Subdomains', { 'group' => $vsite->{'name'} });

&debug_msg("Debug-OID: $oids[0] \n");

$open  = "  # BEGIN WebScripting SECTION.  DO NOT EDIT MARKS OR IN BETWEEN.";
$close = "  # END WebScripting SECTION.  DO NOT EDIT MARKS OR IN BETWEEN.";

foreach $oid (@oids) {
  my ($ok, $subdomain) = $cce->get($oid);
  $config_file = $subdomain_config_dir . "/" . $subdomain->{'group'} . "." . $subdomain->{'hostname'} . ".conf";
  &debug_msg("Editing $config_file \n");
  Sauce::Util::replaceblock($config_file, $open, $serviceCFG, $close);
}

sub debug_msg {
    if ($DEBUG) {
        my $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
}

$cce->bye('SUCCESS');
exit(0);

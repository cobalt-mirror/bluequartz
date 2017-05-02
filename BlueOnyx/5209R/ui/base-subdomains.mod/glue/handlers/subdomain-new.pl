#!/usr/bin/perl -I/usr/sausalito/perl
# Initial Author: Brian N. Smith
# $Id: subdomain-new.pl

use CCE;
use Sauce::Util;
use Switch;
use Sauce::Service;

umask(002);

$cce = new CCE;
$cce->connectfd();

$oid = $cce->event_oid();
($ok, $subdomain) = $cce->get($oid);

($soid) = $cce->find('System');
($ok, $obj) = $cce->get($soid);

# Get "System" . "Web":
($ok, $objWeb) = $cce->get($soid, 'Web');

# HTTP and SSL ports:
$httpPort = "80";
if ($objWeb->{'httpPort'}) {
    $httpPort = $objWeb->{'httpPort'};
}
$sslPort = "443";
if ($objWeb->{'sslPort'}) {
    $sslPort = $objWeb->{'sslPort'};
}

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
  print OUT "IncludeOptional /etc/httpd/conf.d/subdomains/*.conf";
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

        # Get prefered_siteAdmin for ownerships:
        ($ok, $Vsite) = $cce->get($vsiteoid[0], '');
        if ($vsite_php->{prefered_siteAdmin} ne "") {
            $prefered_siteAdmin = $vsite_php->{prefered_siteAdmin};
        }
        else {
            $prefered_siteAdmin = 'apache';
        }

        if ( $$service->{'suPHP_enabled'} ) {
            # Handle suPHP:
            $serviceCFG .= "#<IfModule mod_suphp.c>\n";
            $serviceCFG .= "    suPHP_Engine on\n";
            $serviceCFG .= "    suPHP_UserGroup $prefered_siteAdmin $Vsite->{name}\n";
            $serviceCFG .= "    AddType application/x-httpd-suphp .php\n";
            $serviceCFG .= "    AddHandler x-httpd-suphp .php .php5 .php4 .php3 .phtml\n";
            $serviceCFG .= "    suPHP_AddHandler x-httpd-suphp\n";
            $serviceCFG .= "    suPHP_ConfigPath $Vsite->{'basedir'}/\n";
            $serviceCFG .= "#</IfModule>\n";
        }
        # Handle mod_ruid2:
        elsif ($$service->{mod_ruid_enabled}) {
            $serviceCFG .= "<FilesMatch \\.php\$>\n";
            $serviceCFG .= "    SetHandler application/x-httpd-php\n";
            $serviceCFG .= "</FilesMatch>\n";
            $serviceCFG .= "<IfModule mod_ruid2.c>\n";
            $serviceCFG .= "     RMode config\n";
            $serviceCFG .= "     RUidGid $prefered_siteAdmin $Vsite->{name}\n";
            $serviceCFG .= "</IfModule>\n";
        }
        # Handle FPM/FastCGI:
        elsif ($$service->{fpm_enabled}) {
            # Assign a port number based on the GID of the Vsite.
            # Our GID's for siteX start at 1001, so we need to add
            # 8000 to it to get above 9000.
            ($_name, $_passwd, $gid, $_members) = getgrnam($Vsite->{name});
            if ($gid ne "") {
                $fpmPort = $gid+8000;
            }
            else {
                # Fallback if we didn't get a port:
                $fpmPort = 9000;
            }
            # Double fallback:
            if ($fpmPort < 9000) {
                $fpmPort = 9000;
            }
            $serviceCFG .= "ProxyPassMatch ^/(.*\\.php(/.*)?)\$ fcgi://127.0.0.1:$fpmPort$web_dir\n";
        }
        # Handle 'regular' PHP via DSO:
        else { 
            $serviceCFG .= "<FilesMatch \\.php\$>\n";
            $serviceCFG .= "    SetHandler application/x-httpd-php\n";
            $serviceCFG .= "</FilesMatch>\n";
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
        if (-f "/usr/bin/systemctl") {
            # 5209R and therefore Apache 2.4:
            $serviceCFG .= "  <Directory $web_dir>\n";
            $serviceCFG .= "      AddHandler cgi-script .cgi .pl\n";
            $serviceCFG .= "      Options +ExecCGI\n";
            $serviceCFG .= "  </Directory>\n";
        }
        else {
            $serviceCFG .= "  AddHandler cgi-wrapper .pl\n";
            $serviceCFG .= "  AddHandler cgi-wrapper .cgi\n";
            $serviceCFG .= "  ScriptAlias /cgi-bin/ /usr/local/blueonyx/cgiwrap/cgiwrap/\n";
            $serviceCFG .= "  Action cgi-wrapper /cgi-bin\n";
        }
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

$site_config = "#NameVirtualHost $ipadd:$httpPort
ServerRoot /etc/httpd

<VirtualHost $ipadd:$httpPort>
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

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2008 NuOnce Networks, Inc.
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#     notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#     notice, this list of conditions and the following disclaimer in 
#     the documentation and/or other materials provided with the 
#     distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#     contributors may be used to endorse or promote products derived 
#     from this software without specific prior written permission.
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
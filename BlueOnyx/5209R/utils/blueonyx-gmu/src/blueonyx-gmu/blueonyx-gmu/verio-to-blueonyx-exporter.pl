#!/usr/bin/perl -I./lib/

# Debugging switch (0|1|2):
# 0 = off
# 1 = log to syslog
# 2 = log to screen
#
$DEBUG = "2";
if ($DEBUG) {
    if ($DEBUG eq "1") {
        use Sys::Syslog qw( :DEFAULT setlogsock);
    }
}

#
### Load required Perl modules:
#

use XML::Simple;
use Data::Dumper;
use Apache::ConfigFile;
use List::Flatten;
use Unix::PasswdFile;
use Unix::GroupFile;
use Mail::Sendmail;
use Fcntl qw( :flock );
use DB_File;
use List::MoreUtils qw(uniq);
use Quota;
use POSIX qw/strftime/;
use Getopt::Std;

$owndir = `pwd`;
chomp($owndir);

#
### Check if we are 'root':
#
&root_check;

#
### Path setup:
#

# Output directory (i.e.: Where to store the export data):
$export_dir = "$owndir/data/";

# Config Dir (i.e.: where to store sanitized configs):
$own_config_dir = "$owndir/configs/";

# XML output file:
$xml_out = $export_dir . 'BlueOnyx-Migrate.xml';

# Panel Config file: 
$panel_config = '/usr/local/etc/cpx.conf';

# Apache Config file:
$apache_config = '/www/conf/httpd.conf';

# Sendmail Config files:
$alias_file = '/etc/mail/aliases.db';
$virt_file = '/etc/mail/virtusertable.db';

# Default Disk Quota for auto-generated Users (in MB)*1024 to get the num of blocks:
$user_def_quota = '50'*1024;

# Default Vsite disk quotas for Vsites that have no Reseller and no siteAdmin:
$vsite_def_quota = '1024'*1024;

#
### Command line option handling
#

%options = ();
getopts("hcd:s:", \%options);

# Handle display of help text:
if ($options{h}) {
    &help;
}

# SCP target:
$scp_files_directly = "0";
if ($options{s}) {
    $scp_files_directly = '1';
    $scp_target = $options{s};
}

# Import the configurations of Vsites and User only, but don't import Tarballs:
$config_only = "0";
if ($options{c}) {
    $config_only = "1";
}

# Import directory:
if ($options{d}) {
    $path_to_export = $options{d} . "/";
    $path_to_export =~ s#//#/#;
    # Output directory (i.e.: Where to store the export data):
    $export_dir = "$path_to_export/data/";
    $export_dir =~ s#//#/#;
    # Config Dir (i.e.: where to store sanitized configs):
    $own_config_dir = "$path_to_export/configs/";
    $own_config_dir =~ s#//#/#;
    # XML output file:
    $xml_out = $export_dir . 'BlueOnyx-Migrate.xml';
    $xml_out =~ s#//#/#;
}
else {
    $path_to_export = `pwd`;
    chomp($path_to_export);
    $path_to_export .= "/";
    $path_to_export =~ s#//#/#;
    # Output directory (i.e.: Where to store the export data):
    $export_dir = "$path_to_export/data/";
    $export_dir =~ s#//#/#;
    # Config Dir (i.e.: where to store sanitized configs):
    $own_config_dir = "$path_to_export/configs/";
    $own_config_dir =~ s#//#/#;
    # XML output file:
    $xml_out = $export_dir . 'BlueOnyx-Migrate.xml';
    $xml_out =~ s#//#/#;
}

#
### Special work around for the old CentOS 4 boxes:
#

unless (-d $export_dir) {
    system("mkdir -p $export_dir");
}
unless (-d $own_config_dir) {
    system("mkdir -p $own_config_dir");
}

$is_centos = '0';
if (-f '/etc/redhat-release') {
    $is_centos = `cat /etc/redhat-release |grep "^CentOS release"|wc -l`;
    chomp($is_centos)
}

if ($is_centos eq "1") {
    # Copy httpd.conf:
    system("cp $apache_config $own_config_dir");
    # Comment out the include for the conf.d directory:
    system("sed -i -e 's/^Include conf.d/#Include conf.d/' $own_config_dir/httpd.conf");
    # Point $apache_config to the sanitized config:
    $apache_config = $own_config_dir . '/httpd.conf';
    unless (-f $alias_file) {
        if (-f '/etc/aliases.db') {
            $alias_file = '/etc/aliases.db';
        }
        else {
            &debug_msg("Error: Unable to find aliases.db! Tried /etc/aliases.db and /etc/aliases.db without luck.\n");
        }
    }
}

#
### Path to correct 'pv' binary:
#

$BSD = `uname -a|grep FreeBSD|wc -l`;
chomp($BSD);
$BSD =~ s/\s+//;
$BSD =~ s/\t+//;
$ARCH = `uname -m`;
chomp($ARCH);
$ARCH =~ s/\s+//;

if ($BSD eq "1") {
    $pv_bin = $owndir. "/lib/pv_bsd";
    $md5_binary = 'md5';
}
elsif ($ARCH eq "i386") {
    $pv_bin = $owndir. "/lib/pv";
}
elsif ($ARCH eq "i686") {
    $pv_bin = $owndir. "/lib/pv";
}
elsif ($ARCH eq "x86_64") {
    $pv_bin = $owndir. "/lib/pv64";
}
else {
    print "ERROR: Cannot find a suitable 'pv' binary.\n";
    exit;
}
if ($BSD eq "0") {
    $md5_binary = 'md5sum';
}

#
### Illegal Usernames that are not allowed for Users on BlueOnyx:
#

%illegal_usernames = map { $_ => 1 } qw /
    root bin daemon adm lp sync shutdown halt mail news uucp operator
    games gopher ftp nobody dbus rpm htt nscd vcsa ntp wnn ident canna
    haldaemon rpc named amanda sshd postgres pvm netdump pcap radvd
    quagga mailnull smmsp pegasus apache mailman webalizer xfs cyrus
    radiusd ldap exim mysql fax squid dovecot postfixgdm
    pop alterroot httpd chiliasp qmail share-guest majordomo
    anonymous guest Root Admin ROOT ADMIN admin
/;

#
### Start:
#

&header;

@KnownDomainNames = ();

&debug_msg("################################## \n");
&debug_msg("BlueOnyx Exporter starting up ... \n");
&debug_msg("################################## \n\n");

# Remove old tarballs (if present):
system("rm -Rf $export_dir" . "*.tar.gz");

if (!-f $apache_config) {
    &debug_msg("################################## \n");
    &debug_msg("ERROR: $apache_config not found! \n");
    &debug_msg("################################## \n\n");    
    exit(1);
}

# Get the Apache config file:
$apache = Apache::ConfigFile->read($apache_config);
$vhost_server_name = $apache->cmd_config('ServerName');

if ($vhost_server_name eq "") {
    &debug_msg("################################## \n");
    &debug_msg("ERROR: Unable to parse Apache config! \n");
    &debug_msg("################################## \n\n");
    exit(1);
}
&debug_msg("This Server Name: $vhost_server_name \n\n");

# Hash the Apache Config:
%ApacheConfig = $apache->data;

&debug_msg("################################## \n");
&debug_msg("Parsing Apache config file: \n");
&debug_msg("################################## \n\n");

#
### Setup Vsite Object for ServerName:
#

if ($vhost_server_name ne "") {

    $Vsite->{$vhost_server_name} = {
        'ipaddr' => '',
        'fqdn' => 'www.' . $vhost_server_name,
        'hostname' => 'www',
        'domain' => $vhost_server_name,
        'webAliases' => '',
        'mailAliases' => array_to_scalar($fqdn, "www.$vhost_server_name"),
        'createdUser' => 'admin',
        'maxusers' => '5000',
        'mailCatchAll' => '',
        'webAliasRedirects' => '1',
        'dns_auto' => '0'
        };

    $Vsite->{$fqdn}->{PHP} = {
        'prefered_siteAdmin' => 'apache',
        'suPHP_enabled' => '0',
        'mod_ruid_enabled' => '1',
        'fpm_enabled' => '0',
        'version' => "PHPOS",
        'enabled' => '1'
        };

    $Vsite->{$fqdn}->{CGI} = {
        'enabled' => '1'
        };

    $Vsite->{$fqdn}->{SSL} = {
        'enabled' => '0'
        };

    $Vsite->{$fqdn}->{USERWEBS} = {
        'enabled' => '0'
        };
}

# Remove any keyless entry:
delete($Vsite->{''});

# Get NameVirtualHosts
while ($ip_port = $apache->cmd_config('NameVirtualHost')) {
    @NVH = ();
    @ip_raw = ();
    push @NVH, $ip_port;
    @ip_raw = split(':', $ip_port);
    $ip = $ip_raw[0];
    $port = $ip_raw[1];

    if ($port eq "80") {
        $SSL = 0;
    }
    if ($port eq "443") {
        $SSL = 1;
    }

    # Set ip of primary Vsite:
    if ($vhost_server_name ne "") {
        if ($Vsite->{$vhost_server_name}->{ipaddr} eq "") {
            $Vsite->{$vhost_server_name}->{ipaddr} = $ip;
        }
    }

    # Walk through all VirtualHosts that Apache knows about:
    for $vh ($apache->cmd_context(VirtualHost => $ip_port)) {
        $fqdn = $vh->cmd_config('ServerName');
        $vhost_doc_root = $vh->cmd_config('DocumentRoot');

        # Get path to SSL certificate files:
        if ($SSL eq "1") {
            $SSL_cert = $vh->cmd_config('SSLCertificateFile');
            $SSL_key = $vh->cmd_config('SSLCertificateKeyFile');
            $SSL_intermediate = $vh->cmd_config('SSLCACertificateFile');
            if ((-f $SSL_cert) && (-f $SSL_key)) {
                $Vsite_Extra->{$fqdn}->{SSL} = {
                        'SSLCertificateFile' =>  $SSL_cert,
                        'SSLCertificateKeyFile' =>  $SSL_key,
                        'SSLCACertificateFile' =>  $SSL_intermediate
                    };
            }
        }
        else {
            $SSL_cert = '';
            $SSL_key = '';
            $SSL_intermediate = '';
        }

        # Store path to CGI-Bins:
        $ScriptAlias = $vh->cmd_config_hash('ScriptAlias');
        $Vsite_Extra->{$fqdn}->{CGI_BIN} = $ScriptAlias->{'/cgi-bin/'}[0];

        #if (($fqdn && $vhost_doc_root) && ($fqdn ne $vhost_server_name)) {
        if ($fqdn && $vhost_doc_root) {
            &debug_msg("$fqdn - $ip(:$port) - $vhost_doc_root \n");

            # Store document root:
            $Vsite_Extra->{$fqdn}->{DocumentRoot} = $vhost_doc_root;

            # Take note that we have seen this FQDN:
            unless (in_array(\@KnownDomainNames)) {
                push @KnownDomainNames, $fqdn;
            }

            # Get webAliases:
            @ServerAlias = $vh->cmd_config_hash('ServerAlias');
            @nwServerAlias = uniq(flat @ServerAlias);

            # Stupid thing: The flat command above might have added some 'undef' values.
            # We need to get rid of them:
            @newServerAlias_cleaned = ();
            foreach $x (@nwServerAlias) {
                if ($x eq "www.$fqdn") {
                    next;
                }
                if (length($x) gt "0") {
                    push @newServerAlias_cleaned, $x;
                }
            }

            # Add $fqdn to aliases:
            if (!in_array(\@newServerAlias_cleaned, $fqdn)) {
                push @newServerAlias_cleaned, $fqdn;
            }

            # Turn the alias-array into a scalar:
            $webAliases = array_to_scalar(@newServerAlias_cleaned);

            # Push webAliases to KnownDomainNames:
            foreach $t (@newServerAlias_cleaned) {
                push @KnownDomainNames, $t;
            }

            # Add www.$fqdn again:
            push @KnownDomainNames, "www.$fqdn";

            # Remove duplicates:
            @KnownDomainNames = uniq(@KnownDomainNames);

            # Get Web-Owner:
            $SuexecUserGroup = $vh->cmd_config('SuexecUserGroup');

            # Fallback:
            if ($SuexecUserGroup eq "") {
                $SuexecUserGroup = "apache";
            }

            # Keep track of who owns what:
            if ($SuexecUserGroup) {
                @known_reseller_domains = scalar_to_array($DomainOwners->{$SuexecUserGroup});
                if (!in_array(\@known_reseller_domains, $fqdn)) {
                    push @known_reseller_domains, $fqdn;
                    $DomainOwners->{$SuexecUserGroup} = array_to_scalar(@known_reseller_domains);
                    $Resellers->{$SuexecUserGroup}->{newName} = $SuexecUserGroup . "_admin";
                    $Resellers->{$SuexecUserGroup}->{domains} = array_to_scalar(@known_reseller_domains);
                    $Resellers->{$SuexecUserGroup}->{vsite_quota} = '0';
                }
                else {
                    $DomainOwners->{$SuexecUserGroup} = array_to_scalar(($fqdn));
                }
            }

            # Set to $SuexecUserGroup - we correct this later:
            $prefered_siteAdmin = $SuexecUserGroup;

            #
            ### Assemble Vsite Object:
            #

            $Vsite->{$fqdn} = {
                'ipaddr' => $ip,
                'fqdn' => 'www.' . $fqdn,
                'hostname' => 'www',
                'domain' => $fqdn,
                'webAliases' => $webAliases,
                'mailAliases' => array_to_scalar($fqdn, "www.$fqdn"),
                'createdUser' => 'admin',
                'maxusers' => '5000',
                'mailCatchAll' => '',
                'webAliasRedirects' => '1',
                'dns_auto' => '0'
                };

            $Vsite->{$fqdn}->{PHP} = {
                'prefered_siteAdmin' => $prefered_siteAdmin,
                'suPHP_enabled' => '0',
                'mod_ruid_enabled' => '1',
                'fpm_enabled' => '0',
                'version' => "PHPOS",
                'enabled' => '1'
                };

            $Vsite->{$fqdn}->{CGI} = {
                'enabled' => '1'
                };

            $Vsite->{$fqdn}->{SSL} = {
                'enabled' => $SSL
                };

            $Vsite->{$fqdn}->{USERWEBS} = {
                'enabled' => '0'
                };
        }

        ## Update createdUser if we have a reseller:
        if ($SuexecUserGroup) {
            @known_reseller_domains = scalar_to_array($Resellers->{$SuexecUserGroup}->{domains});
            foreach $x (@known_reseller_domains) {
                $Vsite->{$x}->{createdUser} = $SuexecUserGroup . "_admin";
            }
        }
    } 
}

#
### Cleanup Resellers to elininate all who only own one Vsite:
#

&debug_msg("\n################################## \n");
&debug_msg("Identifying Resellers ... \n");
&debug_msg("################################## \n\n");
foreach $u ( keys %{ $Resellers } ) {
    @known_reseller_domains = scalar_to_array($Resellers->{$u}->{domains});
    $num = scalar(@known_reseller_domains);
    if ($num == "1") {
        delete $Resellers->{$u};
        &debug_msg("User $u will NOT be treated as reseller as he only handles one Vsite. \n");
    }
    else {
        &debug_msg("User $u is treated as reseller as he handles $num Vsites. \n");
    }
}

&debug_msg("\n");

#
### Parsing Panel Config:
#

if (!-f $panel_config) {
    &debug_msg("################################## \n");
    &debug_msg("ERROR: $panel_config not found! \n");
    &debug_msg("################################## \n\n");    
    exit(1);
}

# Create XML object from panel_config:
$xml = new XML::Simple;

# Read XML file:
$data = $xml->XMLin($panel_config);

# Print output:
&debug_msg("\n################################## \n");
&debug_msg("XML Config Contends: \n");
&debug_msg("################################## \n\n");

# Structure conversion:
$raw_Vsites = $data->{domains}->{domain};
$raw_Users = $data->{users}->{user};

# Loop over all Vsites to get the user limit:
foreach $fqdn ( keys %{ $raw_Vsites } ) {
    $user_limit = ${$raw_Vsites}{$fqdn}{user_limit};
    &debug_msg("Processing $fqdn \n");
    # Update $Vsite if the data from the XML makes sense:
    if (($user_limit ne "") && ($user_limit ne "unlimited")) {
       $Vsite->{$fqdn}->{maxusers} = $user_limit;
    }
}
&debug_msg("\n");

#
### Processing Users:
#

# Parse /etc/passwd to get some important data:
$pw = new Unix::PasswdFile "/etc/passwd";
foreach $user ($pw->users) {
    $pwdUsers->{$user}->{fullName} = $pw->gecos($user);
    #$pwdUsers->{$user}->{pass} = $pw->encpass($user);
    $pwdUsers->{$user}->{home} = $pw->home($user);
    $pwdUsers->{$user}->{shell} = $pw->shell($user);
}
# Release lock:
undef $pw;

#
### FreeBSD special:
#

if (-f '/etc/master.passwd') {
    $pw_file = '/etc/master.passwd';
}
else {
    $pw_file = '/etc/shadow';
}

open(PASSWD, $pw_file);
while (<PASSWD>) {
    chomp;
    ($login, $passwd, $uid, $gid, $gcos, $home, $shell) = split(/:/);
    if ($pwdUsers->{$login}) {
        $pwdUsers->{$login}->{pass} = $passwd;
    }
}

foreach $u ( keys %{ $raw_Users } ) {

    # Get capabilities of user fom XML:
    $xmlUserCaps = $raw_Users->{$u}->{capabilities};

    if (scalar(keys %{$xmlUserCaps}) eq "0") {
        &debug_msg("User $u has no capabilities. Ignoring.\n");
        delete $raw_Users->{$u};
        next;
    }

    # Default FTP and Email to off:
    $emailDisabled = "1";
    $ftpDisabled = "1";

    foreach $uCap ( keys %{ $xmlUserCaps } ) {
        # FTP or Email NOT disabled according to XML? Turn them back on:
        if ($uCap =~ /mail/) {
            $emailDisabled = "0";
        }
        if ($uCap =~ /ftp/) {
            $ftpDisabled = "0";
        }
    }

    # Is siteAdmin?
    $siteAdmin = "0";
    $domain_admin = $raw_Users->{$u}->{domain_admin};
    $capabilities = "";
    $capLevels = "";
    if ($domain_admin) {
        # Yes. Grant the equivalent BlueOnyx capabilities:
        $siteAdmin = "1";
        $capabilities = '&siteAdmin&siteSSL&';
        $capLevels = '&siteAdmin&siteSSL&';

        if ($raw_Users->{$u}->{domain} ne $vhost_server_name) {
            if ($Resellers->{$u}) {

                if (in_array(\@KnownDomainNames, $raw_Users->{$u}->{domain})) {
                    $Resellers->{$u}->{primaryDomain} = $raw_Users->{$u}->{domain};
                }
                else {
                    @res_Resc_domains = scalar_to_array($Resellers->{$u}->{domains});
                    $Resellers->{$u}->{primaryDomain} = $res_Resc_domains[0];
                    print "WARN: Domain '" . $raw_Users->{$u}->{domain} . "' doesn't exist in httpd.conf! Primary domain for Reseller '$u' will be $res_Resc_domains[0] instead!\n";
                }
            }
        }
    }

    # Which site does the user belong to?
    $site_of_user = $raw_Users->{$u}->{domain};
    if (($Resellers->{$u}) || ($DomainOwners->{$u})) {
        # Wait a sec! User is a reseller. So he doesn't belong to a site!
        if ($DomainOwners->{$u}) {
            @dom_of_siteAdmin = scalar_to_array($DomainOwners->{$u});
            $site_of_user = $dom_of_siteAdmin[0];
        }
        if ($Resellers->{$u}) {
            $site_of_user = $Resellers->{$u}->{primaryDomain};
        }
    }

    # Make really sure $Resellers->{$u}->{primaryDomain} is set:
    if ($Resellers->{$u}) {
        unless ($Resellers->{$u}->{primaryDomain}) {
            if ($DomainOwners->{$u}) {
                @dom_of_siteAdmin = scalar_to_array($DomainOwners->{$u});
                $Resellers->{$u}->{primaryDomain} = $dom_of_siteAdmin[0];
            }
        }
    }

    # Create basic BlueOnyx User Object:
    # Catch: @@@@@@@@ 'site' is the FQDN. This needs to be fixed on import! @@@@@@@@
    $User->{$u} = {
        "capabilities" => $capabilities,
        "capLevels" => $capLevels,
        "name" => $u,
        "site" => $site_of_user,
        "fullName" => $pwdUsers->{$u}->{fullName},
        "crypt_password" => $pwdUsers->{$u}->{pass},
        "stylePreference" => 'BlueOnyx',
        "emailDisabled" => $emailDisabled,
        "ftpDisabled" => $ftpDisabled,
        "md5_password" => ''
        };

    # Handle Shell access:
    $shell = "0";
    if ($pwdUsers->{$u}->{shell} eq "/bin/tcsh") {
        $shell = "1";
        # Catch: We can't turn on Shell if it's off for the Vsite:
        $Vsite->{$site_of_user}->{Shell}->{enabled} = '1';
    }
    $User->{$u}->{Shell} = {
        "enabled" => $shell,
        };

    # Handle Emails:
    $User->{$u}->{Email} = {
        'aliases' => "",
        'forwardEnable' => "0",
        'vacationMsg' => "",
        'forwardSave' => "0",
        'forwardEmail' => "",
        'vacationMsgStart' => "",
        'vacationMsgStop' => "",
        'vacationOn' => "0"
        };
}

&debug_msg("\n################################## \n");
&debug_msg("siteAdmin: Making sure each Vsite has a siteAdmin: \n");
&debug_msg("################################## \n\n");

$xnum = '1';
foreach $fqdn ( keys %{ $raw_Vsites } ) {

    $webOwner = $Vsite->{$fqdn}->{PHP}->{prefered_siteAdmin};
    if ($webOwner eq "") {
        $webOwner = 'admin';
    }

    if ($Resellers->{$webOwner}) {
        @resellerDomains = scalar_to_array($Resellers->{$webOwner}->{domains});

        if ((in_array(\@resellerDomains, $fqdn)) && ($fqdn ne $Resellers->{$webOwner}->{primaryDomain})) {
            # Domain $fqdn is owned by Reseller $webOwner, but it is NOT his primary domain!

            # Username for new siteAdmin of this Vsite:
            $newSiteAdmin = $Vsite->{$fqdn}->{PHP}->{prefered_siteAdmin} . "_sa" . $xnum;
            &debug_msg("$fqdn: Creating new siteAdmin $newSiteAdmin\n");
            $Vsite->{$fqdn}->{PHP}->{prefered_siteAdmin} = $newSiteAdmin;
            $xnum++;

            # Create the new siteAdmin:
            $User->{$newSiteAdmin} = {
                "capabilities" => '&siteAdmin&siteSSL&',
                "capLevels" => '&siteAdmin&siteSSL&',
                "name" => $newSiteAdmin,
                "site" => $fqdn,
                "fullName" => $User->{$webOwner}->{fullName},
                "crypt_password" => $User->{$webOwner}->{crypt_password},
                "stylePreference" => 'BlueOnyx',
                "emailDisabled" => $User->{$webOwner}->{emailDisabled},
                "ftpDisabled" => $User->{$webOwner}->{ftpDisabled},
                "md5_password" => ''
                };

            # Handle Shell access:
            $User->{$newSiteAdmin}->{Shell} = {
                "enabled" => '0',
                };

            # Handle Emails by forwarding them to the Reseller-account:
            $User->{$newSiteAdmin}->{Email} = {
                'aliases' => "",
                'forwardEnable' => "1",
                'vacationMsg' => "",
                'forwardSave' => "0",
                'forwardEmail' => $webOwner,
                'vacationMsgStart' => "",
                'vacationMsgStop' => "",
                'vacationOn' => "0"
                };

        }
        elsif ($fqdn eq $Resellers->{$webOwner}->{primaryDomain}) {
            # This domain is owned by a reseller. And it's the primary domain he has.
            # We need to create a siteAdmin account *and* the reseller account:

            #
            ### siteAdmin:
            #

            # Username for new siteAdmin of this Vsite:
            $newSiteAdmin = $Vsite->{$fqdn}->{PHP}->{prefered_siteAdmin} . "_sa" . $xnum;
            &debug_msg("$fqdn: Creating new siteAdmin $newSiteAdmin\n");
            $Vsite->{$fqdn}->{PHP}->{prefered_siteAdmin} = $newSiteAdmin;
            $xnum++;

            # Create the new siteAdmin:
            $User->{$newSiteAdmin} = {
                "capabilities" => '&siteAdmin&siteSSL&',
                "capLevels" => '&siteAdmin&siteSSL&',
                "name" => $newSiteAdmin,
                "site" => $fqdn,
                "fullName" => $User->{$webOwner}->{fullName},
                "crypt_password" => $User->{$webOwner}->{crypt_password},
                "stylePreference" => 'BlueOnyx',
                "emailDisabled" => $User->{$webOwner}->{emailDisabled},
                "ftpDisabled" => $User->{$webOwner}->{ftpDisabled},
                "md5_password" => ''
                };

            # Handle Shell access:
            $User->{$newSiteAdmin}->{Shell} = {
                "enabled" => '0',
                };

            # Handle Emails by forwarding them to the Reseller-account:
            $User->{$newSiteAdmin}->{Email} = {
                'aliases' => "",
                'forwardEnable' => "1",
                'vacationMsg' => "",
                'forwardSave' => "0",
                'forwardEmail' => $webOwner,
                'vacationMsgStart' => "",
                'vacationMsgStop' => "",
                'vacationOn' => "0"
                };

            #
            ### Set the newly created siteAdmin as 'prefered_siteAdmin' for this Vsite:
            #

            $Vsite->{$fqdn}->{PHP}->{prefered_siteAdmin} = $newSiteAdmin;

            #
            ### Reseller:
            #

            $reseller_capabilities = "&siteAnonFTP&siteShell&siteAdmin&adminUser&siteDNS&manageSite&siteSSL&resellerPHP&resellerSUPHP&resellerRUID&resellerFPM&resellerMySQL&resellerCGI&resellerSSI&resellerSSL&resellerFTP&";
            $reseller_capLevels = "&adminUser&manageSite&siteDNS&";
            $newResellerName = $Resellers->{$webOwner}->{newName};
            &debug_msg("$fqdn: Creating Reseller account $newResellerName\n");

            # Create the new Reseller-Acount:
            unless ($User->{$newResellerName}) {
                $User->{$newResellerName} = {
                    "capabilities" => $reseller_capabilities,
                    "capLevels" => $reseller_capLevels,
                    "name" => $newResellerName,
                    "site" => '',
                    "fullName" => $User->{$webOwner}->{fullName},
                    "crypt_password" => $User->{$webOwner}->{crypt_password},
                    "stylePreference" => 'BlueOnyx',
                    "emailDisabled" => $User->{$webOwner}->{emailDisabled},
                    "ftpDisabled" => $User->{$webOwner}->{ftpDisabled},
                    "md5_password" => ''
                    };

                # Handle Shell access:
                $User->{$newResellerName}->{Shell} = {
                    "enabled" => $User->{$webOwner}->{Shell}->{enabled},
                    };

                # Handle Emails by forwarding them to the Reseller-account:
                $User->{$newResellerName}->{Email} = {
                    'aliases' => "",
                    'forwardEnable' => "1",
                    'vacationMsg' => "",
                    'forwardSave' => "0",
                    'forwardEmail' => $webOwner . '@' . $Resellers->{$webOwner}->{primaryDomain},
                    'vacationMsgStart' => "",
                    'vacationMsgStop' => "",
                    'vacationOn' => "0"
                    };

                # Handle Reseller limits:
                $User->{$newResellerName}->{Sites} = {
                    "quota" => '50000000',
                    "user" => '1000',
                    "max" => '100',
                    };

            }
        }
        else {
            # Domain $fqdn is NOT owned by a Reseller. Just setting the right webOwner:
            $Vsite->{$fqdn}->{createdUser} = 'admin';
        }
    }
    else {
        # Domain $fqdn is NOT owned by a Reseller. Just setting the right webOwner:
        $Vsite->{$fqdn}->{createdUser} = 'admin';
    }
}

&debug_msg("\n################################## \n");
&debug_msg("Email: Parsing Email Configuration: \n");
&debug_msg("################################## \n\n");

# Read aliases.db and virtusertable.db:
tie %alias_hash, 'DB_File', $alias_file, O_RDONLY, 0666, $DB_HASH or die "can't read file '$alias_file': $!";
tie %virt_hash, 'DB_File', $virt_file, O_RDONLY, 0666, $DB_HASH or die "can't read file '$virt_file': $!";

# Show some statistics:

&debug_msg("$alias_file has " . scalar(keys %alias_hash) . " entries\n");
&debug_msg("$virt_file has " . scalar(keys %virt_hash) . " entries\n\n");

# Loop through the hash for the virtusertable:
for (keys %virt_hash) { 
    chomp($key = $_);
    chomp($value = $virt_hash{$_});

    $value =~ s/\s+//g;

    # Index all except mappings that go to the system 'root':
    if (($key !~ /root@/) && ($key !~ /postmaster@/) && ($key !~ /apache@/) && ($key !~ /webmaster@/) && ($key !~ /www@/) && ($value !~ /^error:nouser/)) {

        if ($value =~ /~/) {
            # Map the forwards that are hacked into /etc/mail/alias:
            $WeirdForwardAlias->{$key} = $value;
            $WeirdForwardAliasReverse->{$value} = $key;
        }
        elsif ($value =~ /%1@/) {
            # This domain forwards emails to another domain:
            $value =~ s/%1@//g;
            $key =~ s/@//g;
            $DomainForwardAlias->{$key} = $value;
        }
        else {
            # All other mappings:
            $VirtUserTable->{$key} = $value;
        }
    }

    # Get a list of *real* user accounts that we terminate to:
    if ($value =~ /^error:nouser/) {
        next;
    }
    if ($value =~ /~/) {
        next;
    }
    if ($value =~ /@/) {
        next;
    }
    unless (in_array(\@RealMailUsers, $value)) {
        push @RealMailUsers, $value;
    }
}

#
## Email Server Aliases
# 
# Explanation: We do have them in %DomainForwardAlias. Additionally we also might want to
# make sure that all webAliases are also valid. We do this here:

foreach $alias ( keys %{ $DomainForwardAlias } ) {
    $alias_target = $DomainForwardAlias->{$alias};
    if (in_array(\@KnownDomainNames, $alias_target)) {
        @mailAliases = ();
        @webAliases = ();
        if ($Vsite->{$alias_target}) {
            @mailAliases = scalar_to_array($Vsite->{$alias_target}->{mailAliases});
            push @mailAliases, $alias;

            #Throw in the webAliases as well:
            @webAliases = scalar_to_array($Vsite->{$alias_target}->{webAliases});
            foreach $ali (@webAliases) {
                push @mailAliases, $ali;
            }
            # Remove duplicates:
            @mailAliases = uniq(@mailAliases);
            $Vsite->{$alias_target}->{mailAliases} = array_to_scalar(@mailAliases);
        }
    }
}

#
### More Email Server Alias madness:
#

# Now add *all* bloody webAliases to mailAliases and then strip out the duplicates again:
foreach $fqdn ( keys %{ $Vsite } ) {

    # Start sane each run:
    $mailAliases = '';
    $webAliases = '';
    $a = '';
    $b = '';
    @m = ();
    @w = ();
    @ali_merged = ();
    @ali_flat = ();

    $mailAliases = $Vsite->{$fqdn}->{mailAliases};
    $webAliases = $Vsite->{$fqdn}->{webAliases};
    @m = scalar_to_array($mailAliases);
    @w = scalar_to_array($webAliases);
    for my $a ( @m ) {
        for my $b ( @w ) {
            push( @ali_merged, ($a, $b) );
        }
    }
    @ali_flat = uniq(@ali_merged);
    $Vsite->{$fqdn}->{mailAliases} = array_to_scalar(@ali_flat);
}

#
### User related aliases:
#

foreach $alias ( keys %{ $VirtUserTable } ) {
    $alias_target = $VirtUserTable->{$alias};
    @domparts = ();
    $userNamePart = '';
    $domainNamePart = '';
    @UserMailAliases = ();
    if (in_array(\@RealMailUsers, $alias_target)) {
        if ($User->{$alias_target}) {
            @domparts = split('@', $alias);

            $userNamePart = $domparts[0];
            $domainNamePart = $domparts[1];

            &debug_msg("Recipient-Account: " . $alias_target . " -> " . $alias . " of Domain: " . $domainNamePart . "\n");

            if ($userNamePart eq $alias_target) {
                # Check if the user belongs to the same domain that he is redirecting the email to:
                if ($User->{$alias_target}->{site} eq $domainNamePart) {
                    #&debug_msg("INFO: Skipping, as it's redundant.\n");
                    # Done. And delete alias:
                    delete $VirtUserTable->{$alias};
                }
                else {
                    #&debug_msg("INFO: NOT Skipping.\n");
                }
            }
            else {
                # userNamePart of the Email-Alias does not match the Account-Username, so it is an Alias:
                #&debug_msg("INFO: $alias is an Alias of User $alias_target of the domain $domainNamePart. We need to add the alias $userNamePart.\n");
                # Get any existing Aliases that the User may have:
                @UserMailAliases = scalar_to_array($User->{$alias_target}->{Email}->{aliases});
                # Cleanup Alias:
                $userNamePart =~ s/\*//g;
                # Push the detected alias:
                push @UserMailAliases, $userNamePart;
                @UserMailAliases = uniq(@UserMailAliases);
                $User->{$alias_target}->{Email}->{aliases} = array_to_scalar(@UserMailAliases);
                # Done. And delete alias:
                delete $VirtUserTable->{$alias};
            }

            if ($domainNamePart eq $User->{$alias_target}->{site}) {
                # We don't actually need to update $User here, cuz BlueOnyx will add all the Vsite aliases on user creation anyway. 
                #&debug_msg("FINE: This alias for domain $domainNamePart points to user $alias_target of domain $User->{$alias_target}->{site}!\n");
                # Done. And delete alias:
                delete $VirtUserTable->{$alias};
            }
            else {
                if ($Resellers->{$alias_target}) {
                    #&debug_msg("INFO: This alias $alias for domain $domainNamePart points to user $alias_target - who is a Reseller!\n");
                    # Done. And delete alias:
                    delete $VirtUserTable->{$alias};
                }
                else {
                    #&debug_msg("WARN: This alias $alias for domain $domainNamePart points to user $alias_target of domain $User->{$alias_target}->{site}!\n");
                    # Done. And delete alias:
                    delete $VirtUserTable->{$alias};
                }
            }
        }
        else {
            @domparts = split('@', $alias);
            $userNamePart = $domparts[0];
            $domainNamePart = $domparts[1];
            &debug_msg("Recipient-Account: " . $alias_target . " -> " . $alias . " of Domain: " . $domainNamePart . "\n");
            &debug_msg("WARN: No bloody idea which site $alias_target belongs to! Checking ....\n");
            if ($pwdUsers->{$alias_target}) {
                &debug_msg("WARN: $alias_target is a local user with the full name of $pwdUsers->{$alias_target}->{fullName} and homeDir of $pwdUsers->{$alias_target}->{home}\n");
                &debug_msg("WARN: Ignoring $alias_target.\n");
                delete $VirtUserTable->{$alias};
            }
            else {
                &debug_msg("WARN: $alias_target is NOT a local user. He is not known to the authentication mechanism nor does he have a home directory.\n");
                &debug_msg("WARN: Ignoring $alias_target.\n");
                delete $VirtUserTable->{$alias};
            }
        }
    }
    else {
        #&debug_msg("LEFT-OVER: Alias $alias points to $alias_target\n");
    }
}

&debug_msg("\n##################################################################\n");
&debug_msg("2nd alias run to catch circular logic aliases: \n");
&debug_msg("##################################################################\n\n");

# Unfunny part: We could have circular logic aliases. So we need to run over all of this again to pick at the left-overs:
foreach $alias ( keys %{ $VirtUserTable } ) {
    $alias_target = $VirtUserTable->{$alias};
    @A_domparts = ();
    $A_userNamePart = '';
    $A_domainNamePart = '';
    $mailAliases = '';
    @U_MailAliases = ();
    @NA_domparts = ();
    $NA_userNamePart = '';
    $NA_domainNamePart = '';
    @DomMailAliases = ();

    # Catch circular logic aliases.
    #
    # Example:
    # User 'thomasre' has a legitimate alias. We catch this in the code above just fine:
    #   thomas.reichmann@reichmann-mc.de        thomasre
    # But he also has this alias, where the target it points to is yet another alias:
    #   thomasreichmann@reichmann-mc.de         thomas.reichmann@reichmann-mc.de

    if ($alias_target =~ /@/) {
        # Get Username and Domain-Name of the circular-logic alias target:
        @A_domparts = split('@', $alias_target);
        $A_userNamePart = $A_domparts[0];
        $A_domainNamePart = $A_domparts[1];

        # Find out if the target domain name is a local domain:
        if ($Vsite->{$A_domainNamePart}) {
            &debug_msg("INFO_X1: Alias $alias points to $alias_target - which is a local Vsite ($A_domainNamePart).\n");

            if ($User->{$A_userNamePart}) {
                &debug_msg("INFO_X1: ... and that points to username $A_userNamePart of local Vsite ($A_domainNamePart).\n");

                # WARNING: Alias info@lzk.de points to werum@lzk.de
                # INFO_X1: Alias info@lzk.de points to werum@lzk.de - which is a local Vsite (lzk.de).
                # INFO_X1: ... and that points to username werum of local Vsite (lzk.de).

                # Get aliases of User:
                $mailAliases = ${$User}{$A_userNamePart}->{Email}->{aliases};
                @U_MailAliases = scalar_to_array($mailAliases);

                # Get Username and Domain-Name of the circular-logic alias source:
                @NA_domparts = split('@', $alias);
                $NA_userNamePart = $NA_domparts[0];
                $NA_domainNamePart = $NA_domparts[1];

                # Push the detected alias:
                push @U_MailAliases, $NA_userNamePart;
                @U_MailAliases = uniq(@U_MailAliases);
                $User->{$A_userNamePart}->{Email}->{aliases} = array_to_scalar(@U_MailAliases);
                &debug_msg("INFO_X1: Adding Alias $NA_userNamePart to mailAliases of user $A_userNamePart of Vsite $A_domainNamePart " . '\o/' . "\n");
                # Done. And delete alias:
                delete $VirtUserTable->{$alias};

            }
            else {
                # We're pointing to an alias username of a local Vsite. So we need to run through all bloody Users of this
                # Domain to find out who the fuck owns this alias. Great!
                foreach $A_User ( keys %{ $User } ) {
                    # Get aliases of User:
                    $mailAliases = ${$User}{$A_User}->{Email}->{aliases};
                    @U_MailAliases = scalar_to_array($mailAliases);
                    if (in_array(\@U_MailAliases, $A_userNamePart)) {
                        &debug_msg("INFO_X2: Alias $alias ($A_userNamePart - $A_domainNamePart) is a mailAliases of user $A_User of Vsite $A_domainNamePart " . '\o/' . "\n");

                        # Get Username and Domain-Name of the circular-logic alias source:
                        @NA_domparts = split('@', $alias);
                        $NA_userNamePart = $NA_domparts[0];
                        $NA_domainNamePart = $NA_domparts[1];

                        # Push the detected alias:
                        push @U_MailAliases, $NA_userNamePart;
                        @U_MailAliases = uniq(@U_MailAliases);
                        $User->{$A_User}->{Email}->{aliases} = array_to_scalar(@U_MailAliases);
                        &debug_msg("INFO_X2: Adding Alias $NA_userNamePart to mailAliases of user $A_User of Vsite $A_domainNamePart " . '\o/' . "\n");
                        # Done. And delete alias:
                        delete $VirtUserTable->{$alias};

                    }
                }
            }
        }
        else {
            # Now it could also be that the circular alias recipient domain is *just* a domain alias. Which would be super stupid!
            # Getting this info will cost us many precious CPU cycles, as we need to loop through all FQDN's:
            foreach $fqdn ( keys %{ $Vsite } ) {
                $mailAliases = $Vsite->{$fqdn}->{mailAliases};
                @DomMailAliases = scalar_to_array($mailAliases);
                if (in_array(\@DomMailAliases, $A_domainNamePart)) {
                    &debug_msg("INFO_X3: Alias $A_domainNamePart is a mailAliases of $fqdn " . '\o/' . "\n");
                }
            }
        }
    }
}

&debug_msg("\n");

#
### Alias Resteficken:
#

&debug_msg("\n##################################################################\n");
&debug_msg("3rd alias run to catch forwards to foreign mailservers: \n");
&debug_msg("##################################################################\n\n");

if ($DEBUG eq "2") {
    &debug_msg("The following Aliases have not yet been associated with anything: \n\n");
    print Dumper($VirtUserTable);
    &debug_msg("\n");
}

foreach $alias ( keys %{ $VirtUserTable } ) {
    $alias_target = $VirtUserTable->{$alias};
    @DomMailAliases = ();
    $bait = '';

    #&debug_msg("Recipient-Account: " . $alias_target . " -> " . $alias . "\n");

    # Find out if this is a catchall:
    if ($alias =~ /^@(.*)/) {
        #&debug_msg("CATCHALL: " . $alias_target . " -> " . $alias . "\n");

        # Strip leading '@' from catchall to find out the domain name:
        $bait = $alias;
        $bait =~ s/@//g;

        # Walk through all local Vsites to find out which Vsite this catchall is for:
        foreach $fqdn ( keys %{ $Vsite } ) {
            $mailAliases = ${$Vsite}{$fqdn}{mailAliases};
            @DomMailAliases = scalar_to_array($mailAliases);
            if (in_array(\@DomMailAliases, $bait)) {
                #&debug_msg("INFO_X4: Catchall $bait is a mailAliases of Vsite $fqdn and emails to $alias_target\n");

                # Need to add an account with forwarding to this external email-addy.
                unless ($CatchAll->{$fqdn}) {

                    # Random 16 char password:
                    $RandPass = join '', map +(0..9,'a'..'z','A'..'Z')[rand(10+26*2)], 1..16;
                    $RandUsername = "mail_" . join '', map +(0..9,'a'..'z')[rand(10+26*2)], 1..16;

                    &debug_msg("INFO_X4: Creating Catchall-Account $RandUsername for Vsite $fqdn - destination: $alias_target\n");

                    # Create basic BlueOnyx User Object for this forwarder:
                    # Catch: @@@@@@@@ 'site' is the FQDN. This needs to be fixed on import! @@@@@@@@
                    $User->{$RandUsername} = {
                        "capabilities" => '',
                        "capLevels" => '',
                        "name" => $RandUsername,
                        "site" => $fqdn,
                        "fullName" => 'Catchall-Account',
                        "crypt_password" => $RandPass,
                        "stylePreference" => 'BlueOnyx',
                        "emailDisabled" => '0',
                        "ftpDisabled" => '1',
                        "md5_password" => ''
                        };

                    # Handle Shell access:
                    $User->{$RandUsername}->{Shell} = {
                        "enabled" => '0',
                        };

                    # Handle Emails:
                    $User->{$RandUsername}->{Email} = {
                        'aliases' => "",
                        'forwardEnable' => "1",
                        'vacationMsg' => "",
                        'forwardSave' => "0",
                        'forwardEmail' => "&$alias_target&",
                        'vacationMsgStart' => time(),
                        'vacationMsgStop' => time(),
                        'vacationOn' => "0"
                        };

                    # Set Catchall for this FQDN to the newly created account:
                    $Vsite->{$fqdn}->{mailCatchAll} = $RandUsername;

                    # Adjust User-Limit accordingly by allowing one more user:
                    $new_user_limit = $Vsite->{$fqdn}->{maxusers};
                    $new_user_limit++;
                    $Vsite->{$fqdn}->{maxusers} = $new_user_limit;

                    # Take note that this Vsite already has a catchall:
                    $CatchAll->{$fqdn} = $RandUsername;
                }

                # Done. And delete alias:
                delete $VirtUserTable->{$alias};
            }
        }
    }
}

if ($DEBUG eq "2") {
    &debug_msg("\nAfter detecting the Catch-Alls we still have these unassociated aliases: \n\n");
    print Dumper($VirtUserTable);
    &debug_msg("\n");
}

foreach $alias ( keys %{ $VirtUserTable } ) {
    $alias_target = $VirtUserTable->{$alias};

    @F_domparts = ();;
    $F_userNamePart = '';
    $F_domainNamePart = '';
    $new_user_limit = '';

    #&debug_msg("Recipient-Account of forwarder: " . $alias_target . " -> " . $alias . "\n");

    # Get Username and Domain-Name of the local account that should forward:
    @F_domparts = split('@', $alias);
    $F_userNamePart = $F_domparts[0];
    $F_domainNamePart = $F_domparts[1];

    if ($F_userNamePart eq "*") {
        &debug_msg("INFO_X5A: Skipping Forwarding-Account $RandUsername with Alias $F_userNamePart for Vsite $F_domainNamePart - destination: $alias_target\n");
        delete $VirtUserTable->{$alias};
        next;
    }

    # Check if the domain exists:
    if ($Vsite->{$F_domainNamePart}) {
        #&debug_msg("Info-X5: Vsite " . $F_domainNamePart . " exists.\n");

        # Random 16 char password:
        $RandPass = join '', map +(0..9,'a'..'z','A'..'Z')[rand(10+26*2)], 1..16;
        $RandUsername = "fw_" . join '', map +(0..9,'a'..'z')[rand(10+26*2)], 1..16;

        # The user doesn't exist either, or we would have catched him by now:
        unless ($User->{$F_userNamePart}) {
            &debug_msg("INFO_X5A: Creating Forwarding-Account $RandUsername with Alias $F_userNamePart for Vsite $F_domainNamePart - destination: $alias_target\n");

            # Create basic BlueOnyx User Object for this forwarder:
            # Catch: @@@@@@@@ 'site' is the FQDN. This needs to be fixed on import! @@@@@@@@
            $User->{$RandUsername} = {
                "capabilities" => '',
                "capLevels" => '',
                "name" => $RandUsername,
                "site" => $F_domainNamePart,
                "fullName" => 'Forward-Account',
                "crypt_password" => $RandPass,
                "stylePreference" => 'BlueOnyx',
                "emailDisabled" => '0',
                "ftpDisabled" => '1',
                "md5_password" => ''
                };

            # Handle Shell access:
            $User->{$RandUsername}->{Shell} = {
                "enabled" => '0',
                };

            # Handle Emails:
            $User->{$RandUsername}->{Email} = {
                'aliases' => "&$F_userNamePart&",
                'forwardEnable' => "1",
                'vacationMsg' => "",
                'forwardSave' => "0",
                'forwardEmail' => "&$alias_target&",
                'vacationMsgStart' => time(),
                'vacationMsgStop' => time(),
                'vacationOn' => "0"
                };
        }
        else {
            # OK. So the User exists. Check if he is member of that Vsite. If not, create a forwarder:
            unless ($User->{$F_userNamePart}->{site} eq $F_domainNamePart) {
                &debug_msg("INFO_X5B: Creating Forwarding-Account $RandUsername with Alias $F_userNamePart for Vsite $F_domainNamePart - destination: $alias_target\n");

                # Create basic BlueOnyx User Object for this forwarder:
                # Catch: @@@@@@@@ 'site' is the FQDN. This needs to be fixed on import! @@@@@@@@
                $User->{$RandUsername} = {
                    "capabilities" => '',
                    "capLevels" => '',
                    "name" => $RandUsername,
                    "site" => $F_domainNamePart,
                    "fullName" => 'Forward-Account',
                    "crypt_password" => $RandPass,
                    "stylePreference" => 'BlueOnyx',
                    "emailDisabled" => '0',
                    "ftpDisabled" => '1',
                    "md5_password" => ''
                    };

                # Handle Shell access:
                $User->{$RandUsername}->{Shell} = {
                    "enabled" => '0',
                    };

                # Handle Emails:
                $User->{$RandUsername}->{Email} = {
                    'aliases' => "&$F_userNamePart&",
                    'forwardEnable' => "1",
                    'vacationMsg' => "",
                    'forwardSave' => "0",
                    'forwardEmail' => "&$alias_target&",
                    'vacationMsgStart' => time(),
                    'vacationMsgStop' => time(),
                    'vacationOn' => "0"
                    };
            }
        }

        # Adjust User-Limit accordingly by allowing one more user:
        $new_user_limit = $Vsite->{$F_domainNamePart}->{maxusers};
        $new_user_limit++;
        $Vsite->{$F_domainNamePart}->{maxusers} = $new_user_limit;

        # Done. And delete alias:
        delete $VirtUserTable->{$alias};

    }
}

if ($DEBUG eq "2") {
    &debug_msg("\nAll Aliases should be done by now and the hash below should be empty: \n\n");
    print Dumper($VirtUserTable);
    &debug_msg("\n");
}

#
### Handle the funny alias/forward combo unique to this solution:
#

&debug_msg("\n##################################################################\n");
&debug_msg("4th alias run to catch the multi-forwarders from alias/virtuser-tables: \n");
&debug_msg("##################################################################\n\n");

# Loop through the hash for the virtusertable:
for (keys %alias_hash) { 
    chop($key = $_);
    chop($value = $alias_hash{$_});
    $value =~ s/\s+//;

    @WF_domparts = ();
    $WF_userNamePart = '';
    $WF_domainNamePart = '';
    @WF_destinations = ();
    @WF_destinations_cleaned = ();
    $wf_recipients = '';

    # This is the droid we're looking for: A weird alias with a tilde in it:
    if ($key =~ /~/) {

        unless ($WeirdForwardAliasReverse->{$key}) {
            &debug_msg("INFO_X6-WEIRD: key $key and value $value - has no counterpart in virtusertable!\n");
        }

        # Check if we have a matching virtusertable-entry:
        if ($WeirdForwardAliasReverse->{$key}) {

            # Get Username and Domain-Name of the local account that should forward:
            @WF_domparts = split('@', $WeirdForwardAliasReverse->{$key});
            $WF_userNamePart = $WF_domparts[0];
            $WF_domainNamePart = $WF_domparts[1];

            # Check if the domain exists:
            if ($Vsite->{$WF_domainNamePart}) {
                #&debug_msg("Info-X6: Vsite " . $WF_domainNamePart . " exists.\n");

                # Random 16 char password:
                $RandPass = join '', map +(0..9,'a'..'z','A'..'Z')[rand(10+26*2)], 1..16;
                $RandUsername = "mf_" . join '', map +(0..9,'a'..'z')[rand(10+26*2)], 1..16;

                # Assemble scalar of destination email-addresses:
                @WF_destinations = split(',', $value);
                $k = '';
                foreach $k (@WF_destinations) {
                    $k =~ s/\s+//;
                    $k =~ s/\n//g;
                    $k =~ s/\r//g;
                    push @WF_destinations_cleaned, $k;
                }
                $wf_recipients = array_to_scalar(@WF_destinations_cleaned);

                # Just to be sure:
                $wf_recipients =~ s/\s+//;

                &debug_msg("INFO_X6: Creating Multi-Forwarder $RandUsername with Alias $WF_userNamePart for Vsite $WF_domainNamePart - destination: $wf_recipients\n");

                # Create basic BlueOnyx User Object for this forwarder:
                # Catch: @@@@@@@@ 'site' is the FQDN. This needs to be fixed on import! @@@@@@@@
                $User->{$RandUsername} = {
                    "capabilities" => '',
                    "capLevels" => '',
                    "name" => $RandUsername,
                    "site" => $WF_domainNamePart,
                    "fullName" => 'Multi-Forwarder',
                    "crypt_password" => $RandPass,
                    "stylePreference" => 'BlueOnyx',
                    "emailDisabled" => '0',
                    "ftpDisabled" => '1',
                    "md5_password" => ''
                    };

                # Handle Shell access:
                $User->{$RandUsername}->{Shell} = {
                    "enabled" => '0',
                    };

                # Handle Emails:
                $User->{$RandUsername}->{Email} = {
                    'aliases' => "&$WF_userNamePart&",
                    'forwardEnable' => "1",
                    'vacationMsg' => "",
                    'forwardSave' => "0",
                    'forwardEmail' => "$wf_recipients",
                    'vacationMsgStart' => time(),
                    'vacationMsgStop' => time(),
                    'vacationOn' => "0"
                    };

                # Adjust User-Limit accordingly by allowing one more user:
                $new_user_limit = $Vsite->{$WF_domainNamePart}->{maxusers};
                $new_user_limit++;
                $Vsite->{$WF_domainNamePart}->{maxusers} = $new_user_limit;

            }
        }
        # Done. And delete alias:
        delete $WeirdForwardAliasReverse->{$key};
    }
}

#
### Email Forwarding as per GUI and procmailrc:
#

&debug_msg("\n##################################################################\n");
&debug_msg("Check User-Accounts for active Procmail forwarding: \n");
&debug_msg("##################################################################\n\n");

foreach $account ( keys %{ $User } ) {
    $user = $User->{$account};
    $userName = $user->{name};

    if (in_array(\@RealMailUsers, $userName)) {

        #
        ### While at it: Also store the paths to the users homeDir:
        #
        $User_Extra->{$userName}->{homeDir} = "/home/$userName";
        #if (-d "/home/$userName/Maildir/") {
        #    $User_Extra->{$userName}->{mailDir} = "/home/$userName/Maildir/";
        #}
        if (-f "/var/mail/$userName") {
            $User_Extra->{$userName}->{mbox} = "/var/mail/$userName";
        }

        if (-f "/home/" . $userName . "/.procmailrc") {
            $autoforwarder = `cat /home/$userName/.procmailrc | grep "^INCLUDERC=" | grep "CPXDIR/mailforward.rc" | wc -l`;
            chomp($autoforwarder);
            $autoforwarder =~ s/\s+//g;

            $autoresponder = `cat /home/$userName/.procmailrc | grep "^INCLUDERC=" | grep "CPXDIR/autoreply.rc" | wc -l`;
            chomp($autoresponder);
            $autoresponder =~ s/\s+//g;

            # Parse Email-Forwarder:
            if ($autoforwarder eq "1") {
                if (-f "/home/" . $userName . "/.cpx/procmail/mailforward.rc") {
                    $fwd_raw = `cat /home/$userName/.cpx/procmail/mailforward.rc | grep "!" | grep -v X-Loop`;
                    chomp($fwd_raw);
                    $fwd_raw =~ s/!//g;
                    $fwd_raw =~ s/\s+//g;
                    @fwd_array = split(',', $fwd_raw);

                    # Activate Email-Forwarding:
                    $User->{$userName}->{Email}->{vacationMsgStart} = time();
                    $User->{$userName}->{Email}->{vacationMsgStop} = time();
                    $User->{$userName}->{Email}->{forwardEnable} = '1';

                    @existing_forwards = ();
                    @existing_forwards = scalar_to_array($User->{$userName}->{Email}->{forwardEmail});
                    foreach $x (@fwd_array) {
                        push @existing_forwards, $x;
                        @existing_forwards = uniq(@existing_forwards);
                    }
                    $fwd_recipients = array_to_scalar(@existing_forwards);
                    $User->{$userName}->{Email}->{forwardEmail} = $fwd_recipients;

                    # Take note if we want to keep a copy or not:
                    $fwd_raw = `cat /home/$userName/.cpx/procmail/mailforward.rc | grep "!" | grep -v X-Loop`;
                    $keep_copy = `cat /home/$userName/.cpx/procmail/mailforward.rc | head -1 | grep "^:0 c" | wc -l`;
                    chomp($keep_copy);
                    $keep_copy =~ s/\s+//g;
                    $User->{$userName}->{Email}->{forwardSave} = $keep_copy;
                }
            }

            # Parse Auto-Responder:
            if ($autoresponder eq "1") {
                if ((-f "/home/" . $userName . "/.cpx/procmail/autoreply.rc") && (-f "/home/" . $userName . "/.cpx/autoreply/message.txt")) {
                    $auto_body = `cat /home/$userName/.cpx/autoreply/message.txt | grep -v "^X-Loop:" | grep -v "^Subject:" | grep -v "^Reply-To:" | grep -v "^MIME"|grep -v "^Content-Type:"`;
                    chomp($auto_body);
                    $auto_body =~ s/\n//g;
                    $auto_body =~ s/\r//g;
                    $User->{$userName}->{Email}->{vacationMsg} = $auto_body;
                    $User->{$userName}->{Email}->{vacationOn} = '1';
                    $User->{$userName}->{Email}->{vacationMsgStart} = time();
                    $User->{$userName}->{Email}->{vacationMsgStop} = time();
                }
            }
        }
        &debug_msg("Auto-Forwarder ($autoforwarder) - Auto-Responder ($autoresponder) \t\t $userName\n");
    }
}

#
### Disk Quota and Limits:
#

&debug_msg("\n##################################################################\n");
&debug_msg("Checking Disk Quota: \n");
&debug_msg("##################################################################\n\n");

foreach $account ( keys %{ $User } ) {
    $user = $User->{$account};
    $userName = $user->{name};

    # Disk Quota:
    $uid = getpwnam($user->{name});

    ($quota->{$userName}->{block_curr}, 
        $quota->{$userName}->{block_soft},
        $quota->{$userName}->{block_hard},
        $quota->{$userName}->{block_timelimit},
        $quota->{$userName}->{inode_soft},
        $quota->{$userName}->{inode_hard},
        $quota->{$userName}->{inode_timelimit}
    ) = Quota::query('/home', $uid);

    # Human readable:
    $userQuota_hr = $quota->{$userName}->{block_hard}/1024 . " MB";

    if ($quota->{$userName}->{block_hard} gt "0") {
        # Create Quota Object:
        $User->{$userName}->{Disk} = {
            'quota' => $quota->{$userName}->{block_hard}
            };
    }
    else {
        $userQuota_hr = $user_def_quota/1024 . " MB";
        # Create Quota Object with default quota:
        $User->{$userName}->{Disk} = {
            'quota' => $user_def_quota
            };
    }

    &debug_msg("$userQuota_hr \t for User $userName \n");

}

&debug_msg("\n##################################################################\n");
&debug_msg("Set siteAdmin and Vsite Disk Quota correctly: \n");
&debug_msg("##################################################################\n\n");

foreach $account ( keys %{ $User } ) {
    $user = $User->{$account};
    $userName = $user->{name};

    # Find out if the user in question is a siteAdmin:
    if ($User->{$userName}->{capLevels} eq '&siteAdmin&siteSSL&') {
        foreach $resName ( keys %{ $Resellers } ) {
            $resData = $Resellers->{$resName};
            @res_domains = scalar_to_array($resData->{domains});
            if (in_array(\@res_domains, $User->{$userName}->{site})) {
                $reseller_quota = $User->{$resName}->{Disk}->{quota};

                # Set siteAdmin quota to the same as the Reseller had:
                $userQuota_hr = $reseller_quota/1024 . " MB";
                $User->{$userName}->{Disk} = {
                    'quota' => $reseller_quota
                    };

                &debug_msg("$userQuota_hr \t for siteAdmin $userName \n");

                # Keep track of assigned Vsite-Quota per FQDN:
                $Vsite_Quota->{$User->{$userName}->{site}} = $reseller_quota;

                # Set Vsite disk quota:
                $Vsite->{$User->{$userName}->{site}}->{Disk}->{quota} = $reseller_quota;

                &debug_msg("$userQuota_hr \t for Vsite $Vsite->{$User->{$userName}->{site}}->{fqdn} \n");

                # Keep track of Quota for Reseller himself:
                $Resellers->{$resName}->{vsite_quota} = $Resellers->{$resName}->{vsite_quota} + $reseller_quota;
            }
        }
    }
}

&debug_msg("\n##################################################################\n");
&debug_msg("Update allowed Disk Quota for Resellers for Vsite usage: \n");
&debug_msg("##################################################################\n\n");

foreach $resName ( keys %{ $Resellers } ) {
    $resData = $Resellers->{$resName};
    $res_vsite_quota = $Resellers->{$resName}->{vsite_quota};
    $newName = $Resellers->{$resName}->{newName};
    $res_vsite_quota_h = $res_vsite_quota/1024 . " MB";

    &debug_msg("Updating Reseller-Account $newName to be able to use $res_vsite_quota_h diskspace for Vsites.\n");

    # Handle Reseller limits:
    $User->{$newName}->{Sites} = {
        "quota" => $res_vsite_quota,
        "user" => '1000',
        "max" => '100',
        };
}

foreach $resName ( keys %{ $Resellers } ) {
    $resData = $Resellers->{$resName};
    $res_vsite_quota = $Resellers->{$resName}->{vsite_quota};
    $newName = $Resellers->{$resName}->{newName};
    $res_vsite_quota_h = $res_vsite_quota/1024 . " MB";

    &debug_msg("Updating Reseller-Account $newName to be able to use $res_vsite_quota_h diskspace for Vsites.\n");

    # Handle Reseller limits:
    $User->{$newName}->{Sites} = {
        "quota" => $res_vsite_quota,
        "user" => '1000',
        "max" => '100',
        };
}

&debug_msg("\n");

foreach $fqdn ( keys %{ $Vsite } ) {
    # Get quota owner of Vsite:
    if ($Vsite->{$fqdn}->{Disk}->{quota} eq "") {
        $q_owner = $Vsite->{$fqdn}->{PHP}->{prefered_siteAdmin};
        # Assign Vsite as much quota as the siteAdmin has:
        $Vsite->{$fqdn}->{Disk}->{quota} = $User->{$q_owner}->{Disk}->{quota};
        $res_vsite_quota_i = $User->{$q_owner}->{Disk}->{quota}/1024 . " MB";

        if (($fqdn eq $vhost_server_name) || ($Vsite->{$fqdn}->{Disk}->{quota} eq "")) {
            $Vsite->{$fqdn}->{Disk}->{quota} = $vsite_def_quota;
            $res_vsite_quota_i = $vsite_def_quota/1024 . " MB";
        }

        &debug_msg("INFO: Setting Vsite '$fqdn' disk quota to of $res_vsite_quota_i\n");
    }
}

#
### Count the number of Users per Vsite and adjust Vsite's 'maxuser' accordingly:
#

&debug_msg("\n");

foreach $u ( keys %{ $User } ) {
    if ($User->{$u}->{site}) {
        unless ($maxuser_sitelist->{$User->{$u}->{site}}) {
            $maxuser_sitelist->{$User->{$u}->{site}} = '0';
        }
        $maxuser_sitelist->{$User->{$u}->{site}}++;
    }
}
foreach $v ( keys %{ $maxuser_sitelist } ) {
    # If a Vsite has a 'maxuser' setting less then the number of actual Users,
    # then bump it to at least the number of Users of that Vsite that we counted:
    #print "maxuser $v $Vsite->{$v}->{maxusers} - $maxuser_sitelist->{$v} \n";
    if ($Vsite->{$v}->{maxusers} < $maxuser_sitelist->{$v}) {
        &debug_msg("INFO: Raising 'maxuser' Setting of Vsite '$v' to $maxuser_sitelist->{$v}\n");
        $Vsite->{$v}->{maxusers} = $maxuser_sitelist->{$v};
    }
}

#
### Cleanup:
#

# Remove any keyless entry:
delete($Vsite->{''});
delete($User->{''});
delete($Vsite_Extra->{''});
delete($User_Extra->{''});
delete($Resellers->{''});

#
### Check for illegal User names:
#

&debug_msg("\n##################################################################\n");
&debug_msg("Ckecking if any User-Name is not allowed on BlueOnyx: \n");
&debug_msg("##################################################################\n\n");

foreach $account ( keys %{ $User } ) {
    $user = $User->{$account};
    $userName = $user->{name};

    # Sanity check:
    if ($userName eq "") {
        delete $User->{$account}->{name};
        next;
    }

    # Dissalow reserved usernames and such that don't start with a letter (or aren't all lowercase):
    if (($illegal_usernames{$userName}) || ($userName !~ /^[a-z][a-z0-9_.-]{0,30}$/)) {
        &debug_msg("WARNING: Username '$userName' is not allowed on BlueOnyx!\n");
        # Create a new random name:
        $RandUsername = 'na_' . $userName . "_" . join '', map +(0..9,'a'..'z')[rand(10+26*2)], 1..12;
        &debug_msg("WARNING: Changing username '$userName' ('$User->{$userName}->{site}') to '$RandUsername'!\n");
        # Copy User Object to new name:
        $User->{$RandUsername} = $User->{$userName};
        # Change name in that new object:
        $User->{$RandUsername}->{name} = $RandUsername;
        # Delete old User Object with the conflicting name:
        delete $User->{$userName};

        # Make sure he wasn't a 'prefered_siteAdmin':
        if ($Vsite->{$User->{$RandUsername}->{site}}->{PHP}->{prefered_siteAdmin} eq $userName) {
            $Vsite->{$User->{$RandUsername}->{site}}->{PHP}->{prefered_siteAdmin} = $RandUsername;
        }

        # Add email alias that uses the old name (because for an alias that's fine):
        @fixAlias = scalar_to_array($User->{$RandUsername}->{Email}->{aliases});
        push (@fixAlias, $userName);
        uniq(@fixAlias);
        $User->{$RandUsername}->{Email}->{aliases} = array_to_scalar(@fixAlias);
    }
}

#
### Pack up the tarballs:
#

if ($config_only eq '0') {
    &debug_msg("\n##################################################################\n");
    &debug_msg("Generating Vsite Tar-Balls:\n");
    &debug_msg("##################################################################\n\n");

    &debug_msg("Please note: The percentage on completion might show < or > 100%. That's fine.\n\n");

    foreach $vsite ( keys %{ $Vsite_Extra } ) {
        foreach $n_dirs ( keys %{ $Vsite_Extra->{$vsite} } ) {
            if (-d $Vsite_Extra->{$vsite}->{$n_dirs}) {
                &debug_msg("Packing Vsite '$vsite' $n_dirs directory '$Vsite_Extra->{$vsite}->{$n_dirs}' into vsite-$vsite-$n_dirs.tar.gz\n");
                $VsiteTarFile = $export_dir . "vsite-$vsite-$n_dirs.tar.gz";
                if ($BSD eq "1") {
                    system("cd $Vsite_Extra->{$vsite}->{$n_dirs}/; tar czf $VsiteTarFile .");
                }
                else {
                    system("cd $Vsite_Extra->{$vsite}->{$n_dirs}/; tar cf - . --group=nobody --owner=nobody| $pv_bin -s \$(du -sb $Vsite_Extra->{$vsite}->{$n_dirs}/ | awk '{print \$1}') | gzip > $VsiteTarFile");
                }
                $md5sum = `$md5_binary $VsiteTarFile`;
                chomp($md5sum);
                if ($BSD eq "0") {
                    $md5sum =~ s/\s+/:/;
                    @md5_clean = split ':', $md5sum;
                    $final_md5sum = $md5_clean[0];
                    $final_md5sum =~ s/\s+//;
                }
                else {
                    $md5sum =~ s/\s+//;
                    @md5_clean = split '=', $md5sum;
                    $final_md5sum = $md5_clean[1];
                    $final_md5sum =~ s/\s+//;
                }
                $Vsite_Tarballs->{$vsite}->{$n_dirs} = {'fileName' => "vsite-$vsite-$n_dirs.tar.gz", 'md5sum' => $final_md5sum};
                if ($scp_files_directly eq "1") {
                    &debug_msg("Transfering $VsiteTarFile to $scp_target and then deleting local copy:\n");
                    system("scp $VsiteTarFile $scp_target");
                    system("rm -f $VsiteTarFile");
                    &debug_msg("\n");
                }
                else {
                    &debug_msg("\n");
                }
            }
        }
    }
}

if ($config_only eq '0') {
    &debug_msg("\n##################################################################\n");
    &debug_msg("Generating User Tar-Balls:\n");
    &debug_msg("##################################################################\n\n");

    &debug_msg("Please note: The percentage on completion might show < or > 100%. That's fine.\n\n");

    foreach $user ( keys %{ $User_Extra } ) {
        foreach $n_dirs ( keys %{ $User_Extra->{$user} } ) {
            if (($n_dirs eq "mbox") && (-f $User_Extra->{$user}->{$n_dirs})) {
                &debug_msg("Packing User '$user' $n_dirs file '$User_Extra->{$user}->{$n_dirs}' into user-$user-$n_dirs.tar.gz\n");
                $UserTarFile = $export_dir . "user-$user-$n_dirs.tar.gz";
                if ($BSD eq "1") {
                    system("cd /var/mail/; tar czf $UserTarFile $user");
                }
                else {
                    system("cd /var/mail/; tar cf - $user --group=nobody --owner=nobody| $pv_bin -s \$(du -sb $User_Extra->{$user}->{$n_dirs} | awk '{print \$1}') | gzip > $UserTarFile");
                }
                $md5sum = `$md5_binary $UserTarFile`;
                chomp($md5sum);
                if ($BSD eq "0") {
                    $md5sum =~ s/\s+/:/;
                    @md5_clean = split ':', $md5sum;
                    $final_md5sum = $md5_clean[0];
                    $final_md5sum = $md5_clean[0];
                    $final_md5sum =~ s/\s+//;
                }
                else {
                    $md5sum =~ s/\s+//;
                    @md5_clean = split '=', $md5sum;
                    $final_md5sum = $md5_clean[1];
                    $final_md5sum =~ s/\s+//;
                }
                $User_Tarballs->{$user}->{$n_dirs} = {'fileName' => "user-$user-$n_dirs.tar.gz", 'md5sum' => $final_md5sum};
                if ($scp_files_directly eq "1") {
                    &debug_msg("Transfering $UserTarFile to $scp_target and then deleting local copy:\n");
                    system("scp $UserTarFile $scp_target");
                    system("rm -f $UserTarFile");
                    &debug_msg("\n");
                }
                else {
                    &debug_msg("\n");
                }
            }
            else {
                if (-d $User_Extra->{$user}->{$n_dirs}) {
                    # Reseller-Check (as we want to ignore the web directories inside his homedir!):
                    if (defined $Resellers->{$user}) {
                        @res_domains = scalar_to_array($Resellers->{$user}->{domains});
                        $exclude_line = "--exclude='mstauber' ";
                        if (scalar(@res_domains) gt "0") {
                            foreach $rd (@res_domains) {
                                $exclude_line .= "--exclude='$rd' ";
                            }
                        }
                    }
                    # Exclude the following for anyone:
                    $exclude_line .= "--exclude='.procmailrc' --exclude='.cpx' --exclude='.spamassassin' ";
                    &debug_msg("Packing User '$user' $n_dirs directory '$User_Extra->{$user}->{$n_dirs}' into user-$user-$n_dirs.tar.gz\n");
                    $UserTarFile = $export_dir . "user-$user-$n_dirs.tar.gz";
                    if ($BSD eq "1") {
                        system("cd $User_Extra->{$user}->{$n_dirs}/; tar czf $UserTarFile $exclude_line .");
                    }
                    else {
                        system("cd $User_Extra->{$user}->{$n_dirs}/; tar cf - . $exclude_line --group=nobody --owner=nobody| $pv_bin -s \$(du -sb $User_Extra->{$user}->{$n_dirs}/ | awk '{print \$1}') | gzip > $UserTarFile");
                    }
                    $md5sum = `$md5_binary $UserTarFile`;
                    chomp($md5sum);
                    if ($BSD eq "0") {
                        $md5sum =~ s/\s+/:/;
                        @md5_clean = split ':', $md5sum;
                        $final_md5sum = $md5_clean[0];
                        $final_md5sum =~ s/\s+//;
                    }
                    else {
                        $md5sum =~ s/\s+//;
                        @md5_clean = split '=', $md5sum;
                        $final_md5sum = $md5_clean[1];
                        $final_md5sum =~ s/\s+//;
                    }
                    $User_Tarballs->{$user}->{$n_dirs} = {'fileName' => "user-$user-$n_dirs.tar.gz", 'md5sum' => $final_md5sum};
                    if ($scp_files_directly eq "1") {
                        &debug_msg("Transfering $UserTarFile to $scp_target and then deleting local copy:\n");
                        system("scp $UserTarFile $scp_target");
                        system("rm -f $UserTarFile");
                        &debug_msg("\n");
                    }
                    else {
                        &debug_msg("\n");
                    }
                }
            }
        }
    }
    &debug_msg("\n");
}
else {
    &debug_msg("\n##################################################################\n");
    &debug_msg("Skipping Generating User Tar-Balls - as requested on command line\n");
    &debug_msg("##################################################################\n\n");
}

#
### Generate XML:
#

&debug_msg("\n##################################################################\n");
&debug_msg("Generating $xml_out XML file.\n");
&debug_msg("##################################################################\n\n");

$Export_Hash->{baseHost} = $vhost_server_name;
$Export_Hash->{DateTime} = strftime('%Y-%m-%d %H:%M:%S',localtime);
$Export_Hash->{Vsite} = $Vsite;
$Export_Hash->{User} = $User;
$Export_Hash->{Vsite_Extra} = $Vsite_Extra;
$Export_Hash->{User_Extra} = $User_Extra;
$Export_Hash->{Resellers} = $Resellers;

if (scalar(keys %{$Vsite_Tarballs}) ne "0") {
    $Export_Hash->{Vsite_Tarballs} = $Vsite_Tarballs;
}
if (scalar(keys %{$User_Tarballs}) ne "0") {
    $Export_Hash->{User_Tarballs} = $User_Tarballs;
}

unless (-d $export_dir) {
    system("mkdir -p $export_dir");
}

$xml = XMLout($Export_Hash, OutputFile => $xml_out, RootName => "migrate");

if ($scp_files_directly eq "1") {
    &debug_msg("Transfering $xml_out to $scp_target and then deleting it locally.\n");
    system("scp $xml_out $scp_target");
    system("rm -f $xml_out");
    &debug_msg("\n");
}


&debug_msg("\n##################################################################\n");
&debug_msg('\o/ All done! \o/' ."\n");
&debug_msg("##################################################################\n\n");

#
### Debugging:
#

#print Dumper($Vsite);
#print Dumper($User);
#print Dumper($Vsite_Extra);
#print Dumper($User_Extra);
#print Dumper($Resellers);
#print Dumper($raw_Users);

#
### Subroutines:
#

sub header {
    print "##################################################################### \n";
    print "# verio-to-blueonyx-exporter.pl: BlueOnyx Generic Migration Utility #\n";
    print "#####################################################################\n";
    print "# \n";
    print "# Verio (FreeBSD and CentOS) to BlueOnyx 5209R exporter.\n";
    print "# This utility parses the Apache and Sendmail config (and \n";
    print "# cpx.conf) to generate an XML file and Tarballs that allow\n";
    print "# you to import all Vsites and Users on BlueOnyx 5209R.\n\n";

}

sub help {
    $error = shift || "";
    &header;
    if ($error) {
        print "ERROR: $error\n\n";
    }
    print "usage:   verio-export-to-blueonyx.pl [OPTION]\n";
    print "         -c export configuration only\n";
    print "         -d export dir (assumes $export_dir) unless specified\n";
    print "         -s SCP target details (like 'root\@host:/dir/')\n";
    print "         -h help, this help text\n\n";
    exit(0);
}

sub debug_msg {
    if ($DEBUG eq "1") {
        $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
    if ($DEBUG eq "2") {
        my $msg = shift;
        print $msg;
    }
}

sub is_array {
    my ($ref) = @_;
    # Firstly arrays need to be references, throw
    #  out non-references early.
    return 0 unless ref $ref;

    # Now try and eval a bit of code to treat the
    #  reference as an array.  If it complains
    #  in the 'Not an ARRAY reference' then we're
    #  sure it's not an array, otherwise it was.
    eval {
        my $a = @$ref;
    };
    if ($@=~/^Not an ARRAY reference/) {
        return 0;
    }
    elsif ($@) {
        die "Unexpected error in eval: $@\n";
    }
    else {
        return 1;
    }
}

sub in_array {
    my ($arr,$search_for) = @_;
    my %items = map {$_ => 1} @$arr; # create a hash out of the array values
    return (exists($items{$search_for}))?1:0;
}

# pack and unpack arrays
sub array_to_scalar {
    my $scalar = "&";
    if ($_ eq "undef") {
        next;
    }
    while (defined($_ = shift)) {
        $scalar .= $_ . '&';
    }
    if ($scalar eq "&") { 
        $scalar = ""; # special case
    }
    return $scalar;
}

sub scalar_to_array {
    my $scalar = shift || "";
    $scalar =~ s/^&//;
    $scalar =~ s/&$//;
    my @data = split(/&/, $scalar);
    for ($i = 0; $i <= $#data; $i++) {
        $data[$i] =~ s/\+/ /g;
        $data[$i] =~ s/%([0-9a-fA-F]{2})/chr(hex($1))/ge;
    }
    return @data;
}

sub root_check {
    my $id = `id -u`;
    chomp($id);
    if ($id ne "0") {
        #print "$0 must be run by user 'root'!\n\n";
        &help("$0 must be run by user 'root'!");
    }
}

#sub uniq {
#    my %seen;
#    grep !$seen{$_}++, @_;
#}

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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
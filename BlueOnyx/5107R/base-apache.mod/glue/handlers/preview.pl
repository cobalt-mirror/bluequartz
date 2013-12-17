#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: preview.pl
# handle the preview for server virtualhost
#

use CCE;
use Sauce::Config;
use Sauce::Util;
use Base::Httpd qw(httpd_add_include httpd_remove_include);
use Base::Httpd;
use FileHandle;
use Getopt::Long;

my $preview_conf = 'preview';
my $CMDLINE = 0;
my $DEBUG = 0;
my $aliases = '';

GetOptions('cmdline', \$CMDLINE, 'debug', \$DEBUG);

my $cce = new CCE;

if ($CMDLINE) 
{
    $cce->connectuds();
} 
else 
{
    $cce->connectfd();
}

my ($oid) = $cce->find('System');
my ($ok, $obj) = $cce->get($oid);

# Get "System" . "Web":
my ($ok, $objWeb) = $cce->get($oid, 'Web');

# HTTP and SSL ports:
$httpPort = "80";
if ($objWeb->{'httpPort'}) {
    $httpPort = $objWeb->{'httpPort'};
}
$sslPort = "443";
if ($objWeb->{'sslPort'}) {
    $sslPort = $objWeb->{'sslPort'};
}

my $param;

$param->{'fqdn'} = $obj->{'hostname'} . '.' . $obj->{'domainname'};

if (! -f "/proc/user_beancounters") {
    ($oid) = $cce->findx('Network', { 'device' => 'eth0' });
}
else {
    ($oid) = $cce->findx('Network', { 'device' => 'venet0:0' });
}
($ok, $obj) = $cce->get($oid);
$param->{'ipaddr'} = $obj->{'ipaddr'};

my @preview_sites = $cce->find('VirtualHost', {'site_preview' => 1});
my $documentRoot = '';
for my $oid (@preview_sites) {
    my ($ok, $vhost) = $cce->get($oid);
    my ($void) = $cce->find('Vsite', {'name' => $vhost->{name}});
    my ($ok, $vsite) = $cce->get($void);
    if (!$defaultRoot) {
        $documentRoot = $vhost->{documentRoot};
    }

    my $script_conf = '';
    my ($ok, $php) = $cce->get($void, 'PHP');
    my ($ok, $cgi) = $cce->get($void, 'CGI');
    my ($ok, $ssi) = $cce->get($void, 'SSI');
    if ($php->{enabled}) {
        $script_conf .= "AddType application/x-httpd-php .php4\nAddType application/x-httpd-php .php\n";
    }
    if ($cgi->{enabled}) {
        $script_conf .= "AddHandler cgi-wrapper .cgi\nAddHandler cgi-wrapper .pl\n";
    }
    if ($ssi->{enabled}) {
        $script_conf .= "AddHandler server-parsed .shtml\nAddType text/html .shtml\n";
    }

    $aliases .=<<END;
Alias /$vhost->{fqdn}/ $vhost->{documentRoot}/
<Directory $vhost->{documentRoot}>
$script_conf
</Directory>
END
}
$param->{documentRoot} = $documentRoot;

# make sure the directory exists before trying to edit the file
if (!-d $Base::Httpd::vhost_dir)
{
    Sauce::Util::makedirectory($Base::Httpd::vhost_dir);
    Sauce::Util::chmodfile(0751, $Base::Httpd::vhost_dir);
}

my $conf = $Base::Httpd::vhost_dir . '/' . $preview_conf;
if (!Sauce::Util::editfile(
        $conf, *edit_preview, $param, $aliases))
{
    $cce->bye('FAIL', '[[base-apache.cantEditVhost]]');
    exit(1);
}

Sauce::Util::chmodfile(0644, $conf);

system("/bin/rm -f $conf.backup.*");

# perview config moves to last line
$ok = httpd_remove_include("$Base::Httpd::vhost_dir/preview");
$ok = httpd_add_include("$Base::Httpd::vhost_dir/preview");
system("/bin/rm -f $Base::Httpd::httpd_conf_file.backup.*");
if (not $ok) {
        $cce->bye('FAIL', '[[base-apache.cantEditHttpdConf]]');
        exit(1);
}

$cce->bye('SUCCESS');
exit(0);


sub edit_preview
{
    my ($in, $out, $param, $aliases) = @_;

# Handle cases where IP is missing (rare, but can happen):
if (!$param->{ipaddr}) {
	$param->{ipaddr} = "127.0.0.1"; 
}

    my $preview_conf =<<END;
# /etc/httpd/conf/vhost/preview
NameVirtualHost $param->{ipaddr}:$httpPort
<VirtualHost $param->{ipaddr}:$httpPort>
ServerName $param->{fqdn}
DocumentRoot /var/www/html
RewriteEngine On
RewriteCond %{HTTP_HOST}                ^([^:]+)
RewriteCond %{DOCUMENT_ROOT}            !-d
RewriteRule .*                          http://%1:444/error/forbidden.html [L,R]
RewriteCond %{HTTP_HOST}                ^([^:]+)
RewriteRule ^/admin/?\$                 http://%1:444/login.php [L,R]
RewriteCond %{HTTP_HOST}                ^([^:]+)
RewriteRule ^/siteadmin/?\$             http://%1:444/login.php [L,R]
RewriteCond %{HTTP_HOST}                ^([^:]+)
RewriteRule ^/personal/?\$              http://%1:444/login.php [L,R]
RewriteCond %{HTTP_HOST}                ^([^:]+)
RewriteRule ^/login/?\$                 http://%1:444/login.php [L,R]

<Directory /var/www/html>
Options +MultiViews
</Directory>

Alias /no-vhost-error/ /usr/sausalito/ui/web/error/
ErrorDocument 401 /no-vhost-error/authorizationRequired.html
ErrorDocument 403 /no-vhost-error/forbidden.html
ErrorDocument 404 /no-vhost-error/fileNotFound.html
ErrorDocument 500 /no-vhost-error/internalServerError.html
$aliases
</VirtualHost>
END

    print $out $preview_conf;

    return 1;
}

# 
# Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without modification, 
# are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation and/or 
# other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
# 
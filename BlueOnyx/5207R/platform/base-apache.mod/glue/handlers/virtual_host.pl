#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: virtual_host.pl
# handle the creation of configuration files for individual vhosts
#

use CCE;
use Sauce::Config;
use Sauce::Util;
use Base::Httpd qw(httpd_get_vhost_conf_file);
use FileHandle;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
        &debug_msg("Debugging enabled for virtual_host.pl\n");
}

my $cce = new CCE;

$cce->connectfd();

my $vhost = $cce->event_object();
my $vhost_new = $cce->event_new();
my $vhost_old = $cce->event_old();

# write SSL configuration in /etc/httpd/conf/vhosts/siteX, not use mod_perl

my ($void) = $cce->find('Vsite', {'name' => $vhost->{name}});
my ($ok, $vobj) = $cce->get($void);
$vhost->{basedir} = $vobj->{basedir};
my ($ok, $ssl) = $cce->get($void, 'SSL');
$vhost->{ssl_expires} = $ssl->{expires};

# Get "System" . "Web":
my ($oid) = $cce->find('System');
my ($ok, $objWeb) = $cce->get($oid, 'Web');

# HTTP and SSL ports:
$httpPort = "80";
if ($objWeb->{'httpPort'}) {
    $httpPort = $objWeb->{'httpPort'};
}
else {

}
$sslPort = "443";
if ($objWeb->{'sslPort'}) {
    $sslPort = $objWeb->{'sslPort'};
}

$HSTS = '0';
$HSTS_head = "";
$HSTS_tail = "";
$HSTS_line = '';
if ($objWeb->{'HSTS'} == '1') {
    $HSTS = $objWeb->{'HSTS'};
    $HSTS_head = "Header always set Strict-Transport-Security";
    $HSTS_tail = "max-age=15768000";
    $HSTS_line = $HSTS_head . ' "' . $HSTS_tail .';"';
}

&debug_msg("HTTP Port: $httpPort\n");
&debug_msg("SSL Port: $sslPort\n");
&debug_msg("HSTS: $HSTS\n");

# make sure the directory exists before trying to edit the file
if (!-d $Base::Httpd::vhost_dir)
{
    Sauce::Util::makedirectory($Base::Httpd::vhost_dir);
    Sauce::Util::chmodfile(0751, $Base::Httpd::vhost_dir);
}

if (!Sauce::Util::editfile(
        httpd_get_vhost_conf_file($vhost->{name}), 
        *edit_vhost, $vhost))
{
    $cce->bye('FAIL', '[[base-apache.cantEditVhost]]');
    exit(1);
}

Sauce::Util::chmodfile(0644, httpd_get_vhost_conf_file($vhost->{name}));

# create include file
my $include_file = httpd_get_vhost_conf_file($vhost->{name}) . '.include';
if (!-e $include_file) {
    my $fh = new FileHandle(">$include_file");
    if ($fh) {
    print $fh "# ${include_file}\n";
    print $fh "# user customizations can be added here.\n\n";
    $fh->close();
    }
    Sauce::Util::chmodfile(0644, $include_file);
}

$cce->bye('SUCCESS');
exit(0);


sub edit_vhost
{
    my ($in, $out, $vhost) = @_;

    my $user_root = $vhost->{documentRoot};
    $user_root =~ s#web$#users/\$1/web/\$3#;

    my $site_root = $vhost->{documentRoot};
    $site_root =~ s/\/web$//;

    my $include_file = httpd_get_vhost_conf_file($vhost->{name}) . '.include';

    my $aliasRewrite, $aliasRewriteSSL;
    &debug_msg("Before trigger.\n");
    if (($vhost->{webAliases}) && ($vhost->{webAliasRedirects} == "0")) {
        &debug_msg("After trigger.\n");
        my @webAliases = $cce->scalar_to_array($vhost->{webAliases});
        foreach my $alias (@webAliases) {
            &debug_msg("Alias alt: $alias\n");
            $alias =~ s/^\*/\\*/g;
            &debug_msg("Alias neu: $alias\n");
            $aliasRewrite .= "RewriteCond %{HTTP_HOST}                !^$alias(:$httpPort)?\$ [NC]\n";
            $aliasRewriteSSL .= "RewriteCond %{HTTP_HOST}                !^$alias(:$sslPort)?\$ [NC]\n";
        }
    }

    &debug_msg("Editing Vhost container for $vhost->{fqdn} and using Port $httpPort\n");

    my $vhost_conf =<<END;
# owned by VirtualHost
NameVirtualHost $vhost->{ipaddr}:$httpPort

# ServerRoot needs to be set. Otherwise all the vhosts 
# need to go in httpd.conf, which could get very large 
# since there could be thousands of vhosts:
ServerRoot $Base::Httpd::server_root

<VirtualHost $vhost->{ipaddr}:$httpPort>
ServerName $vhost->{fqdn}
ServerAdmin $vhost->{serverAdmin}
DocumentRoot $vhost->{documentRoot}
ErrorDocument 401 /error/401-authorization.html
ErrorDocument 403 /error/403-forbidden.html
ErrorDocument 404 /error/404-file-not-found.html
ErrorDocument 500 /error/500-internal-server-error.html
RewriteEngine on
RewriteCond %{HTTP_HOST}                !^$vhost->{ipaddr}(:$httpPort)?\$
RewriteCond %{HTTP_HOST}                !^$vhost->{fqdn}(:$httpPort)?\$ [NC]
$aliasRewrite
RewriteRule ^/(.*)                      http://$vhost->{fqdn}/\$1 [L,R=301]
RewriteOptions inherit
AliasMatch ^/~([^/]+)(/(.*))?           $user_root
Include $include_file
</VirtualHost>
END

    # write SSL config
    my $cafile;
    if ($vhost->{ssl} && $vhost->{ssl_expires}) {
        if (-f "$vhost->{basedir}/certs/ca-certs")
        {
            $cafile = "SSLCACertificateFile $vhost->{basedir}/certs/ca-certs";
        }

        $vhost_conf .=<<END;

NameVirtualHost $vhost->{ipaddr}:$sslPort
<VirtualHost *:$sslPort>
SSLengine on
SSLProtocol +ALL -SSLv2
SSLHonorCipherOrder On
SSLCipherSuite DHE+AES256-CBC-SHA:AES256-GCM-SHA:AES128-GCM-SHA256:HIGH:!aNULL:!eNULL:!EXP:!LOW:!MD5:!RC4
$HSTS_line
$cafile
SSLCertificateFile $vhost->{basedir}/certs/certificate
SSLCertificateKeyFile $vhost->{basedir}/certs/key
ServerName $vhost->{fqdn}
ServerAdmin $vhost->{serverAdmin}
DocumentRoot $vhost->{documentRoot}
ErrorDocument 401 /error/401-authorization.html
ErrorDocument 403 /error/403-forbidden.html
ErrorDocument 404 /error/404-file-not-found.html
ErrorDocument 500 /error/500-internal-server-error.html
RewriteEngine on
RewriteCond %{HTTP_HOST}                !^$vhost->{ipaddr}(:$sslPort)?\$
RewriteCond %{HTTP_HOST}                !^$vhost->{fqdn}(:$sslPort)?\$ [NC]
$aliasRewriteSSL
RewriteRule ^/(.*)                      https://$vhost->{fqdn}/\$1 [L,R=301]
RewriteOptions inherit
AliasMatch ^/~([^/]+)(/(.*))?           $user_root
Include $include_file
</VirtualHost>
END
    }

    # Explanation on the SSL Ciphers SSL Protocol and SSL CipherSuites: The fuckers at RedHat had 
    # crippled OpenSSL so that elliptic curve ciphers were missing. Go figure. Honest broker? My ass!
    #
    # They added some of them back in RHEL6.5. See: https://bugzilla.redhat.com/show_bug.cgi?id=319901
    # As far as Apache is concerned, the ECDHE ciphers *still* do not work. For that we would need Apache 2.4.
    # On the bright side, TLSv1.2 is finally working with this version of OpenSSL.
    # 
    # For securing HTTPS we want to achieve the following:
    # - Do not use weak or comprimised ciphers: OK (as best as possible)
    # - Use Strict Transport Security (HSTS): OK, but make it optional, as it can be a pain in the ass.
    # - Support Forward Secrecy (PFS) for as many browsers as possible: OK, but fail for Internet Explorer.
    #
    # With ECDHE not available (which we'd need to get PFS working with IE browsers), we fall back to DHE,
    # which allows Forward Secrecy on all browsers. Execept the ones from Microsoft. It's the next best 
    # thing below ECDHE and if someone wants to use any version of IE, then I don't care about these lemmings.
    #
    # Most Microsoft browsers post Windows XP's IE will use either TLS_RSA_WITH_AES_256_CBC_SHA instead.
    # Chrome, Firefox, Opera and Safari will almost all use TLS_DHE_RSA_WITH_AES_256_CBC_SHA instead, which 
    # allows Forward Secrecy. Anything but IE6/XP, IE8/XP, Java 6u45 and Java 7u25 (which will use 168bit keys)
    # will use 256bit keys instead. 
    #
    # Protocols: Only IE6/XP will use SSLv3. All the rest default to TLS1.0 or TLSv1.2.
    #
    # Ciphers: RC4 and other weak ciphers have been disabled.
    #
    # As we now support SNI, some browsers or Robots are left out as far as HTTPS is concerned. 
    #
    # That includes:
    #
    # - Android 2.3.7       No SNI
    # - BingBot Dec 2013    No SNI
    # - IE 6 / XP           No SNI
    # - IE 8 / XP           No SNI
    # - Java 6u45           No SNI
    # 
    # Which is not really our problem. We're not sacrificing our security for fuckers that haven't
    # heard the shot yet and who can't be assed to use a more recent OS.
    #
    # Further reading: https://bettercrypto.org/static/applied-crypto-hardening.pdf
    # But note: Their suggested cipher-string and ours are different. Despite that we
    # achieve the same results for all browsers and also retained compatibility with IE6/XP
    # and IE8/XP until we went all out for SNI. Since then it's bye, bye for IE users on XP.

    # append line marking the end of the section specifically owned by the VirtualHost
    my $end_mark = "# end of VirtualHost owned section\n";
    $vhost_conf .= $end_mark;

    my $conf_printed = 0;

    while (<$in>)
    {
        if (!$conf_printed && /^$end_mark$/)
        {
            print $out $vhost_conf;
            &debug_msg("Done1: Editing Vhost container for $vhost->{fqdn} and using Port $httpPort\n");
            $conf_printed = 1;
        }
        elsif ($conf_printed)
        {
            # print out information entered by other objects
            print $out $_;
            &debug_msg("Done2: Editing Vhost container for $vhost->{fqdn} and using Port $httpPort\n");
        }
    }

    if (!$conf_printed)
    {
        print $out $vhost_conf;
    }

    return 1;
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

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
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
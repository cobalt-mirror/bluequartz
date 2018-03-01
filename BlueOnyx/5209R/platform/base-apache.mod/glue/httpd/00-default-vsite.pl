#!/usr/bin/perl
#
# This script creates <VirtualHost> containers for default sites on the fly.
# It creates *-placeholder sites and one for each bound IPv4 and IPv6 IP.
# For the HTTPS VirtualHosts it uses the AdmServ SSL certificate.
#

$DEBUG = "0";

use Apache2::ServerUtil();
use lib qw(/usr/sausalito/perl);

use CCE;

$cce = new CCE;
$cce->connectuds();

($oid) = $cce->find('System');
($ok, $System) = $cce->get($oid);

# Get "System" . "Web":
($ok, $objWeb) = $cce->get($oid, 'Web');

# Get "System" . "SSL":
($ok, $objSSL) = $cce->get($oid, 'SSL');

# Find out if we do have a CA-Cert:
@ca_certs = $cce->scalar_to_array($objSSL->{caCerts});
$CALine = '';
if (scalar(@ca_certs) > 0) {
	# We do have intermediate(s). Use it:
	$CALine = 'SSLCACertificateFile  /etc/admserv/certs/ca-certs';
}

# Server-Name:
$ServerName = $System->{hostname} . '.' . $System->{domainname};

# HTTP and SSL ports:
$httpPort = "80";
if ($objWeb->{'httpPort'}) {
    $httpPort = $objWeb->{'httpPort'};
}
$sslPort = "443";
if ($objWeb->{'sslPort'}) {
    $sslPort = $objWeb->{'sslPort'};
}

# Set up default error pages:
if (!-d '/var/www/html/error') {
	system("mkdir /var/www/html/error");
	system("cp /etc/skel/vsite/en/web/error/*.html /var/www/html/error/")
}

# Get all IPv4 IPs:
@arr_assigned_ipv4 = split (/\n/, `LC_ALL=C /sbin/ip address show |grep inet|grep global|awk -F "inet " '{print \$2}'|awk -F " brd " '{print \$1}'|cut -d / -f1|sed '/^\$/d'`);
@arr_assigned_ipv6 = split (/\n/, `LC_ALL=C /sbin/ip address show |grep inet|grep global|awk -F "inet6 " '{print \$2}'|awk -F " brd " '{print \$1}'|cut -d / -f1|sed '/^\$/d'`);

# Generate config on the fly:
$config = '';
$config = <<CONFIG;
    #NameVirtualHost *:$httpPort
    <VirtualHost *:$httpPort>
        ServerName $ServerName
        ServerAdmin admin
        DocumentRoot /var/www/html/
        ErrorDocument 401 /error/401-authorization.html
        ErrorDocument 403 /error/403-forbidden.html
        ErrorDocument 404 /error/404-file-not-found.html
        ErrorDocument 500 /error/500-internal-server-error.html
    </VirtualHost>

	<VirtualHost *:$sslPort>
		ServerName $ServerName
		SSLengine on
		SSLCompression off
		SSLProtocol TLSv1.2 +TLSv1.1
		SSLHonorCipherOrder On
		SSLCipherSuite HIGH:!LOW:!SEED:!DSS:!SSLv2:!aNULL:!eNULL:!NULL:!EXPORT:!ADH:!IDEA:!ECDSA:!3DES:!DES:!MD5:!PSK:!RC4:@STRENGTH
		# Server Certificate:
		$CALine
		SSLCertificateFile    /etc/admserv/certs/certificate
		SSLCertificateKeyFile /etc/admserv/certs/key
		ServerAdmin admin
		DocumentRoot /var/www/html
		ErrorDocument 401 /error/401-authorization.html
		ErrorDocument 403 /error/403-forbidden.html
		ErrorDocument 404 /error/404-file-not-found.html
		ErrorDocument 500 /error/500-internal-server-error.html
	</VirtualHost>
CONFIG

foreach $x (@arr_assigned_ipv4) {
	$config .= <<CONFIG;
    <VirtualHost $x:$httpPort>
        ServerName $ServerName
        ServerAdmin admin
        DocumentRoot /var/www/html/
        ErrorDocument 401 /error/401-authorization.html
        ErrorDocument 403 /error/403-forbidden.html
        ErrorDocument 404 /error/404-file-not-found.html
        ErrorDocument 500 /error/500-internal-server-error.html
    </VirtualHost>

	<VirtualHost $x:$sslPort>
		ServerName $ServerName
		SSLengine on
		SSLCompression off
		SSLProtocol TLSv1.2 +TLSv1.1
		SSLHonorCipherOrder On
		SSLCipherSuite HIGH:!LOW:!SEED:!DSS:!SSLv2:!aNULL:!eNULL:!NULL:!EXPORT:!ADH:!IDEA:!ECDSA:!3DES:!DES:!MD5:!PSK:!RC4:@STRENGTH
		# Server Certificate:
		$CALine
		SSLCertificateFile    /etc/admserv/certs/certificate
		SSLCertificateKeyFile /etc/admserv/certs/key
		ServerAdmin admin
		DocumentRoot /var/www/html
		ErrorDocument 401 /error/401-authorization.html
		ErrorDocument 403 /error/403-forbidden.html
		ErrorDocument 404 /error/404-file-not-found.html
		ErrorDocument 500 /error/500-internal-server-error.html
	</VirtualHost>
CONFIG
}

foreach $z (@arr_assigned_ipv6) {
	$config .= <<CONFIG;
    <VirtualHost [$z]:$httpPort>
        ServerName $ServerName
        ServerAdmin admin
        DocumentRoot /var/www/html/
        ErrorDocument 401 /error/401-authorization.html
        ErrorDocument 403 /error/403-forbidden.html
        ErrorDocument 404 /error/404-file-not-found.html
        ErrorDocument 500 /error/500-internal-server-error.html
    </VirtualHost>

	<VirtualHost [$z]:$sslPort>
		ServerName $ServerName
		SSLengine on
		SSLCompression off
		SSLProtocol TLSv1.2 +TLSv1.1
		SSLHonorCipherOrder On
		SSLCipherSuite HIGH:!LOW:!SEED:!DSS:!SSLv2:!aNULL:!eNULL:!NULL:!EXPORT:!ADH:!IDEA:!ECDSA:!3DES:!DES:!MD5:!PSK:!RC4:@STRENGTH
		# Server Certificate:
		$CALine
		SSLCertificateFile    /etc/admserv/certs/certificate
		SSLCertificateKeyFile /etc/admserv/certs/key
		ServerAdmin admin
		DocumentRoot /var/www/html
		ErrorDocument 401 /error/401-authorization.html
		ErrorDocument 403 /error/403-forbidden.html
		ErrorDocument 404 /error/404-file-not-found.html
		ErrorDocument 500 /error/500-internal-server-error.html
	</VirtualHost>
CONFIG
}

# For debugging:
if ($DEBUG) {
	open($fh, '>', '/tmp/httpd.debug');
	print $fh $config;
	close $fh;
}

# Push out config:
$server = Apache2::ServerUtil->server;
$server->add_config([split /\n/, $config]);

1;

# 
# Copyright (c) 2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2018 Team BlueOnyx, BLUEONYX.IT
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#    notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#    notice, this list of conditions and the following disclaimer in 
#    the documentation and/or other materials provided with the 
#    distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#    contributors may be used to endorse or promote products derived 
#    from this software without specific prior written permission.
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

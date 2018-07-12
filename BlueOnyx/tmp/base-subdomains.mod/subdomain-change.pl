#!/usr/bin/perl -I/usr/sausalito/perl
# Initial Author: Brian N. Smith
# $Id: subdomain-change.pl

use Sauce::Config;
use FileHandle;
use File::Copy;
use Sauce::Service;
use CCE;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
        &debug_msg("Debugging enabled for subdomain-change.pl\n");
}

umask(002);

$cce = new CCE;
$cce->connectfd();

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

&debug_msg("HTTP-Port is: $httpPort\n");

## Subdomain config dir:
$subdomain_config_dir = "/etc/httpd/conf.d/subdomains";

## Find  search existing Subdomains to verify this is unquie.
@oids = $cce->findx('Subdomains');
foreach $oid (@oids) {
    ($ok, $subObj) = $cce->get($oid);
    $confPath = $subdomain_config_dir . "/" . $subObj->{'group'} . "." . $subObj->{'hostname'} . ".conf";

    @VSoids = $cce->find('Vsite', { 'name' => $subObj->{'group'} });
    ($ok, $vsite) = $cce->get($VSoids[0]);

    &debug_msg("Processing: $confPath\n");

    # Make the edit:
    umask(0077);
    my $stage = "$confPath~";
    open(HTTPD, "$confPath");
    unlink($stage);
    sysopen(STAGE, $stage, 1|O_CREAT|O_EXCL, 0644) || die;
    $ipaddr = $vsite->{'ipaddr'};
    while(<HTTPD>) {
      s|^#NameVirtualHost.*$|#NameVirtualHost $ipaddr:$httpPort|g;
      s/^<VirtualHost.*$/<VirtualHost $ipaddr:$httpPort>/;
      print STAGE;
    }
    close(STAGE);
    close(HTTPD);

    chmod(0644, $stage);
    if(-s $stage) {
      move($stage,"$confPath");
      chmod(0644, "$confPath"); # paranoia
      $apache_ok = "1";
    } else {
      $apache_ok = "0";
    }
}

service_run_init('httpd', 'reload');

$cce->bye('SUCCESS');
exit(0);

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
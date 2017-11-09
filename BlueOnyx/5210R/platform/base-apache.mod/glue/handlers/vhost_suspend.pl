#!/usr/bin/perl -I/usr/sausalito/perl
#
# $Id: vhost_suspend.pl
#
# Setup rewrite rules when disabling a VirtualHost to give a 403 error, since
# we don't want people bounced to the login page anymore.
#

use CCE;
use Sauce::Util;
use Base::Httpd qw(httpd_get_vhost_conf_file);

my $cce = new CCE;
$cce->connectfd();

my $vhost = $cce->event_object();

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

# rewrite the rewrite rules if site is enabled/disabled
my $ok = 1;
my $conf_file = httpd_get_vhost_conf_file($vhost->{name});
if (!$vhost->{enabled}) {
	$ok = Sauce::Util::editfile($conf_file, *rewrite_rules, 1, $vhost);
} elsif (!$cce->event_is_create()) {
	$ok = Sauce::Util::editfile($conf_file, *rewrite_rules, 0, $vhost);
}

if (!$ok) {
	$cce->bye('FAIL', '[[base-apache.cantEditVhost]]');
	exit(1);
}

$cce->bye('SUCCESS');
exit(0);

sub rewrite_rules
{
	my ($in, $out, $add, $vhost) = @_;

	while (my $line = <$in>) {
		if ($add && ($line =~ /^RewriteEngine\s+on\s*$/)) {
			print $out $line;
			print $out <<NEWRULES;
# BEGIN VHOST SUSPEND RULES
RewriteCond %{REQUEST_URI}		/error/.*
RewriteRule .*				- [L]
RewriteCond %{REQUEST_URI}              /libImage/.*
RewriteRule .*                          - [L]
RewriteCond %{HTTP_HOST}		^$vhost->{ipaddr}(:$httpPort)?\$ [OR]
RewriteCond %{HTTP_HOST}		^$vhost->{fqdn}(:$httpPort)?\$ [NC]
RewriteRule .*				- [L,F]
# END VHOST SUSPEND RULES
NEWRULES

		} elsif (!$add && ($line =~ /^# BEGIN VHOST SUSPEND RULES$/)) {
			# remove suspend rewrite rules
			while (my $trash = <$in>) {
				if ($trash =~ /^# END VHOST SUSPEND RULES$/) {
					last;
				}
			}
		} else {
			# some other line, just print it out
			print $out $line;
		}
	}

	return 1;
}

# 
# Copyright (c) 2017 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2017 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
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
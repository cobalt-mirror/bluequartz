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
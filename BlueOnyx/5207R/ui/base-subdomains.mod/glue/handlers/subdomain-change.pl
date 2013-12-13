#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: subdomain-change.pl

use Sauce::Config;
use FileHandle;
use File::Copy;
use Sauce::Service;
use CCE;

# Debugging switch:
$DEBUG = "1";
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
      s|^NameVirtualHost.*$|NameVirtualHost $ipaddr:$httpPort|g;
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
# Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
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
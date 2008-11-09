#!/usr/bin/perl -w

use lib "/usr/sausalito/perl";
use lib "/usr/sausalito/handlers/base/cache";
use Cache;
use Sauce::Util;
use CCE;
use strict;

my $cce=new CCE(Namespace => "CacheACL");
my $confFile="/etc/squid/squid.conf";
my $blockStart="# -*- COBALT-FILTER-RULES -*-\n";
my $blockEnd="# -*- END-COBALT-FILTER-RULES -*-\n";

$cce->connectfd();
my $obj=$cce->event_object();

my (@ip)=$cce->scalar_to_array($obj->{ipaddr});
my (@domain)=$cce->scalar_to_array($obj->{domains});

my $ip_string = "acl Cips dst ".(join " ",@ip)."\n";
my $domain_string = "acl Cdomains dstdomain ".(join " ",@domain)."\n";
my $mode_string;

if($obj->{mode} eq "whitelist"){
	$mode_string="http_access allow Cips\nhttp_access allow Cdomains\nhttp_access deny all\n";
}elsif($obj->{mode} eq "blacklist"){
	$mode_string="http_access deny Cips\nhttp_access deny Cdomains\nhttp_access deny !Safe_ports\nhttp_access deny CONNECT !SSL_ports\n";
}else{
	$mode_string="http_access deny !Safe_ports\nhttp_access deny CONNECT !SSL_ports\n";
}

my $string=$ip_string.$domain_string.$mode_string;

my $edit=\&editSquid;

Sauce::Util::editfile($confFile,$edit,$blockStart,$string,$blockEnd) ||
($cce->bye("FAIL","[[base-cacheACL.editFile]]") && exit(1));

system("/etc/rc.d/init.d/squid reload > /dev/null 2>&1");
# if (system("/etc/rc.d/init.d/squid reload > /dev/null 2>&1")) {
# 	$cce->bye("FAIL","[[base-cacheACL.restartSquid]]");
# 	exit(1);
# }


$cce->bye("SUCCESS");
exit 0;

sub editSquid{
my($in,$out,$start,$txt,$stop)=@_;

while($_=<$in>){
	if($_ eq $start){
		print $out $start;
		print $out $txt;
		print $out $stop;
		$_=<$in> until $_ eq $stop;
	}else{
		print $out $_;
	}
}
return 1;
}

# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without 
# modification, are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, 
# this list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation  
# and/or other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.

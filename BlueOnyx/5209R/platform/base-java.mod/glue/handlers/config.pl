#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: config.pl,v 1.7 2001/07/14 05:38:40 mpashniak Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# Tomcat configurator
# Will DeHaan <null@sun.com>

my $tomcat_properties = '/etc/tomcat6/server.xml';

my $DEBUG = 0;
$DEBUG && warn $0.' '.`date`;

use CCE;
use Sauce::Util;

my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();

my @oids = $cce->find('System');
my ($ok, $java) = $cce->get($oids[0], "Java");

if($ok) {
	my $ret = Sauce::Util::editfile($tomcat_properties, *edit_policy,
		$java->{maxClients});

	unless($ret) 
	{
		$DEBUG && warn "$0 failing, editfile $tomcat_properties, ".
			$java->{maxClients}." failed.\n";
		# $cce->bye('FAIL');
		# exit(1);
	}
}
else
{
	$cce->bye('FAIL');
	exit(1);
}

$cce->bye('SUCCESS');
exit(0);

# Fin


sub edit_policy
{
        my ($in, $out, $max) = @_;
	my $maxConnect = "               port=\"8009\" minProcessors=\"5\" maxProcessors=\"$max\"\n";
	
	while(<$in>)
	{
		if(/port=\"8009\"(.+)maxProcessors=(.+)/)
		{
			$DEBUG && warn "* Found: $_, using $maxConnect *\n";
			print $out $maxConnect;
		} else {
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

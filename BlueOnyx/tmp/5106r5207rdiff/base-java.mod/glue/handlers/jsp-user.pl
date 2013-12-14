#!/usr/bin/perl
# $Id: jsp-user.pl,v 1.6 2001/11/29 18:58:49 will Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# Jsp parser support for users
# Dependent on Vsite.Java.enabled ? all users parse .jsp : no users parse .jsp
# Will DeHaan <null@sun.com>

use lib qw(/usr/sausalito/perl);
use CCE;
use Sauce::Util;
use Base::HomeDir qw(homedir_get_user_dir);

my $DEBUG = 0;
$DEBUG && warn $0.' '.`date`;
eval('use strict;') if ($DEBUG);

my $tomcat_conf = '/etc/tomcat5/server.xml';

my $cce = new CCE;
$cce->connectfd();

my $object = $cce->event_object();
my $old = $cce->event_old();
my $new = $cce->event_new();

# Establish our user and site group
my $site = $object->{site};
$site = $old->{site} if ($old->{site});
$site = $new->{site} if ($new->{site});

# lookup vsite fqdn
my $vsite_oid = ($cce->find('Vsite', {'name' => $site}))[0];
$DEBUG && warn "Found site oid $vsite_oid corresponding to group: $site";
my ($ok, $vsite) = $cce->get($vsite_oid);
$DEBUG && warn "Found site fqdn: ".$vsite->{fqdn};

# lookup vsite Java
my ($a_ok, $jsp_vsite) = $cce->get($vsite_oid, 'Java');

# Bail if java is not enabled for this user's site
unless($jsp_vsite->{enabled})
{
	# Nothing to do!
	$DEBUG && warn "Java not enabled for siite $site";
	$cce->bye('SUCCESS');
	exit 0;
}

my $ret;
if($cce->event_is_destroy())
{
	# flush user lines from tomcat's server.xml
	$ret = (Sauce::Util::editfile(
		$tomcat_conf,
		*user_xml, 
		0, $old->{name}, $site, $vsite->{fqdn}, $vsite->{volume})
		);
	# vsite ipaddr config (may not apply)
	my $ignore = (Sauce::Util::editfile(
		$tomcat_conf,
		*user_xml, 
		0, $old->{name}, $site, $vsite->{ipaddr}, $vsite->{volume})
		);
        $cce->bye('SUCCESS'); # ignore return values on user delete. 
        exit 0;
}
else
{
	# new user, add line
	# flush user line from tomcat's server.xml
	$ret = (Sauce::Util::editfile(
		$tomcat_conf,
		*user_xml, 
		1, $new->{name}, $site, $vsite->{fqdn}, $vsite->{volume})
		);
	# vsite ipaddr config (may not apply)
	my $ignore = (Sauce::Util::editfile(
		$tomcat_conf,
		*user_xml, 
		1, $new->{name}, $site, $vsite->{ipaddr}, $vsite->{volume})
		);
}

if ($ret)
{
        $cce->bye('SUCCESS'); # ignore return values on site delete. 
        exit 0;
} 
else
{
        $cce->bye('FAIL', '[[base-java.failedUserEnable]]');
        exit 1;
}


### Subs

sub user_xml
{
	my($in, $out, $enabled, $user, $group, $fqdn, $volume) = @_;
	$DEBUG && warn "user_xml invoked: $enabled, $user, $group, $fqdn, $volume\n";

	my $found = 0; # Track return status

	my $docbase = homedir_get_user_dir($user, $group, $volume).'/web';
	my $xml =<<EOF;
	  <Context path="/~$user/" docBase="$docbase" debug="0" reloadable="true" unpackWARs="true" autoDeploy="true"/>
EOF
	$DEBUG && warn "My XML:\n$xml";

	while(<$in>) 
	{
		if(/^\s*<Host name=\"$fqdn\".+\sSite\s+$group\s+/ ... /^\s*<\/Host>/) 
		{
			$DEBUG && warn "WITHIN Vsite $fqdn Host block: $_";

			if(/\"$docbase\"/)
			{
				$DEBUG && warn "Found existing user\n";
				print $out $_ if ($enabled);
				$found = 1;
				next;
			}
			elsif(/^\s*\<\/Host>/)
			{
				$DEBUG && warn "found end of host block";
				print $out $xml if ($enabled);
				$found = 1;
			}
			print $out $_;
		}
		else
		{
			print $out $_;
		}
	}
	return $found;
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

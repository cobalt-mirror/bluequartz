#!/usr/bin/perl
# $Id: jsp-vsite.pl,v 1.27.2.1 2002/03/06 01:54:56 pbaltz Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# Jsp parser support by virtual host
# Will DeHaan <null@sun.com>

my $libexec = '/etc/httpd/libexec';

use lib qw(/usr/sausalito/perl);
use CCE;
use Sauce::Util;
use Base::HomeDir qw(homedir_get_group_dir homedir_get_user_dir);

&link_libexec() unless (-e '/etc/httpd/libexec');

my $DEBUG = 0;
$DEBUG && open(STDERR, ">>/tmp/jsp-vsite");
$DEBUG && warn $0.' '.`date`;
eval('use strict;') if ($DEBUG);

my $tomcat_conf = '/usr/java/jakarta-tomcat/conf/server.xml';
my $tomcat_init = '/etc/rc.d/init.d/tomcat.init';
my $tomcat_policy = '/usr/java/jakarta-tomcat/conf/tomcat.policy';

my $cce = new CCE;
# my $cce = new CCE("Domain" => 'base-java');
$cce->connectfd();

my $object = $cce->event_object();
my $old = $cce->event_old();
my $new = $cce->event_new();
$DEBUG && warn $object->{event_class}."\n";

my($ok, $jsp, $vsite);

($ok, $jsp) = $cce->get($cce->event_oid(), "Java");
unless ($ok)
{
	$DEBUG && warn 'Could not find the event_oid '.$cce->event_oid." namespace Java\n";
	$cce->bye('FAIL');
	exit(1);
}

# Tomcat needs to have deleted sites flushed from it's server.xml config
if($cce->event_is_destroy() && $old->{fqdn})
{
	# FQDN
	my $ret = (Sauce::Util::editfile(
		$tomcat_conf,
		*edit_xml, 
		0, $old->{fqdn}, $old->{name}, '')
		);
	# IP address
	$ret = (Sauce::Util::editfile(
		$tomcat_conf,
		*edit_xml, 
		0, $old->{ipaddr}, $old->{name}, '')
		) if ($old->{ipaddr});

	my $ret2 = (Sauce::Util::editfile(
		$tomcat_policy,
		*edit_policy,
		0, $old->{name}, '')
		);

	# Fetch and destroy deployed .war objects - CCE only, no user data 
	# is involved.
	my(@oids) = $cce->find('JavaWar', {'group' => $old->{name}});
	my $oid;
	foreach $oid (@oids)
	{
		$cce->destroy($oid) if ($oid);
	}

        $cce->bye('SUCCESS'); # ignore return values on site delete. 
        exit 0;
}
else
{
	if($new->{fqdn} && $old->{fqdn}) # Vsite.fqdn change (rename)
		{
		my $ret = (Sauce::Util::editfile(
			$tomcat_conf,
			*rename_xml, 
			$old->{fqdn}, $new->{fqdn})
			);
		$DEBUG && warn 'Renaming fqdn from '.$old->{fqdn}.' to '.$new->{fqdn}.': '.$ret;
       	 if($ret)
		{
			$cce->bye('SUCCESS');
			exit 0;
		} 
		else
		{
			$cce->bye('FAIL', '[[base-java.couldNotRename]]');
			exit 1;
		}
	}
	if($new->{ipaddr} && $old->{ipaddr}) # Vsite.fqdn change (rename)
		{
		my $ret = (Sauce::Util::editfile(
			$tomcat_conf,
			*rename_xml, 
			$old->{ipaddr}, $new->{ipaddr})
			);
		$DEBUG && warn 'Renaming fqdn from '.$old->{ipaddr}.' to '.$new->{ipaddr}.': '.$ret;
       	 if($ret)
		{
			$cce->bye('SUCCESS');
			exit 0;
		} 
		else
		{
			$cce->bye('FAIL', '[[base-java.couldNotRename]]');
			exit 1;
		}
	}
}

# Java enable for a new or existing site

$vsite = $object;

$DEBUG && warn "JSP enabled? ".$jsp->{enabled}."\nfqdn: ".$vsite->{fqdn}.
	"\ngroup: ".$vsite->{name}."\n";


#
# FIXME:  This code is necessary in some form if we ever want jsp and
# servlets to be a per user option, since it adds each current site member
# to the tomcat config file.
# build site userlist
# $DEBUG && warn "Searching for users with site: ".$vsite->{name};
# my ($uoid, @users);
# my (@user_oids) = $cce->find('User', {'site' => $vsite->{name}});
# foreach $uoid (@user_oids)
# {
# 	my($ret, $user_obj) = $cce->get($uoid);
# 	push(@users, $user_obj->{name}) if ($user_obj->{name});
# 	$DEBUG && warn "Found user: ".$user_obj->{name}."\n";
# }

my @users = ();

# FQDN
my $ret = (Sauce::Util::editfile(
	$tomcat_conf,
	*edit_xml, 
	$jsp->{enabled}, $vsite->{fqdn}, $vsite->{name}, $vsite->{volume}, @users)
	);
unless($ret)
{
	$DEBUG && warn "Failed to edit Tomcat XML configuration\n";
 	$cce->bye('FAIL');
 	exit 1;
}
# IP address
$ret = (Sauce::Util::editfile(
	$tomcat_conf,
	*edit_xml, 
	$jsp->{enabled}, $vsite->{ipaddr}, $vsite->{name}, $vsite->{volume}, @users)
	);
unless($ret)
{
	$DEBUG && warn "Failed to edit Tomcat XML configuration\n";
 	$cce->bye('FAIL');
 	exit 1;
}

$DEBUG && warn "Calling edit_policy: enabled? ".$jsp->{enabled}.", site: ".$vsite->{name};
$ret =  (Sauce::Util::editfile(
	$tomcat_policy,
	*edit_policy,
	$jsp->{enabled}, $vsite->{name}, $vsite->{volume})
	);
unless($ret)
{
	$DEBUG && warn "Failed to edit Tomcat Policy configuration\n";
 	$cce->bye('FAIL');
 	exit 1;
}

# Create the http://fqdn/WEB-INF/ directory
my @servlet_dirs = ('/WEB-INF', '/WEB-INF/classes', '/WEB-INF/lib');

my($uid, $gid) = ((getpwnam('admin'))[2], (getgrnam($vsite->{name}))[2]);		
foreach my $sdir (@servlet_dirs) 
{
	my $servlet_dir = $vsite->{basedir}.'/web'.$sdir;
	if ($jsp->{enabled} && (! -d $servlet_dir))
	{
		mkdir($servlet_dir);
		chown($uid, $gid, $servlet_dir);
		chmod(02775, $servlet_dir);

	}
}
	
my $htaccess = $vsite->{basedir}.'/web/WEB-INF/.htaccess';	
open(HTA, ">$htaccess");
print HTA "Options None\nDeny from all\n";
close(HTA);
chown($uid, $gid, $htaccess);
chmod(00664, $htaccess);

$DEBUG && warn "$0 kissing CCE goodbye, SUCCESS\n";
$cce->bye('SUCCESS');
exit 0;


# Subs, less greasy than burgers but still a quick lunch #

sub edit_xml
{
	my($in, $out, $enabled, $fqdn, $group, $volume, @users) = @_;
	$volume ||= '/home';
	my $sitebase = homedir_get_group_dir($group, $volume);

	my $found = 0;
	
	my ($user_xml, $user) = ("          <!-- user web contexts -->\n", undef);
	foreach $user (@users)
	{
		my $user_home = homedir_get_user_dir($user, $group);
		$user_xml .= 
		"          <Context path=\"/~$user\" docBase=\"$user_home/web\" debug=\"0\"/>\n";
	}
	chomp($user_xml);

	my $xml =<<EOF;
	<Host name="$fqdn"> <!-- Site $group -->
	  <Context path="" docBase="$sitebase/web" debug="0"/>
$user_xml
	</Host>
EOF

	$DEBUG && warn "My XML:\n$xml";

	my $skip = 0;
	while(<$in>) 
	{

		# A different site may exist using this site's IP address
		# In this case, we make no changes to the ip-based configuration
		#
		# ** We'll allow duplicate entries by IP address as Tomcat
		#    tolerates it while simplifying config edits when deleting
		#    the site who had first claimed the IP address
		#
		# uncomment the following to enable single-IP configs:
		# if(/^\s*<Host name=\"$fqdn\".+Site\s+(\w+)\s/)
		# {
		# 	$DEBUG && warn "Found common fqdn, group: $1\n$_";
		# 	$skip = 1 if ($1 ne $group);
		# }
		$DEBUG && warn "Skip entry because of site conflict? $skip\n";

		if((/^\s*<Host name=\"$fqdn\".+Site\s+$group\s+/ ... 
		    /^\s*<\/Host>/) && !$skip)
		{
			$DEBUG && warn "WITHIN Vsite $fqdn Host block: $_";

			$found = 1;
			print $out $_ if ($enabled);
			$DEBUG && warn "VSITE OUTPUT: $_";
		}
		elsif(/^\s*<\/ContextManager>/ && $enabled && !$found && !$skip)
		{
			$DEBUG && warn "APPEND: $xml";
			print $out $xml;
			print $out $_;
		} 
		else
		{
			print $out $_;
		}
	}
	return 1;
}



sub edit_policy
{
	my($in, $out, $enabled, $group, $volume) = @_;
	$volume ||= '/home';

	my $basedir = homedir_get_group_dir($group, $volume);
	my $codeBasePath = 'file:'.$basedir.'/web/-';

	my $codeBase = <<EOF;
grant codeBase "$codeBasePath" {
  permission SocketPermission "localhost:1024-", "listen,connect,resolve";
  permission java.util.PropertyPermission "*", "read,write";
  permission java.io.FilePermission "$basedir/-", "read,write,delete";
  permission java.lang.RuntimePermission "accessClassInPackage.sun.io";
};
EOF

	my $found = 0;

	$DEBUG && warn "My codeBase:\n$codeBase";

	while(<$in>) 
	{
		if(/^\s*grant codeBase \"$codeBasePath\"/ ... /^\s*\}\;\s*$/)
		{
			$DEBUG && warn "WITHIN codeBase, neato: $_";
			$found = 1;
			print $out $_ if ($enabled);
		}
		else
		{
			print $out $_;
		}
	}
	$DEBUG && warn "Found in block? $found\n";

	print $out $codeBase if ($enabled && !$found);
	return 1;
}

sub rename_xml
{
	my($in, $out, $old, $new) = @_;

	while(<$in>) 
	{
		if(/^\s*<Host name=\"$old\"/ ... /^\s*<\/Host>/) 
		{
			$DEBUG && warn "Replacing fqdn at: $_";
			s/name=\"$old\"/name=\"$new\"/;
		}
		print $out $_;
	}
	return 1;
}

sub link_libexec
{
	chdir('/etc/httpd');
	symlink('../../usr/lib/apache/', 'libexec');
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

#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
# $Id: vsite_destroy.pl,v 1.21.2.3 2002/02/21 23:53:21 pbaltz Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# largely based on siteDel.pm in turbo_ui
# handle cleaning up when a Vsite is deleted
#

use CCE;
use Vsite;
use Sauce::Util;
use Sauce::Config;
use Base::HomeDir qw(homedir_get_group_dir homedir_create_group_link);
use Base::Group qw(groupdel);

my $DEBUG = 0;

my $cce = new CCE('Domain' => 'base-vsite');

$cce->connectfd();

my ($ok, $vsite);

my ($sysoid) = $cce->find('System');

$vsite = $cce->event_old();

# check if any site members still exist
if (($vsite->{name} ne '') &&
    (scalar($cce->find('User', { 'site' => $vsite->{name} })) > 0)) {
	$cce->bye('FAIL', 'siteMembersFound');
	exit(1);
}

# depopulate dns records
if ($vsite->{dns_auto}) 
{
	my @dns_records = $cce->find('DnsRecord', 
			{ 
				'hostname' => $vsite->{hostname}, 
				'domainname' => $vsite->{domain} 
			});

	for my $rec (@dns_records) {
		$cce->destroy($rec);
	}

	# restart dns server
	my $time = time();
	($ok) = $cce->set($sysoid, "DNS", { 'commit' => $time });

	if (not $ok) 
	{
		$cce->warn('[[base-vsite.cantRestartDns]]');
	}
}

my ($vhost_oid) = $cce->find('VirtualHost', { 'name' => $vsite->{name} });
($ok) = $cce->destroy($vhost_oid);
if (!$ok) {
	$cce->bye('FAIL');
	exit(1);
}

# things to do if this is the last vsite using this IP
unless (scalar($cce->find("Vsite", { 'ipaddr' => $vsite->{ipaddr} })))
{
	# find network object and disable and destroy
	# only do this for aliases though
	my ($network) = $cce->find('Network', 
						{ 'ipaddr' => $vsite->{ipaddr}, 'real' => 0 });
	if ($network)
	{
		$cce->set($network, '', { 'enabled' => 0 });
		($ok) = $cce->destroy($network);
		if (not $ok)
		{
			$cce->warn('[[base-vsite.cantDestroyNetwork]]');
		}
	}

	# delete ftp virtual host
	my ($oid) = $cce->find('FtpSite', { 'ipaddr' => $vsite->{ipaddr} });
	if ($oid) {
		($ok) = $cce->destroy($oid);
		if (!$ok) {
			$cce->bye('FAIL');
			exit(1);
		}
	}
}

# delete system group
groupdel($vsite->{name});

# delete the home directory for this site
my $base = homedir_get_group_dir($vsite->{name}, $vsite->{volume});

# destroy the command line friendly symlink
my ($site_link, $link_target) = homedir_create_group_link($vsite->{name}, 
										$vsite->{fqdn}, $vsite->{volume});
unlink($site_link);
Sauce::Util::addrollbackcommand("umask 000; /bin/ln -sf \"$link_target\" \"$site_link\"");

$cce->bye('SUCCESS');
exit(0);
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

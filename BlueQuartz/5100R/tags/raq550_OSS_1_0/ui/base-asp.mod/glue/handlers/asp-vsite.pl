#!/usr/bin/perl
# $Id: asp-vsite.pl,v 1.9.2.1 2002/03/20 10:51:32 pbaltz Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# ASP virtual site service
# Will DeHaan <null@sun.com>

my $ASP_home        = "/home/chiliasp";
my $ASP_vhostctrl   = "$ASP_home/INSTALL/vhostctl";
my $ASP_servicename = 'asp-apache-3000';

use lib qw(/usr/sausalito/perl);
use CCE;
use Sauce::Util;
use Base::HomeDir qw(homedir_get_group_dir);

my $DEBUG = 0;
$DEBUG && warn "$0 ".`date`;

#
###

my $cce = new CCE;
$cce->connectfd();

my $vsite = $cce->event_object();
my $old = $cce->event_old();
my($ok, $asp) = $cce->get($cce->event_oid(), 'Asp');
my $enabled = $asp->{enabled};

unless($ok)
{
	$DEBUG && warn "$0 could not find ".$cce->event_oid()." Asp namespace\n";
	$cce->bye('FAIL');
	exit 1;
}

# we only need to call Chili!soft config script when deleting a site.  
if ($cce->event_is_destroy() && $old->{fqdn})
{
	my $fqdn = $old->{fqdn};
	my $ret = system("$ASP_vhostctrl engine=$ASP_home/$ASP_servicename vhost=$fqdn enable=no > /dev/null 2>&1");
	$cce->bye('SUCCESS'); # ignore the system return value on site delete.  
	exit 0;
}

# Find our site name...
my ($group, $fqdn, $vol)  = ($vsite->{name}, $vsite->{fqdn}, $vsite->{volume});

# group name is assigned during create so it may not be there when
# this handler runs
if ($cce->event_is_create() && $group eq '')
{
	$cce->bye('DEFER');
	exit(0);
}

my $doc_root = homedir_get_group_dir($group, $vol). "/web/";

$DEBUG && warn "$enabled vsite-asp control for $fqdn, $group with web root $doc_root\n";

# configure Apache .asp and .asa handlers
if (!Sauce::Util::editfile('/etc/httpd/conf/vhosts/'.$group, 
	*edit_generic_vhost, $asp->{enabled}, $fqdn))
{
	$cce->bye('FAIL', '[[base-apache.cantEditVhost]]');
	exit(1);
}

# per-site casp configuration system call
my $yesno = $enabled ? 'yes' : 'no';
my $ret = system("$ASP_vhostctrl engine=$ASP_home/$ASP_servicename vhost=$fqdn enable=$yesno > /dev/null 2>&1");

if($ret && !$enabled) 
{
	# non-blocking exit for failed disable attempt
	# $cce->bye('FAIL');
	# exit(1);
}

$cce->bye('SUCCESS');
exit(0);

###
#

sub edit_generic_vhost
{
	my ($in, $out, $enabled, $fqdn) = @_;

	my $ASP_home = '/home/chiliasp';

	my $vhost_conf =<<EOF;
AddHandler chiliasp .asp
AddHandler chiliasp .asa
EOF

	# append line marking the end of the section specifically owned by the VirtualHost
	my $end_mark = "# end of VirtualHost owned section\n";

	while (<$in>)
	{
		if (/^\s*<VirtualHost/ ... /^\s*<\/VirtualHost>/) 
		{
			if(/^\s*<\/VirtualHost>/) 
			{
				print $out $vhost_conf if ($enabled);
				print $out $_;
			} 
			elsif ($_ !~ /^\s*AddHandler\s+chiliasp\s+/) 
			{
				print $out $_;
			}
		}
		else
		{
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

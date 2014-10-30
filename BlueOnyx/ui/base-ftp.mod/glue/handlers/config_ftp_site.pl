#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/ftp
# $Id: config_ftp_site.pl 259 Sun 21 Dec 2008 12:01:08 AM EST mstauber $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# config_ftp_site.pl
# largely based on siteMode.pm and siteAdd.pm in turbo_ui
# update the ftp config file vhost entries
#

use CCE;
use ftp;
use Sauce::Util;
use Sauce::Config;
use Base::Vsite;

my $cce = new CCE("Domain" => "base-ftp");
$cce->connectfd();

my $DEBUG = 0;

my ($ok);

my $ftp_site = $cce->event_object();
my $ftp_site_old = $cce->event_old();

# add, modify, or remove the vhost entry
if (!Sauce::Util::editfile(
				ftp::ftp_getconf, 
				*edit_ftpconfig, 
				$ftp_site, $ftp_site_old))
{
	$cce->bye('FAIL', '[[base-ftp.cantUpdateConfig]]');
	exit(1);
}

if (!Sauce::Util::editfile(
				ftp::ftps_getconf, 
				*edit_ftpsconfig, 
				$ftp_site, $ftp_site_old))
{
	$cce->bye('FAIL', '[[base-ftp.cantUpdateConfig]]');
	exit(1);
}

$cce->bye('SUCCESS');
exit(0);

# edit ftp configuration for ftp virtual hosts
sub edit_ftpconfig
{
	my ($in, $out, $ftp_site, $ftp_site_old) = @_;

	my $config_printed = 0;
	my $site_config;

	if ($ftp_site->{enabled})
	{
		$site_config =<<END;
<VirtualHost $ftp_site->{ipaddr}>
	DefaultRoot     / wheel
	DefaultRoot		/ $Base::Vsite::SERVER_ADMIN_GROUP
	DefaultRoot		~/../../.. $Base::Vsite::SITE_ADMIN_GROUP
	DefaultRoot		~ !$Base::Vsite::SITE_ADMIN_GROUP
	AllowOverwrite	on
	DefaultChdir    /web
	DisplayLogin	.ftphelp
END

		# add denyGroups and denyUsers sections
		if ($ftp_site->{denyGroups} || $ftp_site->{denyUsers})
		{
			$site_config .= "\t<Limit LOGIN>\n";
			if ($ftp_site->{denyGroups})
			{
				my @groups = $cce->scalar_to_array($ftp_site->{denyGroups});
				# need one DenyGroup line per group, because proftpd does
				# a logical and of the comma-seperated group info after
				# the DenyGroup directive
				for my $group (@groups)
				{
					# always exclude wheel, so admin doesn't get locked out
					$site_config .= "\t\tDenyGroup $group,!wheel\n";
				}
			}
			if ($ftp_site->{denyUsers})
			{
				my @users = $cce->scalar_to_array($ftp_site->{denyUsers});
				# same as groups, need one DenyUser per user
				for my $user (@users)
				{
					$site_config .= "\t\tDenyUser $user\n";
				}
			}
			$site_config .= "\t</Limit>\n";
		} # end if denyUsers or denyGroups

		my $group = $ftp_site->{anonymousOwner};

		if ($ftp_site->{anonymous})
		{
			my $ftp_dir = $ftp_site->{anonBasedir} . '/ftp';
			$site_config .= ftp::ftp_anonscript(uc($group), 'nobody', 
										$group, $ftp_dir, 
										$ftp_site->{maxConnections});
		}

		$site_config .= "</VirtualHost>\n";
	}

	while (<$in>)
	{
		# skip everything in the vhost section we are looking for
		if (/^<VirtualHost $ftp_site_old->{ipaddr}>$/ ... /^<\/VirtualHost>$/)
		{
			next;
		}

		print $out $_;
	}

	if ($ftp_site->{enabled})
	{
		print $out $site_config;
	}

	return 1;
}

sub edit_ftpsconfig
{
	my ($in, $out, $ftp_site, $ftp_site_old) = @_;

	my $config_printed = 0;
	my $site_config;

	if ($ftp_site->{enabled})
	{
		$site_config =<<END;
<VirtualHost $ftp_site->{ipaddr}>
	DefaultRoot     / wheel
	DefaultRoot		/ $Base::Vsite::SERVER_ADMIN_GROUP
	DefaultRoot		~/../../.. $Base::Vsite::SITE_ADMIN_GROUP
	DefaultRoot		~ !$Base::Vsite::SITE_ADMIN_GROUP
	AllowOverwrite	on
	DefaultChdir    /web
	DisplayLogin	.ftphelp
END

		# add denyGroups and denyUsers sections
		if ($ftp_site->{denyGroups} || $ftp_site->{denyUsers})
		{
			$site_config .= "\t<Limit LOGIN>\n";
			if ($ftp_site->{denyGroups})
			{
				my @groups = $cce->scalar_to_array($ftp_site->{denyGroups});
				# need one DenyGroup line per group, because proftpd does
				# a logical and of the comma-seperated group info after
				# the DenyGroup directive
				for my $group (@groups)
				{
					# always exclude wheel, so admin doesn't get locked out
					$site_config .= "\t\tDenyGroup $group,!wheel\n";
				}
			}
			if ($ftp_site->{denyUsers})
			{
				my @users = $cce->scalar_to_array($ftp_site->{denyUsers});
				# same as groups, need one DenyUser per user
				for my $user (@users)
				{
					$site_config .= "\t\tDenyUser $user\n";
				}
			}
			$site_config .= "\t</Limit>\n";
		} # end if denyUsers or denyGroups

		my $group = $ftp_site->{anonymousOwner};

		if ($ftp_site->{anonymous})
		{
			my $ftp_dir = $ftp_site->{anonBasedir} . '/ftp';
			$site_config .= ftp::ftp_anonscript(uc($group), 'nobody', 
										$group, $ftp_dir, 
										$ftp_site->{maxConnections});
		}

		$site_config .= "	TLSEngine on\n";
		$site_config .= "	TLSLog /var/log/proftpd/tls.log\n";
		$site_config .= "	TLSRequired off\n";
		$site_config .= "	TLSRSACertificateFile /etc/pki/dovecot/certs/dovecot.pem\n";
		$site_config .= "	TLSRSACertificateKeyFile /etc/pki/dovecot/private/dovecot.pem\n";
		$site_config .= "	TLSVerifyClient off\n";
		$site_config .= "	TLSOptions NoCertRequest NoSessionReuseRequired UseImplicitSSL\n";
		$site_config .= "	TLSRenegotiate required off\n";

		$site_config .= "</VirtualHost>\n";
	}

	while (<$in>)
	{
		# skip everything in the vhost section we are looking for
		if (/^<VirtualHost $ftp_site_old->{ipaddr}>$/ ... /^<\/VirtualHost>$/)
		{
			next;
		}

		print $out $_;
	}

	if ($ftp_site->{enabled})
	{
		print $out $site_config;
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

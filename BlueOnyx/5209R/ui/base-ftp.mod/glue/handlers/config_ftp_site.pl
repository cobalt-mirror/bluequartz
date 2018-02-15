#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/ftp
# $Id: config_ftp_site.pl
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

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#	 notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#	 notice, this list of conditions and the following disclaimer in 
#	 the documentation and/or other materials provided with the 
#	 distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#	 contributors may be used to endorse or promote products derived 
#	 from this software without specific prior written permission.
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
#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
# $Id: vsite_create.pl,v 1.41 2001/11/30 02:15:47 pbaltz Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# largely based on siteMod.pm and siteAdd.pm in turbo_ui
# do the initial setup for a vsite like creating the system group
# and creating the home directory for the site

# this is for profiling purposes

use CCE;
use I18n;
use Vsite;
use File::Path;
use Sauce::Util;
use Sauce::Config;
use Base::HomeDir qw(homedir_get_group_dir homedir_create_group_link);
use Base::Group qw(groupadd group_add_members);
# use Base::User;

# debugging flag, set to 1 to turn on logging to STDERR
my $DEBUG = 0;
if ($DEBUG) 
{ 
	use Data::Dumper; 
}

# set umask, otherwise directories get created with the wrong permissions
umask(002);

my $cce = new CCE('Domain' => 'base-vsite');
$cce->connectfd();

my ($ok, $vsite);
my ($sysoid) = $cce->find('System');

$vsite = $cce->event_object();

# first create the system group for this site
my $group_name = &create_system_group($vsite);

if (not $group_name) 
{
	$cce->bye('FAIL', '[[base-vsite.cantAddSystemGroup]]');
	exit(1);
}

my @admins = ('admin');

# add created admin user to group
push @admins, $vsite->{createdUser};

group_add_members($group_name, @admins);

# create link from /home/sites/fqdn to /home/sites/site
# this is just a nice thing for sys admins, it serves no functional purpose
my $site_dir = homedir_get_group_dir($group_name, $vsite->{volume});
&debug_msg("home $site_dir\n");
my ($site_link, $link_target) = homedir_create_group_link($group_name, 
						$vsite->{fqdn}, $vsite->{volume});
&debug_msg("site link $site_link\n");
# make sure the sites directory exists
if (! -d "$vsite->{volume}/sites")
{
	Sauce::Util::makedirectory("$vsite->{volume}/sites", 0755);
}
Sauce::Util::linkfile($link_target, $site_link);

# group has been added to the system
# Define name and basedir to Vsite object
($ok) = $cce->set($cce->event_oid(), '', 
				{ 'name' => $group_name, 'basedir' => $site_dir });
if (not $ok) 
{
	$DEBUG && print STDERR "ok was $ok\n";
	$cce->bye('FAIL', '[[base-vsite.cantSetSiteGroup]]');
	exit(1);
}

# make sure there is a network interface for this ip
vsite_add_network_interface($cce, $vsite->{ipaddr}, $vsite->{createdUser});

my $locale = I18n::i18n_getSystemLocale($cce);
# make the locale sane

# now copy in the default index.html
# need to do this yet, but should think about how this is going to work some
# should this do it the same as monterey? 
# copy over the index.html template
my $site_web = $site_dir . '/' . Sauce::Config::webdir();
my $webindex = "$site_web/index.html";
my $skel_web = &find_skeleton($locale);

$DEBUG && print STDERR "Setting up site web.\n";
Sauce::Util::modifytree($site_web);
system("/bin/cp -r $skel_web/* \"$site_web/.\"");
Sauce::Util::chmodfile(02775, "$site_web/error");
system("/bin/chmod 0664 $site_web/error/*");
system("/bin/chown -R nobody.$group_name \"$site_web/error\"");

# hack to make sure index.html is at least there
if (! -f $webindex)
{
	Sauce::Util::modifyfile($webindex);
	open HACK, ">$webindex" or die;
	print HACK "<HTML><TITLE>DIRTLY LITTLE HACK</TITLE></HTML><BODY>THIS IS JUST A HACK UNTIL THE LOCALE SKELETON STUFF GETS WORKED OUT.</BODY></HTML>";
	close HACK;
}
else
{
	Sauce::Util::editfile($webindex, *edit_webindex, $vsite);
			
	# restore index permissions
	Sauce::Util::chownfile((getpwnam('nobody'))[2], (getgrnam($group_name))[2], $webindex);
	Sauce::Util::chmodfile(0664,$webindex);
}

# add the default aliases to the virtuser file
for my $alias (keys %DefaultAliases)
{
	my ($ok) = $cce->create('ProtectedEmailAlias',
						{
							'site' => $group_name,
							'fqdn' => $vsite->{fqdn},
							'alias' => $alias,
							'action' => $alias,
							'build_maps' => 0
						});

	# check if the create succeeded, if not send a warning and
	# fail, because the user has no way to create these other than
	# through a vsite create
	if (!$ok)
	{
		# check to see if the alias already exists and is used
		# by some user
		my ($oid) = $cce->find('EmailAlias',
						{
							'alias' => $alias,
							'fqdn' => $vsite->{fqdn}
						});
		if ($oid)
		{
			($ok, my $alias_obj) = $cce->get($oid);
			my $other_site = {};
			if ($alias_obj->{site})
			{
				my ($void) = $cce->find('Vsite', 
								{ 'name' => $alias_obj->{site} });
				($ok, $other_site) = $cce->get($void);
			}

			if ($other_site->{fqdn})
			{
				$cce->warn('vsiteUserOwnsAlias',
					{ 
						'user' => $alias_obj->{action}, 
						'site' => $other_site->{fqdn},
						'alias' => "$alias\@$vsite->{fqdn}"
					});
			}
			else
			{
				$cce->warn('userOwnsAlias', 
					{ 
						'user' => $alias_obj->{action},
						'alias' => "$alias\@$vsite->{fqdn}"
					});
			}
		}
		else
		{
			# no idea why it can't be added
			$cce->warn('cantAddSystemAlias', 
				{ 'alias' => "$alias\@$vsite->{fqdn}" });
		}

		$cce->bye('FAIL');
		exit(1);
	}
}

# create a VirtualHost entry
($ok) = $cce->create('VirtualHost', 
			{ 
				'ipaddr' => $vsite->{ipaddr}, 
				'fqdn' => $vsite->{fqdn}, 
				'documentRoot' => "$site_dir/web",
				'name' => $group_name 
			});

if (not $ok)
{
	$cce->bye('FAIL', '[[base-vsite.cantAddVirtualHost]]');
	exit(1);
}


&debug_msg(Dumper($vsite));

# setup ftp host if necessary
# This is now taken care of in base-ftp
my (@site_ftp) = $cce->find('FtpSite', { 'ipaddr' => $vsite->{ipaddr} });
if (!$site_ftp[0])
{
	 ($ok) = $cce->create('FtpSite', { 'ipaddr' => $vsite->{ipaddr}, 'enabled' => 1 });

	if (not $ok)
	{
		$cce->warn('[[base-vsite.cantAddFtpVhost]]');
	}
} 
else
{
	# inform FTP that the site state has changed
	($ok) = $cce->set($site_ftp[0], '', {'commit' => time()});
}

$cce->bye('SUCCESS');
exit(0);

sub create_system_group
{
	my $vsite = shift;

	my ($name);

	# don't waste any group names
	# go through the Vsites and find the first untaken site\d+ name
	# assume 16 bit gid fields which means there could be a whole lot of groups
	for (my $i = 1; $i <= 2 ** 16; $i++)
	{
		# use getgrnam to check for available groups, 
		# but this creates a possible
		# race condition.  would CCE take care of the race condition?
		if (not getgrnam("site$i")) {
			# found an availble name
			$name = "site$i";

			# add the group, use groupadd to make things standard
			my @ret = groupadd({ 'name' => $name });
			
			if (!$ret[0])
			{
				return '';
			}

			last;
		}
	}

	# create the home directory and sub directories for this site
	my $base = homedir_get_group_dir($name, $vsite->{volume});

	$DEBUG && print STDERR "base is $base\n";
	$DEBUG && print STDERR Dumper($vsite);

	# create dirs with looser permissions first
	if (scalar(mkpath([ "$base/users", ("$base/" . Sauce::Config::webdir()) ], 
						0, 02775)) == 0) 
	{
		&debug_msg("$base failed to create users and web");
		if ($base =~ /^\/.+/)
		{
			rmtree($base);
		}
		return '';
	}
	
	# be safe and make sure $base is not /
	if ($base =~ /^\/.+/)
	{
		Sauce::Util::addrollbackcommand("/bin/rm -rf $base");
	}

	# chmod all the directories
	Sauce::Util::chmodfile(02775, "$base/users");
	Sauce::Util::chmodfile(02775, "$base/" . Sauce::Config::webdir());
	Sauce::Util::chmodfile(02775, $base);

	# chown all the directories just made
	my $gid = getgrnam($name);
	# this chown doesn't need to be rolled back because if the 
	# Vsite create fails the entire $base dir will just get blown away
	system('chown', '-R', "nobody.$name", $base);

	return $name;
}

sub debug_msg
{
	my $msg = shift;
	$DEBUG && print STDERR "$ARGV[0]: ", $msg, "\n";
}

sub edit_webindex {
	my ($in, $out, $vsite) = @_;
	while (<$in>) {
		s/\[DOMAIN\]/$vsite->{fqdn}/g;
		print $out $_;
	}
	return 1;
}

sub find_skeleton
{
	my $locale = shift;

	my $skel_dir = '/etc/skel/vsite';

	# this may need to be more robust, but for now just make it simple
	if (-d "$skel_dir/$locale")
	{
		return "$skel_dir/$locale/" . Sauce::Config::webdir();
	}

	$locale =~ s/^([^_]+).*$/$1/;
	if (-d "$skel_dir/$locale")
	{
		return "$skel_dir/$locale/" . Sauce::Config::webdir();
	}

	# otherwise fall back to a default
	return "$skel_dir/en/" .Sauce::Config::webdir();
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

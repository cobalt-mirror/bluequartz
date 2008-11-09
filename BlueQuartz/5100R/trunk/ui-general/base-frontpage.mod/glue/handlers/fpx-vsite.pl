#!/usr/bin/perl
# $Id: fpx-vsite.pl 709 2006-03-09 14:32:23Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# Virtual site FPX5 support
# Will DeHaan <null@sun.com>

# Global Paths & Config
my $fpx_dir = '/usr/local/frontpage';
my $fpx_exe = $fpx_dir.'/currentversion/bin/owsadm.exe';
my $vhosts_dir = '/etc/httpd/conf/vhosts';
my $namespace = 'Frontpage';

# site web data files searched and replaced on fqdn change:
my @fqdn_change = ('.htaccess', 'access.cnf', 'postinfo.html', '"*.cnf"');
my $find_bin = '/usr/bin/find';
my $perl_bin = '/usr/bin/perl';
my $suexec = '/usr/sbin/suexec';
my $fpx_user = 'nobody'; # executable user who owns fpx files
my $webmaster = 'webmaster'; # initial user for site webs
my $redirect = '>/dev/null 2>&1';
my $htpasswd = '/usr/bin/htpasswd'; 
my $fail;

# Apache vhosts site root Directory config
my $options_on = 'Options All';
my $options_off = 'Options None';
my $override_on = 'AllowOverride All';
my $override_off = 'AllowOverride AuthConfig Indexes Limit';

# Perl libraries, all Sausalito
use lib qw(/usr/sausalito/perl);
use CCE;
use Sauce::Util;
use File::Copy;
use Base::Vsite qw(vsite_update_site_admin_caps);
umask(002); # group write

# Debug, Will-style
my $DEBUG = 0;
$DEBUG && open(STDERR, ">>/tmp/fpx-vsite");
$DEBUG && warn $0.' '.`date`;

my $cce = new CCE;
$cce->connectfd();

my $object = $cce->event_object();
my $old = $cce->event_old();
my $new = $cce->event_new();
$DEBUG && warn $object->{event_class}."\n";

my($ok, $vsite, $fpsite);

($ok, $vsite) = $cce->get($cce->event_oid()); # ok is ignored, if this fails so will the next
($ok, $fpsite) = $cce->get($cce->event_oid(), $namespace);
unless ($ok)
{
	$DEBUG && warn 'Could not find the event_oid '.$cce->event_oid." namespace $namespace\n";
	$cce->bye('FAIL');
	exit(1);
}

my ($aok, $sys_fp) = $cce->get(($cce->find('System'))[0], 'Frontpage');
$DEBUG && warn "EULA? ".$sys_fp->{eula};
if($fpsite->{enabled} && !$sys_fp->{eula}) {
	$cce->bye('FAIL', '[[base-frontpage.mustAcceptEULA]]');
	exit(1);
}

# update site admin capabilities for this site to match enabled state
if (!vsite_update_site_admin_caps($cce, $vsite,
                                    'siteFrontpage', $fpsite->{enabled}))
{
    $cce->bye('FAIL', '[[base-frontpage.cantUpdateSiteAdminCaps]]');
    exit(1);
}

my $fqdn = $vsite->{fqdn};
$fqdn = $new->{fqdn} if ($new->{fqdn});

# Careful order of operations on site creation
if($cce->event_is_create() && 
  (!$vsite->{name} || (! -s $vhosts_dir.'/'.$vsite->{name}))
  ) 
{
	$DEBUG && warn "Defering until vsite has a name";
	$cce->bye('DEFER');
	exit 0;
}


if($cce->event_is_destroy() && $old->{fqdn}) # Site deletion, not to be confused w/FPX disable
{
	# Other handlers wipe user data, Apache conf

	$DEBUG && warn "Site deletion, unlinking ".$fpx_dir.'/'.$old->{fqdn}.':80.cnf';
	Sauce::Util::unlinkfile($fpx_dir.'/'.$old->{fqdn}.':80.cnf');

	$DEBUG && warn "Site deletion, unlinking ".$fpx_dir.'/'.$old->{fqdn}.':443.cnf~';
	Sauce::Util::unlinkfile($fpx_dir.'/'.$old->{fqdn}.':443.cnf'); # generally unused
}
# FPX for this site is not enabled, and isn't supposed to be.  
elsif ((! -e $fpx_dir.'/'.$vsite->{fqdn}.':80.cnf') &&
	(! $fpsite->{enabled}))
{
	$DEBUG && warn "FPX not enabled for ".$vsite->{fqdn}.' '.$vsite->{name}.".  Exiting.";
	$cce->bye('SUCCESS');
	exit 0;
}
elsif($new->{fqdn} && $old->{fqdn}) # Vsite.fqdn change (rename)
{
	$DEBUG && warn "Site name change detected from ".$old->{fqdn}.' to '.$new->{fqdn};

	# http
	Sauce::Util::renamefile($fpx_dir.'/'.$old->{fqdn}.':80.cnf',
	       $fpx_dir.'/'.$new->{fqdn}.':80.cnf');

	# https
	if(-e $fpx_dir.'/'.$old->{fqdn}.':443.cnf')
	{
		Sauce::Util::renamefile($fpx_dir.'/'.$old->{fqdn}.':443.cnf',
		       $fpx_dir.'/'.$new->{fqdn}.':443.cnf');
	}

	my $vsite_root = $object->{basedir};

	# There is no site rename function built into FPX, so we must
	# edit FPX config files directly, performing this change.
	my $search_n_replace;
	foreach $search_n_replace (@fqdn_change)
	{
		my $ret = system("$perl_bin -pi -e \"s#".
			$old->{fqdn}.'#'.$new->{fqdn}.'#g" `'.
			"$find_bin $fpx_dir $vsite_root/web $vsite_root/users -name $search_n_replace".'`'); 
		Sauce::Util::addrollbackcommand("$perl_bin -pi -e \"s#".
			$new->{fqdn}.'#'.$old->{fqdn}.'#g" `'.
			"$find_bin $vsite_root/web $vsite_root/users -name $search_n_replace".'`'); 
	}

	# recalculate web links
	system($fpx_exe, '-o', 'recalc', '-t', 'apache-2.0', '-m', $new->{fqdn}, $redirect); # cleanup

	$DEBUG && warn join(' ', ($fpx_exe, '-o', 'recalc', '-t', 'apache-2.0', '-m', $new->{fqdn}));
}
# Enable FP for the site
elsif ($fpsite->{enabled} && 
	(! -e $fpx_dir.'/'.$vsite->{fqdn}.':80.cnf') &&
	$fpsite->{passwordWebmaster})
{

	# Saftey catch for suexec, which shouldn't be installed
	rename($suexec, $suexec.'.breaks.frontpage')
		if (-x $suexec);

	# we can extend this to specify a database interface
	my $ret = system($fpx_exe, '-o', 'install', '-t', 'apache-2.0', '-m', $fqdn,
		'-p', 80, '-servconf', $vhosts_dir.'/'.$vsite->{name},
		'-u', $webmaster,
		'-xuser', $fpx_user, '-xgroup', $vsite->{name},
		'-password', $fpsite->{passwordWebmaster},
		$redirect);
	if($ret && ($ret != 512))
	{
		$fail = '[[base-frontpage.failInstallSite]]';
	}
	else
	{
		# Repair web dir permissions
		&fpx_recurse_chmod( 02775, $vsite->{basedir}.'/web' );
		# fpx_exe can corrupt/ignore passwords, arghhh...
		system($htpasswd, '-b', $vsite->{basedir}.'/web/_vti_pvt/service.pwd', 'webmaster', $fpsite->{passwordWebmaster});
	}
	$DEBUG && warn "FPX install returned: $ret";

	copy($fpx_dir.'/'.$fqdn.':80.cnf', $fpx_dir.'/'.$fqdn.':443.cnf');
	system('/bin/chown', 'nobody.'.$vsite->{name}, $fpx_dir.'/'.$fqdn.':443.cnf');
	
	# Add email capability to the server
	my $configfile;
	foreach $configfile ($fpx_dir.'/'.$fqdn.':80.cnf', $fpx_dir.'/'.$fqdn.':443.cnf')
	{
		if(-e $configfile)
		{
			Sauce::Util::editfile($configfile, *append_smtphost, '127.0.0.1', $vsite->{fqdn});
		} 
	}
	
	Sauce::Util::addrollbackcommand($fpx_exe, '-o', 'uninstall', '-m', $vsite->{fqdn},
		'-p', 80, '-t', 'apache-2.0',
		'-servconf', $vhosts_dir.'/'.$vsite->{name},
		$redirect);
	$DEBUG && warn join(' ', ($fpx_exe, '-o', 'install', '-m', $fqdn, 
                '-p', 80, '-servconf', $vhosts_dir.'/'.$vsite->{name},
                '-u', $webmaster, '-t', 'apache-2.0',
                '-xuser', $fpx_user, '-xgroup', $vsite->{name},
                '-password', $fpsite->{passwordWebmaster},
                $redirect));

	# edit Apache Options, AllowOverride
	$ret = Sauce::Util::editfile(
		$vhosts_dir.'/'.$vsite->{name},
		*edit_vhost_conf,
		1,
		$vsite->{basedir},
		$vsite->{name},
		);
 
	# Enable for all users when enabling for the site
	# $ret = &enable_site_users($cce, $vsite->{name});
}
# Attempted enable, but missing the webmaster password
elsif ($fpsite->{enabled} && 
	(! -e $fpx_dir.'/'.$vsite->{fqdn}.':80.cnf') &&
	!$fpsite->{passwordWebmaster})
{
	$fail = '[[base-frontpage.missingWebmasterPassword]]';
}
# Already enabled, update webmaster password
elsif ($fpsite->{enabled} && 
	(-e $fpx_dir.'/'.$vsite->{fqdn}.':80.cnf') &&
	$fpsite->{passwordWebmaster})
{
	$DEBUG && warn "webmaster password change only\n";

	# First attempt to change password, if this fails we re-add the webmaster account
	my $ret = system($fpx_exe, 
		'-o', 'users', 
		'-t', 'apache-2.0',
		'-c', 'changepassword',
		'-m', $fqdn,
		'-p', 80, 
		'-servconf', $vhosts_dir.'/'.$vsite->{name},
		'-u', $webmaster,
		'-password', $fpsite->{passwordWebmaster},
		$redirect);
	$DEBUG && warn "$fpx_exe -o users -t apache-2.0 -c changepassword -m $fqdn -p 80 -servconf $vhosts_dir/$vsite->{name} -u $webmaster -password $fpsite->{passwordWebmaster}\n...returned: $ret\n";
	if($ret && ($ret != 512))
	{
		$DEBUG && warn "Attempting to add $webmaster, change password had failed\n";
		my $ret = system($fpx_exe, 
			'-o', 'users', 
			'-t', 'apache-2.0',
			'-c', 'add',
			'-m', $fqdn,
			'-p', 80, 
			'-servconf', $vhosts_dir.'/'.$vsite->{name},
			'-u', $webmaster,
			'-password', $fpsite->{passwordWebmaster},
			$redirect);
		$DEBUG && warn "$fpx_exe -o users -t apache-2.0 -c add -m $fqdn -p 80 -servconf $vhosts_dir/$vsite->{name} -u $webmaster -password $fpsite->{passwordWebmaster}\n...returned: $ret\n";
		if($ret && ($ret != 512))
		{
			$fail = '[[base-frontpage.failChangePassword]]';
		}
		else
		{

			# /usr/local/frontpage/currentversion/bin/owsadm.exe  -o userroles -c add -u webmaster -n admin -w / -p 80 -m vname38.cobalt.com -servconf /etc/httpd/conf/vhosts/site2 

			# Now make the new webmaster an "admin" 
			my $ret = system($fpx_exe, 
				'-o', 'userroles', 
				'-t', 'apache-2.0',
				'-c', 'add',
				'-u', $webmaster,
				'-n', 'admin',
				'-w', '/',
				'-p', '80',
				'-m', $fqdn,
				'-servconf', $vhosts_dir.'/'.$vsite->{name},
				$redirect);

			$DEBUG && warn "$fpx_exe -o userroles -t apache-2.0 -c add -m $fqdn -p 80 -servconf $vhosts_dir/$vsite->{name} -u $webmaster -web / -name admin\n...returned: $ret\n";

			if($ret && ($ret != 512))
			{
				$fail = '[[base-frontpage.failChangePassword]]';
			}
		}
	}
	else
	{
		# Repair web dir permissions
		&fpx_recurse_chmod( 02775, $vsite->{basedir}.'/web' );
	}
	
}
# disable for a site, not to be confused with site deletion
elsif (!$fpsite->{enabled})
{
	$DEBUG && warn "Searching User site is ".$vsite->{name}."\n";
	my (@site_users) = $cce->find('User', { 'site' => $vsite->{name} }); 
	foreach my $uoid (@site_users)
	{
		$DEBUG && warn "Found uoid $uoid, disabling FPX...\n";
		$cce->set($uoid, $namespace, {'enabled' => 0});
	}

	my $ret = system($fpx_exe, '-o', 'uninstall', '-m', $vsite->{fqdn},
		'-p', 80, '-t', 'apache-2.0',
		'-servconf', $vhosts_dir.'/'.$vsite->{name},
		$redirect);
	if($ret && ($ret != 512))
	{
		$fail = '[[base-frontpage.failUninstallSite]]';
	}
	else
	{
		# Repair web dir permissions
		&fpx_recurse_chmod( 02775, $vsite->{basedir}.'/web' );
	}
	Sauce::Util::addrollbackcommand($fpx_exe, '-o', 'install', '-m', $fqdn,
		'-p', 80, '-servconf', $vhosts_dir.'/'.$vsite->{name},
		'-u', $webmaster, '-t', 'apache-2.0',
		'-xuser', $fpx_user, '-xgroup', $vsite->{name},
		'-password', $fpsite->{passwordWebmaster},
		$redirect);
	$DEBUG && warn join(' ',($fpx_exe, '-o', 'uninstall', '-m', 
		$vsite->{fqdn},
                '-p', 80, '-t' , 'apache-2.0',
                '-servconf', $vhosts_dir.'/'.$vsite->{name},
                $redirect));

	# edit Apache Options, AllowOverride
	$ret = Sauce::Util::editfile(
		$vhosts_dir.'/'.$vsite->{name},
		*edit_vhost_conf,
		0,
		$vsite->{basedir},
		$vsite->{name},
		);
	
	# Disable SSL support
	Sauce::Util::unlinkfile($fpx_dir.'/'.$vsite->{fqdn}.':443.cnf'); # generally unused
}

if($fail)
{
	$cce->bye('FAIL', $fail);
	exit 1;
}
else
{
	$cce->bye('SUCCESS');
	exit 0;
}


### Subs

sub enable_site_users
{
	my $cce = shift;
	my $group = shift;
	$DEBUG && warn "$0 Searching for users with site: $group\n";

	# OPTIMIZATION:
	# We could build a staged bash script to execute all fpx_exe 
	# operations in a single system call

	my (@user_oids) = $cce->find('User', {'site' => $group});
	$DEBUG && warn "site uid search.. number of users: ".$#user_oids;

	my $uoid;
	foreach $uoid (@user_oids)
	{
		$DEBUG && warn "Found uid for site ".$vsite->{name}."\n";
		$cce->set($uoid, $namespace, {'enabled' => '1'});
	}
	return 1;
}

sub edit_vhost_conf
# Manages Options and Allowoverride directives on a per-site basis
{
	my($in, $out, $enabled, $group_dir, $group) = @_;
	my ($found_options, $found_allow);

	$DEBUG && warn "edit_vhost_conf called with $enabled, $group_dir, $group\n";

	my $options = $enabled ? $options_on : $options_off;
	my $allow = $enabled ? $override_on : $override_off;

	while(<$in>)
	{
		if(/^\s*<Directory.+\/$group>/ ... /^\s*<\/Directory/)
		{
			if (!$enabled) {
				$found_options = $found_allow = 1;
				next;
			}
			if(/^\s*Options\s/i)
			{
				print $out $options."\n" unless($found_options);
				$found_options = 1;
				# $_ = '# '.$_ unless (/^\s*\#/);
				next;
			}
			elsif(/^\s*AllowOverride\s/i)
			{
				print $out $allow."\n" unless($found_allow);
				$found_allow = 1;
				# $_ = '# '.$_ unless (/^\s*\#/);
				next;
			}

			if(/^\s*<\/Directory/)
			{
				$DEBUG && warn "Inserting Options and/or Allow into Directory\n";
				print $out $options."\n" unless ($found_options);
				print $out $allow."\n" unless ($found_allow);
				$found_options = $found_allow = 1;
			}
		}

		if (! /^\s*UserDir web\s/i) {
			print $out $_;
		}
	}

	if($enabled && !$found_options)
	{
		$DEBUG && warn "Appending Directory block to $vhosts_dir/$group\n";
		print $out <<EOF;
<Directory $group_dir>
$options
$allow
</Directory>
UserDir web
EOF
	} 
	return 1;
}

sub append_smtphost
{
	my($in, $out, $smtphost, $fqdn) = @_;
	$smtphost ||= '127.0.0.1';

	my $found;
	my $config = "SMTPHost: $smtphost\nMailSender: nobody\@$fqdn\n";
	
	while(<$in>)
	{
		if(/^SMTPHost/)
		{
			print $out $config;
			$found = 1;
		}
		else
		{
			print $out $_;
		}
	}
	print $out $config unless ($found);

	return 1;		
}

sub fpx_recurse_chmod
# Resets web directory permissions post-FPX install
# Arguments: directory mode, root directory
{
	my($file_mode, $root_dir)=@_;
	$file_mode ||= "02775"; # Defaults are good
	return unless ( -d $root_dir);

	chmod( $file_mode, $root_dir);

	opendir(SBDR, $root_dir) || return;
	map fpx_recurse_chmod($file_mode, "$root_dir/$_"),
	grep +(-d "$root_dir/$_" and !/^\.{1,2}$/), readdir(SBDR);
	close (SBDR);
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

#!/usr/bin/perl
# $Id: fpx-user.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# Virtual site-bound USER FPX5 web support
# Will DeHaan <null@sun.com>

# Global Paths & Config
my $fpx_dir = '/usr/local/frontpage';
my $fpx_exe = $fpx_dir.'/currentversion/bin/owsadm.exe';
my $vhosts_dir = '/etc/httpd/conf/vhosts';
my $namespace = 'Frontpage';
my @fpstubs = ('/_vti_bin/fpcount.exe', '/_vti_bin/shtml.exe',
	       '/_vti_bin/_vti_aut/author.exe', 
	       '/_vti_bin/_vti_adm/admin.exe');
my @fpdirs =  ('/web/_private', '/web/_vti_bin', '/web/_vti_cnf', 
	       '/web/_vti_pvt', '/web/_vti_txt');
my $redirect = '>/dev/null 2>&1';
my $fail;

# Perl libraries, all Sausalito
use lib qw(/usr/sausalito/perl);
umask(002); # group write
use CCE;
use File::Copy;
use Sauce::Util;

# Debug, Will-style
my $DEBUG = 0;
$DEBUG && warn $0.' '.`date`;
$DEBUG && eval('use strict;');

my $cce = new CCE;
$cce->connectfd();

my $object = $cce->event_object();
my $old = $cce->event_old();
my $new = $cce->event_new();

# User deletion, not to be confused w/FPX disable, or user disabled
if($cce->event_is_destroy() || $object->{site} eq '' || !$object->{enabled})
{
	# Do nothing.
	$DEBUG && warn "Do nothing: event is destroy or object has null site\n";
	$cce->bye('SUCCESS');
	exit(0);
}

my($ok, $user, $vsite, $fpuser, $fpsite);

my $event_oid = $cce->event_oid();
($ok, $user) = $cce->get($event_oid); # ok is ignored, if this fails so will the next
($ok, $fpuser) = $cce->get($event_oid, $namespace);

unless ($ok)
{
	$DEBUG && warn 'Could not find the event_oid '.$cce->event_oid." namespace $namespace\n";
	$cce->bye('FAIL', '[[base-frontpage.noNamespace]]');
	exit(1);
}

my @pwuser = getpwnam($user->{name});
$DEBUG && warn 'User pw: '.join(' ', @pwuser)."\n";

my @site_oids = $cce->find('Vsite', {'name' => $user->{site}});
($ok, $vsite) = $cce->get($site_oids[0]);
($ok, $fpsite) = $cce->get($site_oids[0], $namespace);
unless ($ok)
{
	$DEBUG && warn "Could not find the user site namespace: $namespace\n";
	$cce->bye('FAIL', '[[base-frontpage.couldNotAccessUser]]');
	exit(1);
}

my $ret;

# Careful order of operations on site creation
if(! -d $pwuser[7].'/web')
{
	$DEBUG && warn "Defering until object has a name, web directory\n";
	$cce->bye('DEFER');
	exit 0;
}
elsif ($cce->event_is_create() && !$fpuser->{enabled} && $fpsite->{enabled})
{
	# for transparent site-wide configuration, we would do the following: 
	# # Enable FPX for this user, this handler will be re-invoked
	# $DEBUG && warn "Enabling CCE User.Frontapge.enabled property... Handler will be re-invoked\n";
	# $ret = $cce->set($cce->event_oid(), $namespace, {'enabled' => '1'});

	# if FPX is enabled by user, we do nothing.
	$DEBUG && warn "User created w/FPX disabled--do nothing\n";
	$cce->bye('SUCCESS');
	exit 0;
}
# Update user password
elsif ($new->{md5_password})
{
	# Set user FP web password
        # edit the per-web service.pwd password file
	$DEBUG && warn "Updating user password\n";
	&edit_password();
	$cce->bye('SUCCESS');
	exit 0;
}
# Enable FP for the user
elsif ($fpsite->{enabled} && $fpuser->{enabled})
{
	my ($ok, $userquota) = $cce->get($event_oid, 'Disk');

	# Null, 0 or < 0 indicates unlimited
	$userquota->{quota} ||= 999999; # null/0
	$userquota->{quota} = 999999 if ($userquota->{quota} <= 0); # < 0
 
	my $fqdn = $vsite->{fqdn};
	my $password = $new->{password};
	srand( time() ^ ($$ + ($$ << 12)) ); # Scramble
	$password ||= rand($$);

	$DEBUG && warn "Detecting $pwuser[7]/web/.htaccess\n";	
	unless (-w $pwuser[7].'/web/.htaccess')
	{
		$DEBUG && warn "...Adding missing file: $pwuser[7]/web/.htaccess\n";	
		open(HTA, ">$pwuser[7]/web/.htaccess") || warn "Could not open $pwuser[7]/web/.htaccess: $!";
		print HTA <<EOF;
# -FrontPage-

IndexIgnore .htaccess */.??* *~ *# */HEADER* */README* */_vti*

<Limit GET POST>
order deny,allow
deny from all
allow from all
</Limit>
<Limit PUT DELETE>
order deny,allow
deny from all
</Limit>
EOF
		close(HTA);
		chown($pwuser[2], (getgrnam($vsite->{name}))[2], $pwuser[7].'/web/.htaccess');
		chmod(0664, $pwuser[7].'/web/.htaccess');
	}

	$DEBUG && warn "chdir'ing to $pwuser[7]/web\n";
	chdir($pwuser[7].'/web');
	$ENV{'PATH'} = '/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:/usr/bin/X11:/sbin:/usr/sbin:/usr/local/bin:/usr/local/sbin:/usr/java/jdk1.3/bin:/sbin:/usr/sbin:/usr/local/bin:/usr/local/sbin:/usr/java/jdk1.3/bin:/usr/sausalito/bin:/usr/sausalito/sbin';

	# we can extend this to specify a database interface
	$DEBUG && warn join(' ', ($fpx_exe, '-o', 'install', '-m', $fqdn,
		'-w', '/~'.$user->{name},
		'-p', 80, '-servconf', $vhosts_dir.'/'.$vsite->{name},
		'-u', $user->{name},
		'-xuser', $user->{name}, '-xgroup', $vsite->{name},
		'-password', $password,
		'-nochowncontent', 'yes',
		$redirect));
	$ret = system($fpx_exe, '-o', 'install', '-m', $fqdn,
		'-w', '/~'.$user->{name},
		'-p', 80, '-servconf', $vhosts_dir.'/'.$vsite->{name},
		'-u', $user->{name},
		'-xuser', $user->{name}, '-xgroup', $vsite->{name},
		'-password', $password,
		'-nochowncontent', 'yes',
		$redirect);
	Sauce::Util::addrollbackcommand("$fpx_exe -o uninstall -m \""
		.$vsite->{fqdn}."\" -w \"/~".$user->{name}
		."\" -p 80 -servconf \"$vhosts_dir/".$vsite->{name}
		."\" $redirect");

	# ret = 512 is already installed
	if($ret && ($ret != 512))
	{
		my $fez = `$fpx_exe -o install -m $fqdn -w /~$user->{name} -p 80 -servconf $vhosts_dir/$vsite->{name} -u $user->{name} -xuser $user->{name} -xgroup $vsite->{name} -password $password -nochowncontent yes`;
		$DEBUG && warn $fez;
		$fail = '[[base-frontpage.failInstallUserweb]]';
	}

	$DEBUG && warn "Called $fpx_exe: $ret";
	# FIXME: check ret could only be useful without $redirect, necessesary 
	# because we're connectfd'd.

    # make sure the directories exist for fpx
    for my $dir (@fpdirs)
    {
        if (! -d "$pwuser[7]$dir")
        {
            Sauce::Util::makedirectory("$pwuser[7]$dir", 02775);
        }
    }

    # Set user FP web password
    # edit the per-web service.pwd password file
	&edit_password();

	# Restore web datafile permissions, but not if user is disabled
    Sauce::Util::chmodfile(02771, $pwuser[7]);
    # don't chmod the whole user directory, just web
    # that should be the only one that fpx needs
    &fpx_recurse_chmod(02775, "$pwuser[7]/web"); 
} elsif (!$fpuser->{enabled}) {
	# disable for a user, not to be confused with user deletion
	my $userdir = $pwuser[7];
	my @remove_dirs = @fpdirs;
	for (my $i = 0; $i < scalar(@remove_dirs); $i++) {
		$remove_dirs[$i] =~ s/\/web\///;
	}

	# need to recurse the entire web directory and remove all the
	# frontpage junk
	#
	&fpx_recursive_clean("$pwuser[7]/web", \@remove_dirs);
} 
else
{
	$DEBUG && warn "Could not classify handler action.\n";
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


# Subs

sub edit_password
{
	# Set user FP web password
        # edit the per-web service.pwd password file
	my $password = $new->{md5_password};
	$password ||= $user->{md5_password};
	my $service_pwd = (getpwnam($user->{name}))[7] . '/web/_vti_pvt/service.pwd';
        $ret = Sauce::Util::editfile(
		$service_pwd,
                *implant_password,
                $user->{name},
		$password
		);
	$DEBUG && warn "inserted password: ".$user->{md5_password}." Into: ".$service_pwd;
}

sub implant_password
# Each FP web has its own htpasswd file, called service.pwd.
# We swap out the staged password crypt with a real password
# crypt/md5 from CCE's user record. 
{
	my($in, $out, $username, $crypt_or_md5) = @_;

	my $htpass = $username.':'.$crypt_or_md5."\n";

	while(<$in>)
	{
		print $out $_ unless(/^$username\:/);
	}
	print $out $htpass;

	return 1;
}

sub fpx_recurse_chmod
# Resets web directory permissions post-FPX install
# Arguments: directory mode, root directory
{
        my($file_mode, $root_dir)=@_;
        $file_mode ||= 02775; # Defaults are good
        return unless ( -d $root_dir);

        chmod( $file_mode, $root_dir);

        opendir(SBDR, $root_dir) || return;
        map fpx_recurse_chmod($file_mode, "$root_dir/$_"),
        grep +(-d "$root_dir/$_" and !/^\.{1,2}$/), readdir(SBDR);
        close (SBDR);
}

sub fpx_recursive_clean
{
	my ($dir, $dirs_to_remove) = @_;
	
	local(*DIR);
	opendir(DIR, $dir);
	$DEBUG && print STDERR "entering $dir\n";
	$DEBUG && print STDERR "removing these dirs: ",
		join(' ', @{ $dirs_to_remove }), "\n";
	while (my $entry = readdir(DIR)) {
		# skip ., .., and non-directories
		if (($entry =~ /^\.{1,2}$/) || (! -d "$dir/$entry")) {
			$DEBUG && print STDERR "skipping $entry\n";
			next;
		}

		if (grep(/^$entry$/, @{ $dirs_to_remove }) && ($entry ne '')) {
			$DEBUG && print STDERR "removing $dir/$entry\n";
			Sauce::Util::modifytree("$dir/$entry");
			system('/bin/rm', '-rf', "$dir/$entry");
		} else {
			&fpx_recursive_clean("$dir/$entry", $dirs_to_remove);
		}
	}
	closedir(DIR);
	$DEBUG && print STDERR "leaving $dir\n";
	return;
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

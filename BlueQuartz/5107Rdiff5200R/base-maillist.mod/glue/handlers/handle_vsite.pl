#!/usr/bin/perl
# $Id: handle_vsite.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# handles the creation of a virtual site's majordomo infrastructure

use lib qw( /usr/sausalito/perl );
use CCE;

# Debugging switch:
my $DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}
$DEBUG && warn `date`." $0\n";

my $aliasfile = '/etc/mail/aliases.majordomo';
my $majortemplate = '/usr/local/majordomo/majordomo.cf.template';
my $majordomo_sites = '/usr/local/majordomo/sites';
my @majordomo_aliases = ('majordomo', 'majordomo-owner', 'owner-majordomo');

my($UID, $GID) = ( (getpwnam('mail'))[2], (getgrnam('daemon'))[2] );
my($filemod, $dirmod) = (0640, 0700);

my $cce = new CCE; 
$cce->connectfd();

my $errors = '';

my $obj = $cce->event_object();
my $old = $cce->event_old();
my $new = $cce->event_new();
my $oid = $cce->event_oid();

# this runs in the CONFIGURE stage which is the same stage as the handler
# that sets the Vsite.name property, so make sure on create the name
# property is defined before proceeding
if ($cce->event_is_create() && ($obj->{name} eq ''))
{
	$cce->bye('DEFER');
	exit(0);
}

my $sitelists = $majordomo_sites.'/'.$obj->{name}.'/lists';
my $sitedigest = $majordomo_sites.'/'.$obj->{name}.'/digests';
my $logfile = '/usr/local/majordomo/logs';

if($cce->event_is_destroy())
{
	foreach my $oid (
		$cce->find("MailList", {'site' => $old->{name}}) )
	{
		$cce->destroy($oid); # ignore return on destructive ops
	}

	# Delete the per-site config files/dirs
	my $sitebase = $majordomo_sites.'/'.$old->{name};
	$DEBUG && warn "Want to rm -rf $sitebase\n";
	if ((-d $sitebase) && ($sitebase =~ /\d$/))
	{
		system('/bin/rm', '-rf', $sitebase);
	}

	# Destroy per-site majordomo aliases
	foreach my $alias (@majordomo_aliases)
	{
		my(@marked_for_death) = $cce->find('ProtectedEmailAlias', {
			'alias'=>$alias,
			'fqdn'=>$old->{fqdn},
			});
		foreach my $oid (@marked_for_death)
		{
			my ($ret) = $cce->destroy($oid);
		}
	}

	# Destroy aliases.majordomo entries for majordomo aliases
	my($ok) = Sauce::Util::editfile(
		$aliasfile,
		*edit_majoralias,
		0,
		$old->{name},
		\@majordomo_aliases
		);

}
elsif($new->{fqdn})
{
	my %sub; # substitution template
	$sub{'FQDN'} = $new->{fqdn};
	$sub{'BASEDIR'} = $majordomo_sites.'/'.$obj->{name};
	$sub{'LISTDIR'} = $sitelists;
	$sub{'DIGESTS'} = $sitedigest;
	$sub{'LOG'} = $logfile;

	# update fqdn for local users
	for my $dir (($sitelists, $sitedigest))
	{
		opendir(DIR, $dir);
		while(my $file = readdir(DIR))
		{
			if ((! -f "$dir/$file") || ($file =~ /^\.{1,2}$/)) { next; }
			if ($file =~ /\.config$/) { next; }

			if (!Sauce::Util::editfile("$dir/$file", *edit_list_members,
						$old->{fqdn}, $new->{fqdn}))
			{
				$cce->bye('FAIL', '[[base-maillist.cantEditFile]]');
				exit(1);
			}
		}
		closedir(DIR);
	}

	# Create maillist dir set
	foreach my $dir ($majordomo_sites,
			 $sub{'BASEDIR'}, 
			 $sub{'LISTDIR'},
			 $sub{'DIGESTS'})
	{
		unless(-d $dir)
		{
			mkdir($dir, $dirmod);
			chown($UID, $GID, $dir);
		}
	}

	# create default majordomo
	my $cf = $sub{'BASEDIR'}.'/majordomo.cf';
	if(-r $majortemplate)
	{
		open(TMPL, $majortemplate);
		open(TRGT, "> $cf");
		while(<TMPL>)
		{
			s/\[(FQDN|BASEDIR|LISTDIR|DIGESTS|LOG)\]/$sub{$1}/;
			print TRGT;
		}
		close(TRGT);
		close(TMPL);
		chown($UID, $GID, $cf);
		chmod($filemod, $cf);

		# touch the log
		unless(-e $sub{'LOG'})
		{
			open(LOG, ">".$sub{'LOG'});
			close(LOG);
			chown($UID, $GID, $sub{'LOG'});
			chmod($filemod, $sub{'LOG'});
		}
	}
	else
	{
		$errors .= '[[base-maillist.missingMajordomoCf]]';
	}

	# update aliases
	if($old->{fqdn} ne $new->{fqdn})
	{
		my($ok) = Sauce::Util::editfile(
			$aliasfile,
			*edit_majoralias,
			0,
			$obj->{name},
			\@majordomo_aliases
			);
		my($aok) = Sauce::Util::editfile(
			$aliasfile,
			*edit_majoralias,
			1,
			$obj->{name},
			\@majordomo_aliases
			);

# I don't know why the hell this is in here. It fucks up /etc/mail/alias.majordomo by 
# prefixing the FQDN to ANY output line. See 'sub edit_fqdn' for more info.
# I comitted these lines in http://devel.blueonyx.it/trac/changeset/528/BlueOnyx/5107R/ui/base-maillist.mod
# back in 2010, but dunno why, because /etc/mail/alias.majordomo has no FQDN related stuff in it!
#
#                my($bok) = Sauce::Util::editfile(
#                        $aliasfile,
#                        *edit_fqdn,
#                        $old->{fqdn},
#                        $new->{fqdn}
#                        );
	}
}

if($cce->event_is_create())
{
	foreach my $alias (@majordomo_aliases)
	{
		my $action = 
		my(@oids) = $cce->find('ProtectedEmailAlias', {
			'alias'=>$alias,
			'fqdn'=>$new->{fqdn},
			});
		my ($ret) = $cce->create('ProtectedEmailAlias', {
			'alias'=>$alias,
			'fqdn'=>$new->{fqdn},
			'site'=>$new->{name},
			'action'=>$new->{name}.'-'.$alias,
			}) unless($oids[0]);
	}
}

if($errors)
{
	$cce->bye('FAIL', $errors);
	exit 1;
}

$cce->bye("SUCCESS");
exit 0;



sub edit_majoralias
{
	my($in, $out, $enable, $site, $aliasref) = @_;
	&debug_msg("edit_majoralias invoked: in: $in - out: $out - enable: $enable - site: $site");
	my ($config, %public_alii);
	foreach my $alias (@{$aliasref})
	{
		$public_alii{$site.'-'.$alias} = 1;

		if($alias =~ /owner/i) {
			$config .= $site.'-'.$alias.":\tadmin\n";
		} else {
			$config .= $site.'-'.$alias.":\t".
			'"|/usr/local/majordomo/wrapper majordomo -C '.
			'/usr/local/majordomo/sites/'.$site.'/majordomo.cf"'.
			"\n";
		}
	}
	&debug_msg("aliases config: $config\n");

	while(<$in>)
	{
		if(/^(\S+):\s/)
		{
			if ($public_alii{$1}) 
			{
				&debug_msg("Skipping: $_");
				next;
			}
		}
		print $out $_;
	}
	print $out $config if ($enable);
	return 1;
}

sub edit_list_members
{
	my ($in, $out, $old_fqdn, $new_fqdn) = @_;

	&debug_msg("edit_list_members: in: $in - out: $out - old_fqdn: $old_fqdn - new_fqdn: $new_fqdn");

	while (<$in>)
	{
		s/^([^\@]+)\@$old_fqdn$/$1\@$new_fqdn/;
		print $out $_;
		&debug_msg("edit_list_members: printed: $_");
	}

	return 1;
}

sub edit_fqdn
{
        my ($in, $out, $old_fqdn, $new_fqdn) = @_;

	&debug_msg("edit_fqdn invoked: in: $in - out: $out - old_fqdn: $old_fqdn - new_fqdn: $new_fqdn");

        while (<$in>) {
                s/$old_fqdn/$new_fqdn/; # <-- This is the culprit that prefixes any line with the FQDN!
                print $out $_;
		&debug_msg("edit_fqdn: printed: $_");
        }


        return 1;
}

sub debug_msg {
    if ($DEBUG) {
        my $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
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

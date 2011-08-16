#!/usr/bin/perl -I /usr/sausalito/perl
# $Id: whitebox.pl,v 1.6 2001/07/27 19:40:12 will Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# Cobalt Linux White Box Converter
#
# Disables automated management and configuration utilities
# included in this distribution of Cobalt Linux.
# 
# Primary functions of this script are to:
# - disable administrative daemons (admserv, cced)
# - disable Cobalt Linux Active Monitoring
# - activate all registered whitebox conversion scripts
#   in /etc/cobalt/whitebox.d/
#
# whitebox.d is owned by base-services.mod 

my $DEBUG = 0;
my $destruct = 1; 	# boolean self-destruct : resurrect
			# Never resurrect managed Cobalt Linux tools
			# as the system will not be consistent with
			# what is represented in the GUI.

# The following daemon scripts are abrubtly removed from the gene pool.
my @daemon_initscripts = (
	'admserv', 'cced.init'
	);

my $chkconfig_bin = '/sbin/chkconfig';
my $init_dir = '/etc/rc.d/init.d';
my $crontab = '/etc/crontab';
my $crontab_init = "$init_dir/crond";
my $swatch = 'swatch';
my $whitebox_dir = '/etc/cobalt/whitebox.d';

use I18n;
my $i18n = new I18n;
$i18n->setLocale(I18n::i18n_getSystemLocale());

# FIXME: add warning

use Sauce::Util;
# Disable swatch
Sauce::Util::editfile($crontab, \&edit_crontab, $destruct) ||
	warn "Could not find swatch crontab entry in /etc/crontab\n";
my $out = `$crontab_init reload 2>&1`;
$DEBUG && warn "Stopped swatch, reloaded cron: $out";

# Down system daemons
my $daemon;
foreach $daemon (@daemon_initscripts)
{
	my $opt = '--add';
	$opt = '--del' if ($destruct);
	$DEBUG && warn "Configuring daemon: $daemon\n";
	my $out = `$chkconfig_bin $opt $daemon`;
	$DEBUG && warn "chkconfig $opt $daemon output: $out\n";

	my $arg = 'restart';
	$arg = 'stop' if ($destruct);
	$out = `$init_dir/$daemon $arg`;
	$DEBUG && warn "init script \"stop\" output: $out\n";
}

# Process whitebox.d scripts
if(-d $whitebox_dir)
{
	my ($scriptlet, @wb);
	opendir(WBD, $whitebox_dir) || die 
		"Could not read the whitebox directory: $whitebox_dir: $!";
	while($scriptlet = readdir(WBD))
	{
		next if ($scriptlet =~ /^\.+$/);
		next unless (-x "$whitebox_dir/$scriptlet");
		push(@wb, $scriptlet);
	}
	closedir(WBD);
	foreach $scriptlet (sort @wb)
	{
		my $arg = 'resurrect';
		$arg = 'destruct' if ($destruct);
		$DEBUG && warn "Executing $scriptlet with argument \"$arg\"...\n";
		system("$whitebox_dir/$scriptlet $arg");
	}
}
else
{
	$DEBUG && warn "No whitebox script repository at $whitebox_dir.  Skipping.\n";
}

print "Fin!\n";
exit(0);

# Subs

sub edit_crontab
{
	my($in, $out, $destruct) = @_;
	my $ret = 0;
	while(<$in>)
	{
		if(/\/$swatch\s+/)
		{
			unless($destruct)
			{
				$_ =~ s/^\s*\#\s*//g;
				$ret = 1;
			}
			else
			{
				$_ = '# '.$_ unless (/^\s*\#/);
				$ret = 1;
			}
			print $out $_;
		}
		else
		{
			print $out $_;
		}
	}
	return $ret;
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

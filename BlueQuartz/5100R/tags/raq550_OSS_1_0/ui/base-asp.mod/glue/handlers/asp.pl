#!/usr/bin/perl -w -I /usr/sausalito/perl
# $Id: asp.pl,v 1.9.2.3 2002/10/29 20:57:30 sam Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
#
# Chilisoft ASP public daemon enable/disable
# Also swaps the mod_casp Apache DSO in and out of service.

# configure here: (mostly)
my $SERVICE = "asp-apache-3000";	# name of initd script for this daemon
my $CMDLINE = "asp-apache-3000";  # contents of /proc/nnn/cmdline for this daemon
my $RESTART = "restart"; # restart action
my $PID = "/home/chiliasp/asp-apache-3000/caspd.pid";
my $CCEPROP = 'enabled';

my $DEBUG   = 0;

use lib qw( /usr/sausalito/perl );
use FileHandle;
use Sauce::Util;
use Base::Httpd;

use CCE;
$cce = new CCE;
$cce->connectfd();

my ($sysoid) = $cce->find('System');
my ($ok, $obj) = $cce->get($sysoid, 'Asp');

# fix chkconfig information:
if ($obj->{$CCEPROP}) {
	Sauce::Service::service_set_init($SERVICE, 'on', '345');
	# Enable the Apache casp2 module
	my $ret = Base::Httpd::httpd_add_module('casp2_module', 'modules/mod_casp2.so');
	if($ret) {
		Sauce::Util::editfile(
			"$Base::Httpd::srm_conf_file",
			*_include_caspLib,
			1);
	}
} else {
	Sauce::Service::service_set_init($SERVICE, 'off', '345');
	my $ret = Base::Httpd::httpd_remove_module('casp2_module', 'modules/mod_casp2.so');
	if($ret) {
		Sauce::Util::editfile(
			"$Base::Httpd::srm_conf_file",
			*_include_caspLib,
			0);
	}
        # Remove any extra casp2_module lines when turning ASP off
        Sauce::Util::editfile(
                        "$Base::Httpd::httpd_conf_file",
                        *_remove_casp2_module);
}


# check to see if the service is presently running;
my $running = 0;
{
	my $fh = new FileHandle("<$PID");
	if ($fh) {
		my $pid = <$fh>; chomp($pid);
		$DEBUG && print STDERR "old $SERVICE pid = $pid\n";
		$fh->close();
		
		$fh = new FileHandle("</proc/$pid/cmdline");
		if ($fh) {
			my $cmdline = <$fh>; chomp($cmdline);
			$DEBUG && print STDERR "old $SERVICE cmdline = $cmdline\n";
			$fh->close();
			
			if ($cmdline =~ m/$CMDLINE/) { $running = 1; }
		}
	}
}

# do the right thing
if (!$running && $obj->{$CCEPROP}) {
	system("/etc/rc.d/init.d/${SERVICE}", "start");
	sleep(1); # wait for asp-apache-3000 to really start
}
elsif ($running && !$obj->{$CCEPROP}) {
	system("/etc/rc.d/init.d/${SERVICE}", "stop");
}
elsif ($running && $obj->{$CCEPROP}) {
	system("/etc/rc.d/init.d/${SERVICE}", $RESTART);
}

# is it running now?
$running = 0;
{
	my $fh = new FileHandle("<$PID");
	if ($fh) {
		my $pid = <$fh>; chomp($pid);
		$DEBUG && print STDERR "new $SERVICE pid = $pid\n";
		$fh->close();
		
		$fh = new FileHandle("</proc/$pid/cmdline");
		if ($fh) {
			my $cmdline = <$fh>; chomp($cmdline);
			$DEBUG && print STDERR "new $SERVICE name = $cmdline\n";
			$fh->close();
			
			if ($cmdline =~ m/$CMDLINE/) { $running = 1; }
		}
	}
}

# report the did-not-start error, if necessary:
if ($obj->{$CCEPROP} && !$running) {
	$cce->warn("[[base-asp.${SERVICE}-did-not-start]]");
	$cce->bye("FAIL");
	exit 1;
} else {
	$cce->bye("SUCCESS");
	exit 0;
}


# Swaps the ASP library path in/out of Apache's srm.conf
sub _include_caspLib 
{
	my($in, $out, $bool) = @_;

	my $found = 0;
	my $conf = 'CaspLib ';
	my $conf_literal = $conf.'/home/chiliasp/asp-apache-3000';

	while (<$in>)
	{
	        if (/^\#*\s*($conf.+)$/)	# $1 doesn't include \n
	        {
	                $found = 1;
			if ($bool)
			{
	                	print $out $1."\n"; 
			}
			else
			{ 
				print $out '# '.$1."\n";
			}
	        }
	        else
	        {
	                print $out $_;
	        }
	}

	if ($add && !$found)
	{
		print $out $conf_literal."\n";
	}

	return 1;
}


# If line contains casp2_module then comment it out.
# This function is only called when ASP turned off.
sub _remove_casp2_module
{
	my($in, $out) = @_;

	while (<$in>)
	{
		if ( /^\s*LoadModule\s+casp2_module/ ||
		     /^\s*AddModule\s+mod_casp2\.c/ )
		{
				print $out '# ';
		}
		print $out $_;
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

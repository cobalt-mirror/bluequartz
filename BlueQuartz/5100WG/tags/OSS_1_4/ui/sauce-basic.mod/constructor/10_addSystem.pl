#!/usr/bin/perl -w -I/usr/sausalito/perl -I.

# Copyright 2000, Cobalt Networks.  All rights reserved.
# $Id: 10_addSystem.pl 3 2003-07-17 15:19:15Z will $
# author: jmayer@cobalt.com

use strict;
use CCE;

my $errors = 0;

my %locales = (  
	"en_US" => "&en&",
	"ja" => "&ja&"
);

my $cce = new CCE;
$cce->connectuds();

my ($fullbuild) = `cat /etc/build`;
chomp($fullbuild);

# figure out our product
my ($product, $build, $lang) = ($fullbuild =~ m/^build (\S+) for a (\S+) in (\S+)/);

# count the systems;
my @oids = $cce->find("System");
if ($#oids == 0) {
	# we have one and only one System object.  hooray.
} elsif ($#oids < 0) {
	# we must create a System object with no properties.
	$cce->create("System", {
		hostname=>"localhost",
		domainname=>"localdomain",
		productBuildString=>$fullbuild,
		productIdentity => $product,
		productBuild => $build,
		productLanguage => $lang,
		console=>"0",
		locales => $locales{$lang}
	})
} else { # we have more than one System object.
	print STDERR "ERROR: Multiple system objects detected!\n";
	print STDERR "       attempting to repair...\n";
	shift(@oids); # don't delete this one.
	foreach $_ (@oids) {
		my ($success) = $cce->destroy($_);
		if ($success) {
			print STDERR "       Deleted System object $_\n";
		} else {
			print STDERR "       FAILED to delete System object $_\n";
			$errors++;
		}
	}
}

# sync console (redundant on first create, safe on all others)
# we do it this way, because the console flag may change at powerup
my $conval = `/usr/sbin/cmos -c console`;
chomp($conval);
$cce->set($oids[0], "", {console => $conval eq "on" ? "1" : "0"}) if $oids[0];

$cce->bye();
exit($errors);
	

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

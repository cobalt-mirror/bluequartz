#!/usr/bin/perl -I/usr/sausalito/perl -I.
# $Id: 10_addSystem.pl,v 1.5.2.2 2002/03/12 00:44:45 pbaltz Exp $
# Copyright 2000-2002 Sun Microsystems, Inc.  All rights reserved.

#use strict;
use CCE;
use I18n;

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

my ($i18n) = `grep LANG /etc/sysconfig/i18n`;
if ($i18n =~ m/^LANG="(\S+)"/) {
        $lang = $1;
}
if ($lang =~ /^ja/) {
        $lang = 'ja';
} else {
        $lang = 'en';
}

my $myhost = `/bin/hostname -s`;
chomp ($myhost);
if ( $myhost eq "" ) {
	$myhost = "localhost";
}
my $mydomain = `/bin/hostname -d`;
chomp ($mydomain);
if ( $mydomain eq "" ) {
	$mydomain = "localdomain";
}

my @nss = ();
if (open (FILE, '< /etc/resolv.conf')) {
	while ( defined($_ = <FILE>) ) {
		chomp ();
		# print $_, "\n";
		if (/nameserver\s*(\S.*)/) {
			if (defined ($1)) {
				push @nss, $1;
			}
		}
	}
	close (FILE);
}
my $nsscalar = "";
if ( @nss ) {
	$nsscalar = CCE->array_to_scalar (@nss)
}

#
# figure out which locales the machine has available.  Just look for this
# module, because it will have all the supported locales.
#
my $available_langs = $cce->array_to_scalar(I18n::getAvailableLocales('base-system'));

# count the systems;
my @oids = $cce->find("System");
if ($#oids == 0) {
	# We have only one System object - update it:
    	($sys_oid) = $cce->find('System', '');
    	($ok) = $cce->set($sys_oid, '',{
                hostname => $myhost,
                domainname => $mydomain,
                dns => $nsscalar,
                productBuildString=>$fullbuild,
                productIdentity => $product,
                productBuild => $build,
                productLanguage => $lang,
                console=>"0",
                locales => $available_langs
        });
        $oids[0] = $cce->oid();
} elsif ($#oids < 0) {
	# we must create a System object with no properties.
	$cce->create("System", {
		hostname => $myhost,
		domainname => $mydomain,
		dns => $nsscalar,
		productBuildString=>$fullbuild,
		productIdentity => $product,
		productBuild => $build,
		productLanguage => $lang,
		console=>"0",
		locales => $available_langs
	});
	$oids[0] = $cce->oid();
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
#my $conval = `/sbin/nvram -c console`;
#chomp($conval);
#$cce->set($oids[0], "", { console => $conval eq "on" ? "1" : "0"}) if $oids[0];

# update System.locales everytime in case we add/remove a locale
if ($oids[0]) {
	$cce->set($oids[0], '', { 'locales' => $available_langs });
}

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

#!/usr/bin/perl -w
# this constructor sets the product name field, this can be re-used for
# different products by copying it to product.mod/constructor and changing
# the $product variable to the product name

use strict;
use lib qw( /usr/sausalito/perl );
use CCE;
use I18n;

my $product = 'carmel';

my $cce = new CCE(Domain => "base-$product");
my $i18n = new I18n;

$cce->connectuds();

# find the system object
my ($sysoid) = $cce->find("System");

if (not $sysoid) {
	# BIG PROBLEM no system object
	$cce->warn('noSystemObject');
	$cce->bye('FAIL', 'noSystemObject');
	exit 1;
}

# get admin user, so we have a locale to work with
my ($adminoid) = $cce->find("User", { 'name' => 'admin' });

if (not $adminoid){
	# PROBLEM there is no admin user fail
	$cce->warn('noAdminUser');
	$cce->bye('FAIL', 'noAdminUser');
	exit 1;
}

my ($ok, $admin) = $cce->get($adminoid);

if (not $ok) {
	# can't get admin user
	$cce->warn('cantGetAdmin');
	$cce->bye('FAIL', 'cantGetAdmin');
	exit 1;
}

$i18n->setLocale(I18n::i18n_getSystemLocale());

my ($badkeys, @info);

($ok, $badkeys, @info) = $cce->set($sysoid, '', { 
	'productName' => $i18n->get("[[base-$product.productName]]"),
	'productVendor' => 'Cobalt'
	});

if (not $ok) {
	$cce->warn('cantSetProduct');
	$cce->bye('FAIL', 'cantSetProduct');
	exit 1;
} else {
	# $cce->info(@info);
	$cce->bye('SUCCESS');
	exit;
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

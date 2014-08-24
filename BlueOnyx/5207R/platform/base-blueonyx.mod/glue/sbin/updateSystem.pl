#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id:
# Copyright 2005 Project BlueOnyx.  All rights reserved.

use strict;
use CCE;
use I18n;

my $errors = 0;

my $cce = new CCE;
$cce->connectuds();

my $i18n = new I18n;
$i18n->setLocale(I18n::i18n_getSystemLocale($cce));

my ($fullbuild) = `cat /etc/build`;
chomp($fullbuild);

# figure out our product
my ($product, $build, $lang) = ($fullbuild =~ m/^build (\S+) for a (\S+) in (\S+)/);

# count the systems;
my @oids = $cce->find("System");
if (not @oids) {
	$cce->bye('FAIL');
	exit 1;
}

# update product information
$cce->set($oids[0], '', {
	'productBuildString' =>$fullbuild,
	'productIdentity' => $product,
	'productBuild' => $build,
	'productName' => $i18n->interpolate('[[base-product.productName]]')
});

@oids = $cce->find('Package', { 'name' => 'OS' });
if (not @oids) {
	$cce->bye('FAIL');
	exit 1;
}

# update OS package information
$cce->set($oids[0], '', {
	'version' => "v4.$product"
});

$cce->bye();
exit($errors);

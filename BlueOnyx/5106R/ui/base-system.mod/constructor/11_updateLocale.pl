#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id$
# Copyright 2007 Project BlueQuartz.  All rights reserved.

use strict;
use CCE;
use I18n;

my $cce = new CCE;
$cce->connectuds();

my $available_langs = $cce->array_to_scalar(I18n::getAvailableLocales('base-system'));

my ($oid) = $cce->find('System');
my ($ok) = $cce->set($oid, '', { 'locales' => $available_langs });

$cce->bye('SUCCESS');
exit(0);


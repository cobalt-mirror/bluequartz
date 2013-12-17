#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: create_shop.pl mstauber $
# Copyright 2012, shop.blueonyx.it, All rights reserved.

use strict;
use CCE;
use I18n;

my $cce = new CCE;
$cce->connectuds();

my $i18n = new I18n;

# Check if the object already exists:
my (@oids) = $cce->find('Shop', { });
my $success;

if($#oids < 0) {
    # Create object:
    ($success) = $cce->create('Shop',
            {
                shop_url => 'shop.blueonyx.it',
                update => '0'
            });

}

$cce->bye();

exit 0;

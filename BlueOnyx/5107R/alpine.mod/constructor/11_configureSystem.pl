#!/usr/bin/perl -I/usr/sausalito/perl
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: 11_configureSystem.pl,v 1.1.1.1.2.2 2002/02/11 22:41:44 jcheng Exp $
#
# setup things in the raqish state in the system object

use strict;
use CCE;
use I18n;

my $cce = new CCE;
$cce->connectuds();

my $i18n = new I18n;
$i18n->setLocale(I18n::i18n_getSystemLocale($cce));

# find the system object
my ($oid) = $cce->find('System');
if (!$oid)
{
    $cce->bye('FAIL', '[[base-alpine.cantFindSystem]]');
    exit(1);
}

# setup stuff, don't bother with failure, because messages should be
# propagated up, and, well, this is a constructor so what can we do?
my ($ok) = $cce->set($oid, '', { 'productName' => $i18n->interpolate('[[base-product.productName]]') });

# set Telnet access appropriately for raqs
my ($ok) = $cce->set($oid, 'Telnet', { 'access' => 'reg' });

# turn on console access, this should probably be removed before shipping
($ok) = $cce->set($oid, '', { 'console' => 1 });

$cce->bye('SUCCESS');
exit(0);
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

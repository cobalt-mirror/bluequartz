#!/usr/bin/perl
# $Id: addSWUpdateServer.pl 388 2004-08-20 15:54:02Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

# author: mchan@cobalt.com

use lib '/usr/sausalito/perl';
use Sauce::Config;
use CCE;

my $cce = new CCE;
$cce->connectuds();

my $package_dir = '/home/packages';

my @OIDS = $cce->find('SWUpdateServer');
# create a default SWUpdateServer object if none exists, use default
# properties in update.schema file

if ($#OIDS < 0) {
  # create SWUpdateServer object
  $cce->create('SWUpdateServer', {
	'name'        => 'default',
	'enabled'     => 1,
	'location'    => '',
    });
}

mkdir($package_dir, 0755) unless -d ($package_dir);
chown((getpwnam(Sauce::Config::groupdir_owner))[2,3], $package_dir);

# sync up system with cce
@OIDS = $cce->find('System');
if (@OIDS) {
	my ($ok, $obj) = $cce->get($OIDS[0], 'SWUpdate');
	my $interval = $obj->{updateInterval};

	# some massive hokeyness. set the value twice to fix things up.
	my $new = $interval eq 'Never' ? 'Monthly' : 'Never';
	$cce->set($OIDS[0], 'SWUpdate', { updateInterval => $new });
	$cce->set($OIDS[0], 'SWUpdate', { updateInterval => $interval });
}

$cce->bye('SUCCESS');
exit 0;
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

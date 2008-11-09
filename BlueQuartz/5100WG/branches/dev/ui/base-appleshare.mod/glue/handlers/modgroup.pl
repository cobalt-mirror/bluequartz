#!/usr/bin/perl -I/usr/sausalito/handlers/base/appleshare -I/usr/sausalito/perl
#
# Copyright(c) 2000, Cobalt Networks, Inc.
# Author: asun@cobalt.com
#
use Sauce::Config;
use Sauce::Util;
use Sauce::Service;
use CCE;
use appleshare;

my $cce = new CCE;
$cce->connectfd(\*STDIN, \*STDOUT);

my $obj = $cce->event_object();
my $old = $cce->event_old();

# make sure that the Network Trash Folder is there with the right
# permissions
my $dir = Sauce::Config::groupdir_base . '/' . $obj->{name};
unless (-d $dir) {
    $cce->bye('DEFER');
#    exit 0; 
}
$dir .= '/Network Trash Folder';

my $perms = Sauce::Config::perm_GroupDir();
mkdir($dir, $perms);
chmod($perms, $dir);
chown(Sauce::Config::groupdir_owner(), -1, $dir);

# get the guest workgroup
my @oids = $cce->find('System');
my ($ok, $aobj) = $cce->get($oids[0], 'FileShare');
my $restrict = ($aobj->{name} eq $obj->{name}) ? 0 : 1;

my $err = Sauce::Util::editfile(appleshare::atalk_getconf, 
				*appleshare::edit_group, 
				$old->{name}, $obj->{name}, $restrict);
if ($err) {
	$cce->bye('FAIL', '[base-appleshare.cantModifyGroup]');
	exit 1;
}

my ($aok, $atalk) = $cce->get($oids[0], 'AppleShare');
Sauce::Service::service_run_init('atalk', 'reload') if ($atalk->{enabled});

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

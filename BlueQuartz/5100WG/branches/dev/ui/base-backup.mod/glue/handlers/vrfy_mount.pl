#!/usr/bin/perl  -w -I/usr/sausalito/perl -I.
use strict;
use Backup;
use CCE;

# Author: Brenda Mula, Tim Hockin
# Copyright 2000 Cobalt Networks, Inc.
# http://www.cobalt.com
#
# $Id: vrfy_mount.pl 201 2003-07-18 19:11:07Z will $

my $cce;

my $COBALT_BACKUP	= "/usr/local/sbin/cbackup";
my $ret			= 0;
my $backupOptions	= "";

sub test_backup
{
	print STDERR "Executing $COBALT_BACKUP $backupOptions\n";
	system("$COBALT_BACKUP $backupOptions");
	return $?;
}


#  -------- Main ---------------------

my ($self, @args) = @_;

$cce = new CCE;
$cce->connectfd(\*STDIN,\*STDOUT);

# get Backup Object from CCE to read values
my $oid = $cce->event_oid();
my $obj = $cce->event_object();

my $backup_tag         = $obj->{backupTag};
my $backup_method      = $obj->{backupMethod};
my $backup_destination = $obj->{backupDestination};
my $backup_username    = $obj->{backupUsername};
my $backup_password    = $obj->{password};

$backupOptions = "--name $backup_tag --method $backup_method " .
	"--location '$backup_destination' --test";
if ($backup_username) {
	$backupOptions .= " --username '$backup_username'";
}
if ($backup_password) {
	$backupOptions .= " --password '$backup_password'";
}

$ret = test_backup(); 

# Make sure that error is detected by all.
if ($ret) {
	$cce->warn('[[base-backup.testFailed]]');
	$cce->bye('FAIL');
} else {
	$cce->bye('SUCCESS');
}

exit $ret;
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

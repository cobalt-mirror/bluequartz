#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
#$Id: checkTag.pl 201 2003-07-18 19:11:07Z will $
# 
# Backup.backupTag handler
# author: Brenda Mula <bmula@cobalt.com>
#
# generates the following i18n messages:
#		invalidBackupTag
#		backupTagAlreadyTaken

# I use the name to track Immediate Backups, so don't allow
my @illegal_usernames = qw/
    
/;
my %illegal_usernames = map { $_ => 1 } @illegal_usernames;
use strict;
use Sauce::Config;
use FileHandle;
use CCE;

my $cce = new CCE;
$cce->connectfd(\*STDIN,\*STDOUT);

# retreive user object data:
my $oid = $cce->event_oid();

if (!$oid) {
	# something is very, very wrong.
	$cce->bye('FAIL', 'Bad CSCP header');
	exit(1);
}

my $backupobj = $cce->event_object();
my $name = $backupobj->{backupTag} || "";

##########################################################################
# error checking
##########################################################################
my $error;

print STDERR "trying to create Backup with backupTage = $name\n";
#$error = $cce->validate('backupTag', qr/^[a-zA-Z0-9\.\-\_]{1,36}$/ );

# check for illegal name
if ($error || $illegal_usernames{$name}) {
	$cce->baddata($oid, 'name', '[[base-backup.backupTagInvalid]]');
	$error = 1;
} 

# check for duplicate Tags
if (!$error) {
	my @oids = $cce->find("Backup", { 'backupTag' => $name });
	if ($#oids > 0) {
		$cce->baddata(0, 'name', 
			'[[base-backup.backupTagAlreadyTaken]]');
		$error = 1;
	}
}

if ($error) {
	$cce->bye('FAIL');
	exit(1);
}

# comfortable shoes.
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

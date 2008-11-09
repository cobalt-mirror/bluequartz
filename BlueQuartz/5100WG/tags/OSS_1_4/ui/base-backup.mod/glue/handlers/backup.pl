#!/usr/bin/perl  -w -I/usr/sausalito/perl -I.

use Backup;
use strict;
use CCE;

my $cce;

my $CRON_DAILY_DIR   = "/etc/cron.daily";
my $CRON_WEEKLY_DIR  = "/etc/cron.weekly";
my $CRON_MONTHLY_DIR = "/etc/cron.monthly";
my $COBALT_BACKUP    = "/usr/local/sbin/cbackup";
my $BACKUP_HDR       = "#!/bin/sh\n# Cobalt Networks  Backup (Copyright 2000)\n\n";
my $ret   	     = 0;
my $backupOptions = "";
my $userOptions = "";

sub ScheduleImmediate
{
	my $pid;
	my $ntries = 3;

    FORK: {
	if ($pid = fork) {
	  # parent here
	  # child process pid is available in $pid
	  # Just exit so we return to GUI
	  return 0;
	} elsif (defined $pid) { # $pid is zero here if defined
	  # child here
	  # Close stdin and stdout or the ui won't return.
	  close(STDIN);
	  close(STDOUT);
	  open(STDIN, "</dev/null") || die "open: $!";
	  open(STDOUT, ">/dev/null") || die "open: $!";
	  sleep 5; # let parent task exit
	  print STDERR "Executing $COBALT_BACKUP $backupOptions\n";
	  exec("$COBALT_BACKUP $backupOptions");
	} elsif ($! =~ /No more process/) {
	  # EAGAIN, supposedly recoverable fork error
	  $ntries--;
	  sleep 5;
	  if ($ntries > 0) {
	  	redo FORK;
	  }
	} else {
	  print STDERR "can't fork cbackup process: $!\n";
	  return 1;
	}
    }
}

sub ScheduleBackup
{
    my ($cron_file) = @_;

	open(BSCRIPT, "> $cron_file") || die "foo open: $!\n";
	print BSCRIPT "$BACKUP_HDR";

	print BSCRIPT "$COBALT_BACKUP $backupOptions >> /var/log/backup.log 2>&1\n";
	close(BSCRIPT);

	chmod(0750, $cron_file);
}


#  -------- Main ---------------------

	my ($self, @args) = @_;

	$cce = new CCE;
	$cce->connectfd(\*STDIN,\*STDOUT);

	# get Backup Object from CCE to read values
	my $oid = $cce->event_oid();
	my $obj = $cce->event_object();

	# Only return information when we are 
        # FIX ME
	if (!$cce->event_is_modify() || ($obj->{pendingBackup} eq "0")){
	    exit 0;
	}
	my $backup_tag         = $obj->{backupTag};
	my $backup_schedule    = $obj->{backupSchedule};
	my $backup_history     = $obj->{backupHistory};
	my $backup_method      = $obj->{backupMethod};
	my $backup_destination = $obj->{backupDestination};
	my $backup_location    = $obj->{backupLocation};
	my $backup_type        = $obj->{backupType};
	my $backup_userscope   = $obj->{backupUserScope};
	my $backup_users       = $obj->{backupUsers};
	my $backup_groupscope  = $obj->{backupGroupScope};
	my $backup_groups      = $obj->{backupGroups};
	my $backup_username    = $obj->{backupUsername};
	my $backup_password    = $obj->{password};
	my $backup_cronfile    = $obj->{backupCronFile};
	my $backup_fileset     = $obj->{backupFileSet};
	my $backup_config      = $obj->{backupConfiguration};

	# What do we need to do?
	if ($backup_userscope eq "all") {
		$backup_users = "all";
	} 

	if ($backup_groupscope eq "all") {
		$backup_groups = "all";
	} 


if (defined($backup_users)) {
	if ($backup_users eq "all") {
		$userOptions = "--alluser";
	} elsif ($backup_users ne "none") {
		$userOptions = " --users '$backup_users'";
	}
}

if (defined($backup_groups)) {
	if ($backup_groups eq "all") {
		$userOptions .= " --allgroup";
	} elsif ($backup_groups ne "none") {
		$userOptions .= " --groups '$backup_groups' ";
	}
}

$backupOptions = "--name $backup_tag --method $backup_method " .
	"--location '$backup_destination' " . 
	"$userOptions " .
	"--fileset $backup_fileset";
if ($backup_username) {
	$backupOptions .= " --username '$backup_username'";
}
if ($backup_password) {
	$backupOptions .= " --password '$backup_password'";
}

if ($backup_config) {
	$backupOptions .= " --backup_config";
}

if ($backup_schedule eq "backupImmediate") {
	ScheduleImmediate(); 
} else {
	ScheduleBackup($backup_cronfile);
}

# Make sure that error is detected by all.
if ($ret == 1) {
	$cce->warn("[[base-backup.backupError,prog=\"$COBALT_BACKUP\"]]");
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

#!/usr/bin/perl
# $Id: cron.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

# author: mchan@cobalt.com

use lib '/usr/sausalito/perl';
use CCE;
use Sauce::Util;

my $CRON_DAILY_DIR   = "/etc/cron.daily";
my $CRON_WEEKLY_DIR  = "/etc/cron.weekly";
my $CRON_MONTHLY_DIR = "/etc/cron.monthly";
my $CRONFILE         = "SWUpdate";
my $SWUPDATE_BIN     = "/usr/sausalito/sbin/grab_updates.pl -c";
my $SWUPDATE_HDR     = "#!/bin/sh\n# Cobalt Networks SWUpdate (Copyright 2000)";

my $cce = new CCE;

my ($sysId, $SWUpdate_obj, $updateInterval, $cronDir, $success);

#  -------- Main ---------------------
$cce->connectfd();

($sysId) = $cce->find("System");
($success, $SWUpdate_obj) = $cce->get($sysId, "SWUpdate");

$updateInterval = $SWUpdate_obj->{updateInterval};

# delete all previous crontabs here
deleteUpdateCron();

if ($updateInterval eq "Daily") {
  $cronDir = $CRON_DAILY_DIR;
} 
elsif ($updateInterval eq "Weekly") {
  $cronDir = $CRON_WEEKLY_DIR;
} 
elsif ($updateInterval eq "Monthly") {
  $cronDir = $CRON_MONTHLY_DIR;
} # don't account "Never" since that's default

if ($cronDir) {
  scheduleUpdate();
}

$cce->bye("SUCCESS");
exit 0;


sub scheduleUpdate {
  Sauce::Util::modifyfile("$cronDir/$CRONFILE");
  open (UPDATECRON, "> $cronDir/$CRONFILE");
  print UPDATECRON "$SWUPDATE_HDR\n";

  print UPDATECRON "$SWUPDATE_BIN\n";
  close (UPDATECRON);
  
  Sauce::Util::chmodfile(0755, "$cronDir/$CRONFILE");
}

sub deleteUpdateCron {
  # delete all previous crontabs here
  Sauce::Util::unlinkfile("$CRON_DAILY_DIR/$CRONFILE");
  Sauce::Util::unlinkfile("$CRON_WEEKLY_DIR/$CRONFILE");
  Sauce::Util::unlinkfile("$CRON_MONTHLY_DIR/$CRONFILE");
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

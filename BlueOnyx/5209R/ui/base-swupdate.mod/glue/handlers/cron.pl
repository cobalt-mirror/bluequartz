#!/usr/bin/perl
# $Id: cron.pl

use lib '/usr/sausalito/perl';
use CCE;
use Sauce::Util;

my $CRON_HOURLY_DIR  = "/etc/cron.hourly";
my $CRON_DAILY_DIR   = "/etc/cron.daily";
my $CRON_WEEKLY_DIR  = "/etc/cron.weekly";
my $CRON_MONTHLY_DIR = "/etc/cron.monthly";
my $CRONFILE         = "SWUpdate";
my $SWUPDATE_BIN     = "/usr/sausalito/sbin/grab_updates.pl -c";
my $SWUPDATE_HDR     = "#!/bin/sh\n# BlueOnyx SWUpdate\n# (Copyright Cobalt Networks 2000)";

my $cce = new CCE;

my ($sysId, $SWUpdate_obj, $updateInterval, $cronDir, $success);

#  -------- Main ---------------------
$cce->connectfd();

($sysId) = $cce->find("System");
($success, $SWUpdate_obj) = $cce->get($sysId, "SWUpdate");

$updateInterval = $SWUpdate_obj->{updateInterval};

# delete all previous crontabs here
deleteUpdateCron();

if ($updateInterval eq "Hourly") {
  $cronDir = $CRON_HOURLY_DIR;
} 
elsif ($updateInterval eq "Daily") {
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
  Sauce::Util::unlinkfile("$CRON_HOURLY_DIR/$CRONFILE");
  Sauce::Util::unlinkfile("$CRON_DAILY_DIR/$CRONFILE");
  Sauce::Util::unlinkfile("$CRON_WEEKLY_DIR/$CRONFILE");
  Sauce::Util::unlinkfile("$CRON_MONTHLY_DIR/$CRONFILE");
}

# 
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#   notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#   notice, this list of conditions and the following disclaimer in 
#   the documentation and/or other materials provided with the 
#   distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#   contributors may be used to endorse or promote products derived 
#   from this software without specific prior written permission.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
# COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
# POSSIBILITY OF SUCH DAMAGE.
# 
# You acknowledge that this software is not designed or intended for 
# use in the design, construction, operation or maintenance of any 
# nuclear facility.
# 
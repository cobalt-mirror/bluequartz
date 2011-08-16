#!/usr/bin/perl -w -I/usr/sausalito/perl
#
# $Id: tmpTime.pl,v 1.0.0-1 Wed 17 Sep 2008 09:01:05 AM CEST mstauber Exp $
# Copyright 2008 Solarspeed Ltd. All rights reserved.
#

use CCE;
use Sauce::Util;

my $cce = new CCE(Namespace => "TempTime");
$cce->connectfd();

my $time_obj = $cce->event_object();

# set the ntp Address and toggle the ntp server based upon its existence
my $ntpAddress = $time_obj->{ntpAddress};
my $epochTime = $time_obj->{epochTime};
my $timeZone = $time_obj->{timeZone};

if (($timeZone) && ($epochTime)) {

        # Set Time, Timezone and NTP Server Address:
        system("/usr/sausalito/sbin/setTime \"$epochTime\" \"$timeZone\" \"$ntpAddress\" \"true\"&");

}

$cce->bye('SUCCESS');
exit 0;

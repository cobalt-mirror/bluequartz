#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: set_default_services.pl, v1.0.0.0 Fri 13 Apr 2012 09:43:27 PM EDT mstauber Exp $
# Copyright 2006-2012 Team BlueOnyx. All rights reserved.
#
# Script to set certain services to default state when the system is not yet configured.

use CCE;
my $cce = new CCE;
my $conf = '/var/lib/cobalt';

$cce->connectuds();

my @oids = $cce->find('System');
if (not @oids) {
        $cce->bye('FAIL');
        exit 1;
}

$firstboot = "0";
my ($ok, $obj) = $cce->get($oids[0]);
if ($obj->{isLicenseAccepted} == "0") {
    $firstboot = "1";
}

if ($firstboot == "1") {
    ($ok) = $cce->set($oids[0], 'Email',{
            "queueTime" => "immediate",
            "masqAddress" => "",
            "enableSubmissionPort" => "0",
            "enableImap" => "1",
            "deniedUsers" => "",
            "smartRelay" => "",
            "acceptFor" => "",
            "enableSMTPS" => "0",
            "enableImaps" => "0",
            "enablePops" => "0",
            "relayFor" => "",
            "enableSMTP" => "1",
            "popRelay" => "0",
            "maxMessageSize" => "",
            "enablePop" => "1",
            "enableSMTPAuth" => "1",
            "deniedHosts" => ""
    });
    # YUM updater:
    ($ok) = $cce->set($oids[0], 'yum',{
            "yumUpdateTime" => "6:00",
            "y_force_update" => "2146835920",
            "yumguiEMAIL" => "1",
            "yumUpdateMO" => "1",
            "yumUpdateTH" => "1",
            "autoupdate" => "On",
            "yumUpdateTU" => "1",
            "yumUpdateWE" => "1",
            "yumUpdateSU" => "1",
            "yumguiEMAILADDY" => "admin",
            "yumUpdateFR" => "1",
            "yumUpdateSA" => "1"
    });
}

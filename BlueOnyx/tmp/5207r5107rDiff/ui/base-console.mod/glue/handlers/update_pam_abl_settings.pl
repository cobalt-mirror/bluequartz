#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: update_pam_abl_settings.pl, v1.0.0-0 Wed 05 Aug 2009 05:50:53 AM CEST mstauber Exp $
# Copyright 2006-2009 Solarspeed Ltd. All rights reserved.
# Copyright 2009 Team BlueOnyx. All rights reserved.

# This handler is run whenever pam_abl is modified through the GUI.

# Debugging switch:
$DEBUG = "0";

# Location of pam_abl_config:
$pam_abl_config = "/etc/security/pam_abl.conf";

#
#### No configureable options below!
#

use CCE;
use Data::Dumper;
use Sauce::Util;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';

$cce->connectfd();

# Get our events from the event handler stack:
$oid = $cce->event_oid();
$obj = $cce->event_object();

$old = $cce->event_old();
$new = $cce->event_new();

# Get Object pam_abl_settings for from CODB:
($ok, $abl_settings) = $cce->get($oid);
$PAM_ABL_OID = $oid;

# We're creating or modifying the pam_abl_settings object:
if ((($cce->event_is_create()) || ($cce->event_is_modify())) && ($PAM_ABL_OID eq $oid)) {
    # Someone used the GUI to edit some parameters. Update
    # the existing config file:
    if (-f $pam_abl_config) {

	# Variable cleanup:
	$host_purge = $abl_settings->{"host_purge"};
	$user_purge = $abl_settings->{"user_purge"};
	$host_rule = $abl_settings->{"host_rule"};
	$user_rule = $abl_settings->{"user_rule"};

	# Edit config:
        if (!Sauce::Util::editfile($pam_abl_config, *edit_pam_abl_config, $host_purge, $user_purge, $host_rule, $user_rule)) {
                $cce->bye('FAIL', "Cannot edit $pam_abl_config");
                exit(1);
        }
    }
    else {
	# Ok, we have a problem: No config found.
	# So we just weep silently and exit. 
	$cce->bye('FAIL', "$pam_abl_config not found!");
	exit(1);
    }
}

$cce->bye('SUCCESS');
exit(0);

sub edit_pam_abl_config {

        my($in, $out, $xhost_purge, $xuser_purge, $xhost_rule, $xuser_rule) = @_;
        my($new_config) = <<EOF;
# /etc/security/pam_abl.conf
# debug
host_db=/var/lib/abl/hosts.db
host_purge=$xhost_purge
host_rule=$xhost_rule
user_db=/var/lib/abl/users.db
user_purge=$xuser_purge
user_rule=$xuser_rule
EOF
        print $out $new_config;
        return 1;
}

$cce->bye('SUCCESS');
exit(0);


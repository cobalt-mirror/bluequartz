#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# Author: Brian N. Smith, Michael Stauber 
# Copyright 2006-2007, NuOnce Networks, Inc.  All rights reserved. 
# Copyright 2006-2007, Stauber Multimedia Design  All rights reserved. 
# $Id: yum-checker.pl, v1.0 2007/12/20 9:02:00 Exp $   

use CCE;
use Sauce::Util;
use Sauce::Service;

my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();

my @oids = $cce->find('System');
if (!defined($oids[0])) {
	print STDERR "Sorry, no System object in CCE found!\n";
	exit 0;
}

my $yumguiConf    = "/etc/yumgui.conf";
my $yumguiCrontab = "/etc/cron.d/yumgui.crontab";

my ($ok, $yumguiValues) = $cce->get($oids[0], "yum");

if ($ok) {
    yumguiWriteConf(
	$yumguiValues->{'yumguiEMAIL'},
        $yumguiValues->{'yumguiEMAILADDY'}
        );

    yumguiSetCrontab(
        $yumguiValues->{'yumUpdateSU'},
        $yumguiValues->{'yumUpdateMO'},
        $yumguiValues->{'yumUpdateTU'},
        $yumguiValues->{'yumUpdateWE'},
        $yumguiValues->{'yumUpdateTH'},
        $yumguiValues->{'yumUpdateFR'},
        $yumguiValues->{'yumUpdateSA'},
        $yumguiValues->{'yumUpdateTime'},
        $yumguiValues->{'autoupdate'}
        );
}

$all_rpms = $yumguiValues->{'yumguiEXCLUDE'};
$all_rpms =~ s/\r//;
$all_rpms =~ tr/\r//d;

my @exclude_list = split(/\n/, $all_rpms);
my $rpmlist = "";
foreach my $rpm(@exclude_list) {
  if ( $rpm ) {
    $rpmlist .= $rpm . " ";
  }
}

my $yumconf = "/etc/yum.conf";

if (!Sauce::Util::replaceblock($yumconf, "## start-yum-gui", "exclude=" . $rpmlist, "## stop-yum-gui")) {
  $cce->warn('[base-yum.cantYUMCONF]]', { 'file' => $yumconf });
  $cce->bye('FAIL');
  exit(1);
}

$cce->bye('SUCCESS');
exit(0);

##
## Sort the stuff for the crontab file
##

sub yumguiSetCrontab {
	my $yumUpdateSU   = shift;
	my $yumUpdateMO   = shift;
	my $yumUpdateTU   = shift;
	my $yumUpdateWE   = shift;
	my $yumUpdateTH   = shift;
	my $yumUpdateFR   = shift;
	my $yumUpdateSA   = shift;
	my $yumUpdateTime = shift;
	my $autoupdate    = shift;

	($h, $m) = split(':', $yumUpdateTime);
    	my $crontab_entry = "$m $h * * ";

    	my $tmp_crontab_entry =
        ($yumUpdateSU eq 1 ? "0," : "")
      	. ($yumUpdateMO eq 1 ? "1," : "")
      	. ($yumUpdateTU eq 1 ? "2," : "")
      	. ($yumUpdateWE eq 1 ? "3," : "")
      	. ($yumUpdateTH eq 1 ? "4," : "")
      	. ($yumUpdateFR eq 1 ? "5," : "")
      	. ($yumUpdateSA eq 1 ? "6," : "");

    if ($autoupdate eq "On") {
    	$tmp_crontab_entry =~ s/,$//;
    	$tmp_crontab_entry = "*" if length($tmp_crontab_entry) < 1;
    	$crontab_entry .= $tmp_crontab_entry . " root /usr/sausalito/handlers/base/swupdate/yum-update.sh\n";
    	chomp($crontab_entry);
    	open(CRON, ">$yumguiCrontab") || die "Can't write to crontab";
    	print CRON "# DO NOT EDIT! THIS FILE IS GENERATED AUTOMATICALLY THROUGH THE GUI!\n";
    	print CRON $crontab_entry;
    	print CRON "\n";
    	print CRON "\n";
    	close CRON;
    }
    if ($autoupdate eq "Off") {
	unlink $yumguiCrontab; 
    }
    Sauce::Util::chmodfile(00644, $yumguiCrontab);
    Sauce::Service::service_run_init('crond', 'restart');
}


##
## yumguiWriteConf
## Saves yumgui configuration into /etc/yumgui.conf
##

sub yumguiWriteConf {
    my $yumguiEMAIL                 = shift;
    my $yumguiEMAILADDY             = shift;

    if ($yumguiEMAIL eq "1") {
	open(CONF, ">$yumguiConf") || die "Can't write to configuration file";
    	print CONF "#!/bin/sh
###############################################################
## YUM-GUI 						     ##
##                                                           ##
## Main Configuration File                                   ##
##                                                           ##
## DO NOT EDIT THIS FILE!                                    ##
##                                                           ##
###############################################################

MAILTO=\"$yumguiEMAILADDY\";

";
close CONF;
	Sauce::Util::chmodfile(00644, $yumguiConf);
    }
    else {
	unlink $yumguiConf;
    }
}

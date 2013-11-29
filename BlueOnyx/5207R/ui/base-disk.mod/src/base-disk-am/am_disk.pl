#!/usr/bin/perl
# $Id: am_disk.pl 1101 2008-03-04 18:20:46Z mstauber $
#
# Email is sent to admin for everyone who is over their quota 
# at 4am. 
# 
# Email is sent to users the instant they are over their quota,
# at most once a day.
#

use lib qw( /usr/sausalito/perl /usr/sausalito/perl/Base);
use AM::Util;
use I18n;
use strict;
use Disk;
use CCE;
use SendEmail;
use MIME::Lite;
use Quota;
use Sys::Hostname;
use warnings;

# Get hostname:
my $host = hostname();

my $object;
my $i18n = new I18n();

my $cce = new CCE;
$cce->connectuds();

my @sysoid = $cce->find ('System');
my ($ok, $sysobj) = $cce->get($sysoid[0]);
my $system_lang = $sysobj->{productLanguage};
my $platform = $sysobj->{productBuild};
my $locale = "";
my $l1_oid = "";
my $l2_oid = "";

# We can't email in Japanese yet, as MIME:Lite alone doesn't support it. We'd need MIME::Lite:TT:Japanese 
# and a hell of a lot of dependencies to sort that out. So for now we hard code them to 'en_US' or 'en'
# for emailing purpose from within this script:
  
if ($system_lang eq "ja") {
    if ($platform eq "5106R") {
    	$i18n->setLocale("en_US");
    }
    else {
    	$i18n->setLocale("en_US");
    }
}
else {
    $i18n->setLocale($system_lang);
} 

###

my $DEBUG = 0;

if (!defined($ENV{red_free})) {
    $ENV{red_free} = 100;
}

if (!defined($ENV{red_pcnt})) {
    $ENV{red_pcnt} = 95;
}

if (!defined($ENV{yellow_free})) {
    $ENV{yellow_free} = 125;
}

if (!defined($ENV{yellow_pcnt})) {
    $ENV{yellow_pcnt} = 90;
}

if (!defined($ENV{root_thresh})) {
    $ENV{root_thresh} = 500000;
}

# only send email to admin around 4am
my ( $null, $minutes, $hour) = localtime;
my $time_to_send_admin_mail = 0;
if ($hour == 4 && $minutes < 15) {
    $time_to_send_admin_mail = 1;
}

# mstauber
if ($DEBUG == "1") { $time_to_send_admin_mail = 1; }

my %am_states = am_get_statecodes();

my $ret;

# check if root is getting filled. if so, suspend CCE. if not, restore it.
open(DF, "/bin/df -lP / |");
while (<DF>) {
    if (/^\/dev\//) {
	my ($device, $size, $used, $avail, $percent, $mount) = split(/ +/);
	set_disks_refresh();
	if ($ENV{suspend_cce} && $avail < $ENV{root_thresh}) {
	    $ret = system(( '/usr/sausalito/sbin/cce_lock.pl', '--lock', '--reason=[[base-disk.suspended_cce]]'));
	    print "[[base-disk.suspended_cce]]";
	    exit $am_states{AM_STATE_RED};
	} else {
	    $ret = system(( '/usr/sausalito/sbin/cce_lock.pl', '--sync', '--reason=[[base-backupcontrol.locked]]'));
	}
    }
}
close(DF);


my @dev_warnings = ();

# get mounts from df | grep "^/dev/"
# check if device is readwrite in /proc/mounts
# if more than $red_percent used or less than $red_free available, then red
# if more than $yellow_percent used or less than $yellow_free available, then yellow
my ($rw, $dev_status, $worst_dev_status, $server_status);
$worst_dev_status = $am_states{AM_STATE_GREEN};

open (DF, "/bin/df -lP |");
while (<DF>) {
    chomp;
    if (/^\/dev\//) {
	my ($device, $size, $used, $avail, $percent, $mount) = split(/ +/);
	$dev_status = $am_states{AM_STATE_GREEN};

	$rw = `grep "^$device" /etc/mtab | grep '\\brw\\b'`;
	if (!$rw) {
	    next;
	}

	$percent =~ s/%//;
	$avail = $avail / 1024; # AM thresholds are in megs, df is in kbytes
	$size = $size / 1024;

	if (($percent > $ENV{red_pcnt}) || (($avail < $ENV{red_free}) && ($avail > $ENV{red_free} * 2))) {
	    $dev_status = $am_states{AM_STATE_RED};
	    push @dev_warnings, "[[base-disk.amDiskWarning,fs=\"$mount\",pcnt=\"$percent\",free=\"$avail\"]]"; 
	} elsif (($percent > $ENV{yellow_pcnt}) || (($avail < $ENV{yellow_free}) && ($size > $ENV{yellow_free} * 2))) {
	    push @dev_warnings, "[[base-disk.amDiskWarning,fs=\"$mount\",pcnt=\"$percent\",free=\"$avail\"]]"; 
	    $dev_status = $am_states{AM_STATE_YELLOW};
	}

	$worst_dev_status = $worst_dev_status > $dev_status ? $worst_dev_status : $dev_status;
    }
}
close(DF);

$server_status = $worst_dev_status;

my ($am_oid) = $cce->findx('ActiveMonitor');
my ($okx, $am) = $cce->get($am_oid, 'Disk');

my $now = time;

# ALGORITM
# use Quota.pm to find users over quota
# if they are newly over or it's been a day since we last emailed, then email them.
# record over_quota status every time
# record lastmailed time if we've mailed them

# use Quota.pm to find sites over quota
# if they are over quota, then send mail to admin if necessary
# record user_over_quota status on vsite if one of it's users is over quota
# record lastmailed time if we've mailed the admin about the site


my @site_warnings = ();
my @user_warnings = ();

my ($user, $disk, $user_ok);
my ($user_status);
my (@oids) = ();
my ($newly_over, $send_mail);
my (%site_users) = (); # sites with users over quota. using a hash to remove dups
my (@cce_users) = (); # users that are over quota
my (%lastmailed_users) = (); # users that are over quota who we need to mail. using a hash to remove dups.
my (@lastmailed_sites) = (); # vsites that are over quota who we need to mail
my (%users_to_warn) = (); # users that are over quota who we need to mail, and their associated vsites

my @users_over_quota = users_over_quota();
foreach my $username (@users_over_quota) {
    # check if system user is a CCE user
    my ($oid) = $cce->find('User', {'name' => $username});
    if (!$oid) {
	$DEBUG && print "user $username doesn't exist in CCE, skipping\n";
	# user doesn't exist in CCE, skipping
	next;
    }

    # record which users we need to update in CCE
    push @cce_users, $username;

    # flag that this site has a user over quota
    ($user_ok, $user) = $cce->get($oid);
    $site_users{$user->{site}} = 1;

    # this flag hasn't been updated since last AM run
    # so it shows if we were over quota LAST TIME
    ($user_ok, $disk) = $cce->get($oid, 'Disk');
    my $newly_over = !$disk->{over_quota};

    # mstauber
    if ($DEBUG == "1") { $newly_over = "1"; }

    # has it been over a day since we last mailed them?
    $send_mail = $disk->{lastmailed} < time - 3600*24; 

    # send mail, if necessary
    if ($am->{mail_user} && ($newly_over || $send_mail)) {
	$DEBUG && print "notifying the user $username about quota\n";
	$lastmailed_users{$username} = 1;
	$users_to_warn{$username} = $user->{site};
    }
    
    if ($am->{mail_admin_on_user} &&
	$time_to_send_admin_mail) { #it's 4 am
	$DEBUG && print "notifying the admin about $username about quota\n";
	$lastmailed_users{$username} = 1;

	# Get FQDN of the site that this overquota-user belongs to:
	my ($oidvsite) = $cce->find('Vsite', {'name' => "$user->{site}"});
	my $uservsite_ok = "";
	my $user_vsite = "";
	($uservsite_ok, $user_vsite) = $cce->get($oidvsite);

	push @user_warnings, $username . '@' . $user_vsite->{fqdn};

    }	
    
    $DEBUG && print "done processing $username\n";
}

# flag the user as being over quota
# reset old flags
@oids = $cce->find('User', { 'Disk.over_quota' => 1 });
foreach my $user (@oids) {
    ($user_ok) = $cce->set($user, 'Disk', { 'over_quota' => 0 });
    if (!$user_ok) {
	$DEBUG && print STDERR "couldn't clear over_quota flag on oid $user\n";
    }
}

# update new flag
foreach my $username (@cce_users) {
    my ($oid) = $cce->find('User', { 'name' => $username });
    ($user_ok) = $cce->set($oid, 'Disk', { 'over_quota' => 1 });
    if (!$user_ok) {
	$DEBUG && print STDERR "couldn't set over_quota flag on user $user\n";
    }
}


# flag the site that has a user over quota
# reset old flags
@oids = $cce->find('Vsite', { 'Disk.user_over_quota' => 1 });
foreach my $site (@oids) {
    my ($site_ok) = $cce->set($site, 'Disk', { 'user_over_quota' => 0 });
    if (!$site_ok) {
	$DEBUG && print STDERR "couldn't clear user_over_quota for oid $site\n";
    }
}


# update the new flags
foreach my $site (keys(%site_users)) {
    my ($oid) = $cce->find('Vsite', { 'name' => $site });
    if (!$oid) {
	# an admin user, not on a vsite
	next;
    }

    my ($ok) = $cce->set($oid, 'Disk', { 'user_over_quota' => 1 });
    if (!$ok) {
	$DEBUG && print STDERR "couldn't set user_over_quota for site $site\n.";
	next;
    }
}
	       
my @sites_over_quota = sites_over_quota();

my $site_over_quota = 0;
foreach my $site (@sites_over_quota) {
    # check if system group is a CCE vsite
    my ($oid) = $cce->find('Vsite', {'name' => $site});
    if (!$oid) {
	$DEBUG && print "site $site doesn't exist in CCE, skipping\n";
	# site doesn't exist in CCE, skipping
	next;
    }

    if ( $server_status < $am_states{AM_STATE_YELLOW} ) {
	# site over quota means state yellow
	$server_status = $am_states{AM_STATE_YELLOW};
    }

    # if site over quota, email if necessary
    if ($am->{mail_admin_on_vsite} && $time_to_send_admin_mail) {
	push @lastmailed_sites, $site;
	my ($site_ok, $site_obj) = $cce->get($oid);
	push @site_warnings, $site_obj->{fqdn};
	$DEBUG && print "notifying the admin about $site_obj->{fqdn} about quota\n";
    }

}


# FINALLY, BATCH UPDATES AND EMAILING

# email admin about the affected sites and users
my @am_output = ();
my @mail_output = ();

if (@site_warnings) {
    #push @mail_output, "[[base-disk.sites_over_quota,sites=\"" . join(',', @site_warnings) . "\"]]";
    my $adm_msg_site_bdy = $i18n->get("[[base-disk.sites_over_quota,sites=\"" . join(',', @site_warnings) . "\"]]");
    push @mail_output, $adm_msg_site_bdy;
}
if (@user_warnings) {
    my $adm_msg_bdy = $i18n->get("[[base-disk.users_over_quota,users=\"\"]]"); # Can't entirely remove 'users' parameter w/o editing all locales, too.
    push @mail_output, $adm_msg_bdy;
    # Moved generation of list of usernames outside the i18n call for better readability:
    my $adm_msg_bdy_extra = join(' \n', @user_warnings);
    foreach my $user_single (@user_warnings) {
	push @mail_output, $user_single;
    }
}

# mail to admin
if (@mail_output) {
    $DEBUG && print "mailing to admin: " . join(',', @mail_output) . "\n";

    my ($ok, $am) = $cce->get($am_oid);
    my @am_recips = $cce->scalar_to_array($am->{alertEmailList});
    my $recips = join(',', @am_recips);

    # We don't want to use the crappy SendEmail::sendEmail:
    # SendEmail::sendEmail($recips, 'admin', '[[base-disk.userOverQuota]]', join("\n", @mail_output));

    # Set locale for i18n
    my @l1_oid = ();
    ($l1_oid) = $cce->find('User', {'name' => 'admin'});
    my ($l1_ok, $user_locale) = $cce->get($l1_oid);

    my $locale = $user_locale->{localePreference};

    if (not -d "/usr/share/locale/$locale" && not -d "/usr/local/share/locale/$locale") {
    	$locale = I18n::i18n_getSystemLocale($cce);
    }

    # Build the message using MIME::Lite instead:
    $i18n->setLocale($locale);
    my $a_subject = $host . ": " . $i18n->get('[[base-disk.userOverQuota]]');
    my $a_body = join("\n", @mail_output);

    my $send_msg = MIME::Lite->new(
        From     => "root <root>",
        To       => $recips,
        Subject  => $a_subject,
        Data     => $a_body
    );

    $send_msg->attr("content-type"         => "text/plain");

    # Set charset based on locale: 
    if (($locale eq "ja_JP") || ($locale eq "ja")) { 
        $send_msg->attr("content-type.charset" => "EUC-JP"); 
    } 
    else { 
        $send_msg->attr("content-type.charset" => "UTF-8"); 
    } 

    # Out with the email:
    $send_msg->send;

}


# mail to users
while (my ($user, $site) = each(%users_to_warn)) {
    # need to get complete email address
    my ($oid) = $cce->find('Vsite', { 'name' => $site });
    my $email;

    if (!$oid) {
	# an admin user, not on a vsite
	#
	$email = $user;
    } else {
	my ($site_ok, $site) = $cce->get($oid);
	$email = $user . '@' . $site->{fqdn};
    }

    # SendEmail::sendEmail($email, 'admin', '[[base-disk.userOverQuota]]', '[[base-disk.overQuotaMsg]]');

    # Set locale for i18n
    my @l2_oid = ();

    ($l2_oid) = $cce->find('User', {'name' => $user});
    my ($l2_ok, $user_locale) = $cce->get($l2_oid);

    my $locale = $user_locale->{localePreference};
    if (not -d "/usr/share/locale/$locale" && not -d "/usr/local/share/locale/$locale") {
    	$locale = I18n::i18n_getSystemLocale($cce);
    }

    # Build the message using MIME::Lite instead:
    $i18n->setLocale($locale);
    my $u_subject = $i18n->get('[[base-disk.userOverQuota]]');
    my $u_body = $i18n->get('[[base-disk.overQuotaMsg]]') . "\n\n";

    my $send_msg = MIME::Lite->new(
        From     => "root <root>", 
        To       => $email,
        Subject  => $u_subject,
        Data     => $u_body
    );

    # Set content type:
    $send_msg->attr("content-type"         => "text/plain");

    # Set charset based on locale: 
    if (($locale eq "ja_JP") || ($locale eq "ja")) { 
        $send_msg->attr("content-type.charset" => "EUC-JP"); 
    } 
    else { 
        $send_msg->attr("content-type.charset" => "UTF-8"); 
    } 

    # Out with the email:
    $send_msg->send;
}

# update lastmailed flags
foreach my $username (keys(%lastmailed_users)) {
    my ($oid) = $cce->find('User', { 'name' => $username });
    my ($user_ok) = $cce->set($oid, 'Disk', { 'lastmailed' => $now });
}
foreach my $sitename (@lastmailed_sites) {
    my ($oid) = $cce->find('Vsite', { 'name' => $sitename });
    my ($site_ok) = $cce->set($oid, 'Disk', { 'lastmailed' => $now });
}

# AM output
# AM warnings include server usage
# and a simple note that a site is over quota and to look for a 2nd email about this
if (@dev_warnings) {
    push @am_output, @dev_warnings;
} 
if ($site_over_quota) {
    push @am_output, '[[base-disk.site_over_quota]]';
}

if (!@am_output) {
    push @am_output, '[[base-disk.amDiskOk]]';
}

print join("\n", @am_output);
exit $server_status;


# helper functions
sub users_over_quota {
    my ($name, $null, $uid, $user_gid, $all_gid, $dir);
    my (@users_over_quota) = ();
    my @cceusers;

    # fetch all CCE users
    my @alluseroids = $cce->find('User', '');
    foreach my $entry (@alluseroids) {
        (my $ok, my $user) = $cce->get($entry);
        push(@cceusers,$user->{name});
    }

    # now we do getpwent() and only lookup users who are CCE users
    setpwent();
    while (($name, $null, $uid, $user_gid, $null, $null, $null, $dir) = getpwent()) {
	my $userfound = 0;
	if (grep {$_ eq $name} @cceusers) {
	    $userfound = 1;
	}

	if($userfound==0) {
	    next;
	}

	my $dev = Quota::getqcarg($dir);

	if (!$dev) {
	    # $dev may not always be set. Has problems on extra-admins, which then causes the error:
	    # Use of uninitialized value in subroutine entry at /usr/sausalito/swatch/bin/am_disk.pl line 348, <GEN1> line 27.
	    # So if $dev is not defined, we set a safe default:
	    $dev = "/home";
	}
	my ($used, $quota) = Quota::query($dev, $uid);

	if (! defined $quota || $quota == 0) {
	    # no quota set

	    $DEBUG && print "no quota set on $user, skipping\n";
	    next;
	}

	if ($used >= ($quota * $ENV{red_pcnt} / 100)) { # 90 percent used
	    $DEBUG && print "$name is over quota. used $used of $quota\n";
	    push @users_over_quota, $name;
	}

    }
    endpwent();
    return @users_over_quota;
}

sub sites_over_quota {
    my @sites_over_quota = ();
    my %hash = ();

    # find all disks
    my $cce = new CCE;
    $cce->connectuds();
    my (@disks) = $cce->find('Disk', { 'isHomePartition' => 1 });

    # find all mountpoints
    my @mounts = ();
    foreach my $disk (@disks) {
	my ($ok, $obj) = $cce->get($disk);
	push @mounts, $obj->{mountPoint};
    }

    # this relies on Alpine's hashing scheme. if the hashing scheme changes
    # or this is installed on another product, then this will need to change.
    # find all numeric hashes
    my @hashdirs = ();
    foreach my $mount (@mounts) {
	if (-d "$mount/.sites") {
		opendir(SITEDIR, "$mount/.sites");
		my @dirs = map { "$mount/.sites/$_" } grep /^\d+$/, readdir(SITEDIR);
		push @hashdirs, @dirs;
		close(SITEDIR);
	}
    }

    # find all dirs in all hashes
    my @some_sites = ();
    foreach my $hash (@hashdirs) {
	opendir(HASH, $hash);
	@some_sites = grep !/^\./, readdir(HASH);
	close(HASH);

	# lookup dev
	my $dev = Quota::getqcarg($hash);
	my $is_group = 1;

	foreach my $site (@some_sites) {
	    # lookup gid
	    my ($name, $null, $gid) = getgrnam($site);

	    # do query
	  if ($gid) {
	    my ($used, $quota) = Quota::query($dev, $gid, $is_group);

	    if (! defined $quota || $quota == 0) {
		# no quota set
		$DEBUG && print "no quota set on $name, skipping\n";
		next;
	    }

	    if ($used > $quota) { 
		$DEBUG && print "$name is over quota. used $used of $quota\n";
		push @sites_over_quota, $name;
	    } else {
		$DEBUG && print "$name is NOT over quota.\n";
	    }
	  }
	}
    }

    return @sites_over_quota;
}

# Set Disk.refresh on all disks if our root fs is getting full
sub set_disks_refresh {
    my $diskcce = new CCE;
    $diskcce->connectuds();
    my (@disks) = $diskcce->find('Disk');
    my $diskoid;

    foreach $diskoid (@disks) {
	# We don't care if it fails
	$diskcce->set($diskoid, '', { 'refresh' => time });
    }
    $diskcce->bye();
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
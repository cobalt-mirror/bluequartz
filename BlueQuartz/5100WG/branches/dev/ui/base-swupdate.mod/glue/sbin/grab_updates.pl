#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: grab_updates.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
##########################################################################
#                                                                        #
# Filename: grab_updates.pl                                              #
#                                                                        #
# Author(s): Moshen Chan <mchan@cobalt.com>                              #
#            mangled severely by asun@cobalt.com                         #
#                                                                        #
# Description: Query Cobalt Updates server for new packages              #
#                                                                        #
# Only supports one update server for now                                #
#                                                                        #
# 1. Gather system info (product, serialnumber)                          #
# 2. Get Software Update settings from CCE (move this to config file?)   #
# 3. Query update server and parse data                                  #
# 4. Put all relevant updates into CCE as Package objects                #
#                                                                        #
##########################################################################

use CCE;
use SWUpdate;
use Sauce::Service;

use vars qw($opt_c $opt_u);
use Getopt::Std;
getopts("cu");

# where is everything?
my $tar_cmd = '/bin/tar';
my $tmpdir = swupdate_tmpdir;
my $packagedir = $tmpdir . '/info.' . $$;
my $package_tmp = "$tmpdir/package.$$.tmp";
my $package_list = "$packagedir/package_list";

# Maximum number of seconds we can delay this script (if running from a
# cronjob)
my $delaySeconds = 1800;

# we accept three types of invocations:
# cronjob (-c) --> email notification only
# ui      (-u) --> single line status exit messages, no email
# other        --> same info as email notification but no email
if ($opt_c) {
    sleep(int(rand($delaySeconds)));
    $invocation = 'cron';
} elsif ($opt_u) {
    $invocation = 'ui';
} else {
    $invocation = 'other';
}

################################################################
#   OBTAIN SYSTEM INFORMATION
################################################################

# initialize system information. swupdate_init will spit out
# error messages if appropriate
my %settings;
my ($ret, $status, $info) = swupdate_getsettings(\%settings);
if ($ret) {
    swupdate_notify($invocation, $status eq 'up' ? 'ERROR' : 'INFO', $info); 
    exit 1;
}

# Run a wget query to the update server (we can use the HTTP module
# but this is easier to debug). also, the HTTP module has nasty 
# memory usage characteristics
my ($get, $query, @locations, @location_oids, @stat);

@locations = @{$settings{location}};
@location_oids = @{$settings{location_oid}};
@stat = @{$settings{location_status}};
$swversion = swupdate_version;

# double escape & to prevent wget from unescaping on me. ugh.
my $installed = $settings{installed};
$installed =~ s/\&/sprintf("%%%02X",ord('&'))/ge;
$installed =~ s/([^A-z0-9+\_\-])/sprintf("%%%02X",ord($1))/ge;

$get ='?ui=no';
$get .= "&version=$swversion";
$get .= "&product=$settings{product}";
$get .= "&build=$settings{build}";
$get .= "&serialnum=$settings{serialNumber}";
$get .= "&installed=$installed";
$get .= "&notificationMode=$settings{notifymode}";

cleanup($packagedir);
`mkdir -p $packagedir`;
`chmod -R 700 $packagedir`;

my @packages = ();
my $i;
for($i = 0; $i <= $#locations; $i++) {
    my $location = $locations[$i];
    my $location_oid = $location_oids[$i];
    $info = $stat[$i] eq 'up' ? 'ERROR' : 'INFO';

    my ($error, $state, $message, $count);
    ($error, $state, $message, $status) = swupdate_download($package_tmp, $location, $get);
    if ($error) {
	$info = 'INFO' if $state eq 'up';
	swupdate_notify($invocation, $info, $message, $status);
	unlink($package_tmp);
	cleanup($packagedir, $state, $location_oid);
	if ($state eq 'up') {
	    destroy_pkgs('default');
	    exit 0;
	}
	exit 1;
    }

    # check the package type
    ($error, $state) = swupdate_verifyuntar($package_tmp, $packagedir);
    unlink($package_tmp);
    if ($error lt 0) {
	swupdate_notify($invocation, $info, 'queryerror', \%settings);
	cleanup($packagedir, 'down', $location_oid);
	exit 1;
    }

    # the server is apparently okay. 
    my @packageList = ();
    $count = swupdate_parselist($package_list, \@packageList);
    push @packages, @packageList;

    # darn. it really wasn't okay.
    if ($count < 0) {
	swupdate_notify($invocation, $info, 'badpkgfmt', \%settings);
	cleanup($packagedir, 'down', $location_oid);
	exit 1;
    }
}

# this is an innocuous error, so don't mark the server as down.
unless (@packages) {
    swupdate_notify($invocation, 'INFO', 'nopkgavail', \%settings);
    destroy_pkgs('default');
    cleanup($packagedir, 'up', $location_oid);
    exit 0;
}

my $cce;
$cce = new CCE;
$cce->connectuds;

################################################################
#   UPDATE CCE WITH THE NEW INFO
################################################################
# Generate a list of packages to Alert (Software Update Light
# and Email Notification)
my (%alertPackages, $package);
foreach $package (@packages) {
    # skip non-update packages if necessary
    next if (($settings{notifymode} eq 'updates') and 
	     ($package->{packageType} ne 'update'));

    my ($found) = $cce->find('Package', { 'vendor' => $settings{vendor},
					  'name' => $settings{name},
					  'version' => $settings{version},
					  'updateServer' => 'default' });
    $alertPackages{$package} = $package unless $found;
}

# destroy existing packages
my @destroyed = destroy_pkgs('default', $cce);

# add the packages to cce. if it's okay, save the relevant information
my @alerts;
foreach $package (@packages) {
    my $id = $package->{vendor} . ':' . $package->{name} . ':' . $package->{version};
    my $isOld = grep /^$id$/, @destroyed;
    $package->{new} = 0 if $isOld;
    my ($err, $info) = swupdate_add_pkginfo($cce, $package);
    if ($err > -1) {
	$version = swupdate_fromccevers($package->{version});
	my $path = "$packagedir/$package->{vendor}-$package->{name}-$version";
	swupdate_install_pkginfo($path, $package->{vendor}, $package->{name}, 
				 $package->{version});
	next unless $alertPackages{$package};
	push @alerts, $package if (not defined($package->{new}) and $package->{isVisible});
    } 
}

# everything is okay. send a notification
if (@alerts) {
	swupdate_notify($invocation, 'NOTIFY', '', \@alerts);
}
Sauce::Service::service_run_init('admserv', 'reload');
cleanup($packagedir, 'up', $location_oid);
exit 0;

sub cleanup
{
    my ($dir, $status, $oid) = @_;

    # a little sanity checking is good
    return unless $dir =~ /^$tmpdir\/\S+$/;
    `rm -rf $dir`;

    # fix up status
    if ($status and $oid) {
	my $cce = new CCE;
	$cce->connectuds;
	$cce->set($oid, '', { 'status' => $status });
	$cce->bye('SUCCESS');
    }
}


# Destroy all previous available packages (Done to refresh corrections to
# package availability status, location, etc..in case they change)
sub destroy_pkgs
{
    my ($server, $cceobj) = @_;
    my $cce;

    unless ($cceobj) {
	$cce = new CCE;
	$cce->connectuds;
    } else {
	$cce = $cceobj;
    }
    my @returnArray = $cce->find('Package',
				 {'updateServer' => $server,
				  'installState' => 'Available'});

    my @destroyed = ();
    foreach my $objectID (@returnArray) {
	# save destroyed package list
	my ($success, $obj) = $cce->get($objectID);
	push @destroyed, $obj->{vendor} . ':' . $obj->{name} . ':' . $obj->{version} 	if $obj->{isVisible};
	$cce->destroy($objectID);
    }
    $cce->bye('SUCCESS') unless $cceobj;
    return @destroyed;
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

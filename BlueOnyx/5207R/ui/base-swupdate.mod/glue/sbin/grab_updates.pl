#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: grab_updates.pl
##########################################################################
#                                                                        #
# Filename: grab_updates.pl                                              #
#                                                                        #
# Author(s): Moshen Chan <mchan@cobalt.com>                              #
#            mangled severely by asun@cobalt.com                         #
#            severely untangled by mstauber@blueonyx.it                  #
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

# Notice by mstauber (2015-07-12):
#
# The old mechanism for this was horribly inefficient. It took around 20 seconds
# to grab and process 183 PKGs off NewLinQ (assuming 'All Packages Bundle' linked).
# The reason for this was that all 'Available' PKGs in CODB got deleted. Then the
# whole shebang we pulled off NewLinQ got CREATEd again. These DESTROY and CRÈATE
# transactions aren't 'cheap' and take up valueable processing time. 
#
# So I went in and added some extensive extra logic using hash-tables. We pull
# the stream of 'Available' PKGs off NewLinQ into an array as before. But we also
# built a hash of it for easy indexing. That hash has keys that consist of both
# name and version in the format "$name:version". Which will make unique keys.
#
# We then build hashes of all PKGs in CODB that are listed as 'Installed' and do
# the same for all that are listed as 'Available'. We build up to three hashes
# for each with keys being just name, name:version and location (if applicable).
#
# We then mark any CODB Package that's listed as 'Available' but is no longer
# served off NewLinQ for deletion.
#
# Once that's done we iterate through the PKGs reported off NewLinQ and look
# them up against our hashes for the Installed/Available PKGs in CODB. We set
# and unset the 'new' flag accordingly and if something is reported off NL
# which we don't yet have in CODB, then it will be added.

# The only CREATE transactions we still do are for those PKGs that NL reported,
# but which aren't yet in CODB.
#
# The only DESTROY transactions will be for PKGs that are still listed in CODB
# as 'Available', but are no longer served off NL.
#
# The only SET transactions will be toggling the 'new' flag off and on.
#
# While the new logic for that is extensive and expensive, it still cut down the
# processing time by 14-16 seconds and brought it down to 3-5 seconds in total.
#
# Note: We still run the old destroy_pkgs() if we get no PKGs at all from NL,
# or if there is an error. This is perfectly fine. We just don't delete all
# 'Available' PKGs from CODB during regular updates.
#

use CCE;
use SWUpdate;
use Sauce::Service;
use Data::Dumper;
use Sort::Versions;
use List::MoreUtils qw{any}; 

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
    #sleep(int(rand($delaySeconds)));
    $invocation = 'cron';
}
elsif ($opt_u) {
    $invocation = 'ui';
}
else {
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
%alertPackages = {};

# Get 'System' Object:
@system_oid = $cce->find('System');
($ok, $System) = $cce->get($system_oid[0]);

# Get system SWUpdate settings out of CODB:
($ok, $SWUpdate) = $cce->get($system_oid[0], "SWUpdate");
@AutoUpdateList = $cce->scalar_to_array($SWUpdate->{'AutoUpdateList'});

# Take all NewLinQ reported PKGs and shove them into the hash $NL_Packages:
$NL_Packages;
$NL_Packages_flat;
@PKG_doubles_NL = ();
$PKG_doubles_NL_hash;
foreach $package (@packages) {
    $NL_Packages->{"$package->{'name'}:$package->{'version'}"} = $package;
    # This hash will contain all PKGs and their different versions that are available on NL:
    $PKG_doubles_NL_hash->{"$package->{'name'}"}->{"$package->{'name'}:$package->{'version'}"} = $package->{'version'};
    if ($NL_Packages_flat->{"$package->{'name'}"}) {
        # This array will give us a general idea if a PKG is served off NL in different versions:
        push @PKG_doubles_NL, $package->{'name'};
    }
    $NL_Packages_flat->{"$package->{'name'}"} = $package;
}

#
### Find all Packages in CODB:
#

$CODB_Installed_PKGs;
$CODB_Installed_PKGs_flat;

$CODB_Available_PKGs;
$CODB_Available_PKGs_flat;
$CODB_Available_PKGs_locations;

# Get all PKGs with install state 'Installed':
@CODB_Installed_PKGs_OID = ();
(@CODB_Installed_PKGs_OID) = $cce->find('Package', {'installState' => 'Installed'});
foreach $package (@CODB_Installed_PKGs_OID) {
    ($ok, $PKG) = $cce->get($package);
    $CODB_Installed_PKGs->{"$PKG->{'name'}:$PKG->{'version'}"} = $PKG;
    $CODB_Installed_PKGs_flat->{$PKG->{'name'}} = $PKG;
}

# Get all PKGs with install state 'Available':
@CODB_Available_PKGs_OID = ();
(@CODB_Available_PKGs_OID) = $cce->find('Package', {'installState' => 'Available'});
foreach $package (@CODB_Available_PKGs_OID) {
    ($ok, $PKG) = $cce->get($package);
    $CODB_Available_PKGs->{"$PKG->{'name'}:$PKG->{'version'}"} = $PKG;
    $CODB_Available_PKGs_flat->{$PKG->{'name'}} = $PKG;
    $CODB_Available_PKGs_locations->{$PKG->{'location'}} = $PKG;
}

# Check for PKGs that are in CODB listed as 'Available', but are no longer served off NL:
@PKGs_to_Delete =();
while ( my ($pkgname, $value) = each(%$CODB_Available_PKGs) ) {
    $name = $value->{'name'};
    $version = $value->{'version'};
    $id = "$name:$version";
    if (!$NL_Packages->{$id}) {
        push @PKGs_to_Delete, $CODB_Available_PKGs->{$id}->{'OID'};
        if ($CODB_Available_PKGs->{$id}->{'isVisible'}) {
            push @destroyed, $CODB_Available_PKGs->{$id}->{'vendor'} . ':' . $CODB_Available_PKGs->{$id}->{'name'} . ':' . $CODB_Available_PKGs->{$id}->{'version'};
        }
    }
}

# Check the @packages stream from NewLinQ:
@xcleaned_packages = ();
$AddedForUpdate;
@UpdateAlert = ();
foreach $package (@packages) {
    $id = "$package->{'name'}:$package->{'version'}";
    $id_flat = "$package->{'name'}";

    # Handle Auto-Install/Auto-Update special case:
    if (($package->{'autoinstall'} eq "1") || ($package->{'autoupdate'} eq "1")) {
            $package->{'autotrigger'} = time();
            push @xcleaned_packages, $package;
            $AddedForUpdate->{$id} = $package;
    }

    # Find out if a PKG from the NewLinQ stream is an update to a PKG that is already installed:
    if ($CODB_Installed_PKGs_flat->{$id_flat}) {
        if ($package->{'version'} ne $CODB_Installed_PKGs_flat->{$id_flat}->{'version'}) {
            # PKG is an update to something that is already installed. Add it to @xcleaned_packages and mark it as new:
            if (in_array(\@PKG_doubles_NL, $id_flat)) {
                # PKG is available in different versions.

                # Check what versions are available off NL and which one is installed:
                $available_version = $PKG_doubles_NL_hash->{$id_flat}->{$id};
                $installed_version = $CODB_Installed_PKGs_flat->{$id_flat}->{'version'};

                # Compare versions:
                $compare_result = SWUpdate::compareVersion($installed_version, $available_version);
                if ($compare_result eq "-1") {
                    # Set 'new' flag to '1' as PKG off NL is newer:
                    $package->{'new'} = "1";
                }
                else {
                    # Set 'new' flag to '0' as PKG off NL is of same age or older:
                    $package->{'new'} = "0";
                }
            }
            elsif (in_array(\@AutoUpdateList, $id_flat)) {
                # Package off NL is newer than one already installed. AND is allowed to be autoupdated:
                $package->{'autoupdate'} = "1";
                $package->{'autotrigger'} = time();
                push @xcleaned_packages, $package;
            }
            else {
                # Set the 'new' flag:
                $package->{'new'} = "1";
                # Notify about availability:
                push @UpdateAlert, $package;
                # If Notify on Updates is enabled, then add this PKG to the notification list:
                if (($settings{notifymode} eq 'updates') || ($settings{notifymode} eq 'all')) {
                    $alertPackages{$id_flat} = $package;
                }
            }
            push @xcleaned_packages, $package;
            $AddedForUpdate->{$id} = $package;
        }
    }

    # Check if a PKG off the NL stream is newer than one that we already have listed as 'Available' in CODB:
    if ((!$CODB_Available_PKGs_locations->{$package->{'location'}}) && ($CODB_Installed_PKGs->{$id}->{'version'} ne $package->{'version'})) {
        # Actually: This should no longer fire - at all. As we delete retired PKGs. But better safe than sorry.
        if (in_array(\@PKG_doubles_NL, $id_flat)) {
            # PKG is available in different versions. Set 'new' flag to '0':
            $package->{'new'} = "0";
        }
        else {
            $package->{'new'} = "1";
            # If Notify on Updates is enabled, then add this PKG to the notification list:
            if (($settings{notifymode} eq 'updates') || ($settings{notifymode} eq 'all')) {
                $alertPackages{$id_flat} = $package;
            }
        }
        push @xcleaned_packages, $package;
        $AddedForUpdate->{$id} = $package;
    }

    # Check if a PKG listed as 'Available' in CODB still has the 'new' flag set - even if it didn't update this time around:
    if (($CODB_Available_PKGs_locations->{$package->{'location'}}) && ($CODB_Available_PKGs->{$id}->{'new'} eq "1") && (!$AddedForUpdate->{$id})) {
        # Package $id is listed 'Available', didn't update AND has the NEW flag set. Resetting NEW flag:
        my ($ok) = $cce->set($CODB_Available_PKGs->{$id}->{'OID'}, '', { 'new' => '0' });
    }
}

# Handle delete of retired Packages no longer available on NL:
if (scalar(@PKGs_to_Delete) gt "0") {
    foreach $oid (@PKGs_to_Delete) {
        $cce->destroy($oid);
    }
}

# We only want to update or create the following PKGs in NewLinQ:
@packages = @xcleaned_packages;

# Add the packages to cce. if it's okay, save the relevant information
my @alerts;
foreach $package (@packages) {
    my $id = $package->{vendor} . ':' . $package->{name} . ':' . $package->{version};
    my $isOld = grep /^$id$/, @destroyed;
    $package->{new} = 0 if $isOld;
    my ($err, $info) = swupdate_add_pkginfo($cce, $package);
    if ($err > -1) {
        $version = swupdate_fromccevers($package->{version});
        my $path = "$packagedir/$package->{vendor}-$package->{name}-$version";
        swupdate_install_pkginfo($path, $package->{vendor}, $package->{name}, $package->{version});
        next unless $alertPackages{$package->{name}};
        if ($package->{isVisible}) {
            push @alerts, $package;
        }
    }
}

# Everything is okay. send a notification
if (@alerts) {
    swupdate_notify($invocation, 'NOTIFY', '', \@alerts);
}

# Cleanup:
cleanup($packagedir, 'up', $location_oid);
exit 0;

sub cleanup {
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
sub destroy_pkgs {
    my ($server, $cceobj) = @_;
    my $cce;

    unless ($cceobj) {
        $cce = new CCE;
        $cce->connectuds;
    }
    else {
        $cce = $cceobj;
    }
    my @returnArray = $cce->find('Package', {'updateServer' => $server, 'installState' => 'Available'});

    my @destroyed = ();
    foreach my $objectID (@returnArray) {
        # save destroyed package list
        my ($success, $obj) = $cce->get($objectID);
        push @destroyed, $obj->{vendor} . ':' . $obj->{name} . ':' . $obj->{version}    if $obj->{isVisible};
        $cce->destroy($objectID);
    }
    $cce->bye('SUCCESS') unless $cceobj;
    return @destroyed;
}

sub in_array {
    my ($arr,$search_for) = @_;
    my %items = map {$_ => 1} @$arr; # create a hash out of the array values
    return (exists($items{$search_for}))?1:0;
}

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#     notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#     notice, this list of conditions and the following disclaimer in 
#     the documentation and/or other materials provided with the 
#     distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#     contributors may be used to endorse or promote products derived 
#     from this software without specific prior written permission.
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
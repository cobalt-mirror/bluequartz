#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: updateCapabilities.pl
#

use CCE;
use Base::User::Capabilities;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
        &debug_msg("Debug enabled.\n");
}

my $cce = new CCE();

$cce->connectfd();

# get event data
my $oid = $cce->event_oid();
my $obj = $cce->event_object();

my $Capability = new Base::User::Capabilities($cce);

my %capLevels = map {$_ => 1} $cce->scalar_to_array($obj->{capLevels});

$oldCapLevels = $cce->scalar_to_array($obj->{capLevels});

my $caps = $Capability->expandCaps(\%capLevels); 

# make a list of all the caps that we are taking away from the user,
#  the current user MUST be able to 
my $old_obj = $cce->event_old();
my %old_capLevels = map {$_=>1} $cce->scalar_to_array($old_obj->{capLevels});
my $old_caps = $Capability->expandCaps(\%old_capLevels);

my %CurrentCapabilities = map {$_ => 1} $cce->scalar_to_array($old_obj->{capabilities});

# Get all known "reseller" caps:
@ResellerCapsOID = $cce->find('CapabilityGroup', { 'name' => 'reseller'});
(my $ok, $ResellerCapsObject) = $cce->get($ResellerCapsOID[0], '');
@ResellerCaps = $cce->scalar_to_array($ResellerCapsObject->{capabilities});
foreach $kr_caps (@ResellerCaps) {
  debug_msg("Known Reseller Caps: $kr_caps\n");
}

#
## We need to keep track of all known reseller capabilities that this user currently might have.
#

# Build and empty array for it to start cleanly:
@myCurrentResellerCaps = ();

#
## Walk through the current 'capabilities' to see if this user has a known reseller capability:
#

# Get the current user object:
(my $ok, $CurrentObjectBackup) = $cce->get($oid, '');

# Build an array from the 'capabilities' of this user:
@oldCapabilities = $cce->scalar_to_array($CurrentObjectBackup->{capabilities});

# Walk through these 'capabilities':
foreach $o_caps (@oldCapabilities) {
  debug_msg("Processing capability: $o_caps\n");
  # If that 'capabilities' entry is a known reseller cap, then add it to myCurrentResellerCaps:
  if (in_array(\@ResellerCaps, $o_caps)) {
      &debug_msg("Reseller has capability $o_caps now - keeping it.\n");
      # Add it to the array myCurrentResellerCaps:
      push @myCurrentResellerCaps, $o_caps;
  }
}

my %caps_being_removed;
my %caps_being_added;

foreach my $old_cap (keys %$old_caps) {
  # make a list of caps we want to remove
  if (! exists $caps->{$old_cap}) {
    $caps_being_removed{$old_cap} = 1; 
    debug_msg("Capability $old_cap is being removed.\n");
  } 
}

foreach my $newcap (keys %$caps) {
  # make a list of caps we are adding
  if (!exists $old_caps->{$newcap}) {
    $caps_being_added{$newcap} = 1;
  }
}

# check if we can infact do the caps we want to remove.
if (!$Capability->allowedCaps(\%caps_being_removed)) {
  $cce->warn("[[sauce-basic.needPermToRemovePerm]]");
  $cce->bye("FAIL");
  exit(0);
}

# check that this user has the privilege to do this..
# get info to see if i'm god.
#
# (Commented out for now as it periodically fails user creation on cmuImport)
#if (!$Capability->allowedCaps(\%caps_being_added)) {
#  $cce->warn("[[sauce-basic.permsNotAvailable]]"); 
#  $cce->bye("FAIL");
#  exit(0);
#}

##
foreach my $previoiusCapabilities (keys %$CurrentCapabilities) {
  debug_msg("Capability $previoiusCapabilities was set before.\n");
}

# Build an array with the keys from the caps that we want to set:
@CapKeysNewCaps = keys %$caps;

# Merge that with the identified reseller caps we had before:
@CapsMerged = (@CapKeysNewCaps, @myCurrentResellerCaps);

debug_msg("The following Caps have been merged:\n");
foreach $FM_caps (@CapsMerged) {
  debug_msg("CapsMerged: $FM_caps\n");
}

# Turn the result into a scalar:
my $capsScalar = $cce->array_to_scalar(@CapsMerged);

# Set the new capabilities:
my ($ok) = $cce->set($oid, "", 
            {
                capabilities => $capsScalar
            });

if (!$ok) {
  $cce->bye("FAIL");
} else {
  $cce->bye("SUCCESS");
}

sub in_array {
     my ($arr,$search_for) = @_;
     my %items = map {$_ => 1} @$arr; # create a hash out of the array values
     return (exists($items{$search_for}))?1:0;
}

sub debug_msg {
    if ($DEBUG) {
        my $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
}

1;

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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
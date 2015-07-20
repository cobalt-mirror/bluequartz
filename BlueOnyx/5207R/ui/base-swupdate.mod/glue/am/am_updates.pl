#!/usr/bin/perl -I/usr/sausalito/perl
#
# This Active Monitor component checks if installed Packages are
# up to date or if newer versions of them are available off NewLinQ.
#
# If Packages are set to 'autoinstall', an automatic install of those
# packages will be performed.
#
# If Packages are installed and newer version of said packages are
# available off NewLinQ, then an autoupdate can be performed if the
# package has autoupdate enabled.
#
# Automated installs and updates of Packages trigger an email 
# notification to all Active Monitor alert addresses.
#
# Active Monitor in the GUI will report the following states:
#
# noinfo:   Monitoring disabled.
# green:    All installed PKGs fully up to date.
# yellow:   Multi-Version PKG installed, newer one available.
# red:      Older PKG installed. Newer one available on NewLinQ.

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

use AM::Util;
use CCE;
use Sauce::Service;
use Data::Dumper;
use Sys::Hostname;
use MIME::Lite;
use Encode::Encoder;
use Encode qw(from_to);
use I18n;
use Sys::Hostname;
use POSIX qw(isalpha);

my $host = hostname();
my $now = localtime time;

my $cce = new CCE;
$cce->connectuds();

my %am_states = am_get_statecodes();

# Get 'System' Object:
@system_oid = $cce->find('System');
($ok, $System) = $cce->get($system_oid[0]);
my $system_lang = $System->{productLanguage};

my $i18n = new I18n();
# We can't email in Japanese yet, as MIME:Lite alone doesn't support it. We'd need MIME::Lite:TT:Japanese
# and a hell of a lot of dependencies to sort that out. So for now we hard code them to 'en_US' or 'en'
# for emailing purpose from within this script:
if (($system_lang eq "ja") || ($system_lang eq "ja_JP")) {
    $i18n->setLocale("en_US");

}
else {
    $i18n->setLocale($system_lang);
}

# Get system SWUpdate settings out of CODB:
($ok, $SWUpdate) = $cce->get($system_oid[0], "SWUpdate");
@AutoUpdateList = $cce->scalar_to_array($SWUpdate->{'AutoUpdateList'});

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
@PKG_doubles_NL = ();
$PKG_doubles_NL_hash;
@autoinstall_OIDs = ();
@autoupdate_OIDs = ();
(@CODB_Available_PKGs_OID) = $cce->find('Package', {'installState' => 'Available'});
foreach $package (@CODB_Available_PKGs_OID) {
    ($ok, $PKG) = $cce->get($package);
    $CODB_Available_PKGs->{"$PKG->{'name'}:$PKG->{'version'}"} = $PKG;
    # This hash will contain all PKGs and their different versions that are available on NL:
    $PKG_doubles_NL_hash->{"$PKG->{'name'}"}->{"$PKG->{'name'}:$PKG->{'version'}"} = $PKG->{'version'};
    if ($CODB_Available_PKGs_flat->{$PKG->{'name'}}) {
        # This array will give us a general idea if a PKG is served off NL in different versions:
        push @PKG_doubles_NL, $PKG->{'name'};
    }
    $CODB_Available_PKGs_flat->{$PKG->{'name'}} = $PKG;
    $CODB_Available_PKGs_locations->{$PKG->{'location'}} = $PKG;

    # Check if a Package has the 'autoinstall' flag set:
    if ($PKG->{'autoinstall'}) {
        push @autoinstall_OIDs, $PKG->{'OID'};
    }

    # Check if that PKG can be taken into consideration for autoupdates:
    if (in_array(\@AutoUpdateList, $PKG->{'name'})) {
        if ($PKG->{'location'}) {
            # Check if a PKG of that name is already installed:
            @PKG_oid = $cce->find('Package', {'name' => $PKG->{'name'}, 'installState' => 'Installed'});
            if (scalar(@PKG_oid) gt "0") {
                &debug_msg("Marking PKG $PKG->{'name'} for autoupdate.\n");
                push @autoupdate_OIDs, $PKG->{'OID'};
            }
        }
    }
}

# Check if what NL offers is newer than what we already have installed:
$status = 'AM_STATE_GREEN';
while ( my ($pkgname, $value) = each(%$CODB_Installed_PKGs_flat) ) {
    $name = $value->{'name'};
    $version = $value->{'version'};
    $id = "$name:$version";

    if (in_array(\@PKG_doubles_NL, $name)) {
        # PKG is available in different versions from NewLinQ. We may not want to warn about this.
        &debug_msg("Package $name is available in different versions off NL \n");
        if ($status ne "AM_STATE_RED") {
            &debug_msg("Setting Y\n");
            $status = "AM_STATE_YELLOW";
        }
    }
    elsif ($CODB_Available_PKGs_flat->{$name}) {
        &debug_msg("$name is available on NL as version: " . $CODB_Available_PKGs_flat->{$name}->{'version'} . "\n");
        &debug_msg("Setting R\n");
        $status = "AM_STATE_RED";
    }
    else {
        if (($status ne "AM_STATE_YELLOW") && ($status ne "AM_STATE_RED") && ($status ne "AM_STATE_NOINFO")) {
            &debug_msg("Setting G\n");
            $status = "AM_STATE_GREEN";
        }
    }
}

# Handle Autoinstall:
@PerformedAutoInstall = ();
$PerformedAutoInstallHash;

if (scalar(@autoinstall_OIDs) gt "0") {
    foreach $oid (@autoinstall_OIDs) {
        ($ok, $UpdatePKG) = $cce->get($oid);
        &debug_msg("Performing autoinstall of PKG $UpdatePKG->{'name'} \n");
        if ($UpdatePKG->{'location'}) {

            # Perform the autoinstall:
            &debug_msg("Performing autoinstall of PKG $UpdatePKG->{'name'} via $UpdatePKG->{'location'} \n");
            system("/usr/sausalito/sbin/pkg_install.pl $UpdatePKG->{'OID'}");

            # Keep track of what we did:
            push @PerformedAutoInstall, $UpdatePKG->{'name'};
            $PerformedAutoInstallHash->{"$UpdatePKG->{'name'}"}->{'version'} = $UpdatePKG->{'version'};
            $PerformedAutoInstallHash->{"$UpdatePKG->{'name'}"}->{'nameTag'} = $UpdatePKG->{'nameTag'};
        }
    }
}

# Handle Autoupdate:
@PerformedAutoUpdate = ();
$PerformedAutoUpdateHash;

if (scalar(@autoupdate_OIDs) gt "0") {
    foreach $oid (@autoupdate_OIDs) {
        ($ok, $UpdatePKG) = $cce->get($oid);
        &debug_msg("Performing autoupdate of PKG $UpdatePKG->{'name'} \n");
        if ($UpdatePKG->{'location'}) {

            # Perform the autoupdate:
            &debug_msg("Performing autoupdate of PKG $UpdatePKG->{'name'} via $UpdatePKG->{'location'} \n");
            system("/usr/sausalito/sbin/pkg_install.pl $UpdatePKG->{'OID'}");

            # Keep track of what we did:
            push @PerformedAutoUpdate, $UpdatePKG->{'name'};
            $PerformedAutoUpdateHash->{"$UpdatePKG->{'name'}"}->{'version'} = $UpdatePKG->{'version'};
            $PerformedAutoUpdateHash->{"$UpdatePKG->{'name'}"}->{'nameTag'} = $UpdatePKG->{'nameTag'};
        }
    }
}

#
### Handle Email notification:
#
if ((scalar(@PerformedAutoInstall) gt "0") || (scalar(@PerformedAutoUpdate) gt "0")) {
    $body = "\n";
}
if (scalar(@PerformedAutoInstall) gt "0") {
    $body .= $i18n->get("[[base-swupdate.autoinstallEmail_desc]]") . "\n\n";
    foreach $pkgname (@PerformedAutoInstall) {
        $body .= "  * " . $i18n->get($PerformedAutoInstallHash->{$pkgname}->{'nameTag'}) . " " . $PerformedAutoInstallHash->{$pkgname}->{'version'} . "\n";
    }
    $body .= "\n";
}
if (scalar(@PerformedAutoUpdate) gt "0") {
    $body .= $i18n->get("[[base-swupdate.autoupdateEmail_desc]]") . "\n\n";
    foreach $pkgname (@PerformedAutoUpdate) {
        $body .= "  * " . $i18n->get($PerformedAutoUpdateHash->{$pkgname}->{'nameTag'}) . " " . $PerformedAutoUpdateHash->{$pkgname}->{'version'} . "\n";
    }
    $body .= "\n";
}
if ((scalar(@PerformedAutoInstall) gt "0") || (scalar(@PerformedAutoUpdate) gt "0")) {
    $body .= $i18n->get("[[base-swupdate.autoEmail_finalwords]]") . "\n\n";

    my $conf = '/etc/swatch.conf';
    open(CONF, "< $conf");
    my @email_list;
    while (<CONF>) {
        chomp;
        my($key, $val) = split /\s*=\s*/, $_, 2;
        if ($key eq "email_list") {
            @email_list = split /\s*,\s*/, $val;
        }
    }

    # Need to convert to UTF-8. Ain'that funny. The source *IS* UTF-8:
    from_to($body, "windows-1252", "utf-8");

    my $subject = $host . ": " . Encode::encode("MIME-B", $i18n->get("[[base-swupdate.AutoEmailSubject]]"));
    my $to;
    foreach $to (@email_list) {

        # Build the message using MIME::Lite instead:
        my $send_msg = MIME::Lite->new(
            From     => "root <root>",
            To       => $to,
            Subject  => $subject,
            Data     => $body,
            Charset => 'utf-8'
        );

        # Set content type:
        $send_msg->attr("content-type"         => 'text/plain');
        $send_msg->attr("content-type.charset" => "utf-8");

        # Out with the email:
        $send_msg->send;
    }
}

#
### Return the correct AM state:
#

if ($status eq "AM_STATE_GREEN") {
    &debug_msg("PKGs are up to date. \n");
    print $ENV{greenMsg};
    $cce->bye('SUCCESS');
    exit $am_states{AM_STATE_GREEN};
}
elsif ($status eq "AM_STATE_YELLOW") {
    &debug_msg("A certain Package that you have installed is available in different versions off NewLinQ. You have the older version of this Package installed. \n");
    print $ENV{yellowMsg};
    $cce->bye('SUCCESS');
    exit $am_states{AM_STATE_YELLOW};
}
elsif ($status eq "AM_STATE_RED") {
    &debug_msg("One or more PKGs need updates and updates are available. \n");
    print $ENV{redMsg};
    $cce->bye('SUCCESS');
    exit $am_states{AM_STATE_RED};
}
else {
    # Service is disabled:
    &debug_msg("Not checking for PKG updates.\n");
    $cce->bye('SUCCESS');
    exit $am_states{AM_STATE_NOINFO};    
}

# We should never get here:
$cce->bye('SUCCESS');
exit(0);

#
### Subroutines:
#

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

sub in_array {
    my ($arr,$search_for) = @_;
    my %items = map {$_ => 1} @$arr; # create a hash out of the array values
    return (exists($items{$search_for}))?1:0;
}

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#    notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#    notice, this list of conditions and the following disclaimer in 
#    the documentation and/or other materials provided with the 
#    distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#    contributors may be used to endorse or promote products derived 
#    from this software without specific prior written permission.
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
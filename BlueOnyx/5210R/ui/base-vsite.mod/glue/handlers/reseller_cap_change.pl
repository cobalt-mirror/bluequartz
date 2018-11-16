#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: reseller_cap_change.pl
#
# This handler checks if the User we're working with is a reseller.
# (i.e.: has the capability 'manageSite'). If so, we check if he
# currently owns any Vsites. If that is the case, then we will walk
# through all his owned Vsites and make sure these Vsites only have
# those features active that the Reseller has access to (FTP, JSP, etc.)

use CCE;
use Base::Group qw(system_groupadd group_add_members group_rem_members);
use Base::Vsite;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
        &debug_msg("Debug enabled.\n");
}

my $cce = new CCE('Domain' => 'base-vsite');
$cce->connectfd();

my $user = $cce->event_object();
my $user_old = $cce->event_old();
if ($cce->event_is_destroy())
{
    $user = $user_old;
}

$reserve_user = $user;

# This is a modify() transaction:
if ($cce->event_is_modify()) {

    # Reseller handling:
    &debug_msg("Checking if user has Capability 'manageSite'.\n");
    &debug_msg("We extrapolate: " . $reserve_user->{capLevels} . "\n");
    @userCaps = $cce->scalar_to_array($reserve_user->{capLevels});
    @ResellerCapabilities = $cce->scalar_to_array($reserve_user->{capabilities});
    $has_manageSite = "0";
    if (in_array(\@userCaps, 'manageSite')) {
        $has_manageSite = "1";
        &debug_msg("This user has the Capability 'manageSite'.\n");
    }

    if ($has_manageSite == "0") {
        &debug_msg("This user does not have the Capability 'manageSite'.\n");
        $cce->bye('SUCCESS');
        exit(0);
    }
    else {
        &debug_msg("Checking which sites he owns - if any.\n");
        # Get a list of all sites that this reseller owns:
        my @ResellerSites = $cce->find('Vsite', { 'createdUser' => $reserve_user->{name}});
        foreach my $rsites (@ResellerSites) {
            &debug_msg("Processing Vsite with OID $rsites.\n");
            (my $ok, $modVsiteInfo) = $cce->get($rsites);
            &debug_msg("Modifying Vsite $modVsiteInfo->{'name'} - $modVsiteInfo->{'fqdn'}\n");

            # Handle Shell access:
            (my $ok, $Vsite_feature) = $cce->get($rsites, 'Shell');
            if (in_array(\@ResellerCapabilities, 'resellerShell')) {
                &debug_msg("Reseller has capability 'resellerShell'.\n");
                &debug_msg("Vsite $modVsiteInfo->{'name'} - $modVsiteInfo->{'fqdn'} Shell status: $Vsite_feature->{'enabled'}\n");
            }
            else {
                &debug_msg("Reseller does not have capability 'resellerShell'.\n");
                if ($Vsite_feature->{'enabled'} eq "1") {
                    &debug_msg("Vsite $modVsiteInfo->{'name'} - $modVsiteInfo->{'fqdn'} has Shell enabled. Turning it off.\n");
                    my ($ok) = $cce->set($rsites, 'Shell', { 'enabled' => '0' });
                }
            }

            # Handle resellerAnonFTP access:
            (my $ok, $Vsite_feature) = $cce->get($rsites, 'AnonFtp');
            if (in_array(\@ResellerCapabilities, 'resellerAnonFTP')) {
                &debug_msg("Reseller has capability 'resellerAnonFTP'.\n");
                &debug_msg("Vsite $modVsiteInfo->{'name'} - $modVsiteInfo->{'fqdn'} AnonFtp status: $Vsite_feature->{'enabled'}\n");
            }
            else {
                &debug_msg("Reseller does not have capability 'resellerAnonFTP'.\n");
                if ($Vsite_feature->{'enabled'} eq "1") {
                    &debug_msg("Vsite $modVsiteInfo->{'name'} - $modVsiteInfo->{'fqdn'} has AnonFtp enabled. Turning it off.\n");
                    my ($ok) = $cce->set($rsites, 'AnonFtp', { 'enabled' => '0' });
                }
            }

            # Handle resellerFTP access:
            (my $ok, $Vsite_feature) = $cce->get($rsites, 'FTPNONADMIN');
            if (in_array(\@ResellerCapabilities, 'resellerFTP')) {
                &debug_msg("Reseller has capability 'resellerFTP'.\n");
                &debug_msg("Vsite $modVsiteInfo->{'name'} - $modVsiteInfo->{'fqdn'} FTPNONADMIN status: $Vsite_feature->{'enabled'}\n");
            }
            else {
                &debug_msg("Reseller does not have capability 'resellerFTP'.\n");
                if ($Vsite_feature->{'enabled'} eq "1") {
                    &debug_msg("Vsite $modVsiteInfo->{'name'} - $modVsiteInfo->{'fqdn'} has FTPNONADMIN enabled. Turning it off.\n");
                    my ($ok) = $cce->set($rsites, 'FTPNONADMIN', { 'enabled' => '0' });
                }
            }

            # Handle resellerSSL access:
            (my $ok, $Vsite_feature) = $cce->get($rsites, 'SSL');
            if (in_array(\@ResellerCapabilities, 'resellerSSL')) {
                &debug_msg("Reseller has capability 'resellerSSL'.\n");
                &debug_msg("Vsite $modVsiteInfo->{'name'} - $modVsiteInfo->{'fqdn'} SSL status: $Vsite_feature->{'enabled'}\n");
            }
            else {
                &debug_msg("Reseller does not have capability 'resellerSSL'.\n");
                if ($Vsite_feature->{'enabled'} eq "1") {
                    &debug_msg("Vsite $modVsiteInfo->{'name'} - $modVsiteInfo->{'fqdn'} has SSL enabled. Turning it off.\n");
                    my ($ok) = $cce->set($rsites, 'SSL', { 'enabled' => '0' });
                }
            }

            # Handle resellerSSI access:
            (my $ok, $Vsite_feature) = $cce->get($rsites, 'SSI');
            if (in_array(\@ResellerCapabilities, 'resellerSSI')) {
                &debug_msg("Reseller has capability 'resellerSSI'.\n");
                &debug_msg("Vsite $modVsiteInfo->{'name'} - $modVsiteInfo->{'fqdn'} SSI status: $Vsite_feature->{'enabled'}\n");
            }
            else {
                &debug_msg("Reseller does not have capability 'resellerSSI'.\n");
                if ($Vsite_feature->{'enabled'} eq "1") {
                    &debug_msg("Vsite $modVsiteInfo->{'name'} - $modVsiteInfo->{'fqdn'} has SSI enabled. Turning it off.\n");
                    my ($ok) = $cce->set($rsites, 'SSI', { 'enabled' => '0' });
                }
            }

            # Handle resellerCGI access:
            (my $ok, $Vsite_feature) = $cce->get($rsites, 'CGI');
            if (in_array(\@ResellerCapabilities, 'resellerCGI')) {
                &debug_msg("Reseller has capability 'resellerCGI'.\n");
                &debug_msg("Vsite $modVsiteInfo->{'name'} - $modVsiteInfo->{'fqdn'} CGI status: $Vsite_feature->{'enabled'}\n");
            }
            else {
                &debug_msg("Reseller does not have capability 'resellerCGI'.\n");
                if ($Vsite_feature->{'enabled'} eq "1") {
                    &debug_msg("Vsite $modVsiteInfo->{'name'} - $modVsiteInfo->{'fqdn'} has CGI enabled. Turning it off.\n");
                    my ($ok) = $cce->set($rsites, 'CGI', { 'enabled' => '0' });
                }
            }

            # Handle resellerJSP access:
            (my $ok, $Vsite_feature) = $cce->get($rsites, 'Java');
            if (in_array(\@ResellerCapabilities, 'resellerJSP')) {
                &debug_msg("Reseller has capability 'resellerJSP'.\n");
                &debug_msg("Vsite $modVsiteInfo->{'name'} - $modVsiteInfo->{'fqdn'} Java status: $Vsite_feature->{'enabled'}\n");
            }
            else {
                &debug_msg("Reseller does not have capability 'resellerJSP'.\n");
                if ($Vsite_feature->{'enabled'} eq "1") {
                    &debug_msg("Vsite $modVsiteInfo->{'name'} - $modVsiteInfo->{'fqdn'} has Java enabled. Turning it off.\n");
                    my ($ok) = $cce->set($rsites, 'Java', { 'enabled' => '0' });
                }
            }

            # Handle resellerMySQL access:
            (my $ok, $Vsite_feature) = $cce->get($rsites, 'MYSQL_Vsite');
            if (in_array(\@ResellerCapabilities, 'resellerMySQL')) {
                &debug_msg("Reseller has capability 'resellerMySQL'.\n");
                &debug_msg("Vsite $modVsiteInfo->{'name'} - $modVsiteInfo->{'fqdn'} MYSQL_Vsite status: $Vsite_feature->{'enabled'}\n");
            }
            else {
                &debug_msg("Reseller does not have capability 'resellerMySQL'.\n");
                if ($Vsite_feature->{'enabled'} eq "1") {
                    &debug_msg("Vsite $modVsiteInfo->{'name'} - $modVsiteInfo->{'fqdn'} has MYSQL_Vsite enabled. Turning it off.\n");
                    my ($ok) = $cce->set($rsites, 'MYSQL_Vsite', { 'enabled' => '0' });
                }
            }

            # Handle resellerPHP and resellerSUPHP access:
            (my $ok, $Vsite_feature) = $cce->get($rsites, 'PHP');
            $PHP = '0';
            $suPHP = '0';
            if (in_array(\@ResellerCapabilities, 'resellerPHP')) {
                &debug_msg("Reseller has capability 'resellerPHP'.\n");
                $PHP = '1';
            }
            if (in_array(\@ResellerCapabilities, 'resellerSUPHP')) {
                &debug_msg("Reseller has capability 'resellerSUPHP'.\n");
                $suPHP = '1';
            }
            if (($Vsite_feature->{'enabled'} eq '1') && ($Vsite_feature->{'suPHP_enabled'} eq '1')) {
                if (($PHP eq '1') && ($suPHP eq '0')) {
                    debug_msg("Vsite $modVsiteInfo->{'fqdn'} has suPHP on, but Reseller only has PHP allowed. Change it to PHP.\n");
                    my ($ok) = $cce->set($rsites, 'PHP', { 'enabled' => '1', 'suPHP_enabled' => '0' });
                }
            }
            if (($Vsite_feature->{'enabled'} eq '1') && ($Vsite_feature->{'suPHP_enabled'} eq '0')) {
                if (($PHP eq '0') && ($suPHP eq '1')) {
                    debug_msg("Vsite $modVsiteInfo->{'fqdn'} has PHP on, but Reseller only has suPHP allowed. Change it to suPHP.\n");
                    my ($ok) = $cce->set($rsites, 'PHP', { 'enabled' => '1', 'suPHP_enabled' => '1' });
                }
            }
            if (($Vsite_feature->{'enabled'} eq '1') || ($Vsite_feature->{'suPHP_enabled'} eq '1')) {
                if (($PHP eq '0') && ($suPHP eq '0')) {
                    debug_msg("Vsite $modVsiteInfo->{'fqdn'} has PHP or suPHP on, but Reseller has neither allowed. Turn it off.\n");
                    my ($ok) = $cce->set($rsites, 'PHP', { 'enabled' => '0', 'suPHP_enabled' => '0' });
                }
            }
        }
    }
}

$cce->bye('SUCCESS');
exit(0);

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

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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

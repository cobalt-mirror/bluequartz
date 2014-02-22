#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: system_reseller_delete.pl
#
# This handler checks if the User we're deleting is a reseller.
# (i.e.: has the capability 'manageSite'). If so, we check if he
# currently owns any Vsites. If that is the case, the Vsites will
# be deleted.

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

# This is a destroy() transaction:
if ($cce->event_is_destroy()) {

    # Reseller handling:
    &debug_msg("Checking if user has Capability 'manageSite'.\n");
    &debug_msg("We extrapolate: " . $reserve_user->{capLevels} . "\n");
    @userCaps = $cce->scalar_to_array($reserve_user->{capLevels});
    $has_manageSite = "0";
    foreach my $testcap (@userCaps) {
            if ($testcap eq 'manageSite') {
                    $has_manageSite = "1";
                     &debug_msg("This user has the Capability 'manageSite'.\n");
            }
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
            (my $ok, $delVsiteInfo) = $cce->get($rsites);
            &debug_msg("Deleting Vsite Group $delVsiteInfo->{'name'} - $delVsiteInfo->{'fqdn'}\n");

            # Find all the users of this Vsite:
            my @users = $cce->find('User', { 'site' => $delVsiteInfo->{'name'} });

            for my $user (@users) {
                # override file check since this is a site destroy
                $cce->set($user, '', { 'noFileCheck' => 1 });
                my ($ok, @info) = $cce->destroy($user);
                if (!$ok) {
                    # failed. update status and exit.
                    $cce->bye('FAIL');
                    exit(1);
                } else {
                    $processed++;
                    &debug_msg("Deleted User with OID $user of Vsite $delVsiteInfo->{'name'} - $delVsiteInfo->{'fqdn'}\n");
                }
            }

            # Now destroy the Vsite:
            my ($ok, @info) = $cce->destroy($rsites);
            if ($ok) {
                &debug_msg("Deleted Vsite with OID $rsites - $delVsiteInfo->{'name'} - $delVsiteInfo->{'fqdn'}\n");
            } else {
                &debug_msg("Deletion of Vsite with OID $rsites - $delVsiteInfo->{'name'} - $delVsiteInfo->{'fqdn'} FAILED!\n");
                $cce->bye('FAIL');
                exit(1);
            }            
            
        }
    }
}

$cce->bye('SUCCESS');
exit(0);

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
# Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
# 
# Redistribution and use in source and binary forms, with or without modification, 
# are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation and/or 
# other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
# 
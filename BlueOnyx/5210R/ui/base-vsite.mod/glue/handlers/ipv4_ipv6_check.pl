#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: ipv4_ipv6_check.pl
#
# Verify that Vsites have at least one IPv4 or an IPv6 IP address. They can have
# both as well, but at least one *must* be set.
#

use CCE;
use POSIX qw(isalpha);

# Debugging switch:
$DEBUG = "0";
if ($DEBUG) {
    use Data::Dumper;
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $cce = new CCE('Domain' => 'base-vsite');

$cce->connectfd();

my $vsite = $cce->event_object();
my $vsite_new = $cce->event_new();
my $vsite_old = $cce->event_old();

&debug_msg("vsite: " . $vsite->{fqdn} . " : " . $vsite->{ipaddr} . " - " . $vsite->{ipaddrIPv6} . "\n");
&debug_msg("vsite_new: " . $vsite_new->{fqdn} . " : " . $vsite_new->{ipaddr} . " - " . $vsite_new->{ipaddrIPv6} . "\n");
&debug_msg("vsite_old: " . $vsite_old->{fqdn} . " : " . $vsite_old->{ipaddr} . " - " . $vsite_old->{ipaddrIPv6} . "\n");

my @oids = $cce->find("System");
my ($ok, $system) = $cce->get($oids[0]);
&debug_msg("Start of IPv4/IPv6 IP address validation.\n");
&debug_msg("IPType: " . $system->{IPType} . "\n");

#
### Check availability of protocols:
#

# IPv4 Gateway is empty, but Vsite has IPv4 IP address specified:
if ((($system->{IPType} ne 'IPv4') && ($system->{IPType} ne 'BOTH') && ($system->{IPType} ne 'VZv4') && ($system->{IPType} ne 'VZBOTH')) && ($vsite->{ipaddr} ne "")) {
    $cce->bye('FAIL', "[[base-vsite.IPValidation_ipv4_specified_but_not_allowed,fqdn='$vsite_old->{fqdn}']]");
    &debug_msg("Fail: IPv4 IP Address specified, but IPv4 is not enabled on the server!\n");
    exit(1);
}

# IPv6 Gateway is empty, but Vsite has IPv6 IP address specified:
if ((($system->{IPType} ne 'IPv6') && ($system->{IPType} ne 'BOTH') && ($system->{IPType} ne 'VZv6') && ($system->{IPType} ne 'VZBOTH')) && ($vsite->{ipaddrIPv6} ne "")) {
    $cce->bye('FAIL', "[[base-vsite.IPValidation_ipv6_specified_but_not_allowed,fqdn='$vsite_old->{fqdn}']]");
    &debug_msg("Fail: IPv6 IP Address specified, but IPv6 is not enabled on the server!\n");
    exit(1);
}

if ((($system->{IPType} eq 'VZv4') || ($system->{IPType} eq 'IPv4')) && ($vsite_new->{ipaddr} eq '')) {
    $cce->bye('FAIL', "[[base-vsite.IPValidation_ip_must_be_specified,fqdn='$vsite_old->{fqdn}']]");
    &debug_msg("Fail: IPv4 IP Address empty and IPv6 IP empty. At least one must be set!\n");
    exit(1);
}

if ((($system->{IPType} eq 'VZv6') || ($system->{IPType} eq 'IPv6')) && ($vsite_new->{ipaddrIPv6} eq '')) {
    $cce->bye('FAIL', "[[base-vsite.IPValidation_ip_must_be_specified,fqdn='$vsite_old->{fqdn}']]");
    &debug_msg("Fail: IPv4 IP Address empty and IPv6 IP empty. At least one must be set!\n");
    exit(1);
}

if ((($system->{IPType} eq 'VZBOTH') || ($system->{IPType} eq 'BOTH')) && (($vsite_new->{ipaddr} eq '') && ($vsite_new->{ipaddrIPv6} eq ''))) {
    $cce->bye('FAIL', "[[base-vsite.IPValidation_ip_must_be_specified,fqdn='$vsite_old->{fqdn}']]");
    &debug_msg("Fail: IPv4 IP Address empty and IPv6 IP empty. At least one must be set!\n");
    exit(1);
}

if ((defined $vsite->{ipaddr}) || (defined $vsite->{ipaddrIPv6})) {
    &debug_msg("WARN: IP address change registered!\n");

    # Now it gets manageably complicated:
    #
    # $vsite_new:   Only contains *those* parameters that changed.
    # $vsite:       Contains how it would look after a successful commit. 
    # $vsite_old:   Contains the data as it was before the submit.
    #
    # However: $vsite_new is such an unreliable pice of shit that we cannot use it without going crazy.

    if ($vsite->{ipaddrIPv6} ne $vsite_old->{ipaddrIPv6}) {
        # The IPv6 IP changes.
        &debug_msg("WARN: IPv6 address change registered!\n");
        if (($vsite->{ipaddr} eq "") && ($vsite->{ipaddrIPv6} eq "")) {
            $cce->bye('FAIL', "[[base-vsite.IPValidation_ip_must_be_specified,fqdn='$vsite_old->{fqdn}']]");
            &debug_msg("Fail: IPv4 IP Address empty and IPv6 IP empty. At least one must be set!\n");
            exit(1);
        }
    }

    if ($vsite->{ipaddr} ne $vsite_old->{ipaddr}) {
        # The IPv4 IP changes.
        &debug_msg("WARN: IPv4 address change registered!\n");
        if (($vsite->{ipaddr} eq "") && ($vsite->{ipaddrIPv6} eq "")) {
            $cce->bye('FAIL', "[[base-vsite.IPValidation_ip_must_be_specified,fqdn='$vsite_old->{fqdn}']]");
            &debug_msg("Fail: IPv4 IP Address empty and IPv6 IP empty. At least one must be set!\n");
            exit(1);
        }
    }
}
else {
    &debug_msg("WARN: Inside last 'else': IP address change registered!\n");
}

$cce->bye('SUCCESS');
exit(0);

# For debugging:
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
# Copyright (c) 2017-2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2017-2018 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
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
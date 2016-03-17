#!/usr/bin/perl -I/usr/sausalito/perl
#
# $Id: le_webAliases_change.pl
#
# If a vsite's webAliases get changed, then we need to make sure that Let's Encrypt doesn't
# request a renewal for an alias that the Vsite no longer has.
#

use CCE;

# Debugging switch (0|1):
# 0 = off
# 1 = log to syslog
$DEBUG = "0";
if ($DEBUG) {
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $cce = new CCE('Domain' => 'base-ssl', 'Namespace' => 'SSL');
$cce->connectfd();

my $ssl_info = $cce->event_object();

# If SSL is not enabled or we're not using SSL via Let's Encrypt, then we just exit:
if ((!$ssl_info->{enabled}) || (!$ssl_info->{uses_letsencrypt})) {
    &debug_msg("SSL not enabled.\n");
    $cce->bye('SUCCESS');
    exit(0);
}

# Need to get the vsite for the webAliases:
my ($ok, $vsite) = $cce->get($cce->event_oid());
if (!$ok) {
    &debug_msg("cantGetVsite\n");
    $cce->bye('FAIL', 'cantGetVsite');
    exit(1);
}

my ($ok, $vsiteSSL) = $cce->get($cce->event_oid(), 'SSL');
if (!$ok) {
    &debug_msg("cantGetVsiteSSL\n");
    $cce->bye('FAIL', 'cantGetVsiteSSL');
    exit(1);
}

&debug_msg("webAliases: $vsite->{'webAliases'}\n");
&debug_msg("LEwantedAliases: $vsiteSSL->{'LEwantedAliases'}\n");

# Transform:
@webAliases = $cce->scalar_to_array($vsite->{webAliases});
@LEwantedAliases = $cce->scalar_to_array($vsiteSSL->{LEwantedAliases});

# New Output-Array:
@new_LEwantedAliases = ();

# Run check:
foreach $alias (@LEwantedAliases) {
    if (in_array(\@webAliases, $alias)) {
        push(@new_LEwantedAliases, $alias);
    }
}

# Now check if the Vsite's domain is a webAlias and is not in 'LEwantedAliases':
if (in_array(\@webAliases, $vsite->{'domain'})) {
    unless (in_array(\@LEwantedAliases, $vsite->{'domain'})) {
        &debug_msg("LEwantedAliases: Adding $vsite->{'domain'}\n");
        push(@new_LEwantedAliases, $vsite->{'domain'});
    }
}

# Build new Alias-List:
$Out_LEwantedAliases = $cce->array_to_scalar(@new_LEwantedAliases);
&debug_msg("New LEwantedAliases: $Out_LEwantedAliases\n");

# Update CODB:
$cce->set($vsite->{'OID'}, 'SSL', { 'LEwantedAliases' => $Out_LEwantedAliases });

# Otherwise, everything is okay
$cce->bye('SUCCESS');
exit(0);

#
### Subroutines:
#

sub in_array {
    my ($arr,$search_for) = @_;
    my %items = map {$_ => 1} @$arr; # create a hash out of the array values
    return (exists($items{$search_for}))?1:0;
}

sub debug_msg {
    if ($DEBUG eq "1") {
        $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
}

# 
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
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
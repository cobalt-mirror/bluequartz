#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: fixhosts.pl

use CCE;

my $cce = new CCE;
$cce->connectuds();

@RealNetOids = $cce->find('Network', { 'enabled' => '1', 'real' => '1' } );
@OtherNetOids = $cce->find('Network', { 'enabled' => '1', 'real' => '0' } );
@SysOID = $cce->find("System");

($ok, $System) = $cce->get($SysOID[0]);

$hostname = $System->{'hostname'};
$servername = $System->{'hostname'} . '.' . $System->{'domainname'};

# Set up an array for all IP addresses of this box;
@all_ips = ('127.0.0.1');

$output = '# /etc/hosts' . "\n";
$output .= '# Auto-generated file. Please put your customizations at the very end.' . "\n\n";
$output .= '# Entries for localhost and primary IP address:' . "\n";
$output .= '127.0.0.1' . filler('127.0.0.1') . "\t" . 'localhost' . filler('localhost') . "\t" . 'localhost.localdomain' . "\n";

foreach $oid (@RealNetOids) {
    ($ok, $obj) = $cce->get($oid);
    $MainIpaddr = $obj->{'ipaddr'};
    push (@all_ips, $MainIpaddr);
    $output .= $MainIpaddr . filler($MainIpaddr) . "\t" . $hostname . filler($hostname) . "\t" . $servername . "\n";
}

if (scalar(@OtherNetOids) gt "1") {
    $output .= "\n" . '# Entries for all other IP addresses bound to this server:' . "\n";
}

foreach $oid (@OtherNetOids) {
    ($ok, $obj) = $cce->get($oid);
    $ipaddr = $obj->{'ipaddr'};
    if (($ipaddr ne "127.0.0.1") && ($ipaddr ne $MainIpaddr)) {
        push (@all_ips, $ipaddr);
        $output .= $ipaddr . filler($ipaddr) . "\t" . $hostname . filler($hostname) . "\t" . $servername . "\n";
    }
}

$output .= "\n" . '# Entries for all Vsites on IP addresses of this server:' . "\n";
foreach $ip (@all_ips) {
    @Vsites_on_IP = $cce->find('Vsite', { 'ipaddr' => $ip } );
    if (scalar(@Vsites_on_IP) gt "0") {
        foreach $oid (@Vsites_on_IP) {
            ($ok, $Vsite) = $cce->get($oid);
            $output .= $ip . filler($ip) . "\t" . $Vsite->{'hostname'} . filler($Vsite->{'hostname'}) . "\t" . $Vsite->{'fqdn'} . "\n";
        }
    }
}

$output .= "\n";
$output .= '# The following lines are desirable for IPv6 capable hosts' . "\n";
$output .= '::1                             localhost ip6-localhost ip6-loopback' . "\n";
$output .= 'ff02::1                         ip6-allnodes' . "\n";
$output .= 'ff02::2                         ip6-allrouters' . "\n";
$output .= "\n";
$output .= '# END of auto-generated code. Customize beneath this line.' . "\n";

# Update /etc/hostname:
$etc_hostname = '/etc/hostname';
open(CONF, ">$etc_hostname") || die "Can't write to $etc_hostname!";
print CONF "$hostname\n";
close CONF;

# Read existing /etc/hosts to see if we need to keep modifications that the user added:
$user_additions = '';
$etc_hosts = '/etc/hosts';
if (-f $etc_hosts) {
    if (open(my $fh, '<:encoding(UTF-8)', $etc_hosts)) {
        $found_anchor = '0';
        while (my $row = <$fh>) {
            chomp $row;
            if ($row =~ /^# END of auto-generated code. Customize beneath this line./) {
                $found_anchor = "1";
                next;
            }
            if ($found_anchor eq "1") {
                $user_additions .= $row . "\n";
            }
        }
    }
}

# Combine our output with user_additions:
$output .= $user_additions;

# Update /etc/hosts
open(CONF, ">$etc_hosts") || die "Can't write to $etc_hosts!";
print CONF "$output";
close CONF;

$cce->bye('SUCCESS');
exit(0);

sub filler {
    my ($data) = @_;
    $ln = length($data);
    $maxIPlen = '24';
    $fill = $maxIPlen - $ln;
    $spacer = '';
    while ($fill > '0') {
        $spacer .= " ";
        $fill--;
    }
    return $spacer;
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
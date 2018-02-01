#!/usr/bin/perl -I/usr/sausalito/handlers/base/network
# $Id: IpPooling.pm
#
# Description:
#   Library functions used to check IP ranges.

package IpPooling;


use strict;
require Exporter;
use Net::IP;

my @ISA = qw(Exporter);
my @EXPORT = qw(ippool_is_network_subset
        ippool_is_network_member
        ippool_get_broadcast
        ippool_get_network
        );


my %netmasks = (
          32 => "255.255.255.255", 31 => "255.255.255.254",
          30 => "255.255.255.252", 29 => "255.255.255.248",
          28 => "255.255.255.240", 27 => "255.255.255.224",
          26 => "255.255.255.192", 25 => "255.255.255.128",
          24 => "255.255.255.0",   23 => "255.255.254.0",
          22 => "255.255.252.0",   21 => "255.255.248.0",
          20 => "255.255.240.0",   19 => "255.255.224.0",
          18 => "255.255.192.0",   17 => "255.255.128.0",
          16 => "255.255.0.0",     15 => "255.254.0.0",
          14 => "255.252.0.0",     13 => "255.248.0.0",
          12 => "255,240.0.0",     11 => "255.224.0.0",
          10 => "255.192.0.0",      9 => "255.128.0.0",
           8 => "255.0.0.0",        7 => "254.0.0.0",
           6 => "252.0.0.0",        5 => "248.0.0.0",
           4 => "240.0.0.0",        3 => "224.0.0.0",
           2 => "192.0.0.0",        1 => "128.0.0.0",
           0 => "0.0.0.0",

          "0.0.0.0" => 0,          "128.0.0.0" => 1,
          "192.0.0.0" => 2,        "224.0.0.0" => 3,
          "240.0.0.0" => 4,        "248.0.0.0" => 5,
          "252.0.0.0" => 6,        "254.0.0.0" => 7,
          "255.0.0.0" => 8,        "255.128.0.0" => 9,
          "255.192.0.0" => 10,     "255.224.0.0" => 11,
          "255,240.0.0" => 12,     "255.248.0.0" => 13,
          "255.252.0.0" => 14,     "255.254.0.0" => 15,
          "255.255.0.0" => 16,     "255.255.128.0" => 17,
          "255.255.192.0" => 18,   "255.255.224.0" => 19,
          "255.255.240.0" => 20,   "255.255.248.0" => 21,
          "255.255.252.0" => 22,   "255.255.254.0" => 23,
          "255.255.255.0" => 24,   "255.255.255.128" => 25,
          "255.255.255.192" => 26, "255.255.255.224" => 27,
          "255.255.255.240" => 28, "255.255.255.248" => 29,
          "255.255.255.252" => 30, "255.255.255.254" => 31,
          "255.255.255.255" => 32,
         );


sub ippool_is_network_subset
# Checks if the first range of ip addresses is within the second range of ip addresses
# Arguments: min of 1st range, max of 1st range, min of 2nd range, max of 2nd range
# Side effects: none
# Return value: 1 for yes, 0 for no
{
    my ($min1, $max1, $min2, $max2) = @_;
    
    $min1 = ippool_get_canonical($min1);
    $max1 = ippool_get_canonical($max1);
    
    $min2 = ippool_get_canonical($min2);
    $max2 = ippool_get_canonical($max2);

    # check if net1 range is within net2 range
    return (($min2 <= $min1) && ($max2 >= $max1));
}

sub ippool_is_network_member
# Checks if the specified IP address is within 2nd range
# Arguments: ip address, min of range, max of range
# Side effects: none
# Return value: 1 for yes, 0 for no
{

  my ($ip,$min,$max) = @_;

  return ippool_is_network_subset($ip, $ip, $min, $max);
}

sub ippool_get_broadcast
# Returns the standard broadcast address which would go with this network
#   We assume that we should use the high address, because everyone does now.
# Arguments: IP address and netmask, in dotted quad notation
# Return value: broadcast address, in dotted quad notation
{
    my ($addr,$netmask) = @_;
    my ($a1,$a2,$a3,$a4) = map(pack('C',$_),split(/\./o,$addr));
    my ($m1,$m2,$m3,$m4) = map(pack('C',$_),split(/\./o,$netmask));
    my ($n1,$n2,$n3,$n4,$b1,$b2,$b3,$b4,$f,$bcast);
    $n1 = unpack('C',($m1 & $a1));
    $n2 = unpack('C',($m2 & $a2));
    $n3 = unpack('C',($a3 & $m3));
    $n4 = unpack('C',($a4 & $m4));
    $f = pack('C',0xff);   # 0xff = 255
    $b1 = $n1 + unpack('C',($m1 ^ $f));
    $b2 = $n2 + unpack('C',($m2 ^ $f));
    $b3 = $n3 + unpack('C',($m3 ^ $f));
    $b4 = $n4 + unpack('C',($m4 ^ $f));
    $bcast = "$b1.$b2.$b3.$b4";
    print STDERR "broadcast of $addr, $netmask is: $bcast\n";

    return $bcast;
}

sub ippool_get_network
# Returns the network address which would go with this IP and netmask
# Arguments: IP address and netmask, in dotted quad notation
# Return value: network address, in dotted quad notation
{
    my ($addr,$netmask) = @_;
    my ($a1,$a2,$a3,$a4) = map(pack('C',$_),split(/\./o,$addr));
    my ($m1,$m2,$m3,$m4) = map(pack('C',$_),split(/\./o,$netmask));
    my ($n1,$n2,$n3,$n4,$network);
    $n1 = unpack('C',($m1 & $a1));
    $n2 = unpack('C',($m2 & $a2));
    $n3 = unpack('C',($a3 & $m3));
    $n4 = unpack('C',($a4 & $m4));
    $network = "$n1.$n2.$n3.$n4";
    print STDERR "network of $addr, $netmask is: $network\n";
    return $network;
}


sub ippool_get_canonical
{
    my $ip = shift;
    my ($tmp1, $tmp2, $tmp3, $tmp4) = split(/\./, $ip);
    return $tmp1*(2**24) + $tmp2*(2**16) + $tmp3*(2**8) + $tmp4;
}

# checks that each of the ips is inside at least one range
# ranges is a reference to an array of range objects
# ips is a refernece to an array of ip addresses
sub validate_pooling_state
{
    my ($ranges, $ips) = @_;
    my $ismember = 0;
    my @errors = ();
    my $net_ip = '';
    my $check_ip = '';

    # check that each IP address
    # is within at least one range
    foreach my $ip (@{$ips}) {
        $ismember = 0;
        foreach my $range (@{$ranges}) {

            $net_ip = Net::IP->new($range->{min} . " - "  . $range->{max});
            $check_ip = Net::IP->new($ip);
            if ($net_ip->overlaps($check_ip)) {
                $ismember = 1;
            }
        }
        if (!$ismember) {
            push @errors, $ip;
        }
    }
    
    return @errors;
}

# 
# Copyright (c) 2014-2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014-2018 Team BlueOnyx, BLUEONYX.IT
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
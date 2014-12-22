#!/usr/bin/perl -w
# $Id: unique_soa.pl
#
# fails if SOA is not unique.

use lib qw(/usr/sausalito/perl);
use CCE;
use Data::Dumper;
my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();
my $old = $cce->event_old();
my $new = $cce->event_new();

my $type = $new->{type} || $old->{type};
my $old_soa = undef;
my $new_soa = undef;

my $crit = undef;

if (defined($obj->{ipaddr}) && $obj->{ipaddr} 
 && defined($obj->{netmask}) && $obj->{netmask}) {
  my ($ip, $nm) = normalize_network($obj->{ipaddr}, $obj->{netmask});
  $crit = { 'ipaddr' => $ip, 'netmask' => $nm };
} elsif (defined($obj->{domainname}) && $obj->{domainname}) {
  $crit = { 'domainname' => $obj->{domainname} };
}

if (!defined($crit)) {
  $cce->warn('[[base-dns.invalid-authority]]');
  $cce->bye('FAIL');
  exit(1);
}

my @oids = $cce->find('DnsSOA', $crit);
if ($#oids > 0) {
  $cce->warn('[[base-dns.SOA-already-exists-for-zone]]');
  $cce->bye('FAIL');
  exit(1);
}

$cce->bye('SUCCESS');
exit(0);

########################################
# parse_netmask
########################################
sub parse_netmask
{
  my $nbits = shift;
  if ($nbits =~ m/^\s*\d+\s*$/) {
    return unpack("C4",pack("B32", '1' x $nbits . '0' x (32 - $nbits) ));
  };
  if ($nbits =~ m/^\s*(\d+)\.(\d+)\.(\d+)\.(\d+)\s*$/) {
    return ($1,$2,$3,$4);
  }
  warn ("Invalid netmask: $nbits\n");
  return (255,255,255,255);
}

########################################
# netmask_to_netbits
#
# convert generalized netmask format to just a simple bit-count.
########################################
sub netmask_to_netbits
{
  my $nbits = shift;
  if ($nbits =~ m/^\s*(\d+)\s*$/) {
    return $1;
  };
  if ($nbits =~ m/^\s*(\d+)\.(\d+)\.(\d+)\.(\d+)\s*$/) {
    my @bits = split(//, unpack("B32", pack("C4",$1,$2,$3,$4)));
    $nbits = 0;
    while (@bits) { $nbits += shift(@bits); }
    return $nbits;
  }
  warn ("Invalid netmask: $nbits\n");
  return (32);
}

sub normalize_network
{
  my ($ip, $nm) = (shift, shift);
  
  # normalize netmask to a bitcount: (handle bitcount or dotquad)
  $nm = join(".",parse_netmask($nm));
  my $binmask = pack("C4", split(/\./, $nm));
  my $binip = pack("C4", split(/\./, $ip));
  $ip = join(".", unpack("C4", $binmask & $binip));
  
  return ($ip, $nm);
}

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
#!/usr/bin/perl -w
# $Id: fixnetwork.pl

use lib qw(/usr/sausalito/perl);
use CCE;
my $cce = new CCE;
$cce->connectfd();

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
        &debug_msg("Debug enabled.\n");
}

my $oid = $cce->event_oid();
my $obj = $cce->event_object();

# No parsing of IPv6 IPs. Exit silently.
if (defined($obj->{'type'})) {
  if ($obj->{'type'} eq "Record type is: AAAA") {
    &debug_msg("Obj: " . $obj->{'type'} . "\n");
    $cce->bye("SUCCESS");
    exit(0);
  }
}

if (defined($obj->{ipaddr}) && defined($obj->{netmask})) {
  my $ipbin = pack("C4", split(/\./, $obj->{ipaddr}));
  my $ipnm = pack("C4", parse_netmask($obj->{netmask}));
  my $nbits = netmask_to_netbits($obj->{netmask});
  my $network = join(".",unpack("C4", $ipbin & $ipnm)) . "/" . $nbits;
  $cce->set($oid, "", { "network" => $network } );
}

$cce->bye("SUCCESS");
exit(0);

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
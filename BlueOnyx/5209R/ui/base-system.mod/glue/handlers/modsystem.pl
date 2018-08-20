#!/usr/bin/perl -I/usr/sausalito/perl -I.
# $Id: modsystem.pl

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

use Sauce::Config;
use Sauce::Util;
use CCE;
# use System;

my $cce = new CCE;
$cce->connectfd();

# retreive user object data:
my $oid = $cce->event_oid();

if (!$oid) {
	# something is very, very wrong.
  $cce->bye('FAIL', 'Bad CSCP header');
  exit(1);
}

&debug_msg("Running modsystem.pl \n");

my $obj = $cce->event_object();
my $new = $cce->event_new();
my $old = $cce->event_old();
use Data::Dumper;

##########################################################################
# error checking
##########################################################################
# System::validate();

##########################################################################
# actuate changes
##########################################################################

my $name = $obj->{hostname};
if ($obj->{domainname} && $obj->{hostname} !~ m/\.$obj->{domainname}$/) {
  $name = join(".",($obj->{hostname},$obj->{domainname}));
}
$name =~ s/\.+/\./g;
if (length($name) > 70) {
  $cce->baddata(0, 'hostname', 'hostname-too-long');
  $cce->bye('FAIL');
  exit 1;
}
if ($name =~ m/^localhost\./) {
      $name = "localhost";
}

# Get IPType:
$IPType = $obj->{IPType};
&debug_msg("DEBUG: IPType: $IPType \n");

if (defined($new->{hostname}) || defined($new->{domainname}))
{

  # update /etc/HOSTNAME
  {
      my $fn = sub {
      my ($fin, $fout) = (shift, shift);
      my $name = shift;
      print $fout $name,"\n";
      return 1;
    };
    Sauce::Util::editfile('/etc/HOSTNAME', $fn, $name)
  }
  
  # run the hostname command 
  Sauce::Util::addrollbackcommand("/bin/hostname " . `/bin/hostname`);
  system('/bin/hostname', $name);
}

# If this runs on an OpenVZ VPS, then we can stop right here. Under no bloody
# circumstance are we editing /etc/sysconfig/network, as it's off limits and
# will be redone from scratch by the node on Container restarts anway.
if (($IPType eq 'VZv4') || ($IPType eq 'VZv6') || ($IPType eq 'VZBOTH')) {
  # Comfortable shoes are even more comfortable if you don't have to wear them.
  $cce->bye('SUCCESS');
  exit(0);
}

# update /etc/sysconfig/network
{
    my @IPv4VARS = ("FORWARD_IPV4", "GATEWAY");
    my @IPv6VARS = ("NETWORKING_IPV6", "IPV6FORWARDING", "IPV6_DEFAULTDEV", "IPV6_DEFAULTGW", "IPV6_AUTOCONF");
    my $fn = sub {
    my ($fin, $fout) = (shift,shift);
    my ($name, $gateway, $gateway_IPv6) = (shift, shift, shift);
    my %hash = ();
    while ($_ = <$fin>) {
      chomp($_);
      if (m/^\s*([A-Za-z0-9_]+)\s*\=\s*(.*)/) { 
        $hash{$1} = $2; 
      }
    }
    $hash{HOSTNAME} = $name;

    &debug_msg("DEBUG: gateway: $gateway \n");
    &debug_msg("DEBUG: gateway_IPv6: $gateway_IPv6 \n");

    if ($gateway eq '') {
      # If we have no IPv4 Gateway, then IPv4 networking is disabled:
      $hash{NETWORKING} = "no";
    }
    else {
      $hash{NETWORKING} = "yes";
    }

    $hash{FORWARD_IPV4} = $hash{FORWARD_IPV4} || "false";
    $hash{NOZEROCONF} = $hash{NOZEROCONF} || "yes";

    if (!-e "/etc/is_aws") {
      $hash{GATEWAY} = $gateway || $hash{GATEWAY} || "";
      $hash{IPV6_DEFAULTGW} = $gateway_IPv6 || "";
      if (defined($gateway)) { 
        $hash{GATEWAY} = $gateway; 
      }
      $hash{IPV6_AUTOCONF} = 'no';
      if (defined($gateway_IPv6)) { 
        $hash{NETWORKING_IPV6} = 'yes';
        $hash{IPV6FORWARDING} = 'yes';
        $hash{IPV6_DEFAULTDEV} = 'eth0';
        $hash{IPV6_DEFAULTGW} = $gateway_IPv6;
      }
    }

    foreach $_ ( sort keys %hash ) {
      if ((($gateway_IPv6 eq '') && (in_array(\@IPv6VARS, $_))) || (($gateway eq '') && (in_array(\@IPv4VARS, $_)))) {
        # If we don't have an IPv4 or IPv6 Gatway, then we don't print the protocol related lines.
      }
      else {
        print $fout $_,"=",$hash{$_},"\n";
      }
    }
    return 1;
  };
  Sauce::Util::editfile("/etc/sysconfig/network", $fn, $name, $obj->{gateway}, $obj->{gateway_IPv6});
  Sauce::Util::chmodfile(0644,'/etc/sysconfig/network');
};

# comfortable shoes.
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

sub in_array {
    my ($arr,$search_for) = @_;
    my %items = map {$_ => 1} @$arr; # create a hash out of the array values
    return (exists($items{$search_for}))?1:0;
}

# 
# Copyright (c) 2014-2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014-2018 Team BlueOnyx, BLUEONYX.IT
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
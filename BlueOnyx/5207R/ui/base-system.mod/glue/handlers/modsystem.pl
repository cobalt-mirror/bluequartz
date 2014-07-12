#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: modsystem.pl

use strict;
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

if (defined($new->{hostname}) || defined($new->{domainname}) || defined($new->{gateway}) ) {
  # update /etc/sysconfig/network
  {
      my $fn = sub {
      my ($fin, $fout) = (shift,shift);
      my ($name, $gateway) = (shift, shift);
      my %hash = ();
      while ($_ = <$fin>) {
        chomp($_);
        if (m/^\s*([A-Za-z0-9_]+)\s*\=\s*(.*)/) { 
	    $hash{$1} = $2; 
	}
      }
      $hash{HOSTNAME} = $name;
      $hash{NETWORKING} = "yes";
      $hash{FORWARD_IPV4} = $hash{FORWARD_IPV4} || "false";

      if (!-e "/etc/is_aws") {
	  $hash{GATEWAY} = $gateway || $hash{GATEWAY} || "";
	  if (defined($gateway)) { $hash{GATEWAY} = $gateway; };
      }

      foreach $_ ( sort keys %hash ) {
        print $fout $_,"=",$hash{$_},"\n";
      }
      return 1;
    };
    Sauce::Util::editfile("/etc/sysconfig/network", $fn, $name, $obj->{gateway});
    Sauce::Util::chmodfile(0644,'/etc/sysconfig/network');
  };
}

# comfortable shoes.
$cce->bye('SUCCESS');
exit(0);

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
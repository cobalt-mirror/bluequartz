#!/usr/bin/perl
use lib qw(/usr/sausalito/perl);
 
use strict;
use Sauce::Util;
use CCE;
my $proftpd_conf = "/etc/proftpd.conf";
my $new_file = $proftpd_conf . "~";

if (!-f $proftpd_conf) {
  exit 0;
}

my $open_global = 0;
my $set_ident = 0;

open(IN, "< $proftpd_conf");
open(OUT, "> $new_file");
while (my $line = <IN>) {
  if ($line =~ /(\<Global\>)/) {
    $open_global = 1;
  } elsif ($line =~ /IdentLookups/) {
    $set_ident = 1;
  } elsif ($line =~ /(\<\/Global\>)/) {
    if (!$set_ident) {
      print OUT "   IdentLookups			off\n";
    }
  }
  print OUT $line;
}
close(OUT);
close(IN);

system("mv $new_file $proftpd_conf");

# Handle separate proftpds.conf:
my $proftpds_conf = "/etc/proftpds.conf";
my $new_file = $proftpds_conf . "~";

if (!-f $proftpds_conf) {
  exit 0;
}

my $open_global = 0;
my $set_ident = 0;

open(IN, "< $proftpds_conf");
open(OUT, "> $new_file");
while (my $line = <IN>) {
  if ($line =~ /(\<Global\>)/) {
    $open_global = 1;
  } elsif ($line =~ /IdentLookups/) {
    $set_ident = 1;
  } elsif ($line =~ /(\<\/Global\>)/) {
    if (!$set_ident) {
      print OUT "   IdentLookups      off\n";
    }
  }
  print OUT $line;
}
close(OUT);
close(IN);

system("mv $new_file $proftpds_conf");

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
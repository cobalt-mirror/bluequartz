#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: apache.pl

use CCE;
use Sauce::Util;
use Sauce::Service;

my $apache_conf = "/etc/admserv/conf/httpd.conf";
my $apache_init = "/etc/rc.d/init.d/admserv";

my $cce = new CCE();
$cce->connectfd();

my $obj = $cce->event_object();
my $old = $cce->event_old();

if ((! (-e $apache_conf) || (! (-e $apache_init)))) {
  $cce->warn("[[base-swupdate.apacheNotInstalled]]");
}

# Edit httpd.conf here
Sauce::Util::editfile($apache_conf, \&update_apache_conf, $obj->{location}, $old->{location}) and $cce->warn("[[base-swupdate.errorWritingConfFile]]");

Sauce::Service::service_run_init('admserv', 'reload');
$cce->bye("SUCCESS");
exit 0;

#
# update_apache_conf()
# 
# function for editing httpd.conf
sub update_apache_conf {
  my ($fin, $fout, $location, $old) = @_;
  
  $location =~ /^http:\/\/(.*)/;
  my $domain = $1;
  my $inrequests;
  
  while (<$fin>) {
    print $fout $_;
    if (/^ProxyRequests/) {
      $inrequests = 1;
      last;
    }
  }

  print $fout "ProxyPass /proxyURL/$domain $location\n" if $domain;
  # print everything else out. wipe out old stuff.
  while (<$fin>) {
  next if (/^ProxyPass/ and /$old$/);
    print $fout $_;
  }

  # return success
  return 1;
}

# 
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
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
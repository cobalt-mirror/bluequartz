#!/usr/bin/perl
# $Id: dns_restart.pl
#
# starts, stops, and restarts a service on demand, with some extra
# safety checks.  jm.

# configure here: (mostly)
my $SERVICE = "named";	# name of initd script for this daemon
my $CMDLINE = "named";  # contents of /proc/nnn/cmdline for this daemon
my $RESTART = "reload"; # restart action
my $DEBUG   = 0;

$DEBUG && warn `date` .' '. $0;

use lib qw( /usr/sausalito/perl );
use FileHandle;
use Sauce::Util;
use CCE;
$cce = new CCE;
$cce->connectfd();

my ($sysoid) = $cce->find("System");
my ($ok, $obj) = $cce->get($sysoid, "DNS");

# fix chkconfig information:
if ($obj->{enabled}) {
	Sauce::Service::service_set_init($SERVICE, 'on', '345');
} else {
	Sauce::Service::service_set_init($SERVICE, 'off', '345');
}

# check to see if the service is presently running;
my $running = 0;
{
  my $fh = new FileHandle("</var/named/chroot/var/run/named/$SERVICE.pid");
  if ($fh) {
    my $pid = <$fh>; chomp($pid);
    $DEBUG && warn "old $SERVICE pid = $pid\n";
    $fh->close();
    
    $fh = new FileHandle("</proc/$pid/cmdline");
    if ($fh) {
      my $cmdline = <$fh>; chomp($cmdline);
      $DEBUG && warn "old $SERVICE cmdline = $cmdline\n";
      $fh->close();
      
      if ($cmdline =~ m/$CMDLINE/) { $running = 1; }
    }
  }
}

$DEBUG && warn "Running? $running, Enabled in CCE? ".$obj->{enabled};

# Check if we have Systemd:
if (-f "/usr/bin/systemctl") {
  # Got Systemd:
  $SERVICE = "named-chroot";
}

# do the right thing
if (!$running && $obj->{enabled}) {
  system("/sbin/service ${SERVICE} start >/dev/null 2>&1");
  sleep(1); # wait for named to really start
}
elsif ($running && !$obj->{enabled}) {
  system("/sbin/service ${SERVICE} stop >/dev/null 2>&1");
}
elsif ($running && $obj->{enabled}) {
  system("/sbin/service ${SERVICE} $RESTART >/dev/null 2>&1");
}

# is it running now?
$running = 0;
{
  $SERVICE = "named";
  my $fh = new FileHandle("</var/named/chroot/var/run/named/$SERVICE.pid");
  if ($fh) {
    my $pid = <$fh>; chomp($pid);
    $DEBUG && warn "new $SERVICE pid = $pid\n";
    $fh->close();
    
    $fh = new FileHandle("</proc/$pid/cmdline");
    if ($fh) {
      my $cmdline = <$fh>; chomp($cmdline);
      $DEBUG && warn "new $SERVICE name = $cmdline\n";
      $fh->close();
      
      if ($cmdline =~ m/$CMDLINE/) { $running = 1; }
    }
  }
}

$DEBUG && warn "Running? $running, Enabled in CCE? ".$obj->{enabled};

# proc test has delays that incur a race failure unless we wait at the
# direct expense of the UI.  If there is a failure, AM will catch 
# correct or report it accordingly
# 
# report the did-not-start error, if necessary:
# if ($obj->{enabled} && !$running) {
#   $DEBUG && warn "Kissing CCE goodbye, FAILURE";
#   $cce->warn("[[base-dns.${SERVICE}-did-not-start]]");
#   $cce->bye("FAIL");
#   exit 1;
# }

$DEBUG && warn "Kissing CCE goodbye, SUCCESS";
$cce->bye("SUCCESS");
exit 0;

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
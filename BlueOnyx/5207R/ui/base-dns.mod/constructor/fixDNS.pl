#!/usr/bin/perl -I/usr/sausalito/perl
#
# RHEL/CentOS is normally running SE-linux, this is turned off by default by us.
# This makes it impossible to save AXFR zone-transfers if we don't add "ENABLE_ZONE_WRITE=yes"
# to /etc/sysconfig/named.
#

use lib qw( /usr/sausalito/perl );
use CCE;
use Sauce::Util;
use Sauce::Service;

my $cce = new CCE;

$cce->connectuds();


my $sysconfig = "/etc/sysconfig/named";
$ret = Sauce::Util::editfile($sysconfig, *fix_sysconfig_named);
if(! $ret ) {
  $cce->bye('FAIL', 'cantEditFile', {'file' => $sysconfig});
  exit(0);
} 
system('rm -f /etc/sysconfig/named.backup.*');

my $unitfile = "/usr/lib/systemd/system/named-chroot.service";
if (-f $unitfile) {
  $ret = Sauce::Util::editfile($unitfile, *fix_unitfile_named);
  if(! $ret ) {
    $cce->bye('FAIL', 'cantEditFile', {'file' => $unitfile});
    exit(0);
  }
  system("rm -f rm /usr/lib/systemd/system/named-chroot.service.backup.*");
}

my ($sysoid) = $cce->find("System");
my ($ok, $obj) = $cce->get($sysoid, "DNS");

# Check if we have Systemd:
if (-f "/usr/bin/systemctl") {
  # Got Systemd:
  system("/usr/bin/systemctl daemon-reload");
  $SERVICE = "named-chroot";
}
else {
  $SERVICE = "named";
}

my $running = 0;
{
  my $fh = new FileHandle("</var/named/chroot/var/run/named/named.pid");
  if ($fh) {
    my $pid = <$fh>; chomp($pid);
    $DEBUG && warn "old named pid = $pid\n";
    $fh->close();
    
    $fh = new FileHandle("</proc/$pid/cmdline");
    if ($fh) {
      my $cmdline = <$fh>; chomp($cmdline);
      $DEBUG && warn "old named cmdline = $cmdline\n";
      $fh->close();
      
      if ($cmdline =~ m/$CMDLINE/) { $running = 1; }
    }
  }
}

#print "RUNNING: $running, ". $obj->{enabled}."\n";

# do the right thing
if (!$running && $obj->{enabled}) {
  Sauce::Service::service_set_init($SERVICE, 'on');
  Sauce::Service::service_run_init($SERVICE, 'start');
  sleep(1); # wait for named to really start
}
elsif ($running && !$obj->{enabled}) {
  Sauce::Service::service_set_init($SERVICE, 'off');
  Sauce::Service::service_run_init($SERVICE, 'stop');
}
elsif ($running && $obj->{enabled}) {
  Sauce::Service::service_set_init($SERVICE, 'on');
  Sauce::Service::service_run_init($SERVICE, 'restart');
}

$cce->bye('SUCCESS');
exit(0);

sub fix_sysconfig_named {
    my $in  = shift;
    my $out = shift;

    my $done = 0;
    my $ZONEWRITE = "ENABLE_ZONE_WRITE=yes";
    
    select $out;
    while (<$in>) {
      if (/^ENABLE_ZONE_WRITE/o ) {
        print "$ZONEWRITE\n";
        $done = 1;
      }
      else {
        print $_;
      }
    }

    if (!$done) {
      print "$ZONEWRITE\n";
    }
    return 1;
}

sub fix_unitfile_named {
    my $in  = shift;
    my $out = shift;

    my $done = 0;
    
    select $out;
    while (<$in>) {
      if (/^ExecStartPre=\/bin\/bash/o ) {
        $done = 1;
      }
      else {
        print $_;
      }
    }
    return 1;
}

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2010 Bluapp AB, Rickard Osser <rickard.osser@bluapp.com>
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

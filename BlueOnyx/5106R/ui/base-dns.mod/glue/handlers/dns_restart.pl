#!/usr/bin/perl -w
# $Id: dns_restart.pl 346 2004-04-05 15:40:32Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
#
# starts, stops, and restarts a service on demand, with some extra
# safety checks.  jm.


# configure here: (mostly)
my $SERVICE = "named";	# name of initd script for this daemon
my $CMDLINE = "named";  # contents of /proc/nnn/cmdline for this daemon
my $RESTART = "reload"; # restart action
my $DEBUG   = 1;

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

# do the right thing
if (!$running && $obj->{enabled}) {
  system("/etc/rc.d/init.d/${SERVICE} start >/dev/null 2>&1");
  sleep(1); # wait for named to really start
}
elsif ($running && !$obj->{enabled}) {
  system("/etc/rc.d/init.d/${SERVICE} stop >/dev/null 2>&1");
}
elsif ($running && $obj->{enabled}) {
  system("/etc/rc.d/init.d/${SERVICE} $RESTART >/dev/null 2>&1");
}

# is it running now?
$running = 0;
{
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


# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without 
# modification, are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, 
# this list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation  
# and/or other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.

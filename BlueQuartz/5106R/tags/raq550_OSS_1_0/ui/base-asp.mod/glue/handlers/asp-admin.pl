#!/usr/bin/perl -w
# $Id: asp-admin.pl,v 1.6.2.1 2002/03/01 04:47:06 uzi Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
#
# starts, stops, and restarts a service on demand, with some extra
# safety checks.  jm.


# configure here: (mostly)
my $SERVICE = "asp-admin-5100";	# name of initd script for this daemon
my $CMDLINE = "chiliasp";  # contents of /proc/nnn/cmdline for this daemon
my $RESTART = "reload"; # restart action
my $PID = "/home/chiliasp/admin/logs/httpd.pid";
my $CCEPROP = 'enabledAdmin';

my $DEBUG   = 1;

use lib qw( /usr/sausalito/perl );
use FileHandle;
use Sauce::Util;
use CCE;
$cce = new CCE;
$cce->connectfd();

my ($sysoid) = $cce->find("System");
my ($ok, $obj) = $cce->get($sysoid, "Asp");

# fix chkconfig information:
if ($obj->{$CCEPROP}) {
  Sauce::Service::service_set_init($SERVICE, 'on', '345');
} else {
  Sauce::Service::service_set_init($SERVICE, 'off', '345');
}

# check to see if the service is presently running;
my $running = 0;
{
  my $fh = new FileHandle("<$PID");
  if ($fh) {
    my $pid = <$fh>; chomp($pid);
    $DEBUG && print STDERR "old $SERVICE pid = $pid\n";
    $fh->close();
    
    $fh = new FileHandle("</proc/$pid/cmdline");
    if ($fh) {
      my $cmdline = <$fh>; chomp($cmdline);
      $DEBUG && print STDERR "old $SERVICE cmdline = $cmdline\n";
      $fh->close();
      
      if ($cmdline =~ m/$CMDLINE/) { $running = 1; }
    }
  }
}

# do the right thing
if (!$running && $obj->{$CCEPROP}) {
  system("/etc/rc.d/init.d/${SERVICE}", "start");
  sleep(1); # wait for asp-apache-3000 to really start
}
elsif ($running && !$obj->{$CCEPROP}) {
  system("/etc/rc.d/init.d/${SERVICE}", "stop");
}
elsif ($running && $obj->{$CCEPROP}) {
  system("/etc/rc.d/init.d/${SERVICE}", $RESTART);
}

# is it running now?
$running = 0;
{
  my $fh = new FileHandle("<$PID");
  if ($fh) {
    my $pid = <$fh>; chomp($pid);
    $DEBUG && print STDERR "new $SERVICE pid = $pid\n";
    $fh->close();
    
    $fh = new FileHandle("</proc/$pid/cmdline");
    if ($fh) {
      my $cmdline = <$fh>; chomp($cmdline);
      $DEBUG && print STDERR "new $SERVICE name = $cmdline\n";
      $fh->close();
      
      if ($cmdline =~ m/$CMDLINE/) { $running = 1; }
    }
  }
}

# report the did-not-start error, if necessary:
if ($obj->{$CCEPROP} && !$running) {
  $cce->warn("[[base-asp.${SERVICE}-did-not-start]]");
  $cce->bye("FAIL");
  exit 1;
} else {
  $cce->bye("SUCCESS");
  exit 0;
}


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

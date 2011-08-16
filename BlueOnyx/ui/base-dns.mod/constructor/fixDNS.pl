#!/usr/bin/perl -I/usr/sausalito/perl
# Copyright 2010 Bluapp AB, Rickard Osser <rickard.osser@bluapp.com>, All rights reserved.
#
# RHEL/CentOS is normally running SE-linux, this is turned off by default on all Bluapp/BlueOnyx
# installations, this makes it impossible to save AXFR zone-transfers if we don't add "ENABLE_ZONE_WRITE=yes"
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


my ($sysoid) = $cce->find("System");
my ($ok, $obj) = $cce->get($sysoid, "DNS");


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

print "RUNNING: $running, ". $obj->{enabled}."\n";

# do the right thing
if (!$running && $obj->{enabled}) {
    Sauce::Service::service_run_init('named', 'start');
    sleep(1); # wait for named to really start
} elsif ($running && !$obj->{enabled}) {
    Sauce::Service::service_run_init('named', 'stop');
} elsif ($running && $obj->{enabled}) {
    Sauce::Service::service_run_init('named', 'restart');
}





$cce->bye('SUCCESS');
exit(0);

sub fix_sysconfig_named
{
    my $in  = shift;
    my $out = shift;

    my $done = 0;
    my $ZONEWRITE = "ENABLE_ZONE_WRITE=yes";
    
    select $out;
    while (<$in>) {
	if (/^ENABLE_ZONE_WRITE/o ) {
	    print "$ZONEWRITE\n";
	    $done = 1;
	} else {
	    print $_;
	}
    }
    
    if (!$done) {
	print "$ZONEWRITE\n";
    }
    return 1;
}

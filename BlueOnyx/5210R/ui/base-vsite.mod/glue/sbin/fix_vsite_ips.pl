#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
#
# $Id: fix_vsite_ips
# Copyright 2018 Team BlueOnyx. All rights reserved.
#
# Walks through all Vsites, removes IPs, saves, puts the IPs back in and saves again.
#
# Usage:
#
# Simply run this script once. Running it multiple times will do no harm, though.

use CCE;
my $cce = new CCE;
$cce->connectuds();

# Root check:
my $id = `id -u`;
chomp($id);
if ($id ne "0") {
  print "$0 must be run by user 'root'!\n";

  $cce->bye('FAIL');
  exit(1);
}

# Find all Vsites:
my @vhosts = ();
my (@vhosts) = $cce->findx('Vsite');
my @oids = $cce->find('System');
my $sys_oid = $oids[0];
my ($ok, $System) = $cce->get($sys_oid);
my ($aok, $sys_obj) = $cce->get($sys_oid, 'Network');
my $pooling = $sys_obj->{'pooling'};

print "Going through all Vsites to Re-Apply the IPv4 and IPv6 addresses. \n";

$cce->update($sys_oid, 'Network', { 'pooling' => '0' });

my @ipv4 = ();
my @ipv6 = ();

# Walk through all Vsites:
for my $vsite (@vhosts) {
  ($ok, my $my_vsite) = $cce->get($vsite);

  print "Processing Site: $my_vsite->{fqdn} \n";

  if ($my_vsite->{ipaddr} ne '') {
    push (@ipv4, $my_vsite->{ipaddr});
  }
  if ($my_vsite->{ipaddrIPv6} ne '') {
    push (@ipv6, $my_vsite->{ipaddrIPv6});
  }

  ($ok) = $cce->set($vsite, '',{
      'ipaddr' => '127.0.0.10', 'ipaddrIPv6' => '::10'
     });
  ($ok) = $cce->set($vsite, '',{
      'ipaddr' => $my_vsite->{ipaddr}, 'ipaddrIPv6' => $my_vsite->{ipaddrIPv6}
     });
}

@sorted_ipv4 = sort @ipv4;
@sorted_ipv6 = sort @ipv6;
@ipv4 = uniq(@sorted_ipv4);
@ipv6 = uniq(@sorted_ipv6);

# Get primary IPs of '$device' from Network Config file:
if (($System->{IPType} eq 'VZv4') || ($System->{IPType} eq 'VZBOTH')) {
    # Special case OpenVZ: Primary public IPv4 IP is in 'venet0:0' and not in 'venet0':
    # Additionally: We do NOT have a primary IPv6 IP address under OpenVZ anyway, just extra-IPs.
    $ipv4_ip = `LC_ALL=C cat /etc/sysconfig/network-scripts/ifcfg-venet0:0 | grep IPADDR= | awk -F "IPADDR=" '{print \$2}'`;
    $ipv4_nm = `LC_ALL=C cat /etc/sysconfig/network-scripts/ifcfg-venet0:0 | grep NETMASK= | awk -F "NETMASK=" '{print \$2}'`;
    $ipv6_ip = '';
}
elsif ($System->{IPType} eq 'VZv6') {
    # Special case OpenVZ in pure IPv6 mode: We don't have an IPv4 address or netmask and we don't risk to grab the dummy '127.0.0.1' from 'venet0' either.
    # Additionally: We do NOT have a primary IPv6 IP address under OpenVZ anyway, just extra-IPs.
    $ipv4_ip = '';
    $ipv4_nm = '';
    $ipv6_ip = '';
}
else {
    $ipv4_ip = `LC_ALL=C cat /etc/sysconfig/network-scripts/ifcfg-eth0 | grep IPADDR= | awk -F "IPADDR=" '{print \$2}'`;
    $ipv6_ip = `LC_ALL=C cat /etc/sysconfig/network-scripts/ifcfg-eth0 | grep IPV6ADDR= | awk -F "IPV6ADDR=" '{print \$2}'`;
}
chomp($ipv4_ip);
chomp($ipv4_nm);
chomp($ipv6_ip);

@ipv4 = grep {!/^$ipv4_ip$/} @ipv4;
@ipv6 = grep {!/^$ipv6_ip$/} @ipv6;
$ipv4_out = $cce->array_to_scalar(@ipv4);
$ipv6_out = $cce->array_to_scalar(@ipv6);

$cce->update($sys_oid, 'Network', { 'interfaceConfigure' => $sys_obj->{pooling} });
$cce->update($sys_oid, '', { 'extra_ipaddr' => $ipv4_out, 'extra_ipaddr_IPv6' => $ipv6_out, 'nw_update' => time() });

if ((!-e "/proc/user_beancounters") && (!-f "/etc/vz/conf/0.conf")) {
  my ($priface) = $cce->find('Network',
                   { 
                      'enabled' => 1,
                      'device' => 'eth0' 
                   });
  if ($priface) {
    $cce->update($priface, '', { 'refresh' => time() });
  }
}

# tell cce everything is okay
$cce->bye('SUCCESS');
exit(0);

sub uniq {
    my %seen;
    grep !$seen{$_}++, @_;
}


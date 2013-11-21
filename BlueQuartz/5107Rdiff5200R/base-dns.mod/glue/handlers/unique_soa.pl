#!/usr/bin/perl -w
# $Id: unique_soa.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# fails if SOA is not unique.

use lib qw(/usr/sausalito/perl);
use CCE;
use Data::Dumper;
my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();
my $old = $cce->event_old();
my $new = $cce->event_new();

my $type = $new->{type} || $old->{type};
my $old_soa = undef;
my $new_soa = undef;

my $crit = undef;

if (defined($obj->{ipaddr}) && $obj->{ipaddr} 
 && defined($obj->{netmask}) && $obj->{netmask}) {
  my ($ip, $nm) = normalize_network($obj->{ipaddr}, $obj->{netmask});
  $crit = { 'ipaddr' => $ip, 'netmask' => $nm };
} elsif (defined($obj->{domainname}) && $obj->{domainname}) {
  $crit = { 'domainname' => $obj->{domainname} };
}

if (!defined($crit)) {
  $cce->warn('[[base-dns.invalid-authority]]');
  $cce->bye('FAIL');
  exit(1);
}

my @oids = $cce->find('DnsSOA', $crit);
if ($#oids > 0) {
  $cce->warn('[[base-dns.SOA-already-exists-for-zone]]');
  $cce->bye('FAIL');
  exit(1);
}

$cce->bye('SUCCESS');
exit(0);

########################################
# parse_netmask
########################################
sub parse_netmask
{
  my $nbits = shift;
  if ($nbits =~ m/^\s*\d+\s*$/) {
    return unpack("C4",pack("B32", '1' x $nbits . '0' x (32 - $nbits) ));
  };
  if ($nbits =~ m/^\s*(\d+)\.(\d+)\.(\d+)\.(\d+)\s*$/) {
    return ($1,$2,$3,$4);
  }
  warn ("Invalid netmask: $nbits\n");
  return (255,255,255,255);
}

########################################
# netmask_to_netbits
#
# convert generalized netmask format to just a simple bit-count.
########################################
sub netmask_to_netbits
{
  my $nbits = shift;
  if ($nbits =~ m/^\s*(\d+)\s*$/) {
    return $1;
  };
  if ($nbits =~ m/^\s*(\d+)\.(\d+)\.(\d+)\.(\d+)\s*$/) {
    my @bits = split(//, unpack("B32", pack("C4",$1,$2,$3,$4)));
    $nbits = 0;
    while (@bits) { $nbits += shift(@bits); }
    return $nbits;
  }
  warn ("Invalid netmask: $nbits\n");
  return (32);
}

sub normalize_network
{
  my ($ip, $nm) = (shift, shift);
  
  # normalize netmask to a bitcount: (handle bitcount or dotquad)
  $nm = join(".",parse_netmask($nm));
  my $binmask = pack("C4", split(/\./, $nm));
  my $binip = pack("C4", split(/\./, $ip));
  $ip = join(".", unpack("C4", $binmask & $binip));
  
  return ($ip, $nm);
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

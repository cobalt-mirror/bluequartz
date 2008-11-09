#!/usr/bin/perl -w
#
# automatically maintain an soa for every domain and network.  soa's are
# created and destroyed as needed.

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

if ($type =~ m/^(A)|(CNAME)|(MX)|(NS)$/) {
  if ($cce->event_is_destroy() || defined($new->{domainname})) {
    $old_soa = $old->{domainname} 
	? { 'domainname' => $old->{domainname} }
	: undef;
  }
  if (defined($new->{domainname})) {
    $new_soa = { 'domainname' => $new->{domainname} };
  }
} 

if ($type =~ m/^(PTR)|(NS)$/) {
  if ($cce->event_is_destroy() 
    || defined($new->{ipaddr}) || defined($new->{netmask})) 
  {
    if (defined($old->{ipaddr}) && defined($old->{netmask})) {
      my ($ip,$nm) = normalize_network($old->{ipaddr}, $old->{netmask});
      $old_soa = { 
        'ipaddr' => $ip,
        'netmask' => $nm, 
      };
    }
  }
  if ( defined($new->{ipaddr}) || defined($new->{netmask}) )
  {
    my ($ip,$nm) = normalize_network(
      $new->{ipaddr} || $old->{ipaddr},
      $new->{netmask} || $old->{netmask} );
    $new_soa = { 
      'ipaddr' => $ip,
      'netmask' => $nm, 
    };
  }
}

if (defined($old_soa)) {
  garbage_collect_soa($old_soa);
}

if (defined($new_soa)) {
  maintain_soa($new_soa);
}

$cce->bye("SUCCESS");
exit(0);

sub garbage_collect_soa
{
  my $old_soa = shift;
  print STDERR "Garbage collecting soas:\n".Dumper($old_soa)."\n";  
  my $other_crit = undef;
  my @rec_oids;
  my @more_rec_oids;
 
  if (defined($old_soa->{netmask})) {

    # PTR records
    $other_crit->{network} = $old_soa->{ipaddr}.'/'.netmask_to_netbits($old_soa->{netmask});
    $other_crit->{type} = 'PTR';
    @rec_oids = $cce->find("DnsRecord", $other_crit);

    # subnet delegation
    $other_crit = undef;
    $other_crit->{ipaddr} = $old_soa->{ipaddr};
    $other_crit->{netmask} = $old_soa->{netmask};
    $other_crit->{type} = 'NS';
    @more_rec_oids = $cce->find("DnsRecord", $other_crit);
    push (@rec_oids, @more_rec_oids);

  } else {

    # A records
    $other_crit = undef;
    $other_crit->{domainname} = $old_soa->{domainname};
    $other_crit->{type}='A';
    @rec_oids = $cce->find("DnsRecord", $other_crit);

    # CNAME records
    $other_crit->{type}='CNAME';
    @more_rec_oids = $cce->find("DnsRecord", $other_crit);
    push (@rec_oids, @more_rec_oids);
  
    # MX records
    $other_crit->{type}='MX';
    @more_rec_oids = $cce->find("DnsRecord", $other_crit);
    push (@rec_oids, @more_rec_oids);

    # subdomain delegations
    $other_crit->{type} = 'NS';
    @more_rec_oids = $cce->find("DnsRecord", $other_crit);
    push (@rec_oids, @more_rec_oids);
  }
  print STDERR "Garbage collecting soas:\n".Dumper($old_soa)."\n";  
  
  my @soa_oids = $cce->find("DnsSOA", $old_soa);
  
  print STDERR "destroy soa record-in-use test found oids: ".join(' ',@soa_oids)."\n";

  # my @rec_oids = $cce->find("DnsRecord", $old_soa);

  # if (defined($other_crit)) {
  #   my @more_rec_oids = $cce->find("DnsRecord", $other_crit);
  #   push (@rec_oids, @more_rec_oids);
  # }

  print STDERR "found $#rec_oids DnsRecord's to match, guess we'll keep it\n";
 
  my $oid; 
  if ($#rec_oids < 0) {
    foreach $oid (@soa_oids) {
      $cce->destroy($oid);
    }
  }
}


sub maintain_soa
{
  my $new_soa = shift;
  my(@soa_oids);
    
  @soa_oids = $cce->find("DnsSOA", $new_soa);

  # load default SOA settings
  my ($dns_globalid) = $cce->find("System");
  my ($ok, $dnsdef) = $cce->get($dns_globalid, "DNS");
  if ($ok) {
    ($new_soa->{domain_admin}, $new_soa->{refresh}, $new_soa->{retry}, 
       $new_soa->{expire}, $new_soa->{ttl}) =
       ($dnsdef->{admin_email}, $dnsdef->{default_refresh}, $dnsdef->{default_retry}, 
       $dnsdef->{default_expire}, $dnsdef->{default_ttl});
  }

  if ($#soa_oids < 0) {
    $cce->create("DnsSOA", $new_soa);
  }
}

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

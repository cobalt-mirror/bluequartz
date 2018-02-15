#!/usr/bin/perl -I/usr/sausalito/perl
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: Nettest.pm,v 1.1 2001/11/05 21:00:35 will Exp $
#
# Network tests for dns zone collision tests
#

package Base::Nettest;

use Exporter;
use vars qw(@ISA @EXPORT_OK);

@ISA    = qw(Exporter);

@EXPORT_OK = qw(
		net_convert_netmask
		test_network_in_network
		);

1;

sub net_convert_netmask
# Convert netmask from decimal notation to dotted quad (16 => 255.255.0.0) or vice versa
# Arguments: netmask in decimal, or netmask in dotted quad
# Return value; netmask in dotted quad if decimal argument given, or netmask in
#      decimal if dotted quad argument is given
# Example: $quadmask = Cobalt::Network::net_convert_netmask(26);
{
  my ($mask) = @_;
  # I should be able to do this in some cool mathematical way.
  # If only I knew how.
  my %netmasks = (
                  32 => "255.255.255.255", 31 => "255.255.255.254",
                  30 => "255.255.255.252", 29 => "255.255.255.248",
                  28 => "255.255.255.240", 27 => "255.255.255.224",
                  26 => "255.255.255.192", 25 => "255.255.255.128",
                  24 => "255.255.255.0",   23 => "255.255.254.0",
                  22 => "255.255.252.0",   21 => "255.255.248.0",
                  20 => "255.255.240.0",   19 => "255.255.224.0",
                  18 => "255.255.192.0",   17 => "255.255.128.0",
                  16 => "255.255.0.0",     15 => "255.254.0.0",
                  14 => "255.252.0.0",     13 => "255.248.0.0",
                  12 => "255,240.0.0",     11 => "255.224.0.0",
                  10 => "255.192.0.0",      9 => "255.128.0.0",
                   8 => "255.0.0.0",        7 => "254.0.0.0",
                   6 => "252.0.0.0",        5 => "248.0.0.0",
                   4 => "240.0.0.0",        3 => "224.0.0.0",
                   2 => "192.0.0.0",        1 => "128.0.0.0",
                   0 => "0.0.0.0",

                  "0.0.0.0" => 0,          "128.0.0.0" => 1,
                  "192.0.0.0" => 2,        "224.0.0.0" => 3,
                  "240.0.0.0" => 4,        "248.0.0.0" => 5,
                  "252.0.0.0" => 6,        "254.0.0.0" => 7,
                  "255.0.0.0" => 8,        "255.128.0.0" => 9,
                  "255.192.0.0" => 10,     "255.224.0.0" => 11,
                  "255,240.0.0" => 12,     "255.248.0.0" => 13,
                  "255.252.0.0" => 14,     "255.254.0.0" => 15,
                  "255.255.0.0" => 16,     "255.255.128.0" => 17,
                  "255.255.192.0" => 18,   "255.255.224.0" => 19,
                  "255.255.240.0" => 20,   "255.255.248.0" => 21,
                  "255.255.252.0" => 22,   "255.255.254.0" => 23,
                  "255.255.255.0" => 24,   "255.255.255.128" => 25,
                  "255.255.255.192" => 26, "255.255.255.224" => 27,
                  "255.255.255.240" => 28, "255.255.255.248" => 29,
                  "255.255.255.252" => 30, "255.255.255.254" => 31,
                  "255.255.255.255" => 32,
                 );

  return $netmasks{$mask};
}

sub test_network_in_network
# Test if one IP network is a subset of another network
#   This is not only for testing if an address is on a network--
#   it also tests wether a subnet is within a larger subnet
#   (e.g., 1.2.3.0/24 is a subnet of 1.2.0.0/16)
#   (generally: is the first network within the second?)
# argument 0 is the IP address of the first network
# argument 1 is the netmask of the first network
# argument 2 is the IP address of the second network
# argument 3 is the netmask of second network
# IP addresses do not have to have all 4 octets; any octets that are not
#   defined will be assumed to be 0 (e.g., "10.10.0.0" is equivalent
#   to "10.10".)
# The netmask can be given either in dotted quad (255.255.0.0) or in
#   decimal (16).  If in dotted quad, all four quads must be defined
#   (e.g., "255.255" is not acceptable--you must use "255.255.0.0")
# Return value: 1 if the first network is entirely within the second
#   network or if the two networks are the same; 0 otherwise.
#   Note that if the second network is entirely within the first,
#   we will return 0.
# Example: netutil_test_network_in_network("192.168.45.0",24,"192.168.0.0",16);
{
    my ($ip1, $netmask1, $ip2, $netmask2) = @_;

    # convert the IP addresses from dotted quad (e.g., 192.168.123.45)
    # to binary (e.g., 11000000101010000111101100101101)
    $ip1 = unpack('B32',pack('C4',split('\.',$ip1)));
    $ip2 = unpack('B32',pack('C4',split('\.',$ip2)));

    # If the netmask is given in dotted quad, convert to decimal
    $netmask1 = &net_convert_netmask($netmask1)
      if ($netmask1 =~ /\./o);
    $netmask2 = &net_convert_netmask($netmask2)
      if ($netmask2 =~ /\./o);

    # chop off the bits according to the netmask; if the netmask
    # is 32, then no chopping is done (it's exactly that IP)
    # (To return all but the last character, use substr($string, 0, -1))
    $ip1 = substr($ip1,0,$netmask1) if ($netmask1 != 32);
    $ip2 = substr($ip2,0,$netmask2) if ($netmask2 != 32);

    # Now do the comparisons...
    if ($netmask1 >= $netmask2) {
      # the second network is as large or larger than the first
      # (a smaller subnet mask means a larger network)
      return 1 if ($ip1 =~ /^$ip2/);
    } else {
      # The following tests if the second net is in the first
      # the first network is larger (smaller mask)
      return 2 if ($ip2 =~ /^$ip1/);
    }
    return 0;
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

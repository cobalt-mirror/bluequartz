#!/usr/bin/perl
# Author: Brian N. Smith
# Copyright 2005, NuOnce Networks.  All rights reserved.
# $Id: minicalc.pl,v 1.00 2005/09/24 20:49:56 bsmith Exp $

my $output_what = $ARGV[0];
my $ipaddr = $ARGV[1];
my $netmask = $ARGV[2];

# convert the ip address and netmask to binary representations
my $binip = pack('CCCC', split(/\./, $ipaddr));
my $binmask = pack('CCCC', split(/\./, $netmask));

# calculate the network
my $binnet = $binip & $binmask;

# calculate the broadcast address
my $binbcast = $binnet | ~$binmask;

# convert network and broadcast into dotted-quad format
my $network = join('.', unpack('CCCC', $binnet));
my $bcast   = join('.', unpack('CCCC', $binbcast));

if ( $output_what eq "id" ) { print $network; }
if ( $output_what eq "bid" ) { print $bcast; }


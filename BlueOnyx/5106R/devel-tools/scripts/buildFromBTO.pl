#!/usr/bin/perl
#
# set CMOS bits to hop on BTO and rebuild the box.


use strict;
use lib qw(/usr/sausalito/perl);
use BTO;
use Build;

# constants
my $nvramBIN = "/sbin/nvram";
my $autobuildLatestFlag = 0;

die "Usage:\n$0 <product type> <product> <version> <ip of BTO>\n" if $#ARGV lt 2;
my ($productType, $product, $version, $BTOIP) = @ARGV;

# set our BTOIP cmos bits
if (defined($BTOIP)) {
  if(! setBTOIP($BTOIP)) {
    print "Could not set the BTO IP in cmos.  Not root?\n";
    exit 1;
  }
}
# if version is set to "LATEST" we want the latest build of that product
if ($version =~ /latest/i) {
  $autobuildLatestFlag = ($version =~ /auto/i) ? 1 : 0;
  $version = returnLatest($productType, $product);
  if ($version eq "-1") {
    print "We have the latest version for $productType: $product\n";
    exit 0;
  }
}

# set our BTO flags
if(!setBTOflags_nvram($productType, $product, $version, $autobuildLatestFlag)) {
  print "Could not set BTO flags, invalid product type or product?\n";
  clearBTOflags();
} else {
  # set to netboot
  system("$nvramBIN -c defboot n");
  if ($autobuildLatestFlag) {
    print "Rebooting for autobuild latest config";
  } else {
    print "Rebooting to build $productType: $product $version off of BTO: $BTOIP\n";
  }
  sleep 3;
  system("/sbin/reboot");
}

#----------------------------------END--------------------------------------#

######################################################
#
# Takes in type, product and returns the latest version
# or -1 if there are no later versions 
#
sub returnLatest {
  my ($type, $product) = @_;
  my $btoIP = join(".", getBTOIP_cmos());
  my $mountDir = "/mnt";
  my $btoDir = "$mountDir/bto-sync/$type";
  
  my $myVersionCVS = get_product();
  my $latestVersion;
  my @versions;
  
  # Mount the BTO server
  system("mount -t nfs $btoIP:/nfsroot-x86 $mountDir");
  if ($? != 0) {
    print "Could not mount $btoIP!\n"; 
    exit 0;
  }
  # Get our list of versions
  opendir(DIR, $btoDir);
  while (my $build = readdir(DIR)) {
    my $build_version;
    if ($build =~ /^$product/) {
      if ($build =~ /_(.+)/) { $build_version = $1;}
      push @versions, $build_version;
    }
  }
  closedir(DIR);
  
  @versions = sort versions @versions;
  $latestVersion = pop @versions;
  
  # compare to see if this version is better than what we have
  my $latestVersionCVS = getCVSversion("$btoDir/$product\_$latestVersion/installer/release_spec.$product");
  system("umount $mountDir");
  if (($latestVersionCVS ne $myVersionCVS) & isGreater($latestVersionCVS, $myVersionCVS)) {
    return $latestVersion;
  } else {
    return -1;
  }
}

######################################################
#
# Returns my build version and product type
#  
sub get_product {
  my ($build, $product);
  if (open(BUILD, '/etc/build')) {
    while (<BUILD>) {
      if (/build\s+(\S+)/) {
	$build = $1;
      }
      if (/for\s+a\s+(\S+)\sin/) {
	$product = $1;
      }
    }
    close(BUILD);
  }
  return ($product, $build);
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

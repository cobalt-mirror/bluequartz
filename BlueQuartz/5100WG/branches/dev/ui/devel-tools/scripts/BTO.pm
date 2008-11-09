use strict;

use vars qw(@ISA @EXPORT);

@ISA = qw(Exporter);
@EXPORT = qw(getBTOIP_cmos setBTOIP getBTOflags_cmos setBTOflags_nvram 
	     clearBTOflag isGreater);

my $nvramBIN = "/sbin/nvram";
my $cmosBIN = "/usr/sbin/cmos";

# This is our BTOflag  mapping
my @productMap = (
                  "NONE",       # 0 NONE
                  "4100R"       # 1 4100R
                 );
my @typeMap = (
               "NONE",          # 0 NONE
               "daily",         # 1 daily
               "experimental",  # 1 experimental
               "production"     # 2 production
              );

######################################################
#
# Return our ROM version
# 

sub getROMVersion {
  my $VERSION = `$cmosBIN -c romrev`;
  chomp $VERSION;
  return $VERSION;
}


######################################################
#
# get BTOIP using the old cmos program
# 
sub getBTOIP_cmos {
  my @IP = (0, 0, 0, 0);
  my $OFFSET = 0x58;
  
  # the BTOIP bits only mean something if the rom is
  # >=2.9.16
  if (isGreater(getROMVersion(), "2.9.16")) {
    # rom =>2.9.16, extract the bits
    for (my $i = 0; $i < 4; $i++) {
      my $executeString = sprintf("$cmosBIN 0x%x", $OFFSET);
      my $outString;
      $outString = `$executeString`;
      if ($outString =~ (/=\s(\d+)\s\(/)) {
	$IP[$i] = $1;
      }
      $OFFSET++;
    }
    return @IP;
  } else {
    return @IP;
  }
}

######################################################
#
# Sets BTOIP in cmos
#
sub setBTOIP {
  my $IPstring = shift;
  system("$nvramBIN -c btoip $IPstring");
  return !$?
}

######################################################
#
# get BTO flags using the old CMOS program
# (type, product, version, build latest flag) return 0 if unsucessful
sub getBTOflags_cmos {
  my @BTOFLAGS;
  my $OFFSET = 0x53;
  my $bitString = "";
  my $version = "";

  # the BTO flag bits only mean something if the rom is
  # >=2.9.16
  if (isGreater(getROMVersion(), "2.9.16") == 1 ) {
    # rom =>2.9.16, extract the bits
    for (my $i = 0; $i < 4; $i++) {
      my $executeString = sprintf("$cmosBIN 0x%x", $OFFSET);
      my $outString;
      $outString = `$executeString`;
      if ($outString =~ (/=\s(\d+)\s\(/)) {
	$BTOFLAGS[$i] = $1;
      }
      $OFFSET++;
    }
    # bang some bits to get our type, product and version info
    my $bitString = $BTOFLAGS[0] << 24 | $BTOFLAGS[1] << 16 | $BTOFLAGS[2] << 8 | $BTOFLAGS[3];
    # minor version (or day of month if using build date versioning) is 12 bits
    my $minorVersion = $bitString & 0xfff;
    # major version (or month if using build date versioning) is next 4 bits
    my $majorVersion = ($bitString >> 12) & 0xf;
    # year - if using build date versioning is next 4 bits (start at 01, 00 means we are using x.xx version format)
    my $buildYear = ($bitString >> 16) & 0xf;
    # product is next 8 bits
    my $productID = ($bitString >> 20) & 0xff;
    # product type is next 3 bits
    my $productTypeID = ($bitString >> 28) & 0x7;
    # autobuild latest flag is next bit
    my $buildLatest = ($bitString >> 31) & 0x1; 

    # figure out if we stuffed our CMOS with x.xxx CVS version format or year.month.day format
    if ($buildYear == 0) {
      # version is in CVS format
      $version = "$majorVersion.$minorVersion";
    } else {
      # version is in year.month.day format
      my $year = sprintf("%02s", $buildYear);
      my $month = sprintf("%02s", $majorVersion);
      my $day = sprintf("%02s", $minorVersion);
      $version = "$year.$month.$day";
    }
    
    if (! (defined($typeMap[$productTypeID]) && defined($productMap[$productID])) && defined($version)) {
      return("NONE", "NONE", "NONE", 0);
    } else {
      return($typeMap[$productTypeID], $productMap[$productID], $version, $buildLatest);
    }
  } else {
    # not supported
    return(0, 0, 0, 0);
  }
}


######################################################
#
# set BTO flags using the nvram program
# takes (type, product, version, build latest flag)
# return 0 on failure 1 on success
sub setBTOflags_nvram {
  my ($productType, $product, $version, $buildLatest) = @_;
  my $productTypeID;
  my $productID;
  my $majorVersion;
  my $minorVersion;
  my $year;
  my $month;
  my $day;
  
  for (my $i = 0; $i <= $#typeMap; $i++) {
    if ($typeMap[$i] eq "$productType") {
      $productTypeID = $i;
    }
  }
  for (my $i = 0; $i <= $#productMap; $i++) {
    if ($productMap[$i] eq $product) {
      $productID = $i;
    }
  }
  # Make sure we have mappings for the passed arguments
  if (! (defined($productTypeID) && defined($productID) && defined($version))) {
    return 0;
  }
  
  # Did we pass in the version in date format or cvs format?
  if ($version =~ /(\d+)\.(\d+)\.(\d+)/) {
    $year = $1;
    $majorVersion = $2; # month
    $minorVersion = $3; # day 
  } elsif ($version =~ /(\d+)\.(\d+)/) {
    # year == 00 signifies we are using CVS version format
    $year = "00";
    $majorVersion = $1;
    $minorVersion = $2;
  } else {
    return 0;
  }
  # Convert to hex
  # product type is 3 bits, build latest flag is 1 bit (tacked before the 3 bits of productID)
  $productTypeID = sprintf("%lx", $buildLatest << 3 | $productTypeID);
  # product is 8 bits
  $productID = sprintf("%02lx", $productID);
  # year version is 4 bits
  $year = sprintf("%lx", $year);
  # major version is 4 bits
  $majorVersion = sprintf("%lx", $majorVersion);
  # minor version is 12 bits
  $minorVersion = sprintf("%03lx", $minorVersion);
  # Set our cmos bits
  my $execString = "$nvramBIN -c btocode 0x" . $productTypeID . $productID . $year . $majorVersion . $minorVersion;
  print "$execString\n";
  system("$execString");
  
  # reread our cmos bits to validate
  my ($t, $p, $v, $a) = getBTOflags_cmos();
  if (($t eq $productType) && ($p eq $product) && ($v eq $version) && ($a == $buildLatest)) {
    return 1;
  } else {
    print "Set BTO CMOS flags, verify does not match.\n";
    print "it is $t, $p, $v, BuildLatestFlag: $a\n";
     # reset our cmos bits
    #clearBTOflags();
    return 0;
  }
}



######################################################
#
# Clears the BTO cmos flags
#
sub clearBTOflags {
  system("$nvramBIN -c btocode 0x00000000");
}

########################################################
#
# Return 1 if arg1 version is equal or greater than arg2
# isgreater(arg1, arg2)

sub isGreater {
  my($arg1, $arg2) = @_;

  my ($l, $g) = sort versions($arg1, $arg2);
  if ($g eq $arg1) {
    return 1;
  } else {
    return 0;
  }
}

######################################################
#
# version sort routine
# 
sub versions {
  my(@A) = ($::a =~ /(\.|\d+|[^\.\d]+)/g);
  my(@B) = ($::b =~ /(\.|\d+|[^\.\d]+)/g);
  my($A,$B);
  while(@A and @B) {
    $A=shift @A;
    $B=shift @B;
    if($A eq "." and $B eq ".") {
      next;
    } elsif( $A eq "." ) {
      return -1;
    } elsif( $B eq "." ) {
      return 1;
    } elsif($A =~ /^\d+$/ and $B =~ /^\d+$/) {
      if ($A =~ /^0/ || $B =~ /^0/) {
        return $A cmp $B if $A cmp $B;
      } else {
        return $A <=> $B if $A <=> $B;
      }                                                                      
    } else {
      $A = uc $A;
      $B = uc $B;
      return $A cmp $B if $A cmp $B;
    }       
  }
  @A <=> @B;
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

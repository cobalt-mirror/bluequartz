#!/usr/bin/perl -w

#####################################################
#
# Takes release_spec file
# and directory with RPM's and verifies that the 
# RPM's exist.

use strict;
use File::Copy;

if ($#ARGV != 1) {
  die "Usage:\n$0 <release_spec> <RPM directory>\n";
}

my ($RELSPEC, $RPMDIR) = @ARGV;

my $oldRPMDIR = "/fargo/i386";
my $exitValue = 0;

print "Verifying $RELSPEC with $RPMDIR\n";

# Get hardware generations supported
my @HARDWARE_GENS = split(/\n/, `grep -v '^#' $RELSPEC | sed -n -e "/%begin Hardware_Gen/,/%end Hardware_Gen/ p" | grep -v '^%'`);

open(LIST, "$RELSPEC") || die "Can't access $RELSPEC\n";
while (<LIST>) {
  my $dir = "";
  my $file = "";
  
  if (/\#?(RPM:)?\s?(.*\.rpm)/) {
    my $file = $2;
    chomp $file;
    # Look to see if there is a tag specifying which module the RPM exists in
    if (/:(\S+)/ && (!($1 eq "."))) {
      $dir = "$RPMDIR/$1";
    } else {
      $dir = "$RPMDIR/rpms";
    }
    # Look to see if there is a hardware tag on the RPM
    if ($file =~ /\%HGEN\%/) {
      foreach my $HARDWARE_GEN (@HARDWARE_GENS) {
	my $realfile = $file;
	$realfile =~ s/\%HGEN\%/$HARDWARE_GEN/g;
	if (! -e "$dir/$realfile") {
	  print "Hardware specific $file missing in $dir\n";
	  $exitValue = 1;
	}
      }
    }
    # No hardware tag
    elsif (! -e "$dir/$file") {
      # We are missing the file, check in old repository.
      if (-e "$oldRPMDIR/$file") {
	# it exists, lets copy it over to new dir
	print "$file missing in $dir but exists in $oldRPMDIR, so copying file to $dir\n";
	if (!-d "$dir") {
	  mkdir("$dir");
	}
	if (! (copy("$oldRPMDIR/$file", "$dir") && chmod(0644, "$dir/$file"))) {
	  print "copy of $file to $dir failed\n";
	}
      } else {
	# it doesn't exist, cache an error value
	print "$file missing in $dir\n";
	$exitValue = 1;
      }
    }
  }
}
close(LIST);

if (!$exitValue) {
  print "$RELSPEC verified\n";
}

exit $exitValue;

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

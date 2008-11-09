#!/usr/bin/perl -w
#
#  $Id: versioncheck.pl 922 2003-07-17 15:22:40Z will $
# 
# Copyright 2000 Cobalt Networks, Inc http://www.cobalt.com/

use strict;

# wrap this around the packing_list, and output new packing
# list as we verify rpm versioning to resolve conflicts.
# accounts for UPDATE[1-9] also.

# hardcoded stuff
my $pack = 'packing_list';     # location of packing list
my $nupk = 'packing_list.upd'; # temp packing list we'll create
my $rpms = 'RPMS';             # location of RPMS
my $base = shift @ARGV;        # base directory for pkg install
$ENV{HOME} = '/root';          # for the sanity of rpm

# Main

# process root
chdir ( $base ) or die "Failed to change to directory $base: $!\n";
stripit( $pack, $nupk, "." );

# look for update directories
for( 1..9 ) {
  if ( -e "UPDATE$_" ) {
    stripit( $pack,  $nupk, "UPDATE$_" );
  }
}


# stripit()
#
# overview: tests and removes rpms for packing_list and directory
# inputs:   packing list, temp packing list, and base directory
# returns:  0 on success 1 on error
sub stripit {
  my( $oldpl, $newpl, $basdir ) = @_;

  open( OFH, "$basdir/$oldpl" )    or die "Failed to open $oldpl: $!\n";
  open( NFH, ">$basdir/$newpl" )   or die "Failed to open $newpl: $!\n";

  OUT: while( <OFH> ) {
    chomp;

    if ( /^RPM/ ) {
      /RPM: (\S+)/;  # RPM name is stored in $1
      my $rpm = $1;
      my $return = `rpm -U $basdir/$rpms/$1 --test 2>&1`;

      CASE: {
        if ( $return =~ /already/ )  { # This rpm is already installed
                                       deleteit("$basdir/$rpms/$rpm");
                                       next OUT;
                                     };
        if ( $return =~ /newer/ )    { # A newer version of this rpm is installed
                                       deleteit("$basdir/$rpms/$rpm");
                                       next OUT;
                                     };
       }

    }
    print NFH $_, "\n";
  }

  close( OFH );
  close( NFH );

  rename( "$basdir/$newpl", "$basdir/$oldpl" );

  return;
}

# deleteit()
#
# overview: deletes specified file
# inputs:   EXACTLY one filename, no lists
# returns:  0 on success 1 on error
sub deleteit($) {
  my $filename = shift;

  if ( ! -e $filename ) { return 1; }  # oops, no file there

  unlink ( $filename );

  return;
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

#!/usr/bin/perl

use strict;
use lib qw(/usr/sausalito/perl);
use Devel;
use Build;

#######################################################################
#
#  Set environment variables
#
#######################################################################

# Set up ssh for cvs!  Make sure your key is in
# ~/.ssh/authorized_keys with no passphrase so we 
# can do this without prompting
my $CVSRAQ_CVS = ':ext:build@cvsraq:/home/cvs';
$ENV{CVS_RSH} = "ssh";
$ENV{CVSROOT} = $CVSRAQ_CVS;

# Send errors from this script to:
my $MAILTO = 'root@localhost';

my $BUILD_DIR = "/home/build";
my $BUILDSCRIPT = "/usr/sausalito/bin/AutoBuild.pl";
my $DEBUG = 0;
my $HELP = 0;

my @PRODUCTS;
my @CRONSCHEDULES;

use Getopt::Long;
GetOptions(
	"debug" => \$DEBUG,
	"build-dir=s" => \$BUILD_DIR,
	"help"    => \$HELP);

#######################################################################
#
#  Set up our build schedule
#
#######################################################################

# Check out products directory
$DEBUG && print "Checking out products directory under $BUILD_DIR\n";
if (!coProducts($BUILD_DIR)) {
  print "Error checking out products\n";
  exit 1;
}

# Read products directory
opendir PRODUCTS_DIR, "$BUILD_DIR/products.prd" || die "Can't open $BUILD_DIR/products.prd";

# Get all products with build.conf configuration files
foreach my $PRODUCT (readdir PRODUCTS_DIR) {
  next if ($PRODUCT =~ /(CVS)|(\.+)/);
  next if (! -e "$BUILD_DIR/products.prd/$PRODUCT/build.conf");
  $DEBUG && print "Found $PRODUCT build.conf in $BUILD_DIR/products.prd/$PRODUCT/build.conf\n";
  push(@PRODUCTS, $PRODUCT);
}

# Read configuration file and generate a schedule
foreach my $PRODUCT (@PRODUCTS) {
  my $BUILDCONF = "$BUILD_DIR/products.prd/$PRODUCT/build.conf";
  my %CONF = parseBuildConf($BUILDCONF);
  
  if (confirmServer($CONF{BUILDSERVER}) && ($CONF{SCHEDULE} ne "")) {
    $DEBUG && print "We schedule a build for $PRODUCT\n";
    $DEBUG && print "Cronline is: $CONF{SCHEDULE}\t$BUILDSCRIPT --config=$BUILDCONF --build-dir=$BUILD_DIR/$PRODUCT\n";

    my $CRONLINE =  "$CONF{SCHEDULE}\t$BUILDSCRIPT --config=$BUILDCONF --build-dir=$BUILD_DIR/$PRODUCT\n";
    push(@CRONSCHEDULES, $CRONLINE);
  } else {
    $DEBUG && print "We don't schedule a build for $PRODUCT\n";
  }
}

# Installs the new crontab
installCrontab($MAILTO, @CRONSCHEDULES);

exit 0; 

##########################################
#
# Check out products.prd from CVS, takes
# directory to check out under.
#
sub coProducts {
  my $DIR = shift;

  chdir($DIR) || die "Can't find $DIR\n";
  
  if (-d 'products.prd') {
    if (!cvs_cmd("update -PAd products.prd")) {
      return 0;
    }
  } else {
    if (!cvs_cmd("co products.prd")) {
      return 0;
    }
  }
  return 1;
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

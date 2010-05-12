#!/usr/bin/perl -w

use strict;
use lib qw(/usr/sausalito/perl);
use FileHandle;
use Devel;
use Build;

# Build variables
my $PRODUCT = "ptlobos";
my $XLOCALEPAT = "ja es de fr";
my $BUILD_DIR = "/home/build";
my $RPM_DIR = "";
my $BTO = "bto-dev";
my $BUILD_VERSION = "";
my $BUILD_TYPE = "daily";

my $PRODUCTCODE;

# Script variables
my $PRODUCT_DIR = "";
my $CUTSCRIPT = "cut_to_bto";
my $CCEDIR = "/usr/sausalito";
my $CHECKOUTSCRIPT = "$CCEDIR/bin/checkout_product.pl";
my $MAKESCRIPT = "$CCEDIR/bin/make_release";
my $VERIFYSCRIPT = "$CCEDIR/bin/verifyRelease.pl";

# Misc config variables
my $CONFIGFILE = "";
my %CONFIG;
my $PUSH ;
my $WAITFORHARNESS;
my @PUSHTOBTO;
my $QUIET = 0;
my $FORCE = 0;
my $CVSVERSION = "Not Applicable";

##################################################################
#
# Set up our environment
#
##################################################################

# Set up ssh for cvs!  Make sure your key is in
# ~/.ssh/authorized_keys with no passphrase so we 
# can do this without prompting
my $CVSRAQ_CVS = ':ext:build@cvsraq:/home/cvs';
my $GLAZED_CVS = ':ext:build@glazed:/fargo/cvs';
$ENV{CVS_RSH} = "ssh";
$ENV{CVSROOT} = $CVSRAQ_CVS;


# Get options from the command line
use Getopt::Long;
GetOptions("config-file=s" => \$CONFIGFILE,
	   "exclude-locales=s" => \$XLOCALEPAT,
	   "build-dir=s" => \$BUILD_DIR,
	   "rpm-dir=s"  => \$RPM_DIR,
	   "product=s" => \$PRODUCT,
	   "bto=s" => \$BTO,
	   "set-version=s" => \$BUILD_VERSION,
	   "set-type=s" => \$BUILD_TYPE,
	   "push" => \$PUSH,
	   "quiet" => \$QUIET,
	   "force" => \$FORCE);


# If we use a build.conf file, extract the config options
# overrides command line.
if ($CONFIGFILE) {
  %CONFIG = parseBuildConf($CONFIGFILE);
  
  $PRODUCT = $CONFIG{PRODUCT};
  $XLOCALEPAT = $CONFIG{EXCLUDELOCALE};
  $BTO = $CONFIG{BTO};
  $BUILD_VERSION = $CONFIG{SETVERSION};
  $BUILD_TYPE = $CONFIG{BTOTYPE};
  $PUSH = ($CONFIG{PUSHTOBTO} =~ /yes/i) ? 1 : 0;
  $WAITFORHARNESS = ($CONFIG{RUNTESTHARNESS} =~ /yes/i) ? 1 : 0;
}

# Set up misc. variables
$PRODUCT_DIR = "$BUILD_DIR/products.prd/$PRODUCT";
$ENV{XLOCALEPAT} = $XLOCALEPAT;
if (! $RPM_DIR) {
  $RPM_DIR = "/fargo/$PRODUCT";
}
umask(022);

my $LOGFILE = "/tmp/autobuild_$PRODUCT.log";
my $TESTHARNESS_DRIVER = "bp2.cobalt.com";

##################################################################
#
# Print AutoBuild Info
#
##################################################################
if (!$QUIET) {
  print "AUTOBUILDING WITH OPTIONS:\n";
  print "Product:           $PRODUCT\n";
  print "Exclude Locales:   $XLOCALEPAT\n";
  print "Build Version:     $BUILD_VERSION\n";
  print "Build Type:        $BUILD_TYPE\n";
  print "Build Directory:   $BUILD_DIR\n";
  print "RPM Repository Dir:$RPM_DIR\n";
  print "BTO Server:        $BTO\n";
  print "Config File:       $CONFIGFILE\n";
}

# If $BUILD_VERSION is "DATE" replace that with a date string
if ($BUILD_VERSION eq "DATE") {
  $BUILD_VERSION = getDateVersion();
  $QUIET || print "Build Version:     $BUILD_VERSION\n";
}
$QUIET || print "---------------------------------------------\n\n";

#################################################################
#
# Do the work
#
#################################################################

unlink("$LOGFILE");

# Checkout our product
system("$CHECKOUTSCRIPT --product=$PRODUCT --build-dir=$BUILD_DIR --quiet >> $LOGFILE 2>&1");
($? == 0) || exitScript(1, "Checkout Error");

# Checkout and install devel-tools
install_Develtools($BUILD_DIR);

# Don't commit if we are not pushing
my $COMMITOPTION = ($PUSH) ? "--commit" : "";
my $FORCEOPTION = ($FORCE) ? "--force" : "";

# Call the big make_release script with options
system("$MAKESCRIPT --product=$PRODUCT --build-dir=$BUILD_DIR --rpm-dir=$RPM_DIR --exclude-locales=\"$XLOCALEPAT\" --quiet $COMMITOPTION $FORCEOPTION>> $LOGFILE 2>&1");
($? == 0) || exitScript(1, "Build Errors");

# Make release_spec
chdir("$PRODUCT_DIR") || exitScript(1, "Can't find build directory: $PRODUCT_DIR\n");

system("rm release_spec.*; $CCEDIR/bin/make_release_spec --process-all *.tmpl --build-dir=$BUILD_DIR --quiet >> $LOGFILE 2>&1");
($? == 0) || exitScript(1, "Error on making release_spec");

# Check all files exist in release_spec
opendir PRODUCT, "$PRODUCT_DIR" || die "Can't open product directory: $PRODUCT_DIR.\n";
for my $release_spec (grep /^release_spec\.*/o, readdir PRODUCT) {
  system("$VERIFYSCRIPT $PRODUCT_DIR/$release_spec $RPM_DIR >> $LOGFILE 2>&1");
  ($? == 0) || exitScript(1, "Error on $release_spec verification");
}
closedir PRODUCT;

# Quit here if we are not pushing to BTO
if (! $PUSH) {
  exitScript(0);
}


# ACK! Switch CVSROOT to check out latest installer
$QUIET || print STDOUT "\nChecking out installer.\n";
chdir("$BUILD_DIR");
$ENV{CVSROOT} = $GLAZED_CVS;
cvs_cmd("co installer") || exitScript(1, "Couldn't check out installer");

chdir("$BUILD_DIR/installer") || die "Couldn't CD into $BUILD_DIR/installer\n";

# Copy release_spec's to installer.  Commit.
$QUIET || print STDOUT "Copy release_spec's to installer.\n";

opendir PRODUCT, "$PRODUCT_DIR" || die "Can't open product directory: $PRODUCT_DIR.\n";
for my $release_spec (grep /^release_spec\.*/o, readdir PRODUCT) {
  my $CVSadd = (-e "$BUILD_DIR/installer/$release_spec") ? 0 : 1;
  system("cp $PRODUCT_DIR/$release_spec $BUILD_DIR/installer");
  if ($CVSadd) {
    cvs_cmd("add $release_spec") || exitScript(1, "Release Spec CVS add: $PRODUCT_DIR/$release_spec failed!");
  }
  cvs_cmd("commit -m \"Automated build. Exclude: $XLOCALEPAT. $BUILD_VERSION\" $release_spec") || exitScript(1, "Release Spec commit: $PRODUCT_DIR/$release_spec failed!");
  # get our CVS version
  $CVSVERSION = getCVSversion("$BUILD_DIR/installer/$release_spec");
  push(@PUSHTOBTO, $release_spec);
}
closedir PRODUCT;

# The cut to scripts automatically set build versions to CVS
if ($BUILD_VERSION =~ /CVS/i) {
  $BUILD_VERSION = "";
}

# Push to BTO - I am in installer directory.
for my $push_spec (@PUSHTOBTO) {
  if ($push_spec =~ /release_spec\.(.*)/) {
    $PRODUCTCODE = $1;
    $QUIET || print "Cutting $PRODUCT $PRODUCTCODE to $BTO.\n";
    system("sh ./$CUTSCRIPT $PRODUCT $PRODUCTCODE $BTO $BUILD_TYPE $BUILD_VERSION >> $LOGFILE 2>&1");
    ($? == 0) ||  exitScript(1, "Error on push to $BTO");
  }
}
# Exit
exitScript(0);

sub exitScript {
  my ($exitValue, $error) = @_;
  my $HARNESSREPORTFILE = "";
  my @MAILTO = ();

  # mail reports out
  
  if ($CONFIGFILE) {
    if ($exitValue == 0) {
      $HARNESSREPORTFILE = ($WAITFORHARNESS) ? getHarnessReport($PRODUCTCODE, $CVSVERSION, $TESTHARNESS_DRIVER) : "";
      mailReport($BUILD_VERSION, $CVSVERSION, "", $CONFIGFILE, $LOGFILE, $HARNESSREPORTFILE);
    } else {
      mailReport("none", 0, $error, $CONFIGFILE, $LOGFILE, $HARNESSREPORTFILE);
    }
  }
  exit($exitValue);
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

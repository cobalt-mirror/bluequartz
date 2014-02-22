#!/usr/bin/perl

use strict;
use lib qw(/usr/sausalito/perl);
use File::Copy;
use FileHandle;
use Devel;

#############################################################
#
#  Variables and options setup
#
#############################################################
my $PRODUCT = "";
my $BUILD_DIR = "/home/build";
my $HELP = 0;
my $QUIET = 0;
my %MODULE_LIST;

use Getopt::Long;
GetOptions( 
     	"build-dir=s" => \$BUILD_DIR,
	"product=s" => \$PRODUCT,
	"help"    => \$HELP,
	"quiet"   => \$QUIET);

if ($HELP || ($PRODUCT eq "")) {
  print STDERR <<EOT ;
You can specify these options:
  --product=                  <product codename>
  --build-dir=                <checkout directory> (defaults to "/home/build")
  --cvs-tags=                 <cvs tags to pass in module checkout>  (optional)
  --quiet                     <only display output on errors>
  --help                      <this text>
EOT
  exit(1);
}

# check environment variables
if (!$ENV{"CVSROOT"}) {
  print STDERR <<EOT ;
Error: all of these environment variables must be defined:
  CVSROOT
EOT
  exit(1);
}


# make sure checkout directory exists.
if (!-d $BUILD_DIR) {
  mkdir($BUILD_DIR, 0755) || die "Can't make $BUILD_DIR\n";
}
chdir($BUILD_DIR);

$QUIET || print "*** checking out all modules for $PRODUCT ***\n";
$QUIET || print "Checking out products.prd\n";
if (-d 'products.prd') {
  if (!cvs_cmd("update -PAd products.prd")) {
    die "Error on products.prd update.\n";
  }
}else{
  if (!cvs_cmd("co products.prd")) {
    die "Error on products.prd checkout.\n";
  }
}

if (-f "products.prd/$PRODUCT/devel_list"){
  check_out_modules("products.prd/$PRODUCT/devel_list", \%MODULE_LIST, $QUIET) ||
    die "Error on checkout of modules\n";
}

# Checkout all of the modules here.
opendir PRODUCT, "products.prd/$PRODUCT/" ||
  die "Can't open products.prd/$PRODUCT/, unable to access packing_list files\n";
foreach my $list (grep /^packing_list/o, readdir PRODUCT) {
  check_out_modules("products.prd/$PRODUCT/$list", \%MODULE_LIST, $QUIET) || 
    die "Error on checkout of modules\n";
}
closedir PRODUCT;

# Success
$QUIET || print "End of checkout for $PRODUCT\n";
exit 0;
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

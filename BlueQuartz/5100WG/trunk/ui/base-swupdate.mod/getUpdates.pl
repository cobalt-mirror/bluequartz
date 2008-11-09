#!/usr/bin/perl

# Copyright 2000, Cobalt Networks.  All rights reserved.
# $Id: getUpdates.pl 3 2003-07-17 15:19:15Z will $
# author: mchan@cobalt.com

use strict;
use CGI;

my $packagelist_dir = "/home/httpd/packages";
my $package_tar = "package-info.tar.gz";

my $cgi = new CGI;

my $product = $cgi->param("product");
my $build = $cgi->param("build");
my $serial = $cgi->param('serialnum');
my $notifyMode = $cgi->param('notificationMode');
my $installedScalar = $cgi->param('installed');
my $ui = $cgi->param("ui");

# This is an array of package filenames the client has installed
$installedScalar = CGI::unescape($installedScalar);
my @installedPackages = scalar_to_array($installedScalar);

# No params passed
unless ($product) {
  returnError("Needed parameters not defined!");
}

# If $ui not passed, default is yes
$ui = 'yes' unless $ui;

# Safety checking
if (($product =~ /\//) || ($build =~ /\//)) {
  returnError("Parameter not formatted correctly!");
}

# Do we have a packagelist for that product and build?
my $list = "$packagelist_dir/$product/$package_tar";
returnError("No packages available for product ($product)") 
	unless -f $list;

print "Cache-Content: no-cache\n";
print "Content-Type: application/x-gzip\n\n";
`cat $list`;
exit 0;


sub returnError() {
  my $message = shift;
  
  print 
    $cgi->header,
    $cgi->start_html("Query Error!"),
    $cgi->h2("Query Error: $message"),
    $cgi->end_html;
  exit(1);
}

sub scalar_to_array {
  my $scalar = shift || "";
  $scalar =~ s/^,//;
  $scalar =~ s/,$//;
  my @data = split(/,/, $scalar);
  for (my $i = 0; $i <= $#data; $i++) {
    $data[$i] =~ s/\+/ /g;
    $data[$i] =~ s/%([0-9a-fA-F]{2})/chr(hex($1))/ge;
  }
  return @data;
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

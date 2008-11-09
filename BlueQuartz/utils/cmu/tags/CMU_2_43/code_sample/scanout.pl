#!/usr/bin/perl -w 
# $Id: scanout.pl 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http://www.cobalt.com
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

# If you are not toor, go away :)
die "You must run this script as root\n" if ($< != 0);

require Getopt::Std;
my $opts = {};
Getopt::Std::getopts('acd:f:ghn:p', $opts);

# Do this b4 use and require to save time
if(defined($opts->{h})) { printUsage(); }

#use lib "/usr/cmu/perl";
use lib "/home/cpr/perl_modules";
#require [PRODUCT];
#require RaQUtil;
require CmuCfg;
use TreeXml;
require Archive;
use MIME::Base64;

use strict;
my $newConf = CmuCfg::mapOpts('scanout', $opts);

my ($confXml, $glbConf, $appConf);
if($newConf->{readConfig} eq 't') {
	$appConf = CmuCfg::getConf();
	$glbConf = CmuCfg::getGlbConf($appConf);
} else {
	while (my $line = <STDIN>) { $confXml .= $line; }
	$glbConf = TreeXml::readXmlStream($confXml, 0);
}
$glbConf = CmuCfg::mergeOpts($glbConf, $newConf);
	
use Data::Dumper;
warn Dumper($glbConf);


my @encodedFields = (
	'fullname', 
	'altname',
	'vacationmsg',
); 

# data structure to build
my $tree;

$tree->{migrate}->{exportPlatform} = '[PRODUCT]';
$tree->{migrate}->{adjustPlatform} = '[PRODUCT]';
$tree->{migrate}->{cmuVersion} = $VERSION;


if (defined($glbConf->{outFile})) {
    TreeXml::writeXml($tree, $glbConf->{outFile});
} else {
	# this is needed for open3
	close(STDERR);
    print TreeXml::writeXml($tree);
}

exit 0;

sub printUsage
# Print the help message
# Side Effect: exists the program in an error state
{
    print <<EOF;
usage:   $0 [OPTIONS] 
         -a Export the user admin files
         -c export configuration only
         -d build directory, this is where export will place all exported files, the default is /home/cmu/FQDN
         -f The file where the output xml is placed
         -g read the config info from /etc/cmu/, you must use this option if
calling this script directly
         -p Do not export user passwords
         -h help, this help text
EOF

    exit 1;
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

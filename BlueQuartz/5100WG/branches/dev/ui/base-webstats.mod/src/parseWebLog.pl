#!/usr/bin/perl -w -I/usr/sausalito/perl

# Author: Phil Ploquin
# Copyright 2000, Cobalt Networks.  All rights reserved.

use strict;
use WebLogParser;

if ($#ARGV < 0)
{
  print STDERR "Usage : $0 <log file>\n";
  exit 0;
}

my $logFileName = $ARGV[0];

# read in the prior state
print "Reading in last values...\n";
WebLogParser::readLastValues;

# copy the log file to be parsed and reset it
print "Preparing log file...\n";
system ("cp $logFileName $logFileName.tmp");
system ("cat /dev/null > $logFileName");

# go ahead and parse the new data
print "Parsing new log file...\n";
WebLogParser::readNewLog("$logFileName.tmp");
unlink("$logFileName.tmp");

# now dump out the meta files
print "Dumping out values...\n";
WebLogParser::dumpMetaFiles;

# sort the files
print "Sorting files...\n";
WebLogParser::sortFiles;

# resolve the IP's
print "Resolving IP's...\n";
WebLogParser::resolveIPs;

print "done.\n";
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

#!/bin/perl

#(Message inbox:15)
#Return-Path: Majordomo-Users-Owner@greatcircle.com
#Message-Id: <m0oXmfl-0002wDC@hock.bolis.sf-bay.org>
#From: Alan Millar <amillar@bolis.sf-bay.org>
#Subject: Perl prog to create list archives
#To: majordomo-users@greatcircle.com
#Date: Wed, 1 Sep 1993 00:32:03 -0800 (PDT)
#Cc: brent@greatcircle.com
#Reply-To: Alan Millar <AMillar@bolis.sf-bay.org>
#
#
#Hi-
#
#Here is a perl program I wrote to keep mailing list archives.  
#It is designed to produce list archive files similar to Revised
#Listserv.  Each message is separated by a line of "==="s and
#most of the header "noise" is gone.  Instead of being stored
#in one big file, they are split into one file per month with
#the name logYYMM where YY and MM are the numeric year and
#month.
#
#I call it from /usr/lib/aliases using:
#
#  listname-archive:	"|/usr/local/mail/majordomo/wrapper archive.pl \
#		/usr/local/mail/lists/listname.archive"
#
#Where the last parameter is the directory name to put the
#log files into.
#
#Give it a try and let me know what you think.
#
#- Alan
#
#----                                                            ,,,, 
#Alan Millar            amillar@bolis.SF-Bay.org              __oo  \  
#System Administrator                                           =___/
#The skill of accurate perception is called cynicism by those who don't 
#possess it.
#----


# archive.pl
# Mailing list archiver.  Specify the directory (not the file)
#  on the command line.  Messages are written to a file 
#  called 'logYYMM' in that directory, where YY is the two digit 
#  year and MM is the two-digit month.

# Written by Alan Millar August 25 1993.  

# All these should be in the standard PERL library
unshift(@INC, $homedir);
require "majordomo.pl";		# all sorts of general-purpose Majordomo subs
require "shlock.pl";		# NNTP-style file locking

# The headers we want to keep, in order:
@keepHeaders = 
  ( "To", "cc"
  , "from", "reply-to", "organization"
  , "date", "subject"
  , "summary", "keywords"
  , "Content-Type"
  );

#-----------------------------------
# Set up output file.  See if directory is specified on command line.
$outputDir = $ARGV[0];
if (! -d $outputDir) {
    $outputDir = "/tmp"; 
}
$outputDir =~ s/\/$//; # drop trailing slash

#------------------------------------
# Get date for log file name
($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);

# log file name is form "logYYMM"
$logFile = sprintf("$outputDir/log%2.2d%2.2d",$year,$mon + 1);

# open output file
&lopen(OUTPUT,">>",$logFile);

# Parse the mail header of the message, so we can figure out who to reply to
&ParseMailHeader(STDIN, *hdrs);

# Print the headers we want

print OUTPUT "========================================";
print OUTPUT "======================================\n";

foreach $key (@keepHeaders) {
  $key =~ tr[A-Z][a-z];
  if (defined($hdrs{$key})) {
    $newKey = $key; substr($newKey,0,1) =~ tr/a-z/A-Z/;
    printf OUTPUT "%-15s%s\n", "$newKey: ", $hdrs{$key};
  } # if non-blank
} # foreach

print OUTPUT "\n";

# copy the rest of the message

while (<STDIN>) {
    print OUTPUT $_;
}
print OUTPUT "\n";

&lclose(OUTPUT);
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

#!/bin/perl
#
# Given an archive directory, create a table of contents file and a topics
# file.  The table of contents file simply lists each subject that appears
# in each archive file, while the topics file is a list of each unique
# subject and the files that subject appears in.
#
# I run this from cron every night....
#
# Paul Close, April 1994
#

if ($#ARGV != -1) {
    $dir = $ARGV[0];
    shift;
}
else {
    die "usage: $0 archive_directory\n";
}

opendir(FILES, $dir) || die "Can't open directory $dir: $!\n";
@files = readdir(FILES);	# get all files in archive directory
closedir(FILES);

open(INDEX,">$dir/CONTENTS") || die "Can't open $dir/CONTENTS: $!\n";
open(TOPICS,">$dir/TOPICS") || die "Can't open $dir/TOPICS: $!\n";

foreach $basename (@files) {
    next if $basename eq '.';
    next if $basename eq '..';
    next if $basename eq "CONTENTS";
    next if $basename eq "TOPICS";
    print INDEX "\n$basename:\n";
    open(FILE, "$dir/$basename") || next;
    while (<FILE>) {
	if (/^Subject:\s+(.*)/i) {
	    ($subj = $1) =~ s/\s*$//;
	    next if $subj eq "";
	    #
	    # for index file, just print the subject
	    #
	    print INDEX "    $subj\n";
	    #
	    # for topics file, strip Re:'s, remove digest postings,
	    # and trim the length to 40 chars for pretty-printing.
	    #
	    1 while ($subj =~ s/^Re(\[\d+\]|2?):\s*//i);  # trim all Re:'s
	    next if $subj eq "";
	    next if $subj =~ /[A-Za-z]+ Digest, Volume \d+,/i;
	    next if $subj =~ /[A-Za-z]+ Digest V\d+ #\d+/i;
	    if (length($subj) > 40) {
		$subj = substr($subj, 0, 37) . "...";
	    }
	    #
	    # Make a key that's all lower case, and no whitespace to
	    # reduce duplicate topics that differ only by those.  This
	    # also results in a list of topics sorted case-independent.
	    #
	    ($key = $subj) =~ tr/A-Z/a-z/;
	    $key =~ s/\s+//g;
	    $subjlist{$key} .= "$basename,";
	    if (!defined($realsubj{$key})) {
		$realsubj{$key} = $subj;
	    }
	}
    }
    close(FILE);
}
close(INDEX);

foreach $subj (sort keys %subjlist) {
    #
    # for each subject, record each file it was found in
    #
    undef %found;
    undef @names;
    for (split(",", $subjlist{$subj})) {
	$found{$_} = 1;
    }
    #
    # make list of 'found' names and wrap at 80 columns
    #
    $names = join(", ", sort keys %found);
    undef @namelist;
    while (length($names) > 40) {
	$index = 40;
	$index-- until (substr($names, $index, 1) eq " " || $index < 0);
	push(@namelist,substr($names,0,$index));
	$names = substr($names,$index+1);
    }
    push(@namelist,$names);
    printf TOPICS "%-40s %s\n", $realsubj{$subj}, $namelist[0];
    for ($i=1; $i <= $#namelist; $i++) {
	print TOPICS " " x 41, $namelist[$i], "\n";
    }
}
close(TOPICS);

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

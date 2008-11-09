#!/usr/bin/perl  -w -I/usr/sausalito/perl -I.
# Tim Hockin <thockin@cobalt.com>
# Strip a matching XML record from an XML file (for cbackup)

use strict;
use XML::Parser;
use Backup;

my $backup_tag = shift || exit(42);
my $backup_time = shift || exit(43);
my $histfile = shift || "/home/users/admin/.cbackup/history";
my $tmpfile = "$histfile.$$";
my $found = 0;

# nuke and create the tempfile
umask(027);
unlink($tmpfile);
open (TMPFILE, ">$tmpfile") || die "Can't to open temp file: $!\n";

# do the file
rm_entry($histfile, $backup_tag, $backup_time);

close TMPFILE;

# cleanup and move tmpfile into place
unlink("$histfile.bak");
rename($histfile, "$histfile.bak");
rename($tmpfile, $histfile);
system("chgrp httpd $histfile");
chmod(0640, $histfile);

my ($srchname, $srchtime, $found_srchname, $found_srchtime);
my $cur_tag;
my $hunting;
my $record;

# remove a given entry from the given file
sub rm_entry
{
	my ($file);

	# save the tag/time that we are looking for globally
	$file = shift || "/tmp/foo";
	$srchname = shift || "bar";
	$srchtime = shift || 0;

	$record = "";

	my $parser= new XML::Parser(Style => 'Stream');
	if ($file) { 
		$parser->parsefile($file);
		print TMPFILE "\n";
	} else { 
		die "Unable to get filename: $!\n"  
	}
}

# --------------------------
#  XML Parsing Routines
# --------------------------

# called by the parser for all start tags
sub StartTag
{   
	my($p, $t, %att) = @_;

	$cur_tag = $t;

	# we're only interested in backup tags
	if ($found || ($t ne "backup" && !$hunting)) {
		print TMPFILE $p->recognized_string();
		return;
	}

	$hunting = $found ? 0 : 1;

	# clean slate
	if ($t eq "backup") {
		$found_srchname = 0;
		$found_srchtime = 0;
	}

	# build up a record string to print or discard later
	$record .= $p->recognized_string();

	return;
}

# called by the parser for each string
sub Text
{
	my $p = shift;
	my $text = $_;

	if ($hunting) {
		# if it is a "backup_tag" or "start_time" tag, flag it
		if (($cur_tag eq "name") && ($text eq $srchname)) {
			$found_srchname = 1;
 		}
		if (($cur_tag eq "start_time") && ($text eq $srchtime)) {
			$found_srchtime = 1;
 		}

		$record .= "$text";
	} else {
		print TMPFILE "$text";
	}

	return;
}

# called by the parser for all end tags
sub EndTag
{
	my ($p, $t, %att) = @_;

	if ($hunting && $t eq "backup") {
		$hunting = 0;
		if (($found_srchname == 0) || ($found_srchtime == 0)) {
			print TMPFILE $record, $p->recognized_string();
		} else {
			# dump it if we haven't found it
			$found = 1;
		}
		$record = "";
	} else {
		if ($hunting) {
			$record .= $p->recognized_string();
		} else {
			print TMPFILE $p->recognized_string();
		}
	}

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

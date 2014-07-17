#!/usr/bin/perl
# $Id: po_automerge.pl 3 2003-07-17 15:19:15Z will $
#
# Scans the "new" directory tree for locale/<locale>/*.po files
# and finds their counterparts in the "original" tree.  Messages
# found in the original but not the new will automatically be 
# appended to the new branch.  
#
# .po integrity tests are run before and after the auto-merge
# 

my $locale = $ARGV[0];
my($new, $original) = ($ARGV[2], $ARGV[1]);

if (($locale eq '') || ($new eq '') || ($original eq '')) {
	&usage();
	exit(1);
} elsif ((! -d $new) || (! -d $original)) {
	&usage();
	exit(1);
}

open(FIND, "find $new -name \"\*.po\" |") || warn "No find: $!";
while(my $file = <FIND>) {
	chomp($file);
	next unless ($file =~ /locale\/$locale\//);
	print STDERR "Processing $file...\n";
	my $fn = $file;
	$fn =~ s/^.+\/([^\/]+)$/$1/;
		
	warn "WARNING Bad Po format for $file" unless (&potest($file));

	my $cp = `find $original -name \"$fn\" | grep 'locale/$locale/'`;
	chomp($cp);

	if ($cp eq '') {
		print STDERR "No original found for $file\n";
	}

	my %orig;
	my @file_order = ();
	open(ORIG, $cp) || warn "Could not open po $cp: $!";
	while(my $line = <ORIG>) {
		if (($line =~ /^\s*\#/) || ($line =~ /^\s*$/)) {
			push @file_order, $line;
			next;
		} elsif ($line =~ /msgid\s*\"([^\"]+)\"/) {
			my $key = $1;
			push @file_order, $key;
			$orig{$key} = '';
			my $subsearch = '';
			while($subsearch = <ORIG>) {
				if ($subsearch !~ /^\s*msgstr\s*/) {
					next;
				} else {
					last;
				}
			}
			# check for a line continuation
			while ($subsearch !~ /"\s*$/s) {
				my $next_part = <ORIG>;
				$subsearch .= $next_part;
			}
			$subsearch =~ /msgstr\s*"(.+)"\s*$/s;
			$orig{$key} = $1;
		}
	}
	close(ORIG);

	my %gnu;
	open(NEW, $file) || warn "Could not open po $file: $!";
	while(my $line = <NEW>) {
		if($line =~ /msgid\s*\"([^\"]+)\"\s*$/) {
			my $key = $1;
			my $subsearch = '';
			while($subsearch = <NEW>) {
				if ($subsearch !~ /^\s*msgstr\s*/) {
					next;
				} else {
					last;
				}
			}
			# check for a line continuation
			while ($subsearch !~ /"\s*$/s) {
				my $next_part = <NEW>;
				$subsearch .= $next_part;
			}
			$subsearch =~ /msgstr\s*"(.+)"\s*$/s;
			# watch for duplicates
			my $msgstr = $1;
			if (exists($gnu{$key}) && ($msgstr ne $gnu{$key})) {
				print "Duplicate msgid found for $key:\n";
				print "First value:  $gnu{$key}\n";
				print "Second value: $msgstr\n";
				print "Keep current value (Y/n)? ";
				my $reply = <STDIN>;
				if ($reply =~ /^n/i) {
					$gnu{$key} = $msgstr;
				}
			} else {
				# first time seeing this id
				$gnu{$key} = $msgstr;
			}
		}
	}
	close(NEW);

	# check if this is a new po file to put in place
	if ($cp eq '') {
		$cp = $file;
		$cp =~ s/^$new/$original/;
		my $directory = $cp;
		$directory =~ s/\/[^\/]+$//;
		if (! -d $directory) {
			system('/bin/mkdir', '-p', $directory);
		}
	}
	
	# output the new po files with changes from new file
	open(OLDFILENAME, ">$cp") or warn($!);
	my %seen = ();
	for my $line (@file_order) {
		if (($line =~ /^\s*#/) || ($line =~ /^\s*$/)) {
			print OLDFILENAME $line;
		} elsif (!$seen{$line}) {
			# print out id if this is the first time seeing it
			# FIXME: output differences
			print OLDFILENAME "msgid \"$line\"\n";
			print OLDFILENAME "msgstr \"" . $gnu{$line} . "\"\n";
			
			$seen{$line} = 1;
			delete($orig{$line});
			delete($gnu{$line});
		}
	}

	# see if there is anything left in %gnu
	for my $key (keys(%gnu)) {
		print OLDFILENAME "msgid \"$key\"\n";
		print OLDFILENAME "msgstr \"" . $gnu{$key} . "\"\n\n";
		delete($gnu{$key});
	}

	close(OLDFILENAME);
	if (scalar(keys(%orig)) != 0) {
		print STDERR "WARNING!!!!  The following keys are left in orig.\n";
		print STDERR join("\n", keys(%orig)), "\n";
	}

	warn("WARNING Broken .po $cp") unless (&potest($cp));
}

exit(0);

sub usage
{
	print STDERR <<USAGE;
Usage: po_automerge.pl <locale> <old directory> <new directory>

<locale>	locale to merge (ie en, ja, de, etc.)
<old directory>	directory containing old po files
<new directory>	directory containing new po files
USAGE
}

sub potest
{
	my $msgfmt = '/usr/bin/msgfmt';
	my $po = shift;
	my $stage = "/tmp/po$$";
	unlink($stage);
	my $ret = system($msgfmt, '-e', '-o', $stage, $po);
	return 0 if ($ret);
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

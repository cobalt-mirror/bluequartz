#!/usr/bin/perl -I /usr/sausalito/perl
# $Id: multi-volume.pl 201 2003-07-18 19:11:07Z will $
#
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#
use strict;
use DB_File;
use File::Basename;
use Getopt::Long;
use Backup;
use vars qw/ $opt_location $opt_ftpconfig $opt_oldarchive $opt_newarchive $opt_create $opt_extract /;

#
# Overview:
#
# multi-volume.pl is a script called by tar that handles
# the creation and extraction of multi-volume archives.
# This scripts is called with --info-script when tar is
# called with -M
#
# Multi-volume tar archives usually span tapes, not file.
# So when the end of one volume is hit, tar prompts for
# the user to enter the next volume.  Since we are using
# files, we need to account for the tar archives having
# the same name to successfully restore files that traverse
# multiple archives.  This is where this script comes in.
#
# Details:
#
# Create: Using this script with creation is fairly straight 
# forward and easy to use.  It will take the name of the archive
# (archive.tar) and move the name to another archive for
# logical sequential naming (archive{nnn}.tar).  The naming
# takes the base-name and adds numerical tags on the end
# starting with 000 and goes up to 999.  So when an archive
# hits the "tape-size" (file limit size), it spins it off
# to the next available archive name archive000.tar, archive001.tar,
# archive{nnn}.tar.
#
# Extract: Using the script with extraction is a little more
# complex.  When restoring an archive, we must first move the
# first one back to its base-name (archive000.tar to archive.tar),
# then begin the extraction.  We can still specify this script
# with --info-script, but now we need to keep track of which
# file have been unarchived.  Before this script is executed,
# the first archive.tar must be in place.  We can make the assumption
# that archive.tar is archive000.tar and then generate a hash
# table in /tmp to keep track of which archives have been extracted.
# This script doesn't stay in memory, we must use external
# files to keep track to processed archives.

# Globals
my $arch;       # Curret archive in need or renaming
my $base;       # Basename of new archive
my $hashtbl;	# Hash table to keep track of used archives
my $newarch;	# Holds the name of the renamed archive
my $ftp;


GetOptions(
    'ftpconfig:s',
    'location:s',
    'oldarchive:s',
    'newarchive:s',
    'create!',
    'extract!'
);

# Get command line args
if ((! defined($opt_oldarchive))) { usage() }
if ((! defined($opt_newarchive))) { usage() }
if ((defined($opt_create)) && (defined($opt_extract))) { usage() }
$arch    = $opt_oldarchive;
$base    = $opt_newarchive;
$hashtbl = '/var/tmp/.bkup';

# Call the right routines..
if ($opt_create) { 
	if ( ! -e $arch ) { die "Error: no such file: $arch\n" }
	archive($base,$newarch,$arch, $opt_ftpconfig, $opt_location); 
} elsif ( $opt_extract ) { 
	extract($arch,$base); 
} else {
	usage();
}

exit 0;

# Subroutines
sub archive 
# Subroutine:    archive()
# Arguments:     base archive name
# Return:        Success or Failure
# External Cals: None
# Modifies:      Nothing
{
	my ($bname, $nname, $rname, $ftpconfig, $location)  = @_;
	my $number;
	my $count = 0;

	# Get next volume
	my ($filename, $path, @suffixlist) = fileparse($bname);

	#$DEBUG && warn "nname=$nname\n";
	#$DEBUG && warn "bname=$bname\n";
	#$DEBUG && warn "path=$path\n";
	#$DEBUG && warn "filename=$filename\n";

	my $countlog = $path . '/.' . $filename . '.count';
	if (-f $countlog) {
		open COUNT, "$countlog";
		$count = -1;
		while (<COUNT>) {
			if (/([0-9]{3})/) {
				$count = $1;
				$count++;
			}
		}
		close(COUNT);
	}	

	if ($count >= 0) {
		open COUNT, "> $countlog" or die "can't store count in $countlog.\n";
		printf COUNT ("%03d\n", $count);
		close(COUNT);
	}

	$nname = sprintf ("%s%03d.%s", $bname, $count, 'tar');
	rename ($rname, $nname);

	if ($ftpconfig && $location) {
		# Send the old archive to the server via ftp
		my $bu = Backup->new( 
			fsdir		=> $location,
			ftp		=> 1,
			ncftpconfig	=> $ftpconfig,
		);

		# Send will remove the file if successful
		my $ret = $bu->ftp_send($nname);
	}

	return;
}

sub extract
# Subroutine:     extract()
# Arguments:      current archive
# Return:         Success or Failure
# External Calls: create_hash() update_hash()
# Modifies:       Nothing
{
	my ($arc, $bname) = @_;
	my ($next, $prev);

	create_hash($arc,$bname);

	# determine next volume
	$next = getnext_hash($bname);

	# move current archive back
	$next =~ /(\d+)\./;
	$prev = $1 - 1;
	$prev = sprintf("%03d", $prev);
	$prev = $base . $prev . ".tar";
	print "Previous: $prev Next: $next\n";
	rename($arc,$prev);
	
	# rename next archive to base.tar
	rename($next,$arc);

	# update database
	update_hash($next,1);

	return;
}

sub create_hash
# Subroutine:     create_hash()
# Arguments:      hash location
# Return:         Success or Failure
# External Calls: None
# Modifies:       $hashtbl
{
	my ($arc,$bname) = @_;
	my (%hash, $number, $delete);
	my $count = 0;
	my $tmp = $bname . "000.tar";
	my $test = $bname . "001.tar";

	if ( -e $hashtbl ) { 
		# Take measures to assure we are not overwriting
		# a Database in use by the current restore
		dbmopen %hash, $hashtbl, 0666
			or die "Error: Can't open $hashtbl: $!\n";

		# If the current archive set is unknown delete hash.
		$delete = 1 if ( ! exists $hash{$tmp} );
		
		# If the second archive has been processed, return.
		# WATCH FOR RACE CONDITION!!!
		return if ( $hash{$test} == 1 );

		dbmclose %hash;

		unlink $hashtbl if $delete;
	}

	system("/bin/touch $hashtbl");
	chmod 0700, $hashtbl;

	# Assume 000.tar is done, it is the current archive
	dbmopen %hash, $hashtbl, 0666
		or die "Error: Can't open $hashtbl: $!\n";
	$hash{$tmp} = 1;

	# Create entries for others
	for ($count..999) {
		$number = sprintf("%03d", $count);
		$tmp = $bname . $number . ".tar";
		if ( -e $tmp ) {
			$hash{$tmp} = 0;
		}
		$count++;
	}

	dbmclose %hash;

	return;
}

sub getnext_hash
# Subroutine:     getnext_hash()
# Arguments:      basename
# Return:         next volume to rename
# External Calls: None
# Modifies:       Nothing
{
	my $bname = shift;
	my (%hash,$tmp,$number);
	my ($cur, $orig);
	my $count = 0;

	dbmopen %hash, $hashtbl, 0666
		or die "Error: Can't open $hashtbl: $!\n";

	for ($count..999) {
		$number = sprintf("%03d", $count);
		$tmp = $bname . $number . ".tar";
		if ( ! exists $hash{$tmp} ) {
			# No more left, rename last archive
			$cur = $bname . ".tar";
			$orig = $bname . sprintf("%03d",($number - 1)) . ".tar";

			if ( -e $cur ) {
				rename($cur,$orig);
			}

			exit 0;
		}
		if ( $hash{$tmp} == 0 ) {
			dbmclose %hash;
			return $tmp;
		}
		$count++;
	}

	return;
}

sub update_hash
# Subroutine:     update_hash()
# Arguments:      archive name, state
# Return:         Success or Failure
# External Calls: None
# Modifies:       Nothing
{
	my ($bkup, $state) = @_;
	my %hash;

        dbmopen %hash, $hashtbl, 0666
                or die "Error: Can't open $hashtbl: $!\n";

	$hash{$bkup} = $state;

	dbmclose %hash;

	return;
}

sub usage
# Subroutine:     usage()
# Arguments:      None
# Return:         Nothing
# External Calls: None
# Modifies:       Nothing
{

print STDERR <<"USAGE";

multi-volume.pl - Cobalt Backup Multi-Volume Archive Program

    Arguments:
        -a: Orignal Archive Name (required)
        -b: New Archive Name     (required)
        -c: Create (use this during backup)
        -x: Extract (use this during restore)

USAGE

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

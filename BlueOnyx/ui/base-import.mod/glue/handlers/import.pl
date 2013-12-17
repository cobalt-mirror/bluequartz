#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: import.pl 
# Copyright 2000-2002 Sun Microsystems, Inc., All rights reserved.

#
# This script can be used to import objects from a file.  The fileformat is
# as follows:
#
# User	name	fullName	Email.aliases
# ADD	user1	User One	&one&
# ADD	user2	User Two	&two&too&

use CCE;
use I18n;
use FileHandle;
use Data::Dumper;
use IO::File;
use POSIX qw(tmpnam setsid close getpwnam chown chmod);
use vars qw(@errors $processed $tmpfh);

# create the CCE connection
my $cce = new CCE();
$cce->connectuds();

$cce->authkey($ENV{'CCE_USERNAME'}, $ENV{'CCE_SESSIONID'});
# get the filehandle
my $fh;

my $fileName;
my $tmpfh;

my $incomingFile;
my $DEBUG = 0;
if ($ARGV[0] eq "-c") {
	$incomingFile = $ARGV[1];
	$DEBUG = 1;
	print STDERR "Using console mode.\n";
} else {
	$incomingFile = $ARGV[0];
} 
if ($incomingFile) {
	if (!$DEBUG) {
		$fileName = tmpnam();
		$fileName =~ /^.*\/(\S*)$/;
		print "$1\n";
		my $pid;
		$cce->bye("SUCCESS");
		defined($pid = fork) || die "Can't Fork!: $!";
		if ($pid) {exit(0)};
		POSIX::setsid() || die "Can't start a new session: $!";
		# close all other apache related file descriptors..
		opendir FDDIR, "/proc/$$/fd";
		my @allDescriptors = grep !/^\./, readdir FDDIR;
		foreach my $desc (@allDescriptors) {
			POSIX::close($desc) || die "HELP ME";
		}
		open(\*STDIN, "</dev/null");
		open(\*STDOUT, ">/dev/null");
		open(\*STDERR, ">/dev/null");
		$tmpfh = IO::File->new($fileName, O_RDWR|O_CREAT|O_EXCL);
		# turn on autoflush for the log file or the ui doesn't
		# get updated until the very end
		$tmpfh->autoflush(1);
		chmod 0600, $fileName;
	} else {
		# debug run so make $tmpfh point at STDERR;
		$tmpfh = *STDERR;
	}
} else {
	print "Usage: import.pl [-c] filename\n";
	print "\t-c:\trun in console mode\n";
	print "\tfilename:\tfile to import\n";
	$cce->bye("SUCCESS");
	exit;
}

$cce = new CCE;
$cce->connectuds();
$cce->authkey($ENV{'CCE_USERNAME'}, $ENV{'CCE_SESSIONID'});
$fh = new FileHandle(); 
open($fh, "< " . $incomingFile);
$DEBUG || unlink $incomingFile;
$DEBUG && print STDERR "Opened $incomingFile\n";

$processed = "0";

# create the I18n object used for internationlization
my $i18n = new I18n();
if($ARGV[1] =~ /^locale=(.+)/) {
	$i18n->setLocale( $1 );
} else {
	$i18n->setLocale(&_get_locale($cce));
}

# parse
my $class = "";
my @attrs = ();
my $state = "";
my @entries = ();
$DEBUG && print STDERR "About to start looping.\n";
while (<$fh>) {
	chomp;
	@entries = split(/\t/);
	$state = shift(@entries);
	if ($state eq "BLANK")  {
		$processed++;
		 next;
	}
	if ($state ne "ADD") {
		$class = $state;
		@attrs = @entries
	} else {
		my $i = 0;
		my $ns_settings = {};
		#
		# Split data up into hashes of data to set.
		# per namespace and the empty keyval is
		# the base namespace for create. 
		#
		while ($attrs[$i]) {
			my ($ns, $nsattr);
			my $attr = $attrs[$i];
			my $val = shift(@entries);
			if ($attr =~ /\./) {
				($ns, $nsattr) = split(/\./, $attr);
				if ((ref $ns_settings->{$ns}) ne 'HASH') {
					$ns_settings->{$ns} = {};
				}
				$ns_settings->{$ns}->{$nsattr} = 
					$i18n->encodeString($val);
			} else {
				$ns_settings->{""}->{$attr} =
					$i18n->encodeString($val);
			}
			$i++;
		}
		$DEBUG && print STDERR "About to handle_add a $class with " .
				       Dumper($ns_settings) . "\n";
		handle_add($class, $ns_settings, $cce, $i18n);
		rewrite_logfile();
	}
}

$DEBUG && print STDERR Dumper(\@errors);
$DEBUG && print STDERR "Done\n";
$cce->bye("SUCCESS");
exit(0);

sub rewrite_logfile
{
	if (!$DEBUG) {
		# can't seek on STDERR
		seek ($tmpfh, 0, 0);
	}

	print $tmpfh "$processed\n";
	print $tmpfh scalar(@errors) . "\n";

	for my $error (@errors) {	
		while (my ($key, $val) = each(%{$error->{table}})) {
			while (($key2, $val2) = each(%$val)) {
				if ($key2 =~ /^password/) {
					$val2 = "***";
				}
				print $tmpfh "$key2=$val2\n";
			}
		}
		print $tmpfh "--\n";
		for my $errorMsg (@{$error->{msgs}}) {
			print $tmpfh $errorMsg . "\n";
		}
		print $tmpfh "--\n";
	}
}

sub handle_add
{
	my ($class, $ns_settings, $cce, $i18n) = @_;
	
	# do the create
	my %orig_settings = %$ns_settings;	
	my ($ok, $badkeys, @info) = $cce->create($class, $ns_settings->{""});
	if ($ok) {
		delete $ns_settings->{""};
		my $oid = $cce->oid();
		while (my ($key, $value) = each(%$ns_settings)) {
			if ($DEBUG) {
				print STDERR "the value is " . Dumper($value) .
					     "\n";
			}
			($ok, $badkeys, @info) = $cce->set($oid, $key, $value);
			delete $ns_settings->{$key};
			if (!$ok) {
				$cce->destroy($oid);
				&create_error($i18n, \%orig_settings, $badkeys,
					      \@info);
				$DEBUG && print STDERR Dumper($badkeys);
				$DEBUG && print STDERR Dumper(\@info);
				last;
			}
		}	
	} else {
		$DEBUG && print STDERR "CREATE FAILED!\n";
		&create_error($i18n, \%orig_settings, $badkeys, \@info);
		$DEBUG && print STDERR Dumper($badkeys);
		$DEBUG && print STDERR Dumper(\@info);
	}
	$processed++;
}

sub create_error
{
	my ($i18n, $table, $badkeys, $info, $entry) = @_;
	my $error = {};
	my ($tkey, $tvalue) = each %$badkeys;
	my $newTable = {};
	$badkeys = $tvalue;
	
	my ($key, $value);

	while (($key, $value) = each(%$table)) {
		while (($tkey, $tvalue) = each(%$value)) {
			$newTable->{$tkey} = $tvalue;
		}
	}
			
	$error->{table} = $table;
	$error->{msgs} = ();

	$newTable->{line} = $processed + 1;
	while (($key, $value) = each(%$badkeys)) {
		#
		# we must loop throught the table to find the proper oid
		# and all that
		#
		$newTable->{entryDetails} = getEntryMessage($newTable);
		
		$newTable->{key} = $key;

		if ($key =~ /^name$/) {
			$newTable->{key} = "[[base-import.intname]]"; 
		}
		if ($key =~ /^fullName$/) {
			$newTable->{key} = "[[base-import.intfullName]]";
		}
		if ($key =~ /^password$/) {
			$newTable->{key} = "[[base-import.intpassword]]";
		}
		if ($key =~ /^aliases$/) {
			$newTable->{key} = "[[base-import.intaliases]]";
		}
		
		if ($value =~ /\"(.*)\"/) {
			$value = $1;
		}
		$newTable->{error} = $value;

		push(@{$error->{msgs}},
		     $i18n->get("[[base-import.errorMessage]]", \%$newTable));
	}

	# add the contents of info to the errors
	if (!ref($info) && $info) {
		if ($info =~ /(\[\[(.*)\]\])/) {
			$newTable->{error} = $1;
			
			# clean up \", because i18n->get reescapes them
			$newTable->{error} =~ s/\\"/"/g;

			push(@{$error->{msgs}},
			     $i18n->get("[[base-import.errorMessage]]",
			                \%$newTable));
		}
	} elsif (ref($info) eq 'ARRAY') {
		foreach my $infoMsg (@$info) {
			if ($infoMsg =~ /(\[\[(.*)\]\])/) {
				$newTable->{error} = $1;

				# clean up \", because i18n->get reescapes them
				$newTable->{error} =~ s/\\"/"/g;

				push(@{$error->{msgs}},
				     $i18n->get("[[base-import.errorMessage]]",
				     		\%$newTable));
			}
		}
	}
	push(@errors, $error);
}

sub getEntryMessage
{
	my $entry = shift;
	my $msg;	

	while (my ($key, $value) = each(%$entry)) {
		if ($key =~ /^password/) {
			$value = "***";
		}
		$msg && ($msg .= ", ");
		$msg .= $key . "='$value'";
	}	
	$DEBUG && print STDERR "$msg\n";
	return $msg;
}

#
# _get_locale -- Get a locale.  Locale of user or system locale or en.
#
# Arguments: a CCE reference
# Returns:   A locale.
#
sub _get_locale
{
	$cce = shift;
	my $language;

	my ($ok, $oid) = $cce->whoami();
	($ok, my $object) = $cce->get($oid);
	$language = ${$object}{'localePreference'} if ($ok);

	if ($language eq '') {
		die "Failed to get locale\n";
	}

	chomp $language;

	# success
	return $language if (length $language > 0 && $language ne "browser");

	# try system locale
	$language = I18n::i18n_getSystemLocale($cce);
	if (length($language) > 0 && $language ne 'browser') {
		return $language;
	}

	# failure
	return "en";
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

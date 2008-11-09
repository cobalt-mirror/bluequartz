#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: import.pl 3 2003-07-17 15:19:15Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

# Author: Mike Waychison <mwaychison@cobalt.com>
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
use Jcode;

my $locale = I18n::i18n_getSystemLocale();

# create the CCE connection
my $cce = new CCE();
$cce->connectuds();

$cce->authkey($ENV{'CCE_USERNAME'}, $ENV{'CCE_SESSIONID'});
#get the filehandle
my $fh;

my $fileName;
my $tmpfh;

my $incomingFile;
my $DEBUG = 0;
if ($ARGV[0] eq "-c") {
	$incomingFile = $ARGV[1];
	$DEBUG = 1;
	print "Using console mode.\n";
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
$DEBUG && print "Opened $incomingFile\n";

$processed = "0";

# create the I18n object used for internationlization
my $i18n = new I18n();
if($ARGV[1] =~ /^locale=(.+)/) {
	$i18n->setLocale( $1 );
} else {
	$i18n->setLocale( _get_locale($cce));
}
#parse
my $class = "";
my @attrs = ();
my $state = "";
my @entries = ();
$DEBUG && print "About to start looping.\n";
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
		my $i=0;
		my $ns_settings = {};
		# Split data up into hashes of data to set.
		# per namespace and the empty keyval is
		# the base namespace for create. 
		while ( $attrs[$i] ) {
			my $ns; my $nsattr;
			my $attr = $attrs[$i];
			my $val = shift(@entries);
			if ($attr =~ /\./) {
				($ns, $nsattr) = split(/\./, $attr);
				if ((ref $ns_settings->{$ns}) ne 'HASH') {
					$ns_settings->{$ns} = {};
				}
				$ns_settings->{$ns}->{$nsattr} = conv($val);
			} else {
				$ns_settings->{""}->{$attr} = conv($val);
			}
			$i++;
		}
		$DEBUG && print  "About to handle_add a $class with " . Dumper($ns_settings) . "\n";
		handle_add($class, $ns_settings, $cce, $i18n);
		rewrite_logfile();
	}
}

$DEBUG && print Dumper @errors;
$DEBUG && print "Done\n";
$cce->bye("SUCCESS");

sub rewrite_logfile {
	if (!$DEBUG) {
		seek ($tmpfh, 0, 0);
		print $tmpfh ("$processed\n");
		print $tmpfh (scalar @errors . "\n");

		foreach my $error (@errors) {	
			while (($key, $val) = each(%{$error->{table}})) {
				while (($key2, $val2) = each(%$val)) {
					if ($key2 =~ /^password/) {
						$val2 = "***";
					}
					print $tmpfh "$key2=$val2\n";
				}
			}
			print $tmpfh "--\n";
			foreach my $errorMsg (@{$error->{msgs}}) {
				print $tmpfh $errorMsg . "\n";
			}
			print $tmpfh "--\n";
		}
	}
}
sub handle_add {
	my ($class, $ns_settings, $cce, $i18n) = @_;
	#bless("CCE", $cce);
	
	#do the create
	my %orig_settings = %$ns_settings;	
	my ($ok, $badkeys, @info) = $cce->create($class, $ns_settings->{""});
	if ($ok) {
		delete $ns_settings->{""};
		my $oid = $cce->oid();
		while (my ($key, $value) = each(%$ns_settings)) {
			$DEBUG && print "the value is " . Dumper ($value) . "\n";
			($ok, $badkeys, @info) = $cce->set($oid, $key, $value);
			delete $ns_settings->{$key};
			if (!$ok) {
				$cce->destroy($oid);
				create_error ($i18n, \%orig_settings, $badkeys, @info);
				$DEBUG && print Dumper ($badkeys);
				$DEBUG && print Dumper (@info);
				last;
			}
		}	
	} else {
		create_error ($i18n, \%orig_settings, $badkeys, @info);
		$DEBUG && print Dumper ($badkeys);
		$DEBUG && print Dumper (@info);
	}
	$processed++;
}

sub create_error {
	my ($i18n, $table, $badkeys, $info, $entry) = @_;
	my $error = {};
	my ($tkey, $tvalue) = each %$badkeys;
	my $newTable = {};
	$badkeys = $tvalue;
	
	my $key; my $value;

	while (($key, $value) = each(%$table)) {
		while (($tkey, $tvalue) = each(%$value)) {
			$newTable->{$tkey} = $tvalue;
		}
	}
			
	$error->{table} = $table;
	$error->{msgs} = ();

	$newTable->{line} = $processed + 1;
	while (($key, $value) = each(%$badkeys)) {
		# we must loop throught the table to find the proper oid and all that
		$newTable->{entryDetails} = getEntryMessage($newTable);
		
		$newTable->{key} = $key;
		$_ = $key;
		SWITCH: {
			if (/^name$/) {	$newTable->{key} = "[[base-import.intname]]"; last SWITCH;}
			if (/^fullName$/) { $newTable->{key} = "[[base-import.intfullName]]"; last SWITCH;}
			if (/^password$/) { $newTable->{key} = "[[base-import.intpassword]]"; last SWITCH;}
			if (/^aliases$/) { $newTable->{key} = "[[base-import.intaliases]]"; last SWITCH;}
		}
		
		if ($value =~ /\"(.*)\"/) {
			$value = $1;
		}
		$newTable->{error} = $value;
		#print Dumper( %$newTable);
		push(@{$error->{msgs}} ,$i18n->get("[[base-import.errorMessage]]", \%$newTable));
	}
	if (!ref $info && $info) {
		if ($info =~ /(\[\[(.*)\]\])/) 	{
			$newTable->{error} = $1;
			push(@{$error->{msgs}}, $i18n->get("[[base-import.errorMessage]]", \%$newTable));
		}
	}
	foreach my $infoMsg (@$info) {
		if ($infoMsg =~ /(\[\[(.*)\]\])/) {
			$newTable->{error} = $1;
			push(@{$error->{msgs}}, $i18n->get("[[base-import.errorMessage]]", \%$newTable));
		}
	}
	push(@errors, $error);
}

sub getEntryMessage {
	my $entry = shift;
	my $msg;	
	#$entry = $entry->{""};
	while (my ($key, $value) = each(%$entry)) {
		if ($key =~ /^password/) {
			$value = "***";
		}
		$msg && ($msg .= ", ");
		$msg .= $key . "='$value'";
	}	
	print $msg . "\n";
	return $msg;
}
sub _get_locale
# _get_locale -- Get the locale of the admin user
#
# Arguments: None
# Returns:   Locale of admin, undef on failure
{
	$cce = shift;
        my $language;

        my (@oids) = $cce->find("User", { "name" => "admin" } );
        my ($ok, $object) = $cce->get( $oids[0] ) if (@oids > 0);
        $language = ${$object}{'localePreference'} if ($ok);

        if ( ! $language ) {
                die "Failed to get locale\n";
        }

        chomp $language;

        # success
        return $language if (length $language > 0 && $language ne "browser");

        # failure
        return "en";
}

sub conv{
	my $val=shift;

	if($locale eq "ja"){
		return Jcode->new($val)->sjis;
	}else{
		return $val;
	}
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

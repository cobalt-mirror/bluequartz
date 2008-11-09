#!/usr/bin/perl -I/usr/sausalito/perl

use strict;
use FileHandle;
use Sauce::Util;

my ($command, @args) = @ARGV;
# grab the filename
my $filename = (getpwuid($<))[7] . "/addressBook";
$_ = $command;
SWITCH: {
	/^add$/ && do {
		# add a record 
		if (!Sauce::Util::editfile($filename, \&addRecord, @args)) {
			exit 0;
		}
		last SWITCH;
	};
	/^drop$/ && do {
		# drop a record
		if (!Sauce::Util::editfile($filename, \&dropRecord, @args)) {
			exit 0;
		}
		last SWITCH;
	};
	/^modify$/ && do {
		# modify a record
		if (!Sauce::Util::editfile($filename, \&modifyRecord, @args)) {
			exit 0;
		}
		last SWITCH;
	};
	/^getall$/ && do {
		# print out ALL entries
		my $fh = new FileHandle;
		$fh->open("<$filename");
		if ($fh) {
			while (<$fh>) {
				print STDOUT $_;
			}
			$fh->close();
		} else {
			exit 0;
		}
		last SWITCH;
	};				
	&usage();
}
exit 1;
sub usage {
	print "Usage: addressbook add|remove|modify ...\n";
	print "  getall: Get a listing of all records\n";
	print "  add: Add a new addressbook record\n";
	print "    Fullname,\n    Email Address\n    Phone Number\n    Fax Number\n    Homepage Address\n    Other Address\n    Remarks\n";
	print "  drop: Drop a record\n";
	print "    ID\n";
	print "  modify: modify a record\n";
	print "    ID,\n    Fullname,\n    Email Address\n    Phone Number\n    Fax Number\n    Homepage Address\n    Other Address\n    Remarks\n";
}


sub escape {
	my $string = shift;
	$string =~ s/\t/\\\t/g;
	return $string;
}

sub unescape {
	my $string = shift;
	$string =~ s/\\\t/\t/g;
	return $string;
}

sub dropRecord {
	my ($fin, $fout, $oid, @args) = @_;
	while (<$fin>) {
		/^(\d*)/;
		if (!($1 eq $oid)) {
			print $fout $_;		
		}
	}
	return 1;
}

sub modifyRecord {
	my ($fin, $fout, @args) = @_;
	foreach $_ (@args) {
		&escape(\$_);
	}
	my ($oid, $fullname, $email, $phone, $fax, $homepage, $addresses, $remarks) = @args; 
	while (<$fin>) {
		/^(\d*)/;
		if ($1 eq $oid) {
			print $fout "$oid\t$fullname\t$email\t$phone\t$fax\t$homepage\t$addresses\t$remarks\n";
		} else {
			print $fout $_;
		}
	}
	return 1;
}
		
sub addRecord {
	my ($fin, $fout, @args) = @_;
	foreach $_ (@args) {
		&escape(\$_);
	}
	my ($fullname, $email, $phone, $fax, $homepage, $addresses, $remarks) = @args;
	my @ar;
	#grab current IDs
	while (<$fin>) {
		/^(\d*)/;
		@ar->[$1] = 1;	
		print $fout $_;
	}
	#find a new entry number
	my $i;
	for ($i=0;@ar->[$i];$i++) {};
	#append the next entry
	print $fout "$i\t$fullname\t$email\t$phone\t$fax\t$homepage\t$addresses\t$remarks\n";
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

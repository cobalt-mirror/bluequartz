#!/usr/bin/perl

use File::Copy;

my $DEBUG = 1;

my $fargo;
$fargo = '/fargo' if(-d '/fargo/i386');
$fargo = '/mnt' if(-d '/mnt/i386');
die "Could not find glazed:/fargo at /fargo or /mnt\n" unless ($fargo);
print "Found glazed:/fargo at $fargo\n";

my %filetree; # huge cache of rpms on glazed
my %filetree_src; # srpms
my @rpmset; # locally installed rpms
my @missingrpms; # rpms that could not be found on glazed
my @missingsrpms; # srpms that could not be found on glazed
my $rpmdst = 'RPMS';
my $srpmdst = 'SRPMS';

print "Building filetree hash from glazed:";

foreach my $dir ('/pacifica', '/ptlobos', '/monterey', '/rom-utils', '/source', '/alpine', '/atherton', '/bigbear', '/bluelinq', '/btos', '/cce', '/devel-tools', '/distro', '/fargo', '/rpms', '/srpms', '/kernel') {
	print "$dir ";
	open(FIND, "find $fargo$dir -name \"\*.rpm\" |");
	while(<FIND>) { 
		chomp;

		# Strip rpm suffix and path for matching
		my $basename = $_;
		$basename =~ s/\.[^\.]+\.rpm$//;
		$basename =~ s/.+\/([^\/]+)$/$1/;

		if(/\.src\./) {
			$filetree_src{$basename} = $_;
			# $DEBUG && print "Found SRPM $basename at $_\n";
		} else {
			$filetree{$basename} = $_;
			# $DEBUG && print "Found RPM $basename at $_\n";
		}
	}
	close(FIND);
}
print "\nDone.\n\nReading locally installed RPMS. \n";

open(RPMQ, "rpm -qa |");
while(<RPMQ>) { chomp; push(@rpmset, $_); }
close(RPMQ);

print "Creating directories $rpmdst and $srpmdst\n";
unless (-d $rpmdst) {
	mkdir ($rpmdst, 0755)
		|| die "Could not create directory $rpmdst: $!";
}
unless (-d $srpmdst) {
	mkdir ($srpmdst, 0755) 
		|| die "Could not create directory $srpmdst: $!";
}

my $index = 1;

print "Matching and copying RPMS:\n";
foreach my $rpm (@rpmset) {
	my ($foundrpm, $foundsrpm);

	$DEBUG && print "...Processing $index of ".$#rpmset." RPMS...\n";
	if($filetree_src{$rpm}) {
		# Except Sausalito srpms
		next if (/^base\-\w/ && /\.src\.rpm/);

		$DEBUG && print "  SRPM $rpm found on glazed, copying...\n";
		copy($filetree_src{$rpm}, $srpmdst) 
			|| warn "COPY FAILED of $filetree_src{$rpm} to $srpmdst: $!";
		$foundsrpm = 1;
	} 
	if ($filetree{$rpm}) {
		$DEBUG && print "  RPM $rpm found on glazed, copying...\n";
		copy($filetree{$rpm}, $rpmdst) 
			|| warn "COPY FAILED of $filetree_src{$rpm} to $srpmdst: $!";
		$foundrpm = 1;
		
	} 
		
	# Advanced trickery here, search glazed
	if (!$foundsrpm) {
		push(@missingsrpms, $rpm);
		warn "***** Could not locate SRPM for $rpm\n" 
	}
			
	if (!$foundrpm) {
		push(@missingrpms, $rpm);
		warn "***** Could not locate RPM for $rpm\n" 
	}
	$index++;
}

print "Done.\n";
print "Missing SRPMS:\n".join(' ', @missingsrpms)."\n";
print "Missing RPMS:\n".join(' ',@missingrpms)."\n";

exit 0;
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

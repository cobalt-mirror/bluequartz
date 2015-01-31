# $Id: Global.pm 1065 2008-01-27 09:19:54Z shibuya $
# Cobalt Networks, Inc http::/www.cobalt.com
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.

package Global;
use strict;

require Exporter;

use vars qw(@ISA @EXPORT @EXPORT_OK);
@ISA	= qw(Exporter);	
@EXPORT	= qw(
		&cmuLog
		&getBuild
		&parseD
		&memInfo
		$validExport
		$validImport
		%adjustPath
);
@EXPORT_OK = qw();

use vars qw($validExport $validImport %adjustPath);
$validExport = { 
	Qube2	=>	1, 
	Qube3	=>	1, 
	RaQ2	=>	1,
	RaQ3	=>	1,
	RaQ4	=>	1,
	RaQXTR	=>	1,
	RaQ550	=>	1,
	'5100R'	=>	1,
	'5106R' =>	1,
	'5107R' =>	1,
	'5108R' =>	1,
	'5160R' =>	1,
	'5161R' =>	1,
	'5207R' =>	1,
	'5208R' =>	1,
	'5209R' =>	1,
	'5200R' =>	1,
	TLAS1HE	=>	1,
	TLAS2	=>	1
};
$validImport = { 
	Qube3	=>	1, 
	RaQ3	=>	1,
	RaQ4	=>	1,
	RaQXTR	=>	1,
	RaQ550	=>	1,
	'5100R'	=>	1,
	'5106R' =>	1,
	'5107R' =>	1,
	'5108R' =>	1,
	'5160R' =>	1,
	'5161R' =>	1,
	'5207R' =>	1,
	'5208R' =>	1,
	'5209R' =>	1,
	'5200R' =>	1,
	TLAS1HE	=>	1,
	TLAS2	=>	1
}; 

%adjustPath = (
               Qube2 => 'Qube3',
               RaQ2 => 'RaQ3',
               RaQ3 => 'RaQ4',
               RaQ4 => 'RaQXTR',
               RaQXTR => 'RaQ550',
               RaQ550 => 'TLAS1HE',
               TLAS1HE => '5100R',
               '5100R' => 'TLAS2',
               'TLAS2' => '5106R',
               '5106R' => '5107R',
               '5107R' => '5108R',
               '5108R' => '5106R',
               '5108R' => '5107R',
               '5108R' => '5160R',
               '5160R' => '5161R',
               '5108R' => '5106R',
               '5106R' => '5107R',
               '5107R' => '5108R',
               '5108R' => '5207R',
               '5207R' => '5208R',
               '5208R' => '5209R',
               '5209R' => '5106R'
               );
        
    # Note: The old adjustPath was just from the oldest model to the newest.
    # This only allowed migrations from an old platform to a newer. But not
    # going from 5108R 'down' to 5106R - for example.
    # So at the end of the old adjustPath (which ended with 5161R) I added
    # 5109R again and make it adjustable to the bottom end of the supported
    # platform list: 5106R.

1;

sub cmuLog 
# This function will print error messages to screen and the log file.
# Arguments: code, message
# Returns: none
# Side Effects: writes info to the log file and maybe the screen
{
    my $code = shift;
    my $string = shift;

	my $logFile = "/home/cmu/cmu.log";

	if($string =~ /^(INFO: )(.*)/) { 
		print $2, "\n";
	} elsif($string =~ /^ERROR/) {
		print $string;
	}
	# Everything get logged, biaaaatch!
	my $msg = $code.": ".$string;
	system("echo -n \"$msg\" >> $logFile");
	
}

sub getBuild
# Translates all of build tags into basic product names
# Arguments: none
# Returns: name of build
# Side Effects: selling out
{
	my $bldFile = "/etc/build";
	my %bldHash = ( 
		# Qube Builds
		"2800WG",		"Qube2",
		"4000WG",		"Qube3",
		"4010WG",		"Qube3",
		"4100WG",		"Qube3",
		# RaQ Builds
		"2700R",			"RaQ1",
		"2799R",			"RaQ2",
		"2800R",			"RaQ2",
		"3000R",			"RaQ3",
		"3001R",			"RaQ4",
		"3100R",			"RaQ4",
		"3500R",			"RaQXTR",
		"3599R",			"RaQXTR",
		"4100R",			"RaQ550",
		# BlueQuartz builds
		"5100BQ",			"Qube3",
		"5100WG",			"Qube3",
		"4200R",			"RaQ550",
		"4900R",			"RaQ550",
		"5100R",			"5100R",
		"5101R",			"5100R",
		"5102R",			"5100R",
		"5103R",			"5100R",
		"5104R",			"5100R",
		"5105R",			"5100R",
		# BlueOnyx builds
		"5106R",			"5106R",
		"5107R",			"5107R",
		"5108R",			"5108R",
		"5160R",			"5160R",
		"5161R",			"5161R",
		"5207R",			"5207R",
		"5208R",			"5208R",
		"5209R",			"5209R",
		"5200R",			"5200R",
		# TLAS
		"TLAS1HE",			"TLAS1HE",
		"TLAS2",			"TLAS2"
	);
 
	open(FH, "< $bldFile") 
		|| die "Error opening file $bldFile: $!";
	my $data;
	while(<FH>) {
		$data .= $_;
	}
	close(FH);
	for (sort keys %bldHash) {
		return $bldHash{$_} if ($data =~ /\Q$_\E/);
  	}
}

sub parseD
# Arguments: the -d option
# Returns: the full path to the dir
# Side effects: will create dirs on export
{
	my $dir = shift;

	my $pwd;
	if ($dir eq ".") {
		$pwd = `/bin/pwd`;
		chomp $pwd;
		$dir = $pwd;
	}
	unless($dir  =~ /^\//) {
		$pwd = `/bin/pwd`;
		chomp $pwd;
		$dir = $pwd."/".$dir;
	}
	
	if($0 =~ /Export/ && -d $dir) {
		print "Directory already exists: $dir Overwrite? (Yes/No) ";
		my $opt = <STDIN>;
		unless($opt =~ /(yes|y)/oi) {
			cmuLog("ERROR","Need valid export directory\n");
			exit 1;
		}
	}
	return $dir;
}

sub memInfo
{
	my $pid = $$;
	open(FH, "< /proc/$pid/status") || return "Cannot open file /proc/$pid:$!\n";
	
	my $mem;	
	while(<FH>) {
		if(/^VmSize:\s*(.*)$/) {
			$mem = $1;
			last;
		}
	}
	close(FH);
	($mem) ? (return $mem) : (return "memInfo: FAILED");
}

1;
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

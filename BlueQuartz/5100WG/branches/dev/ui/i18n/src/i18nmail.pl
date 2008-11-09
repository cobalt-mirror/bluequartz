#!/usr/bin/perl -w
# $Id: i18nmail.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

use lib '/usr/sausalito/perl';
use Getopt::Std;
use SendEmail;


getopts('t:f:s:b:I:c:b:l:');

if($opt_t){
	#we have a -t option, therefore we are in the 'old' mode.

	if (! defined $opt_t || ! defined $opt_f || !defined $opt_s || !defined $opt_b){
		print STDERR "Usage : $0 -t <to user> -f <from user> -s <i18n subject> -b <i18n body>\n";
	  exit(1);
	}

	SendEmail::sendEmail($opt_t, $opt_f, $opt_s, $opt_b, "", "", $opt_l);
	exit(0);

}elsif($ARGV[0]){
	#we have something left on the command line, must be the
	#To: addresses.  Therefore we are in 'mail' emulation mode.

	my($to,$from,$body,$subject,$cc,$bcc);
	my $asPipe = 0;
	my $rin = "";
	vec($rin,fileno(STDIN),1)=1;

	if(select($rin,undef,undef,0.25)!=0){
		$asPipe=1;
		{local $/=undef;$body=<STDIN>;}
		chomp $body;
	}
	

	$to = join ',',@ARGV;
	$from = getpwuid($<);
	if ($opt_f) {
	  $from = $opt_f;
	}

	#the reason these are so frikin complex is to avoid undefined variable
	#warnings.  I hate undefined variable warnings.  :)

	$subject = (defined($opt_s) || $asPipe ? $opt_s : getInput("Subject: ")) || "";
	$body = (defined($body) || $asPipe ? $body : getInput("","\n.\n")) || "";
	$cc = (defined($opt_c) || $asPipe ? $opt_c : getInput("Cc: ")) || "";
	$bcc = $opt_b || "";


	SendEmail::sendEmail($to,$from,$subject,$body,$cc,$bcc, $opt_l);
	exit(0);

}else{
	#old and new usage
	exit(1);
}


sub getInput{
	my($prompt,$term)=@_;
	my $ret="";

	$term=$term || "\n";
	local $/=$term;

	print $prompt;
	$ret=<STDIN>;
	chomp($ret);

	return $ret;
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

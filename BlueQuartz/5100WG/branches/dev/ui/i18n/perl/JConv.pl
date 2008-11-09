#!/usr/bin/perl
# $Id: JConv.pl 201 2003-07-18 19:11:07Z will $

use Getopt::Long;
use Jcode;

my (%opt) = ();
GetOptions(\%opt, "to=s", "from:s");

if (!$opt{'to'}) {
    die "Must have --to";
}

$jcode = Jcode->new();

while (<>) {
    chomp;
    if ($opt{'from'}) {
	$jcode->set($_, $opt{'from'});
    } else {
	$jcode->set($_);
    }
    print doConversion($jcode, $opt{'to'});
}

sub doConversion($jcode, $to) {
    my($jcode) = shift;
    my($to) = shift;
    
    if ($to eq "sjis") {
	$ret = $jcode->sjis;
    } elsif ($to eq "jis") {
	$ret = $jcode->jis;
    } elsif ($to eq "unicode") {
	$ret = $jcode->ucs2;
    } elsif ($to eq "utf8") {
	$ret = $jcode->utf8;
    } elsif ($to eq "euc") {
	$ret = $jcode->euc;
    } elsif ($to eq "ascii") {
	$ret = $jcode->euc;
    } elsif ($to eq "binary") {
	$ret = $jcode->binary;
    } elsif ($to eq "iso-2022-jp") {
	$ret = $jcode->iso_2022_jp;
    } 
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

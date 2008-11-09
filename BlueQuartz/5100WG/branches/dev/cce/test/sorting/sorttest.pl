#!/usr/bin/perl 
# $Id: sorttest.pl 229 2003-07-18 20:22:20Z will $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

use lib qw( ../../client/perl );
use CCE;
my $cce = new CCE;

if ($#ARGV >= 0) {
  $cce->connectuds($ARGV[0]);
} else {
  $cce->connectuds("cced.socket");
}

$cce->create("t1", { val1 => "D", val2 => "e", val3 => "60" } );
$cce->create("t1", { val1 => "B", val2 => "c", val3 => "61" } );
$cce->create("t1", { val1 => "b", val2 => "b", val3 => "7.9" } );
$cce->create("t1", { val1 => "A", val2 => "d", val3 => "3.1" } );
$cce->create("t1", { val1 => "a", val2 => "a", val3 => "95.01" } );

my $wrsock = $cce->{wrsock};
print $wrsock "find t1\n";
&readto201($cce);
print $wrsock "find t1 sort val2\n";
&readto201($cce);
print $wrsock "find t1 sort val1\n";
&readto201($cce);
print $wrsock "find t1 sorttype ascii sortprop val1\n";
&readto201($cce);
print $wrsock "find t1 sorttype ascii sortprop val2\n";
&readto201($cce);
print $wrsock "find t1 sortnum val3\n";
&readto201($cce);
print $wrsock "find t1 sorttype old_numeric sortprop val3\n";
&readto201($cce);
print $wrsock "find t1 sorttype \"locale(en_US)\" sortprop val1\n";
&readto201($cce);

sub readto201
{
	my $cce = shift;
	my $rdsock = $cce->{rdsock};
	while(<$rdsock>) {
		if (/^201 OK$/) {
			print;
			last;
		} else {
			print;
		}
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

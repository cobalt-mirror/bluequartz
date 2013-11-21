#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: am_mem.pl 1539 2010-12-05 02:15:54Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# test the memory state
#


use AM::Util;
use strict;

my %am_states = am_get_statecodes();
my ($memYellow, $memRed);
my $errcount;
my $debug = 0;

sub dprint
{
	if ($debug) {
		print @_;
	}
}

# init threshholds
if (!defined($ENV{memYellow})) {
	$ENV{memYellow} = 80;
}
if (!defined($ENV{memRed})) {
	$ENV{memRed} = 90;
}

# get memory numbers
open(MEM, "</proc/meminfo");


# read meminfo lines
my $minfo;

while(!eof(MEM)) {
    $_ = <MEM>;
    (my $key, my $value) = split(/\s+/);
    $key =~ s/://g;
    $minfo->{$key} = $value;
}
close(MEM);

# Mem free + buffered + cache + swap free
my $free = $minfo->{MemFree} + $minfo->{Buffers} + $minfo->{Cached} + $minfo->{SwapFree};

dprint("free  = $free\n");

# Total mem + total swap
my $total = $minfo->{MemTotal} + $minfo->{SwapTotal};
dprint("total = $total\n");

my $used = $total - $free;
my $pct = $used / $total * 100 + 0.5;

dprint("used  = $used\n");
dprint("pct   = $pct\n");

if ($pct > $ENV{memRed}) {
	print("[[base-am.amMemWarning_heavy]]");
	exit $am_states{AM_STATE_RED};
}

if ($pct > $ENV{memYellow}) {
	print("[[base-am.amMemWarning_moderate]]");
	exit $am_states{AM_STATE_YELLOW};
}

print("[[base-am.amMemWarning_light]]");
exit $am_states{AM_STATE_GREEN};
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

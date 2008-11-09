#!/usr/bin/perl -I/usr/sausalito/handlers/base/appleshare -I/usr/sausalito/perl

package appleshare;

use Sauce::Config;

sub atalk_getscript
{
    return '/etc/rc.d/init.d/atalk';
}

sub atalk_getconf
{
    return '/etc/atalk/AppleVolumes.system';
}

sub atalk_getnetatalk
{
    return '/etc/atalk/netatalk.conf';
}

sub share_comment
{
    my ($when, $group) = @_;

    return "\# $group $when -- do not edit this line";
}

sub share_block
{
    my ($group, $restrict) = @_;
    my $groupdir = Sauce::Config::groupdir_base;
    $restrict = $restrict ? "	allow:\@${group}" : '';
    return "$groupdir/$group    \"$group\"$restrict\n";
}

# edit AppleVolumes. this wipes out any current block. it adds in a new
# one if desired.
sub edit_group
{
    my ($input, $output, $ogroup, $ngroup, $restrict) = @_;
    my ($inblock, $found);

    # search for cobalt part
    while (<$input>) {
	print $output $_;
	if (/^# Cobalt config start/) {
	    $found = 1;
	    last;
	}
    }

    return -1 unless $found;

    my $end = share_comment('end', $ogroup);
    my $begin = share_comment('begin', $ogroup);
    $begin =~ /^$begin/;
    $end =~ /^$end/;
    while (<$input>) {
	if ($inblock) {
	    if ($_ =~ $end) {
		$inblock = 0;
	    }
	    next;
	}

	# look for for the beginning of the appropriate block 
	if ($_ =~ $begin) {
	    $inblock = 1;
	    next;
	}

	# print out the group unless it doesn't exist anymore
	if ((/^# Cobalt config stop/) and $ngroup) {
	    print $output share_comment('begin', $ngroup) . "\n";
	    print $output share_block($ngroup, $restrict);
	    print $output share_comment('end', $ngroup) . "\n";
	}
	
	print $output $_;
    }

    return 0;
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

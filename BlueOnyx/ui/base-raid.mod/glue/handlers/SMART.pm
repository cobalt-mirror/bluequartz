#!/usr/bin/perl


package SMART;

use strict;
use lib qw(/usr/sausalito/perl);
use vars qw(@ISA @EXPORT);

require Exporter;
@ISA = qw(Exporter);
@EXPORT = qw(get_smart_info smart_to_array array_to_smart);


# Runs ide-smart with and returns 2 has references.
# The first hash reference contains the Id and Value data and has the following structure:
# hash->{drive}->{id} = value
#             |->{id} = value
#
# The second hash reference contains the failure state of the Ids and has the following
# structure:
#
# hash->{drive}->{id} = boolean FAILED
#             |->{id} = boolean
# The boolean equals 1 if the id has failed

1;

sub get_smart_info {
    my @drives = @_;
    my $data = undef;
    my $failures = undef;
    my ($id, $val);
    
    foreach my $drive (@drives) {
	open(SMART, "/usr/local/sbin/ide-smart $drive 2>&1 |") or die "can't run ide-smart from get_smart_info";
	while (<SMART>) {
	    if (/Id=\s*(\d+).*Value=\s*(\d+)/) {
		$id = $1;
		$val = $2;
		$data->{$drive}->{$id} = $val;
		if (/Failed/) {
		    $failures->{$drive}->{$id} = 1;
		}
	    }
	}
	close(SMART);
    }

    if (wantarray) {
	return ($failures, $data);
    } else {
	return $data;
    }
}


# takes hash reference of smart info and returns an array of elements 
# of form drive/id/value
sub smart_to_array {
    my $hash = shift;
    my @drives = keys(%$hash);
    my @result = ();
    foreach my $drive (@drives) {
	foreach my $id (keys %{$hash->{$drive}}) {
	    push @result, "$drive/$id/" . $hash->{$drive}->{$id};
	}
    }
    return @result;

}

# takes array of elements of form drive/id/value and returns hash ref
# hash->{drive}->{id}->{value}
sub array_to_smart {
    my $hash = undef;
    my @fields;
    foreach my $entry (@_) {
	@fields = split('/', $entry);
	$hash->{$fields[0]}->{$fields[1]} = $fields[2];
    }
    return $hash;
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

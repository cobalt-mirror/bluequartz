# $Id: ReMap.pm 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http://www.cobalt.com
# Sun Microsystems, Inc http://www.sun.com

package ReMap;

use vars qw($VERSION @queue);

my %badGroups = ();

my %badUsers = ();

1;

# this should look like
# %remap = (
#	name 	=> $sessionID
#	type	=> user
#	old		=> oldname
#	new		=> newname
#	deps	=> @groups, @mailLists
#	
sub new
{
	my $proto = shift;
	my $class = ref($proto) || $proto;
	my $self = {};
	bless ($self, $class);
	if(@_) {
		my %opts = @_;
		while(my($fn,$fv) = each %opts) { $self->{$fn} = $fv; }
	}
	return $self;
}

sub init
{

}
# queue operations add, remove, process

sub add
# add a remap object to the queue
{

}

sub remove
# remove a remap object form the qube
{

}

sub process
# run through the enitre queue
{

}

# operation of the remap itself
sub run
# run a remap object
# isn't this dep on the type yet again?
{

}

sub destroy
# get rid of
{

}

sub depList
# list depenancies
{

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

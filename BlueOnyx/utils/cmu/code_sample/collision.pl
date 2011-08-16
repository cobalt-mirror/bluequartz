#!/usr/bin/perl

# a segment implementing a possible collision type conflict detection
# this includes group, aliases, list, and usernames

# this can can be broken in to two steps: 
#   1. conflicts within the imported data (this has also been called 'verify')
#   2. conflicts between the imported data and existing data
#
# In step 1 collisions must be detected between groups, aliases, lists, and 
# usernames. Even as a normal Qube has a matching list for every group, the 
# imported data cannot have such a collision since the Qube will generate the
# corresponding list when the group is created (imported). The categories do
# not have to be checked against themselves as the implemenetation as a hash
# ensures that we will have only one.
#
# In step 2 collisions must be detected between groups, aliases, lists, and 
# usernames including checks against the same categories.
#
# running time excluding the loading of the trees(these would be passed in)
# should be on the order of O(I + 2a) where I is the size of the entire 
# import tree and a is the number of user aliases.



use TreeXml;
use Data::Dumper;

my $exTree = readXml('existing.xml', 0, 0); # existing tree
my $imTree = readXml('import.xml', 0, 0); # imported tree


# bring aliases out of thier possible array structure and into a hash
sub raiseAlii {
    my $tree = shift;
    my $aliasRef = {};

    foreach my $user ( keys %{ $tree->{user} }) {
	if (ref($tree->{user}->{$user}->{aliases}->{alias}) eq 'ARRAY') {
	    foreach my $alias (@{ $tree->{user}->{$user}->{aliases}->{alias}}){
		$aliasRef->{$alias} = 1;
	    }
	}
	elsif (exists $exTree->{user}->{$exUser}->{aliases}->{alias}) {
	    $aliasRef->{$tree->{user}->{$user}->{aliases}->{alias}} = 1;
	}
	
    }
    return $aliasRef;
}
my $exAlii = raiseAlii($exTree);
addNode('alias', $exAlii, $exTree);

my $imAlii = raiseAlii($imTree);
addNode('alias', $imAlii, $imTree);

foreach my $group (keys %{ $imTree->{group} }) {
    checkImport($group, 'group');
    checkExist($group, 'group');
}

foreach my $user (keys %{ $imTree->{user} }) {
    checkImport($user, 'user');
    checkExist($user, 'user');
}

foreach my $mailList (keys %{ $imTree->{mailList} }) {
    checkImport($mailList, 'list');
    checkExist($mailList, 'list');
}

foreach my $alias (keys %{ $imTree->{alias} }) {
    checkImport($alias, 'alias');
    checkExist($alias, 'alias');
}

#print Dumper($imTree->{mailList});
#print Dumper($exTree->{mailList});

sub checkExist {
    my $value = shift;
    my $type = shift;
    
    print "imported $type $value: conflicts with existing group\n" 
	if (exists $exTree->{group}->{$value}); 

    print "imported $type $value: conflicts with existing user\n" 
	if (exists $exTree->{user}->{$value});

    print "imported $type $value: conflicts with existing list\n" 
	if (exists $exTree->{mailList}->{$value});
    
    print "imported $type $value: conflicts with existing alias\n" 
	if (exists $exTree->{alias}->{$value});
}

sub checkImport {
    my $value = shift;
    my $type = shift;

    print "imported $type $value: conflicts with imported group\n"
	if ($type ne 'group' && exists $imTree->{group}->{$value});

    print "imported $type $value: conflicts with imported user\n"
	if ($type ne 'user' && exists $imTree->{user}->{$value});

    print "imported $type $value: conflicts with imported list\n"
	if ($type ne 'list' && exists $imTree->{mailList}->{$value});

    print "imported $type $value: conflicts with imported alias\n"
	if ($type ne 'alias' && exists $imTree->{alias}->{$value});
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

# $Id: Conflict.pm 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http://www.cobalt.com

package Conflict;

# this is just a tools for conflict.pl
use vars qw(@remapQueue $RESO $VERSION $DEBUG);

@remapQueue = ();
$RESO = 2; # 0=quit;1=drop;2=prompt
$VERSION = 2.5;
$DEBUG = 0;

require Global;
import Global qw(&cmuLog);

1;

sub new
{
	my $proto = shift;
	my $class = ref($proto) || $proto;
	my $self = {};
	bless ($self, $class);
	
	my $expt = shift || die "You must provide an export tree\n";
	my $impt = shift || die "You must provide an import tree\n";
	$self->{e} = $expt;
	$self->{i} = $impt;
	$self->{merged} = {};

	return $self;
}


sub handleCollision {
	my $self = shift;
	my $conflictor = shift;
	my $type = shift;
    my $newVal = $conflictor;

	# this stores good users, aliases, groups, and maillist names
	my %import;
    print "handleCollision conflictor=$conflictor type=$type\n" if $DEBUG;

	if ($RESO eq 0) { # die
		die 'Fatal conflict found: $type $conflictor';
    } elsif ($RESO eq 1) { # drop
		$self->dropVal($conflictor, $type);
		$newVal = '';
    } else { # prompt
		my $msgStr = "\nThere is a conflict with $newVal in $type.\n";
		$msgStr .= "Drop or Quit? (d/q):";
		my $changed = 0;
		while ($changed == 0) {
    	print $msgStr;
    	$newVal = <STDIN>;
    	chomp($newVal);
		next if($newVal eq '');
		if($newVal =~ /^(q|quit)/i) {
			cmuLog("ERROR","User aborted import at conflict $conflictor\n");
			exit 1;
		} elsif ($newVal =~ /^(d|drop)$/i) {
			$self->dropVal($conflictor, $type);	
			$newVal = '';
			$changed = 1;
		} elsif (!checkAllExisting($newVal, $type) && !$import{$newVal}) {
			$self->remapVal($conflictor, $newVal, $type);
			$changed = 1;
		} else {
			print "A problem has been detected with the entry: $newVal\n";
		}
	}
    }
    return $newVal;
}

sub raiseAlii {
    my $tree = shift;
    my $aliasRef = {};

    foreach my $user ( keys %{ $tree->{users} }) {
		my $alii = $tree->{users}->{$user}->{aliases}->{alias};
        if (ref($alii) eq 'ARRAY') {
            foreach my $alias (@{ $alii }){ $aliasRef->{$alias} = $user; }
        }
        elsif ($alii) { $aliasRef->{$alii} = $user; }
    }
    return $aliasRef;
}

sub checkAllExisting {
	my $self = shift;
    my $value = shift;
    return ($self->{e}->{groups}->{$value} ||
	    $self->{e}->{users}->{$value} ||
	    $self->{e}->{mailLists}->{$value} ||
	    $self->{e}->{aliases}->{$value}
	    );
}

sub dropVal
{
	my $self = shift;
	my $name = shift;
	my $type = shift;
	
	if($type eq 'users') {
		$self->removeUser($name);
		TreeXml::deleteNode($name, $self->{i}->{$type});
	} elsif ($type eq 'aliases') {
		$self->removeAlias($name);
	} elsif ($type eq 'groups') {
		TreeXml::deleteNode($name, $self->{i}->{$type});
		TreeXml::deleteNode($name, $self->{i}->{mailLists});
	} elsif ($type eq 'mailLists') {
		TreeXml::deleteNode($name, $self->{i}->{$type});
	}

}

sub changeVal
{	
	my $self = shift;
	my $old = shift;
	my $new = shift;
	my $type = shift;

	if($type eq 'users') { $self->remapUser($old, $new) } 
	elsif ($type eq 'aliases') { $self->remapAlias($old,$new) } 
	elsif ($type eq 'groups') { $self->remapGroup($old, $new); } 
	elsif ($type eq 'mailLists') {
		TreeXml::renameNode($old, $new, $self->{i}->{$type});
	} else {
		cmuLog("ERROR", "Unknown conflict resolution type\n");
	}
}

sub remapUser
{
	my $self = shift;
	my $old = shift;
	my $new = shift; 
	my @keys;
	
	TreeXml::renameNode($old, $new, $self->{i}->{$type});
	# remap any group memberships
	@keys = keys %{ $self->{i}->{groups} };
	foreach my $grp (@keys) {
		my $mem = $self->{i}->{groups}->{$grp}->{members};
		my $newMem = $self->remapItem($old, $new, $mem);
		$self->{i}->{groups}->{$grp}->{members} = $newMem;
	}
	
	# remap any mail list memberships
	@keys = keys %{ $self->{i}->{mailLists} };
	foreach my $lst (@keys) {
		my $list = $iTree->{mailLists}->{$lst}->{local_recips};
		my $newList = $self->remapItem($old, $new, $list);
		$self->{i}->{mailLists}->{$lst}->{local_recips} = $newList;
	}
}

sub remapAlias
{
	my $self = shift;
	my $old = shift;
	my $new = shift;

	# this is pretty harmless
	my $user = $self->{i}->{aliases}->{$old};

	my $alii = $self->{i}->{users}->{$user}->{aliases};	
	my $newAlii = TreeXml::remapItem($old, $new, $alii);
	$self->{i}->{users}->{$user}->{aliases} = $newAlii
}

sub remapGroup
{
	my $self = shift;
	my $old = shift;
	my $new = shift; 

	TreeXml::renameNode($old, $new, $self->{i}->{$type});
	# remap permissions in the archive
	foreach my $lst (keys %{$iTree->{mailLists}}) {
		my $list = $self->{i}->{mailLists}->{$lst};
		if($list->{group} eq $old) {
			TreeXml::renameNode($old, $new, $self->{i}->{mailLists});
			$self->{i}->{mailLists}->{$new}->{group} = $new;
		}
	}
}

sub removeUser
{
	my $user = shift || return;
	my @keys;

	# remove any group memberships
	@keys = keys %{ $iTree->{groups }};
	foreach my $grp (@keys) {
		next unless($iTree->{groups}->{$grp}->{members});
		my $ret = removeItem($user, $iTree->{groups}->{$grp}->{members});
		if($ret) {
			cmuLog("Conflict", "chaning $user owned files to admin in groups-$grp.xml\n");
			remapXmlFile($user, "admin", "uid", "groups-$grp.xml");
		}
	}

	# remap any mail list memberships
	@keys = keys %{ $iTree->{mailLists} };
	foreach my $lst (@keys) {
		next unless($iTree->{mailLists}->{$lst}->{local_recips});
		removeItem($user, $iTree->{mailLists}->{$lst}->{local_recips});
	}
}

sub removeAlias
{
	my $alias = shift || return;
	my $user = $iTree->{aliases}->{$alias};
	
	if($iTree->{users}->{$user}->{aliases}) {
	removeItem($alias, $iTree->{users}->{$user}->{aliases});
	}
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

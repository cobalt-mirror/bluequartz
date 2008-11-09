#!/usr/bin/perl -I/usr/sausalito/handlers/base/winshare -I/usr/sausalito/perl
# $Id: domain_admins.pl 201 2003-07-18 19:11:07Z will $
#
# Copyright(c) 2000, 2001 Sun Microsystems, Inc.
# Will DeHaan <null@sun.com>
#
# Keep /etc/group's winshare group in sync with the User.capabilities 
# modifyWinshare property roster
#
use Sauce::Service;
use Sauce::Util;
use CCE;

my $cce = new CCE;
$cce->connectfd(\*STDIN, \*STDOUT);

my $obj = $cce->event_object();
my $old = $cce->event_old();

# system group 'winshare' members hash
my @winshare = getgrnam('winshare');
my %members = map{$_, 1} split(/\s/, $winshare[3]);

my $dirty_groups = 0; # Set to 1 to rewrite /etc/groups

# Ensure admin and root are winshare members
if(!$members{'admin'} || !$members{'root'})
{
	$members{'admin'} = $members{'root'} = 1;
	$dirty_groups = 1;
}

if(($cce->event_is_destroy() && $members{$old->{name}}) ||
	(($obj->{capabilities} !~ /&(modifyWinshare|systemAdministrator)&/) &&
	 $members{$obj->{name}}))
{
	# delete $old->{name} from the winshare roster
	delete($members{$old->{name}});
	$dirty_groups = 1;
}
elsif (($obj->{capabilities} =~ /&(modifyWinshare|systemAdministrator)&/) &&
	!$members{$obj->{name}})
{
	# add $obj->{name} to the winshare roster
	$members{$obj->{name}} = 1;
	$dirty_groups = 1;
}

# edit groups
if($dirty_groups)
{
	# create the winshare group if necessary
	system('/usr/sbin/groupadd', 'winshare') unless ($winshare[0]);

	my $ret = Sauce::Util::editfile(
		'/etc/group', *edit_group, 'winshare', keys %members);
	chmod(0644, '/etc/group');

}

$cce->bye('SUCCESS');
exit 0;


# Subs

sub edit_group
{
	my ($fin, $fout, $group) = (shift, shift, shift);

	my $found = 0;
	while(<$fin>)
        {
                if (m/^$group:x:\d+:/) {
			my @bits = split(/:/, $_);
			$bits[3] = join(',',@_);
			$_ = join(':', @bits)."\n";
			$found = 1;
                }
                print $fout $_;
        }
	return $found;
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

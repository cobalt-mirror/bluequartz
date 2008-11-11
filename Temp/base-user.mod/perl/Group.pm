#!/usr/bin/perl -I/usr/sausalito/perl
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: Group.pm,v 1.12.2.1 2002/06/18 21:54:06 naroori Exp $
#
# commonly used functions such as groupadd, groupmod, groupdel
# TODO: get input on the API, documentation
#

package Base::Group;

=pod

=head1 NAME

Base::Group - methods to add, modify, and delete system groups

=head1 SYNOPSIS

 use Base::Group;
 use Base::Group qw(groupadd system_groupadd groupmod groupdel);

 my $group = {
 	'name' => 'group1',
	'oldname' => 'oldgroup1',
	'gid' => 501,
	'password' => '*',
	'members' => 'user1,user2,user3'
 };
				
 Base::Group::groupadd(\@groups_info);
 Base::Group::system_groupadd($group1);
 Base::Group::groupmod(\@groups_info);
 Base::Group::groupdel('group1', 'group2', 'group3', ...);

 Base::Group::group_add_members('group1', 'user1', 'user2', 'user3', ...);
 Base::Group::group_rem_members('group2', 'userA', 'userB', 'userC', ...);

=head1 DESCRIPTION

Base::Group is a collection of routines to add, modify, and remove system
groups.  The functionality is roughly similar to that of the groupadd,
groupmod, and groupdel programs that ship with RedHat Linux.  The difference
is that the included methods know about the preferred group information
storage method for Sun Microsystems Linux.  

This module should always be used when doing anything with groups in CCE 
handlers, although this module does not interact with CCE in anyway and does 
not make changes to the CCE database.  The normal RedHat programs will still
work, and this module will see changes made by those programs.  However, all
changes made by this module may not be visible to the RedHat programs.  This
should be considered the authoritative means of dealing with making changes
to the system group information.  All other means are considered deprecated for
use in Sun Microsystems Linux.

=head1 EXPORTS

All of the methods provided by Base::Group can be imported in the calling
namespace with the standard C<use Module qw (function 1 function2 ...);> pragma.
All variables in Base::Group are considered private.  Changing any of their
values will break things.

=cut

use Exporter;
use vars qw(@ISA @EXPORT_OK);

@ISA = qw(Exporter);

@EXPORT_OK = qw(
                system_groupadd groupadd groupmod groupdel
                group_add_members group_rem_members
                );

use FileHandle;
use Sauce::Util;

use vars qw($DEBUG $GIDS_LOCKFILE $GIDS_CACHE $MIN_GID $MAX_GID);
$DEBUG = 0;
$MIN_GID = 500;  # min gid to assign to new groups
$MAX_GID = 2 ** 16;  # max gid to assign to new groups

# some private variables that are global to reduce the number of open
# and close system calls
my %gids;

=pod

=head1 GROUP 'OBJECT'

Base::Group does not use PERL objects (yet. this may change, but hopefully
backward compatibility will remain).  The group "object" the group* methods
expect is really just a reference to a PERL hash.  The group hash has the
following structure.

$group = {
 'name' => 'groupname',
 'oldname' => 'oldgroupname',
 'gid' => 501,
 'password' => 'crypted_password',
 'members' => 'mem1,mem2,mem3'

};

The members of the hash are:

=over 4

=item name

This is the name of the group that will be added or modified.

=item oldname

This is the old name of the group.  Use this when the name of a group should be
changed.  The I<name> value should be the group's new name, while I<oldname>
is the group's current name.  I<oldname> need not be specified unless the
group's name is being changed.

=item gid

The group id number to assign to the group.  This is optional when adding a
group, because if it is not specified the next available group id will be
assigned to the group.  It can be specified to change a group's id when
modifying an already existing group.

=item password

This is always optional.  If it is desirable to set a password for a group, 
I<password> should be the md5 encrypted password to assign to the group.  If
no password is specified during group addition, the password for the group
defaults to '*'.


=item members

This is always optional.  It can be used to set the initial group members
during group addition, or it can also be used to specify the exact group
membership during group modification.  If specified, it's value should contain
a string with a comma separated list of group members (e.g. 'user1,user2').
The I<group_add_members> and I<group_rem_members> methods can be used to add or
remove specific members from a group that has already been created.

=back

=head1 METHODS

=over 4

=item groupadd($group or \@groups)

This method adds the group or groups specified by the single argument.  The
argument can be a single group "object", or a reference to a list of group
"objects" to be added at the same time.  

It returns a two element list with the first element indicating success or 
failure.  The second element is a reference to a list of the names of the groups
that groupadd was unable to add.  If all groups are added successfully, the 
first element will be true, or 1.  If the method was unable to add any of the 
groups or some other error occurred, the first element will be false, or 0.  
Check the group names list if the method fails.  The group names list reference
will be undefined if some general error occured, or it will contain a reference
to the list of names of groups that could not be added.

=cut
# takes a list reference for bulk group adds to a list containing hash references
# to passwd "structs" of group information for addition
# or just a hash reference for one group
# returns a list with the first element being the success code (1 for success,
# 0 for failure for any groups) and the second element being a list reference
# to a list containing names of groups whose add failed
sub groupadd
{
    return _internal_groupadd([PWDB_UNIXDB, PWDB_SHADOWDB], @_);
}

=pod

=item system_groupadd($group or \@groups)

This method behaves exactly identical to the I<groupadd> method with the
exception that groups added are always put in /etc/group instead of the
preferred group information database used by groupadd.  This should only be
used for essential system groups, and in general will not be needed by the
average handler or constructor script in CCE.  The return value is the same as
that for I<groupadd>.

=cut
# same as groupadd but add to flat files
sub system_groupadd
{
    return _internal_groupadd([PWDB_UNIX, PWDB_SHADOW], @_);
}

=pod

=item groupmod($group or \@groups)

As with I<groupadd> and I<system_groupadd>, this method can be used on a single
group "object" or a reference to a list of group "objects".  The only
member of the group "object" that must be specified when passing it to groupmod
is the I<name>.  The other members are optional, and if given will cause the
group information in the system to be updated to reflect the new value.  To
modify the name of a group, I<name>'s value should be the new group name, and
I<oldname>'s value should be the current group name.

The return value is a list just like that returned by I<groupadd>, but the
list of group names on failure are groups that could not be modified.  If
changing a group name fails, the name in the list will be the current group
name (the value of the I<oldname> member of the group "object").

Note that if files or directories are owned by the group being modified, and the
group id number (gid) is being changed.  The file ownership will NOT be updated
with the new gid by this method.

This method works for groups in all group information "databases".  It will
modify a group no matter which "database" it is found in.

=cut

# take the same arguments as groupadd, but the oldname property should be
# the groupname of the group before calling groupmod (ie if you are changing
# the group's groupname the oldname property should be the old groupname)
# returns the same info as groupadd
sub groupmod
{
    my $groups = shift;

    # what did we get
    my $internal_list = [];
    if (ref($groups) eq 'HASH')
    {
        push @$internal_list, $groups;
    }
    elsif (ref($groups) eq 'ARRAY')
    {
        $internal_list = $groups;
    }
    else
    {
        # what are they doing to me?
        return (0, undef);
    }
    
    # succeed by default
    my $success = 1;
    my $bad_groups = [];

    # set umask and save old value
    my $old_umask = umask(022);

    for my $group (@$internal_list)
    {
        my $old_gid = 0;
        my $group_name = $group->{oldname} ? $group->{oldname} : $group->{name};

        my $opt = "";

        # get information for rollback
        my @group_info = getgrnam($group);

        # store old settings for rollback
        my $old_group = {
                            'name' => $group_info[0],
                            'gid' => $group_info[2],
                            'password' => $group_info[1],
                            'members' => $group_info[3]
                        };

        # parse new group settings
        $old_gid = $group_info[2];
        if (exists($group->{gid}))
        {
            my @foo = getgrgid($group->{gid});
            if (scalar(@foo) && $foo[0] ne $group_name)
            {
                $DEBUG && warn("$group->{gid} is already in use");
                $success = 0;
                push @$bad_groups, $group_name;
                next;
            }

            $opt .= "-g $group->{gid} ";
        }

        if ($group->{oldname})
        {
            $opt .= "-n $group->{name} ";
        }

        if ($group->{password})
        {
            $opt .= "-p '$group->{password}' ";
        }

        if (!system("/usr/sbin/groupmod $opt $group_name"))
        {
            $success = 0;
            push @$bad_groups, $group_name;
        }
        else
        {
            if ($group->{members})
            {
                my $ok = system("/usr/bin/gpasswd -M \"$group->{members}\" $group->{name}");
            }

            # handle a group name change
            my $oldgroup_name = '';
            if ($group->{oldname})
            {
                $oldgroup_name = "'oldname' => $group->{name}, ";
            }
#ROLLBACK GROUPMOD
            my $rollback_cmd = "/usr/bin/perl -I/usr/sausalito/perl -e "
                    . "\"use Base::Group qw(groupmod); "
                    . "print STDERR \\\"ROLLBACK GROUPMOD\\n\\\"; "
                    . "groupmod({ "
                            . "'name' => '$old_group->{name}', "
                            . $oldgroup_name
                            . "'gid' => '$old_group->{gid}', "
                            . "'password' => '$old_group->{password}', "
                            . "'members' => '$old_group->{members}' "
                            . "});\"";

            Sauce::Util::addrollbackcommand($rollback_cmd);
        }
    }

    # restore old umask
    umask($old_umask);

    return ($success, $bad_groups);
}

=pod

=item groupdel('group1', 'group2', ...)

I<groupdel> takes a list of group names as it's arguments.  The specified groups
are removed from the system group "databases".  It returns a list similar to
the list returned by I<groupadd> and I<groupmod> with the exception that the
group names list reference contains the names of groups that could not be
removed.

=cut

# groupdel
# arguments: list of groupnames
# returns same as groupadd and groupmod
sub groupdel
{
    my @groups = @_;

    # succeed by default
    my $success = 1;
    my $bad_groups = [];

    # save old umask and set to a known value
    my $old_umask = umask(022);
    
    for my $group (@groups)
    {
        # get information for rollback
        my @group_info = getgrnam($group);
        if (!scalar(@group_info))
        {
            # group doesn't exist, just let it slide
            next;
        }

        # save old group info for rollback
        my @members = split(' ', $group_info[3]);
        my $old_group = {
                            'name' => $group_info[0],
                            'gid' => $group_info[2],
                            'password' => $group_info[1],
                            'members' => join(',', @members)
                        };

        my $ret = system("/usr/sbin/groupdel $group_info[0]");
        if ($ret != 0)
        {
            $DEBUG && warn("removing $group failed");
            $success = 0;
            push @$bad_groups, $group;
        }
        else
        {
#ROLLBACK GROUPDEL
            my $rollback_cmd = '/usr/bin/perl -I/usr/sausalito/perl -e '
                    . "\"use Base::Group qw(groupadd); "
                    . "print STDERR \\\"ROLLBACK GROUPDEL\\n\\\"; "
                    . 'groupadd({ '
                            . "'name' => '$old_group->{name}', "
                            . "'gid' => '$old_group->{gid}', "
                            . "'password' => '$old_group->{password}', "
                            . "'members' => '$old_group->{members}' "
                            . "});\"";

            Sauce::Util::addrollbackcommand($rollback_cmd);
        }
    } # done deleting groups

    # restore umask
    umask(022);

    return ($success, $bad_groups);
}

=pod

=item group_add_members($group_name, @user_names)

Adds the specified users to the group specified in C<$group_name>.  Specifying
a user in I<@user_names> who is already a group member is okay, because
duplicate entries in the group membership are removed.  The method returns true
for success and false for failure.

=over 4

=item *

I<$group_name> is the name of the group to which to add the members.

=item *

I<@user_names> is a list of user names that should be added to the group.  The
user names are not checked to see if they actually exist.

=back

=cut
# first argument group name followed by a list of usernames
# adds usernames to member list of group name
# returns true on success, false on failure
sub group_add_members
{
    my ($group, @members) = @_;

    return _modify_members(1, $group, @members);
}

=pod

=item group_rem_members($group_name, @user_names)

Removes the users specified in the I<@user_names> list from the group specified
by I<$group_name>.  Returns true for success and false for failure.

=over 4

=item *

I<$group_name> is the name of the group from which to remove the users in 
I<@user_names>.

=item *

I<@user_names> is a list of users to remove from the specified group.  
Specifiying a user who does not exist or is not a group member will not generate
an error.

=back

=back

=cut
# first argument group to modify, followed by a list of usernames
# removes users from a group if they were a member
# returns true on success, false on failure
sub group_rem_members
{
    my ($group, @members) = @_;

    return _modify_members(0, $group, @members);
}

=pod

=head1 NOTES

Depends on PWDB perl module for seamless access to the various group information
databases.  See the PWDB documentation for more information.

=head1 SEE ALSO

perl(1), groupadd(8), groupmod(8), groupdel(8), PWDB

=cut

# only private functions below
sub _modify_members
{
    my ($add, $group, @members) = @_;

    # save umask and set to a known value
    my $old_umask = umask(022);

    # get information for rollback
    my @group_info = getgrnam($group);

    # use this for rollback too
    my @mem = split(' ', $group_info[3]);
    my $cur_mem = join(',', @mem);

    if ($cur_mem)
    {
        $DEBUG && warn("current members $cur_mem in $group group");
        # generate hash of members for easy searching
        my %cur_users = map { $_ => 1 } split(',', $cur_mem);
        for my $member (@members)
        {
            if ($cur_users{$member})
            {
                delete($cur_users{$member});
            }
        }
        
        if ($add)
        {
            push @members, keys(%cur_users);
        }
        else # remove
        {
            @members = keys(%cur_users);
        }
    }
    elsif (!$add)
    {
        # make sure on a remove a user isn't added if they weren't already
        # a member
        @members = ();
    }

    # succeed by default
    my $success = 1;
    my $list = join(',', @members);
    my $ret = system("/usr/bin/gpasswd -M \"$list\" $group");
    if ($ret != 0)
    {
        $DEBUG && warn('gpasswd failed');
        $success = 0;
    }

    # handle rollback for group add/rem members
    my $rollback_cmd = "/usr/bin/perl -I/usr/sausalito/perl -e \""
            . 'use Base::Group qw(groupmod); '
            . "print STDERR \\\"ROLLBACK GROUPMOD\\n\\\"; "
            . "groupmod({ 'name' => '$group', 'members' => '$cur_mem' });\"";

    Sauce::Util::addrollbackcommand($rollback_cmd);
    
    # restore umask
    umask($old_umask);

    return $success;
}

sub sel
{
  return $_[int(rand(1+$#_))];
}

sub cryptpw
{
    my $pw = shift;
    my @saltchars = ('a'..'z','A'..'Z',0..9);
    srand();
    my $salt = sel(@saltchars) . sel(@saltchars);
    my $crypt_pw = crypt($pw, $salt);
    $salt = '$1$';
    for (my $i = 0; $i < 8; $i++) { $salt .= sel(@saltchars); }
    $salt .= '$';
    my $md5_pw = crypt($pw, $salt);
    return ($crypt_pw, $md5_pw);
}

sub get_free_gid
{
    # fetch all gids
    my @gids;
    while (my ($gid) = (getgrent())[2]) {
        push(@gids, $gid);
    }
    return _last_free_id(@gids);
}

sub _last_free_id {
    my ($class, @ids) = @_;

    # sort them
    @ids = sort { $a <=> $b } @ids;
    # del nobody
    pop @ids;
    # return free available uid
    return $ids[-1] + 1;
}

sub _internal_groupadd
{
    my ($src, $groups) = @_;

    $DEBUG && warn("in groupadd");

    # what did we get
    my $internal_list = [];
    if (ref($groups) eq 'HASH')
    {
        push @$internal_list, $groups;
    }
    elsif (ref($groups) eq 'ARRAY')
    {
        $internal_list = $groups;
    }
    else
    {
        $DEBUG && warn("unknown argument type passed in");
        # what are they doing to me?
        return (0, undef);
    }
    
    # succeed by default
    my $success = 1;
    my $bad_groups = [];

    # save old umask and set to a known value
    my $old_umask = umask(022);

    for my $group (@$internal_list)
    {
        my $gid_opt = exists($user->{gid}) ? "-g $user->{gid}" : "";

        my $ret = system("/usr/sbin/groupadd $group->{name} $gid_opt");
        if ($ret != 0)
        {
            $DEBUG && warn('pwdb_add failed');
            $success = 0;
            push @$bad_groups, $group->{name};
        }
        else
        {
            if ($group->{members}) {
                my $ok = system("/usr/bin/gpasswd -M \"$group->{members}\" $group->{name}");
            }

# ROLLBACK GROUPADD
            my $rollback_cmd = "/usr/bin/perl -I/usr/sausalito/perl -e \""
                    . 'use Base::Group qw(groupdel); '
                    . "print STDERR \\\"ROLLBACK GROUPADD\\n\\\"; "
                    . "groupdel('$group->{name}');\"";

            Sauce::Util::addrollbackcommand($rollback_cmd);
        }
    } # done adding current group

    # restore old umask
    umask($old_umask);

    return ($success, $bad_groups);
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

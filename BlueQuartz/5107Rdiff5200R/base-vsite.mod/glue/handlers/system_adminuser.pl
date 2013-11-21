#!/usr/bin/perl -I/usr/sausalito/perl
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: system_adminuser.pl,v 1.4 2001/10/27 03:30:35 pbaltz Exp $
#
# make sure all adminUser's get added to the site groups when the user
# is created or destroyed and to the admin-users group
# also make sure that siteAdmin's get added to the site-adm group
# and to their site group so they can manage everything

use CCE;
use Base::Group qw(system_groupadd group_add_members group_rem_members);
use Base::Vsite;

my $DEBUG = 0;

my $cce = new CCE('Domain' => 'base-vsite');
$cce->connectfd();

my $user = $cce->event_object();
my $user_old = $cce->event_old();
if ($cce->event_is_destroy())
{
    $user = $user_old;
}

# just succeed if this is not an admin user or site admin
if ($user->{capLevels} !~ /&(adminUser|siteAdmin)&/ &&
    $user_old->{capLevels} !~ /&(adminUser|siteAdmin)&/)
{
    $cce->bye('SUCCESS');
    exit(0);
}

my $result = $1;
my $remove = 0;
if ($cce->event_is_destroy() || $user->{capLevels} !~ /&$result&/)
{
    $remove = 1;
}

if ($result eq 'adminUser')
{
    # now iterate all system groups and add/remove this user from all site
    # groups
    setgrent();
    my $group;
    my @groups;
    my $i = 0;
    while($groups[$i] = getgrent())
    {
        $i++;
    }

    for (@groups)
    {
        $group = $_;

        # make sure we skip all non-site groups
        if ($group !~ /^site\d+$/) { next; }
    
        if ($remove)
        {
            if (!group_rem_members($group, $user->{name}))
            {
                $cce->bye('FAIL', 'cantRemoveAdminFromGroup',
                        { 'group' => $group, 'user' => $user->{name} });
                endgrent();
                exit(1);
            }
        }
        else
        {
            if (!group_add_members($group, $user->{name}))
            {
                $cce->bye('FAIL', 'cantAddAdminToGroup',
                        { 'group' => $group, 'user' => $user->{name} });
                endgrent();
                exit(1);
            }
        }
    }
    endgrent();

    $DEBUG && print STDERR "ADDING $user->{name} to admin-users\n";
    $DEBUG && print STDERR "$Base::Vsite::SERVER_ADMIN_GROUP $remove\n";
    
    # also make sure user is added to/removed from admin-users group
    if (!&mod_group($Base::Vsite::SERVER_ADMIN_GROUP, $user->{name}, $remove))
    {
        if ($remove)
        {
            $cce->bye('FAIL', 'cantRemoveUserFromServerAdminGroup',
                    { 'user' => $user->{name} });
        }
        else
        {
            $cce->bye('FAIL', 'cantAddUserToServerAdminGroup',
                    { 'user' => $user->{name} });
        }
        exit(1);
    }
}
elsif ($result eq 'siteAdmin')  # handle site admins
{
    $DEBUG && warn("args are $user->{name}, $remove");
    if ($remove)
    {
        if (!group_rem_members($user->{site}, $user->{name}))
        {
            $cce->bye('FAIL', 'cantRemoveFromSiteGroup');
            exit(1);
        }
    }
    else
    {
        if (!group_add_members($user->{site}, $user->{name}))
        {
            $cce->bye('FAIL', 'cantAddToSiteGroup');
            exit(1);
        }
    }

    # call function to handle this
    if (!&mod_group($Base::Vsite::SITE_ADMIN_GROUP, $user->{name}, $remove))
    {
        # different errors on add and remove
        if ($remove)
        {
            $cce->bye('FAIL', 'cantRemoveUserFromSiteAdminGroup',
                    { 'user' => $user->{name} });
        }
        else
        {
            $cce->bye('FAIL', 'cantAddUserToSiteAdminGroup',
                    { 'user' => $user->{name} });
        }

        exit(1);
    }
} # end if ($result eq 'siteAdmin')

$cce->bye('SUCCESS');
exit(0);

sub mod_group
{
    my ($group, $user, $remove) = @_;

    if (!$group || !$user)
    {
        return 0;
    }

    # make sure group exists first
    if (!scalar(getgrnam($group)))
    {
        # try to create it
        if (!(system_groupadd({ 'name' => $group }))[0])
        {
            $DEBUG && print STDERR "system_groupadd failed\n";
            return 0;
        }

        $DEBUG && warn(join(' ', getgrnam($group)));
    }

    # add or remove as necessary
    if ($remove)
    {
        if (!group_rem_members($group, $user))
        {
            $DEBUG && print STDERR "group_rem_members failed\n";
            return 0;
        }
    }
    else
    {
        if (!group_add_members($group, $user))
        {
            $DEBUG && print STDERR "group_add_members failed\n";
            return 0;
        }
    }

    return 1;
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

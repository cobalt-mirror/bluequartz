#!/usr/bin/perl -I/usr/sausalito/perl
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: root_access.pl,v 1.4 2001/11/20 18:47:36 pbaltz Exp $
#
# add or remove a user from the wheel group if they are allowed root access
# via the su program.  also create a "root" account for them

use CCE;
use Base::Group qw(group_add_members group_rem_members);
use Base::User qw(system_useradd usermod userdel);

my $cce = new CCE('Domain' => 'base-user');
$cce->connectfd();

my $root_prefix = 'root-';
my $user;
if ($cce->event_is_destroy())
{
    $user = $cce->event_old();
}
else
{
    $user = $cce->event_object();
}

if (!$user->{name})
{
    $cce->bye('DEFER');
    exit(0);
}

my ($ok, $root_access) = $cce->get($cce->event_oid(), 'RootAccess');

if (!$ok)
{
    $cce->bye('FAIL', 'cantGetRootAccess', { 'name' => $user->{name} });
    exit(1);
}

if (!$root_access->{enabled} || $cce->event_is_destroy())
{
    my $ret = group_rem_members('wheel', $user->{name});
    
    if (!$ret)
    {
        $cce->bye('FAIL', 'cantDisableRootAccess', { 'name' => $user->{name} });
        exit(1);
    }

    # destroy their root account
    ($ret) = userdel(0, $root_prefix . $user->{name});

    # if this fails it is really bad, don't let the user be destroyed
    # or there will be a back door account
    if (!$ret)
    {
        $cce->bye('FAIL', 'cantDeleteAlterRoot', 
                { 'root' => ($root_prefix . $user->{name}) });
        exit(1);
    }

    # clean up root email alias
    my ($alias) = $cce->find('ProtectedEmailAlias',
                            {
                                'action' => $user->{name},
                                'alias' => ($root_prefix . $user->{name}),
                                'fqdn' => ''
                                });
    if ($alias)
    {
        ($ok) = $cce->destroy($alias);
        if (!$ok)
        {
            $cce->bye('FAIL');
            exit(1);
        }
    }
}
elsif ($root_access->{enabled})
{
    my $ret = group_add_members('wheel', $user->{name});

    if (!$ret)
    {
        $cce->bye('FAIL', 'cantEnableRootAccess', { 'name' => $user->{name} });
        exit(1);
    }

    # create the alterroot account if necessary
    my (@pwent) = getpwnam($root_prefix . $user->{name});
    if ($pwent[0] ne $root_prefix . $user->{name})
    {
        my $alterroot = {
                            'name' => ($root_prefix . $user->{name}),
                            'uid' => 0,
                            'group' => 'root',
                            'homedir' => '/root',
                            'shell' => '/bin/bash',
                            'password' => $user->{md5_password},
                            'dont_create_home' => 1
                        };

        my @ret = system_useradd($alterroot);

        if (!$ret[0])
        {
            $cce->bye('FAIL', 'cantCreateAlterRoot',
                    { 'root' => ($root_prefix . $user->{name}) });
            exit(1);
        }

    }
    else
    {   # update user password
        my ($ret) = usermod({
                            'name' => ($root_prefix . $user->{name}),
                            'password' => $user->{md5_password}
                        });

        if (!$ret)
        {
            $cce->bye('FAIL', 'cantUpdateAlterRoot',
                    { 
                        'root' => ($root_prefix . $user->{name}),
                        'name' => $user->{name}
                    });
            exit(1);
        }
    }

    # make sure the root-account gets email sent to the user's account
    my ($alias) = $cce->find('ProtectedEmailAlias',
                            {
                                'action' => $user->{name},
                                'alias' => ($root_prefix . $user->{name}),
                                'fqdn' => ''
                                });
    if (!$alias)
    {
        ($ok) = $cce->create('ProtectedEmailAlias',
                        { 
                            'action' => $user->{name}, 
                            'alias' => ($root_prefix . $user->{name}),
                            'local_alias' => 1
                            });
        if (!$ok)
        {
            $cce->bye('FAIL');
            exit(1);
        }
    }
} # end elsif($root_access->{enabled})

$cce->bye('SUCCESS');
exit(0);
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

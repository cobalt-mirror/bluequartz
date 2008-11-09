#!/usr/bin/perl -w -I/usr/sausalito/perl/ -I/usr/sausalito/handlers/base/email
# $Id: personal.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# translate the User.Email.aliases property into EmailAlias
# objects.  Temporary fix since I don't feel like rewriting the UI tonight.

use CCE;
use Email;
use Sauce::Util;

my $fail = 0;

my $cce = new CCE ( Namespace => "Email",
                    Domain => 'base-email' );
$cce->connectfd();

my $oid = $cce->event_oid();
my $old = $cce->event_old();
my $new = $cce->event_new();
my $obj = $cce->event_object();

my ($ok, $user_obj, $user_old, $user_new) = $cce->get($oid);

if ($cce->event_is_create() && !$user_obj->{name})
{
    $cce->bye('DEFER');
    exit(0);
}

# so to make this easy, no longer use aliases
# just put everything in virtuser, appending the system fqdn if the user
# is not part of a site, and appending the site fqdn if the user is a member
# of a site
if ($cce->event_is_destroy())
{
    for my $alias ($cce->scalar_to_array($old->{aliases}))
    {
        my ($aoid) = $cce->find('EmailAlias',
                        {
                            'alias' => $alias,
                            'action' => $user_old->{name},
                            'site' => $user_old->{site}
                        });
        if ($aoid)
        {
            $cce->destroy($aoid);
        }
    }
}
else
{
    my $fqdn = '';
    my $vsite = {};
    if ($user_obj->{site})
    {
        my ($vsoid) = $cce->find('Vsite', { 'name' => $user_obj->{site} });
        ($ok, $vsite) = $cce->get($vsoid);

        if (!$ok)
        {
            $cce->bye('FAIL', 'cantReadVsite', { 'name' => $user_obj->{site} });
            exit(1);
        }
        $fqdn = $vsite->{fqdn};
    }

    # info to use when finding aliases
    my $find_action = (exists($user_new->{name}) ? $user_old->{name} :
                                                        $user_obj->{name});
    my $find_site = (exists($user_new->{site}) ? $user_old->{site} :
                                                        $user_obj->{site});
    my %new_aliases = map { $_ => 1 } $cce->scalar_to_array($obj->{aliases});

    for my $alias (keys(%new_aliases))
    {
        # sanity check
        if (!$alias) { next; }
        
        if (!$cce->event_is_create())
        {
            ($oid) = $cce->find('EmailAlias',
                            {
                                'alias' => $alias,
                                'action' => $find_action,
                                'site' => $find_site
                            });
            if ($oid)
            {
                ($ok) = $cce->set($oid, '',
                                {
                                    'action' => $user_obj->{name},
                                    'site' => $user_obj->{site},
                                    'alias' => $alias,
                                    'fqdn' => $fqdn
                                });
                if (!$ok)
                {
                    $cce->bye('FAIL', '[[base-email.cantUpdateAlias]]');
                    exit(1);
                }

                # go to next alias
                next;
            }
        }

        # need to create the alias if we got here
        ($ok) = $cce->create('EmailAlias',
                        {
                            'alias' => $alias,
                            'action' => $user_obj->{name},
                            'site' => $user_obj->{site},
                            'fqdn' => $fqdn
                        });
        if (!$ok)
        {
            $cce->bye('FAIL', '[[base-email.cantCreateAlias]]');
            exit(1);
        }
    }

    # delete old aliases as necessary
    my @old_aliases = $cce->scalar_to_array($old->{aliases});

    for my $alias (@old_aliases)
    {
        if (!exists($new_aliases{$alias}))
        {
            ($oid) = $cce->find('EmailAlias',
                                {
                                    'action' => $find_action,
                                    'alias' => $alias,
                                    'site' => $find_site
                                });
            if ($oid)
            {
                $cce->destroy($oid);
            }
        }
    }
}
    
$cce->bye('SUCCESS');
exit 0;
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

#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email
# $Id: handle_alias.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc.  All rights reserved.
#
# handles creation, modification, and deletion of Email Aliases.
# ie. maintains the /etc/mail/virtusertable file.

my $DEBUG = 0;

use CCE; 
use Sauce::Util; 
use Email;

if ($DEBUG)
{
    use Data::Dumper;
}

my $cce = new CCE( 'Domain' => 'base-email' );
$cce->connectfd();

my $obj = $cce->event_object();
my $new = $cce->event_new();
my $old = $cce->event_old();

# get the fqdn if necessary
my $sys_fqdn = '';
if (!$old->{fqdn} || !$obj->{fqdn})
{
    my ($soid) = $cce->find('System');
    my ($ok, $sys) = $cce->get($soid);
    if (!$ok)
    {
        $cce->bye('FAIL', '[[base-email.cantReadSystem]]');
        exit(1);
    }

    $sys_fqdn = $sys->{hostname} . '.' . $sys->{domainname};
}

# build up alias hash
my $alias = {};

$DEBUG && print STDERR Dumper($new, $old, $obj);

# check if the alias or fqdn changed, so the old value can
# be removed
if ($cce->event_is_destroy())
{
    $key = $old->{alias} . '@' . ($old->{fqdn} ? $old->{fqdn} : $sys_fqdn);
    $alias->{$key} = '';
}
elsif (exists($new->{alias}) || exists($new->{fqdn}))
{
    $key = $obj->{alias} . '@' . ($obj->{fqdn} ? $obj->{fqdn} : $sys_fqdn);
    $alias->{$key} = $obj->{action};

    # delete old alias if this is not a create
    if (!$cce->event_is_create())
    {
        # handling both properties, so only include old information
        # if that property changed, else use current information
        $key = exists($new->{alias}) ? $old->{alias} : $obj->{alias};
        
        # substitute system fqdn if old fqdn is blank
        my $old_fqdn = $old->{fqdn} ? $old->{fqdn} : $sys_fqdn;
        if (exists($new->{fqdn}))
        {
            $key .= '@' . $old_fqdn;
        }
        else
        {
            # fqdn not changing add the correct fqdn
            $key .= '@' . ($obj->{fqdn} ? $obj->{fqdn} : $sys_fqdn);
        }

        # sanity check to see if the new entry is the same as the
        # old entry.  if that's the case, don't blank out the entry
        if (!exists($alias->{$key}))
        {
            $alias->{$key} = '';
        }
    }
}
elsif (exists($new->{action}))
{
    $key = $obj->{alias} . '@' . ($obj->{fqdn} ? $obj->{fqdn} : $sys_fqdn);
    $alias->{$key} = $obj->{action};
}

$DEBUG && print STDERR 'BIG FAT MARKER', Dumper($alias);

# rollback to recreate db to avoid having to copy the db file at all
Sauce::Util::addrollbackcommand("/usr/bin/makemap hash $Email::VIRTUSER < " .
				"$Email::VIRTUSER >/dev/null 2>&1");
my $ok = Sauce::Util::editfile(
            $Email::VIRTUSER,
            *Sauce::Util::hash_edit_function,
            '#',
            { 're' => '\s*', 'val' => "\t" }, 
            $alias);

$DEBUG && warn("editfile returned $ok");

if (!$ok)
{
    $cce->bye('FAIL', 'cantEditFile', { 'file' => $Email::VIRTUSER });
    exit(1);
}

# if the local_alias field is set, add/remove in aliases as well 
my $local_alias = {};
if (($cce->event_is_destroy() && $old->{local_alias}) ||
    ($old->{local_alias} && ($old->{local_alias} != $obj->{local_alias})))
{
    $local_alias = {
            'old_alias' => $old->{alias},
            'old_target' => $old->{action}
            };
}
elsif ($obj->{local_alias})
{
    $local_alias = {
            'old_alias' => $old->{alias},
            'old_target' => $old->{action},
            'new_alias' => $obj->{alias},
            'new_target' => $obj->{action}
            };
}

$DEBUG && print STDERR Dumper($local_alias);
if (scalar(keys(%$local_alias))&&
    !Sauce::Util::editfile(&Email::Aliases, *edit_access, $local_alias))
{
    if ($local_alias->{new_alias})
    {
        $cce->bye('FAIL', 'cantCreateLocalAlias',
                { 
                    'alias' => $local_alias->{new_alias},
                    'target' => $local_alias->{new_target}
                });
    }
    else
    {
        $cce->bye('FAIL', 'cantRemoveLocalAlias',
                {
                    'alias' => $local_alias->{old_alias},
                    'target' => $local_alias->{old_target}
                });
    }
    exit(1);
}

$DEBUG && warn('returning success');
$cce->bye('SUCCESS');
exit(0);

sub edit_access
{
    my ($in, $out, $alias) = @_;

    $DEBUG && print STDERR "in edit_access\n";
    $DEBUG && print STDERR Dumper($alias);

    while (<$in>)
    {
        # skip the old entry we're searching for
        if (/^$alias->{old_alias}:\s+$alias->{old_target}\s*$/)
        {
            next;
        }

        print $out $_;
    }

    # if there is a new alias print it out
    if ($alias->{new_alias})
    {
        print $out $alias->{new_alias}, ":\t", $alias->{new_target}, "\n";
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

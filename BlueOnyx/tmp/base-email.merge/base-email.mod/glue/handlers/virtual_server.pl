#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: virtual_server.pl 259 2004-01-03 06:28:40Z shibuya $
#
# handles email server virtualization for Vsites
# add hostname, ip address, and mail aliases to appropriate sendmail config
# files

use CCE;
use Email;

my $cce = new CCE;
$cce->connectfd();

my $vsite = $cce->event_object();
my $vsite_old = $cce->event_old();
my $vsite_new = $cce->event_new();

# Deviation from RaQ 1-4/XTR: Do not open local subnet relays
#
# my %access_hash = ();
# 
# if ($cce->event_is_destroy() || 
#         (exists($vsite_new->{ipaddr}) && $vsite_old->{ipaddr}))
# {
#     # figure out if another site uses this ip address also
#     my @sites = $cce->find('Vsite', { 'ipaddr' => $vsite_old->{ipaddr} });
# 
#     if (!scalar(@sites))
#     {
#         # remove this ip from the relay list
#         $access_hash{$vsite_old->{ipaddr}} = 0;
#     }
# }
# 
# if (!$cce->event_is_destroy())
# {
#     $access_hash{$vsite->{ipaddr}} = 1;
# }
# 
# if (!Sauce::Util::editfile(&Email::AccessDB, *edit_access, \%access_hash))
# {
#     $cce->bye('FAIL', '[[base-email.cantEditAccess]]');
#     exit(1);
# }

# now handle local-host-names file
my %local_hosts;

# always remove "old" fqdn, it will get added back in below
# if necessary
if ($vsite_old->{fqdn})
{
    $local_hosts{$vsite_old->{fqdn}} = 0;
}

# add the site hostname
if (!$vsite->{suspend} && $vsite->{fqdn})
{
    $local_hosts{$vsite->{fqdn}} = 1;
}

# mark all old aliases for removal first, aliases
# not being removed get added in again below
if ($vsite_old->{mailAliases})
{
    for my $alias ($cce->scalar_to_array($vsite_old->{mailAliases}))
    {
        $local_hosts{$alias} = 0;
    }
}

# mark new aliases for addition
if (!$vsite->{suspend} && $vsite->{mailAliases})
{
    for my $alias ($cce->scalar_to_array($vsite->{mailAliases}))
    {
        $local_hosts{$alias} = 1;
    }
}

# edit the file
if (!Sauce::Util::editfile(&Email::SendmailCW, 
        *edit_local_hosts, \%local_hosts))
{
    $cce->bye('FAIL', '[[base-email.cantEditLocalHosts]]');
    exit(1);
}

$cce->bye('SUCCESS');
exit(0);


sub edit_access
{
    my ($in, $out, $access_hash) = @_;

    my $begin = "# BEGIN VSite relays (do not edit anything between BEGIN and END)";
    my $end = "# END VSite relays (do not edit anything between BEGIN and END)";

    my $found = 0;
    
    while(<$in>)
    {
        if (/^# BEGIN VSite relays/)
        {
            # in our section
            $found = 1;
            print $out $begin, "\n";

            while(<$in>)
            {
                if (/^# END VSite relays/)
                {
                    last;
                }
                
                /^([^\s]+)/;
                my $relay = $1;
                if ($access_hash->{$relay} || !exists($access_hash->{$relay}))
                { 
                    # either supposed to be there or not ours
                    print $out $_;
                    delete($access_hash->{$relay});
                }
            } # end of while loop for the owned section

            # print anything left that's not there
            for my $relay (keys(%$access_hash))
            {
                if ($access_hash->{$relay})
                {
                    print $out "$relay\tRELAY\n";
                }
            }
            
            print $out $end, "\n";
        }
        else
        {
            # not in my section
            print $out $_;

            # make sure no duplicates get added
            /^([^\s]+)/;
            delete($access_hash->{$1});
        }
    }  # end of while loop to process the whole file

    # make sure we printed our section
    if (!$found)
    {
        print $out $begin, "\n";
        for my $relay (keys(%$access_hash))
        {
            if ($access_hash->{$relay})
            {
                print $out "$relay\tRELAY\n";
            }
        }
        print $out $end, "\n";
    }

    return 1;
}

sub edit_local_hosts
{
    my ($in, $out, $local_hosts) = @_;

    my $found = 0;

    my $begin = "# BEGIN VSite Hosts (don't edit between BEGIN and END)";
    my $end = "# END VSite Hosts (don't edit between BEGIN and END)";
    while (<$in>)
    {
        if (/^# BEGIN VSite Hosts/)
        {
            $found = 1;
            print $out $begin, "\n";
            while (<$in>)
            {
                if (/^# END VSite Hosts/)
                {
                    last;
                }
                
                /^(.+)$/;
                my $fqdn = $1;
                if ($local_hosts->{$fqdn} || !exists($local_hosts->{$fqdn}))
                {
                    print $out $_;
                    delete($local_hosts->{$fqdn});
                }
            }

            # print anything that wasn't printed yet
            for my $thing (keys(%$local_hosts))
            {
                if ($thing && $local_hosts->{$thing})
                {
                    print $out $thing, "\n";
                }
            }

            print $out $end, "\n";
        }
        else
        {
            print $out $_;
        }
    }

    if (!$found)
    {
        print $out $begin, "\n";
        for my $thing (keys(%$local_hosts))
        {
            if ($thing && $local_hosts->{$thing})
            {
                print $out $thing, "\n";
            }
        }

        print $out $end, "\n";
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

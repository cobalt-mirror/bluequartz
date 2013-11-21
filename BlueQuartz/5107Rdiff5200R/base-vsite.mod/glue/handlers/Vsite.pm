#!/usr/bin/perl -w -I/usr/sausalito/perl
# Vsite.pm: Functions for virtual hosting
#
# $Id: Vsite.pm,v 1.20 2001/12/04 07:28:44 pbaltz Exp $
#
# Written by
#   Will DeHaan (will@cobaltnet.com)
#   Brion Moss (brion@cobaltnet.com, brion@nda.com)
#   Duncan Laurie <duncan@cobaltnet.com>
#   Harris Vaegan-Lloyd <harris@cobaltnet.com>
#
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#

package Vsite;

require Exporter;

use vars qw(@ISA @EXPORT @EXPORT_OK);

@ISA    = qw[ Exporter ];
@EXPORT = qw[
        &vsite_disable &vsite_enable

        &vsite_get_xml 
        
        $Webdir
         $Sites_AdminGroup
        %DefaultAliases

        vsite_add_network_interface vsite_del_network_interface
    ];

use integer;
use Sauce::Config;
use File::Copy;
use Base::HomeDir qw(homedir_get_group_dir);

use vars qw(
        $DEFAULT_INTERFACE
        $Webdir
        $Sites_AdminGroup
        
        %DefaultAliases);

# globals used here
if (! -f "/proc/user_beancounters") {
	$DEFAULT_INTERFACE = 'eth0';
}
else {
	$DEFAULT_INTERFACE = 'venet0';
}

# hard-coded configuration options
$Webdir = Sauce::Config::webdir();
$Sites_AdminGroup = 'site-adm';

# Mail
%DefaultAliases =     (
                    "MAILER-DAEMON",   1,
                    "sys",             1,
                    "nobody",          1,
                    "root",            1,
                    'apache',          1,
                    'admin',           1
                    );

### Removed;
#                    "postmaster",      1,
#                    'admin',           1,



sub vsite_disable
# disable a virtual site
#   1. disable all virtual site users
#   2. chmod 0000 site home directory
# arguments: vsite groupname
# return value: std
{
    my ($cce, $group) = @_;
    
    # make sure group isn't blank or we disable /home/sites below
    return 0 unless ($group);

    my @users = $cce->find('User', { 'site' => $group });
    
    foreach (@users)
    {
        $cce->set($_, '', { 'enabled' => 0 });    
    }
    
    my $mode = oct '0000';
    my $dir = homedir_get_group_dir($group);
    
    Sauce::Util::chmodfile($mode, $dir);
    
    return 1;
}

sub vsite_enable
# enable a virtual site
#   1. re-enable all virtual site users
#   2. chmod site home directory back to normal
# arguments: vsite groupname
# return value: std
{
    my ($cce, $group) = @_;

    return 0 unless ($group);

    my @users = $cce->find('User', { 'site' => $group });

    foreach (@users)
    {
        $cce->set($_, '', { 'enabled' => 1 });
    }

    my $mode = oct '2775';
    my $dir = homedir_get_group_dir($group);

    Sauce::Util::chmodfile($mode, $dir);

    return 1;
}

sub vsite_add_network_interface
{
    my $cce = shift;
    my $ipaddr = shift;
    my $user = shift;
    my $device = shift;

    $device ||= $DEFAULT_INTERFACE;

    my ($net_if) = $cce->find('Network', 
						{ 
							'ipaddr' => $ipaddr,
							'enabled' => 1
						});
    if (not $net_if)
    {
        # need to create a new network interface
        my ($eth) = $cce->find("Network", { 'device' => $device });
        (my $ok, $eth) = $cce->get($eth);

        for (my $alias = 0;; $alias++)
        {
            ($net_if) = $cce->find("Network", { 'device' => ($eth->{device} . ":$alias") });
            if (not $net_if)
            {
                ($ok) = $cce->create("Network",
                    { 'device' => ($eth->{device} . ":$alias"),
                      'ipaddr' => $ipaddr,
                      'netmask' => $eth->{netmask},
                      'enabled' => 1 });

                if (not $ok)
                {
                    $cce->bye('FAIL', '[[base-vsite.cantCreateNetwork]]');
                    exit(1);
                }
                
                last;
            }
        }
    }

    return 1;
}

# this will bring down a Network interface that is no longer in use
# as long as it is an alias
sub vsite_del_network_interface
{
    my ($cce, $ipaddr) = @_;

    # only destroy the interface if not in use
    my @vsite_oids = $cce->find('Vsite', { 'ipaddr' => $ipaddr });
    if (scalar(@vsite_oids) == 0)
    {
        # destroy the interface, but only if it is not real
        my ($net_oid) = $cce->find('Network', 
                    { 'ipaddr' => $ipaddr, 'real' => 0 });
        if ($net_oid)
        {
            $cce->set($net_oid, '', { 'enabled' => 0 });
            $cce->destroy($net_oid);
        }
    }

    return 1;
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

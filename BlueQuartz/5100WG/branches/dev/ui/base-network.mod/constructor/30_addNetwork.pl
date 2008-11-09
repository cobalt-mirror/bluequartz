#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: 30_addNetwork.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

# author: jmayer@cobalt.com

# this needs use lib because it isn't a handler
use lib qw(/usr/sausalito/handlers/base/network);
use CCE;
use Network qw(find_eth_ifaces);

my $DEBUG = 0;

my $errors = 0;

my $cce = new CCE;
$cce->connectuds();

# get all the names of all current interfaces and aliases
my @devices = find_eth_ifaces();

# find_eth_ifaces failed if @devices is empty
if (!scalar(@devices))
{
    $cce->bye('FAIL', '[[base-network.noEthIfsFound]]');
    exit(1);
}

# for each inteface or alias
# get information from ifconfig
# make sure a Network object exists in CCE
# if necessary, destroy duplicates in CCE
for my $device (@devices) 
{
    $DEBUG && print STDERR "current device: $device\n";

    # parse ifconfig
    my $ip = ''; 
    my $nm = '';
    my $mac = '';
    my $data = join('', `$Network::IFCONFIG $device`);
    if ($data =~ m/^$device/s) 
    {
        if ($data =~ m/inet addr:\s*(\S+)/s) 
        {
            $ip = $1;
        }
        if ($data =~ m/Mask:\s*(\S+)/s) 
        {
            $nm = $1;
        }
    }


    my @oids = $cce->find('Network', { 'device' => $device } );

    my $oid = 0;
    if (scalar(@oids) == 1) 
    {
        $oid = $oids[0];
    } 
    elsif (scalar(@oids) == 0) 
    {
        # no Network object for this device, so create one
        my $obj = { 'device' => $device };
        if ($device !~ /:\d+$/)
        {
            # this is a real interface
            $obj->{real} = 1;
        }

	my($ok, $system) = $cce->get( ($cce->find('System'))[0] );
	if (($device eq 'eth0') && ($system->{productBuild} =~ /\d+WG/)) 
	{
		$obj->{bootproto} = 'dhcp';
		unlink('/etc/NET-CONFIG');
	}
	else
	{
		$obj->{bootproto} = 'none';
	}

        # if according to ifconfig this interface has an address and
        # netmask, set those and mark it as enabled
        if ($ip && $nm) 
        {
            $DEBUG && print STDERR "Using old config: $ip/$nm\n";
            $obj->{ipaddr} = $ip;
            $obj->{netmask} = $nm;
            $obj->{enabled} = 1;
        } 
        else 
        {
            # check the config file to see if this interface should
            # be enabled on boot
            $obj->{enabled} = &onboot($device);
        }

        my ($success) = $cce->create('Network', $obj);
        if (!$success) 
        {
            $DEBUG && print STDERR "Failed to create Network object for $device\n";
            $errors++;
        } 
        else 
        {
            $DEBUG && print STDERR "Created Network object for $device.\n";
        }
        # turn on NAT and IPForwarding
        hack_on_nat();

        # update MAC address
        # don't update mac address
        # $oid = $cce->oid();
    } 
    else 
    {
        # destroy extras
        shift(@oids);
        for my $network (@oids) 
        {
            my ($success) = $cce->destroy($network);
            if ($success) 
            {
                $DEBUG && print STDERR "Destroyed surplus Network.$device object $network\n";
            } 
            else 
            {
                $DEBUG && print STDERR "Failed to destroy surplus Network.$device object $network\n";
                $errors++;
            }
        }
    }
   
    # make sure the real flag is properly set
    if ($oid && $device !~ /:\d+$/) 
    {
        $cce->set($oid, '', { 'real' => 1 });
    }
    elsif ($oid)
    {
        $cce->set($oid, '', { 'real' => 0 });
    }
}

$cce->bye();
exit($errors);

sub hack_on_nat
{
    my ($oid) = $cce->find('System');
    if ($oid) 
    {
        my ($ok) = $cce->set($oid, 'Network', 
                        { 
                            'nat' => '1',
                            'ipForwarding' => '1',
                        });

        if (not $ok)
        {
            $cce->warn('[[base-network.cantTurnOnNat]]');
        }

        # debugging:
        if ($DEBUG)
        {
            system('/bin/cp',
                '/etc/sysconfig/network',
                '/tmp/.network.' . scalar(time()) );
        }
    }
}
    
# check if ifcfg has onboot flag set to yes
# if so returns 1, else returns 0
sub onboot
{
    my $device = shift;

    my $onboot = 0;

    if(open(IFCFG, "$Network::NET_SCRIPTS_DIR/ifcfg-$device")) 
    {
        while(<IFCFG>) 
        {
            if(/^ONBOOT=(\w+)$/i) 
            {
                if ($1 =~ /yes/i) 
                {
                    $onboot = 1;
                }
            }
        }

        close IFCFG;
    }

    return $onboot;
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

#!/usr/bin/perl -I/usr/sausalito/perl -I.
#
# $Id: change_route.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# Keeps the routing table on a running machine up to date with
# as little disturbance as possible.
#
# This script:
#   1) parses the existing routing table
#   2) computes a new "target" routing table
#   3) calculates the differences between the two routing tables, and
#   4) runs a minimum number of route commands to change from old
#      routing to new.
#
# routes are gathered from:
#   network interfaces (ie. the routes that ifconfig creates)
#   the system default gateway
#   explicit Route objects.
#
# Note: the "-c" option can be used to run change_route.pl as a standalone
# command-line tool, rather than a handler.
#
# FIXME: for the next product, change_route.pl should probably be update
# to get info from a config file, rather than from CCE.  Making change_route
# more independent of CCE would be more better.  -jm.

use lib qw(/usr/sausalito/perl /usr/sausalito/handlers/base/network);

use FileHandle;
use Data::Dumper;
use Sauce::Config;
use Sauce::Util;
use CCE;
use Getopt::Long;
use Network qw(find_eth_ifaces);

my $CMDLINE = 0;
my $DEBUG = 0;
GetOptions('cmdline', \$CMDLINE, 'debug', \$DEBUG);

$DEBUG && print STDERR "$0: starting.\n";

my $cce = new CCE(Domain => 'base-network');

if ($CMDLINE) 
{
    $cce->connectuds();
} 
else 
{
    $cce->connectfd();
}

$DEBUG && print STDERR "event oid is ", $cce->event_oid(), "\n";

# 1. extract list of current routes:
my $cur_routes = {};
{
    # read the current kernel routing table
    my @data = `$Network::ROUTE -n`;

    # skip headers
    shift(@data); 
    shift(@data);
    
    # process the current route
    while (my $route = shift(@data)) 
    {
        # split up columns
        my @a = split(/\s+/, $route);
        
        # treat default gw special:
        if ($a[0] eq '0.0.0.0' && $a[2] eq '0.0.0.0') 
        {
            # dont' specify a device for the default gateway, because it's the default
            $a[7] = '';
        }
        
        my $key = $a[0] . '/' . $a[2];
        if (defined($cur_routes->{$key})) { $key .= '/'; }
        $cur_routes->{$key} = "$a[1]/$a[7]/$a[3]";
    }
};

$DEBUG && print STDERR "$0: current routing table:\n",Dumper($cur_routes),"\n";

# 2. compute set of desired routes
my $new_routes = {};

# the default Network routes:
my (@networks) = $cce->find('Network');
my @nets = ();
for my $network_oid (@networks) 
{
    my ($ok, $net) = $cce->get($network_oid);
    
    # make sure this interface is up
    next if (!defined($net->{enabled}) || !$net->{enabled} 
            || !defined($net->{ipaddr})  || ($net->{ipaddr} eq '')
            || !defined($net->{netmask}) || ($net->{netmask} eq ''));

	# don't use aliases as devices for routes, not necessary, and it
	# ends up breaking things sometimes
	$net->{device} =~ s/^(eth\d+):*\d*$/$1/;

    # add this interface's ip address to the routes to add
    push (@nets, $net);
    $new_routes->{$net->{ipaddr} . '/255.255.255.255'} =
                                    '0.0.0.0/' . $net->{device} . '/UH';
  
    # add the network this interface should listen on to the routes to add
    $new_routes->{ip_and_ip($net->{ipaddr}, $net->{netmask}) . '/' . $net->{netmask}} =
                                    '0.0.0.0/' . $net->{device} . '/U';
}

# err, override the above info with info from ifconfig, if available:
my @ifaces = find_eth_ifaces();
for my $interface (@ifaces)
{
    my $data = `$Network::IFCONFIG $interface 2>/dev/null`;
    
    # check if the interface is up and add routes to the new_routes hash as necessary
    if ($data =~ /^$interface/m && $data =~ /^\s+UP/m) 
    {
        if ($data =~ m/^\s+inet addr:(\S+).*?Mask:(\S+)/m) 
        {
            my ($ipaddr, $netmask) = ($1, $2);
            $DEBUG && print STDERR "read from ifconfig: $interface $ipaddr/$netmask\n";
			# don't use aliases for device names
			$interface =~ s/^(eth\d+):*\d*$/$1/;

            # add a host entry for this interface
            $new_routes->{$ipaddr . '/255.255.255.255'} =
                                    '0.0.0.0/' . $interface . '/UH';

            # add a network entry for this interface
            $new_routes->{ ip_and_ip($ipaddr, $netmask) . '/' . $netmask } =
                                    '0.0.0.0/' . $interface . '/U';
        }
    } 
    else 
    {
        $DEBUG && print STDERR "$interface is not yet up.\n";
    }
}

# handle adding the default gateway to the routes
my ($system_oid) = $cce->find('System');
$DEBUG && print STDERR "found system object with oid $system_oid\n";
my ($ok, $system) = $cce->get($system_oid);
if ($system->{gateway}) 
{
    my $default_dev = which_device($system->{gateway}, @nets);
    #$new_routes->{"0.0.0.0/0.0.0.0"} = $system->{gateway} . "/${default_dev}/UG";
    # don't associate the default gw with a particular device:
    $new_routes->{'0.0.0.0/0.0.0.0'} = $system->{gateway} . '//UG';
}

# handle the the loopback route (huh, now I'm sure this doesn't need to be here)
{
    # is the loopback interface up?
    my $d = join('', `$Network::IFCONFIG lo 2>/dev/null`);
    if ($d =~ m/UP LOOPBACK RUNNING/) 
    {
        # make sure it has a route:
        $new_routes->{'127.0.0.0/255.0.0.0'} = '0.0.0.0/lo/U';
    }
}

# additional user-entered static routes
my (@routes) = $cce->find('Route');
$DEBUG && print STDERR "routes: @routes\n";

for my $route_oid (@routes) 
{
    my ($ok, $route) = $cce->get($route_oid);
    my $gateway = $route->{gateway};
    if (!$gateway) { $gateway = '0.0.0.0'; }

    # what device for this route?
    my $dev = $route->{device};
    if (!$dev) { $dev = which_device($gateway, @nets); }
    if (!$dev) 
    {
        $cce->warn('gateway-not-reachable', { 'gateway' => $gateway } );
        $DEBUG && print STDERR "$0: gateway not reachable: $gateway\n";
        next;
    }
    
    # make sure the gateway for this route is not in the subnet to which we are
    # trying to route
    if ((ip2bin($route->{target}) & ip2bin($route->{netmask})) 
            == (ip2bin($route->{gateway}) & ip2bin($route->{netmask}))) 
    {
        $cce->warn('gatewayWithinOwnTargetSubnet', 
            { 
                gateway => $route->{gateway},
                target => bin2ip(ip2bin($route->{netmask}) & ip2bin($route->{target})) 
            });
        $cce->bye('FAIL');
        exit(1);
    }

    my $flag = 'U'; 
    if ($gateway && ($gateway ne '0.0.0.0')) 
    {
        $flag .= 'G'; 
    }

    # add the static route to our routing table
    $new_routes->{ip_and_ip($route->{target}, $route->{netmask}) . '/' . $route->{netmask}}
                = ${gateway} . '/' . $dev . '/' . $flag;
}

$DEBUG && print STDERR "$0: desired routes:\n",Dumper($new_routes),"\n";

# 3. compute the differences
my %del = %$cur_routes; # list of routes to delete
my %add = %$new_routes; # list of routes to add

# cancel out overlap:
my ($key, $val);

# remove routes from the delete hash that are in the new routing
# table still, so they don't need to be removed and added again
while (($key,$val) = each %$new_routes) 
{
    if (defined($del{$key}) && ($del{$key} eq $val)) 
    { 
        delete $del{$key};
    }
}

# don't re-add routes that already exist
while (($key,$val) = each %$cur_routes) 
{
    if (defined($add{$key}) && ($add{$key} eq $val)) 
    { 
        delete $add{$key};
    }
}


# 4. sync the routing table.
my $errors = 0;

# delete old routes
while (($key, $val) = each %del) 
{
    my ($target, $mask) = split(/\//, $key);
    my ($gateway, $device, $flags) = split(/\//, $val);
    my @opts;

    # setup the correct arguments needed by the route command
    if ($flags =~ m/H/) 
    {
        @opts = ('-host', $target, $device);
    } 
    else 
    {
        @opts = ('-net', $target, 'netmask', $mask, $device);
    }
    if (!$device) { pop(@opts); }
    
    $DEBUG && print STDERR "$0: route del @opts\n";
    Sauce::Util::addrollbackcommand($Network::ROUTE, 'add', @opts);
    system($Network::ROUTE, 'del', @opts);
    if ($?) 
    { 
        $errors++; 
        $DEBUG && print STDERR "FAILED($?): route del @opts\n";
    }
}

# add new routes:
my @bad_routes = ();
{
    my @routes = ();
    my @defered = ();
    my $last_defered = -1;
    while (($key, $val) = each %add) 
    {
        push (@routes, $key . '/' . $val);
    }
    while (@routes) 
    {
        $DEBUG && print STDERR "myroutes: \n\t", join("\n\t", @routes), "\n";
        while (@routes) 
        {
            my ($val) = shift (@routes);
            my ($target, $mask, $gateway, $device, $flags) = split(/\//, $val);
            my @opts;
            if ($flags =~ m/H/) 
            {
                @opts = ('-host', $target);
            } 
            else 
            {
                if ($mask eq '0.0.0.0') 
                {
                    @opts = ('default');
                } 
                else 
                {
                    @opts = ('-net', $target, 'netmask', $mask);
                }
            }
            if ($flags =~ m/G/) 
            {
                push (@opts, 'gw', $gateway);
            }
            if ($device) { push (@opts, $device); }
            $DEBUG && print STDERR "$0: route add @opts\n";
            Sauce::Util::addrollbackcommand($Network::ROUTE, 'del', @opts);
            system($Network::ROUTE, 'add', @opts);
            if ($?) 
            { 
                $DEBUG && print STDERR "\tfailed: defering.\n";
                push (@defered, $val);
            }
        }
        # check for deadlock:
        if (($#defered+1) == $last_defered) 
        {
            # deadlock, we can not add these routes.
            $errors++;
            $DEBUG && print STDERR "No route: \n\t", join("\n\t", @defered), "\n";
            @bad_routes = @defered;
            @defered = ();
        }
        # set up next pass
        $last_defered = 1 + $#defered;
        @routes = @defered;
        @defered = ();
    }
};

if ($errors) 
{ 
    $DEBUG && print STDERR "$0: $errors errors!\n"; 
   
    for my $route (@bad_routes)
    {
        my @info = split('/', $route);
        # check if the bad route is for the default gateway
		if ($info[0] eq '0.0.0.0' && $info[3] eq '')
		{
			# if the default gateway route can't be added then bail
			$cce->warn('badDefaultGateway', { 'gateway' => $info[2] });
		}
		else
		{
			$cce->warn('invalid-route', 
                    { 
                        'network' => $info[0],
                        'gateway' => $info[2],
                        'device' => "[[base-network.$info[3]]]"
                    });
		}
    }
    
    # write debugging output:
    if ($DEBUG)
    {
        my $fh = new FileHandle('>/tmp/.change_route.debug.' . scalar(time()));
        if ($fh) 
        {
            print $fh 'cur_routes = ', Dumper($cur_routes), "\n\n";
            print $fh 'new_routes = ', Dumper($new_routes), "\n\n";
            print $fh 'del = ', Dumper(\%del), "\n\n";
            print $fh 'add = ', Dumper(\%add), "\n\n";
            print $fh 'bad_routes = ', Dumper(\@bad_routes), "\n\n";
            print $fh "current state: \n", `$Network::ROUTE -n`, "\n\n";
            $fh->close();
        }
    }

    ## don't fail: there's a reason for this.
    ## think of a system where many routes are
    ## being added and destroyed.  Sometimes,
    ## destroying one route will make another route
    ## invalid.  But, destroying that route must
    ## still be legal!  We need to rethink a few
    ## things here: perhaps the current state of the
    ## routing table should be used to repopulate CCE
    ## after every route change.  In any case, this
    ## current solution will do for now.
    # $cce->bye('FAIL');
    # exit(1);
}

$cce->bye('SUCCESS');
exit(0);

sub ip_and_ip
{
    my ($ip1, $ip2) = (shift, shift);
    my ($bip1,$bip2) = (ip2bin($ip1), ip2bin($ip2));
    return bin2ip($bip1 & $bip2);
}

# convert an ip address from its binary representation
# to its dotted-quad representation
sub bin2ip
{
    return join('.', unpack('C4', pack('N', shift)));
}

# convert an ip address from dotted-quad to binary
sub ip2bin
{
    return unpack('N', pack('C4', split(/\./, shift)));
}

sub which_device
{
    my ($ip, @nets) = @_;
    foreach my $net (@nets) 
    {
        if (is_ip_in_net($ip, $net->{ipaddr}, $net->{netmask})) 
        {
            return ($net->{device});
        }
    }
    return '';
}

sub is_ip_in_net
{
    my ($ip, $network, $netmask) = @_;
    my ($bip, $bnet, $bmask) = (ip2bin($ip), ip2bin($network), ip2bin($netmask));
    return (($bip & $bmask) == ($bnet & $bmask));  
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

#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/network
#
# $Id: rewrite-ifcfg.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# rewrite the ifcfg file for the interface being modified with the new settings
#

use CCE;
use Sauce::Util;
use Network;

my $DEBUG = 0;

my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();
my $new = $cce->event_new();
my $old_net = $cce->event_old();

my $device = $obj->{'device'};
my $ipaddr = $obj->{'enabled'} ? $obj->{'ipaddr'} : '0.0.0.0';
my $netmask = $obj->{'netmask'};
my $enabled = $obj->{'enabled'} && $ipaddr;
my $bootproto = $obj->{'bootproto'};
my ($network, $broadcast) = calcnetwork($ipaddr, $netmask);
my $onboot = $enabled ? 'yes' : 'no';

# this checks ipaddr here, because ipaddr is not optional
# and there is never a need to write an ifcfg file until
# the ipaddr field for a Network object is filled in
# this handler doesn't run on Network._DESTROY
if (not $obj->{ipaddr})
{
	$cce->bye('SUCCESS');
	exit(0);
}

# edit ifcfg file
my $filename = $Network::NET_SCRIPTS_DIR . '/ifcfg-' . $device;
my $ok = Sauce::Util::editfile($filename, *edit_ifcfg, $filename);

# bring down the old device first if it's changing, otherwise
# bringing up the new device will fail if the ip is the same
if (!$cce->event_is_create() && $new->{device})
{
	# bring down the old device if necessary
	if ($old_net->{enabled})
	{
		my @down_cmd = ($Network::IFCONFIG, $old_net->{device}, 'down');
		system(@down_cmd);

		# rollback for old device taken care of by next addrollbackcommand
	}
}

# check if there is an alias to bring down before changing this interface
if ($enabled && $obj->{real} && $new->{ipaddr}) {
	my ($alias_oid) = $cce->find('Network',
				     { 
				         'real' => 0,
					 'enabled' => 1,
					 'ipaddr' => $new->{ipaddr} 
				     });
	if ($alias_oid) {
		my ($ok, $alias) = $cce->get($alias_oid);

		#
		# need to bring down the alias interface so that the IP
		# can be assigned to the real interface being modified
		# this is okay, because the alias object will end up being
		# destroyed by the cleanup_aliase.pl handler anyways
		#
		my @cmd = ($Network::IFCONFIG, $alias->{device}, 'down');
		my @rollback_cmd = ($Network::IFCONFIG, $alias->{device});
		push @rollback_cmd, $alias->{ipaddr};
		my $a_broadcast = (calcnetwork($alias->{ipaddr},
					       $alias->{netmask}))[1];
		push @rollback_cmd, 'broadcast', $a_broadcast;
		push @rollback_cmd, 'netmask', $alias->{netmask}, 'up';

		# ifconfig alias down
		system(@cmd);
		$DEBUG && warn(join(' ', @cmd));
		Sauce::Util::addrollbackcommand(@rollback_cmd);
	}
}

# modify the running interface
my @cmd = ($Network::IFCONFIG, $device); 
if ($enabled)
{
	push @cmd, $ipaddr, 'broadcast', $broadcast, 'netmask', $netmask;
}
else
{
	push @cmd, '0.0.0.0';
}
push @cmd, ($enabled ? 'up' : 'down');
$DEBUG && warn('modify: ' . join(' ', @cmd));
system(@cmd);

# add rollback command, in case there is a failure somewhere else
my @rollback_cmd = ($Network::IFCONFIG);
push @rollback_cmd, ($old_net->{device} ? $old_net->{device} : $device);
if ($old_net->{ipaddr} && ($old_net->{ipaddr} ne '0.0.0.0'))
{
	my ($junk, $bcast) = calcnetwork($old_net->{ipaddr}, $old_net->{netmask});
	push @rollback_cmd, $old_net->{ipaddr},
			'broadcast', $bcast, 'netmask', $old_net->{netmask};
}
push @rollback_cmd, ($old_net->{enabled} ? 'up' : 'down');
$DEBUG && warn('rollback: ' . join(' ', @rollback_cmd));
Sauce::Util::addrollbackcommand(@rollback_cmd);

# handle changing the device name
if (!$cce->event_is_create() && $new->{device})
{
	# add rollback command to bring down new device if necessary
	# the new device must go down before the old device is brought back
	# up if the ip is the same
	if ($enabled)
	{
		@rollback_cmd = ($Network::IFCONFIG, $new->{device}, 'down');
		Sauce::Util::addrollbackcommand(@rollback_cmd);
	}
	
	# clean up old ifcfg file
	my $old_filename = $Network::NET_SCRIPTS_DIR . '/ifcfg-' 
						. $old_net->{device};
	Sauce::Util::unlinkfile($old_filename);
}

# assure correct permissions on file
Sauce::Util::chmodfile(0755, $filename);

$cce->bye('SUCCESS');
exit(0);

####################### helper functions ##############################

# calculate the network to which the specified ip address belongs
sub calcnetwork
{
	my ($ipaddr, $netmask) = (shift, shift);

	# convert the ip address and netmask to binary representations
	my $binip = pack('CCCC', split(/\./, $ipaddr));
	my $binmask = pack('CCCC', split(/\./, $netmask));

	# calculate the network
	my $binnet = $binip & $binmask;

	# calculate the broadcast address
	my $binbcast = $binnet | ~$binmask;
	
	# convert network and broadcast into dotted-quad format
	my $network = join('.', unpack('CCCC', $binnet));
	my $bcast   = join('.', unpack('CCCC', $binbcast));
	
	return ($network, $bcast);
}

sub edit_ifcfg {
		my ($fin, $fout, $filename) = @_;
		print $fout <<EOT;
# $filename
# generated by $0
#
# ${oid}.device = $device
# ${oid}.ipaddr = $ipaddr
# ${oid}.netmask = $netmask
# ${oid}.enabled = $enabled
DEVICE=$device
BOOTPROTO=$bootproto
ONBOOT=$onboot
BROADCAST=$broadcast
NETWORK=$network
NETMASK=$netmask
IPADDR=$ipaddr
USERCTL=no
EOT
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

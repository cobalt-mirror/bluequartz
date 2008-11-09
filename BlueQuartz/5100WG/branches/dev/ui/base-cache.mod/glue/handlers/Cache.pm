#!/usr/bin/perl -I/usr/sausalito/perl
#
# $Id: Cache.pm 3 2003-07-17 15:19:15Z will $
# Author: Patrick Bose
# Copyright 2000, Cobalt Networks, Inc.
#
# This module contains routines to start and stop the caching process,
# add or remove web caching from startup scripts, and enable
# or disable the firewall rules used for caching.
#
# WARNING: These routines are intended to be used by handlers, 
# i.e. they do not update CCE.  If you use them outside the context
# of CCE, you will risk CCE being out of synch with the actual
# state of the caching system or not satisfying dependencies.

package Cache;

use CCE;

###########################################################################
# This subroutine enables web caching as a transparent proxy by:
#   - calling disable_cache to flush out existing squid firewall setup, etc.
#   - adding squid to startup scripts
#   - starting squid
#   - updating firewall rules to configure as a transparent proxy
# params: CCE object (optional)
# returns: nonesub enable_cache
sub enable_cache
{
    my $cce = connect_cce(shift);
    disable_cache($cce);
    enable_cache_init();
    start_cache();
    enable_cache_fw($cce);
}

###########################################################################
# This subroutine disables web caching by:
#   - destroying squid owned FirewallRules in CCE
#   - removing squid from startup scripts
#   - stopping squid
# params: CCE object (optional)
# returns: nonesub disable_cache
sub disable_cache
{
    disable_cache_fw( connect_cce(shift) );
    disable_cache_init();
    stop_cache();
}

###########################################################################
# This subroutine configures the firewall for http redirect by:
#   - updating firewall rules to configure as a transparent proxy
#         - accept port 80 requests destined for this machine
#         - redirect port 80 requests destined for other machines to squid
#         - it adds these rules to the top of the list
# params: CCE object (optional)
# returns: none
sub enable_cache_fw
{
    my $cce = connect_cce(shift);

    my @system_oid = $cce->find("System");
    if (@system_oid == 0) {
	$cce->bye("FAIL", "no_system_object");
	exit(1);
    }

    # make sure ip forwarding is on
    $cce->set($system_oid[0], "Network", { ipForwarding => "1" } );

    #  turn redirect on: add firewall rules
    my @primary_if_oid = $cce->find("Network", { device => "eth0" } );

    if (@primary_if_oid == 0) {
	$cce->bye("FAIL", "no_prim_network_object");
	exit(1);
    }

    my ($ok, $primary_if_obj) = $cce->get($primary_if_oid[0]);

    my $network_ip   = ${$primary_if_obj}{"ipaddr"};
    my $network_mask = ${$primary_if_obj}{"netmask"};
    my @interface_oids = $cce->find("Network");
    my ($src_ip_start, $src_ip_stop) = get_start_and_stop($network_ip, $network_mask);

    # Accept traffic directed at this machine
    foreach $interface_oid (@interface_oids) {

    ($ok, $interface_obj) = $cce->get($interface_oid);

    $cce->create("FirewallRule", 
                            { policy => "ACCEPT",
                              protocol => "tcp",
                              source_ip_start => $src_ip_start,
                              source_ip_stop => $src_ip_stop,
                              dest_ip_start => ${$interface_obj}{"ipaddr"},
                              dest_ip_stop => ${$interface_obj}{"ipaddr"},
                              dest_ports => "80",
                              owner => "squid",
                              description => "Do not redirect traffic intended for this machine" })
    unless ( (${$interface_obj}{"ipaddr"} eq "") || ( !${$interface_obj}{"enabled"} ) );
                              
    }

    $cce->create("FirewallRule", 
			    { policy => "ACCEPT",
			      protocol => "tcp",
			      source_ip_start => $src_ip_start,
			      source_ip_stop => $src_ip_stop,
			      dest_ip_start => "127.0.0.1",
			      dest_ip_stop => "127.0.0.1",
			      dest_ports => "80",
			      owner => "squid",
			      description => "Do not redirect traffic intended for this machine" }); 

    # Now do the redirect rule
    $cce->create("FirewallRule", 
			    { policy => "REDIRECT",
			      redir_target => "3128",
			      protocol => "tcp",
			      source_ip_start => $src_ip_start,
			      source_ip_stop => $src_ip_stop,
			      dest_ip_start => "0.0.0.0",
			      dest_ip_stop => "255.255.255.255",
			      dest_ports => "80",
			      owner => "squid",
			      description => "Redirect HTTP traffic to squid" });

    my @redirect_rules = $cce->find("FirewallRule", { owner => "squid", policy => "REDIRECT" } );
    my @accept_rules = $cce->find("FirewallRule", { owner => "squid", policy => "ACCEPT" } );

    my @inputchain_oid = $cce->find("FirewallChain", { name => "input" } );
    if (@inputchain_oid == 0) {
	$cce->create("FirewallChain", { name => "input" } );	
        @inputchain_oid = $cce->find("FirewallChain", { name => "input" } );
    }

    ($ok, $input_chain) = $cce->get($inputchain_oid[0]);

    my @existing_rules = $cce->scalar_to_array(${$input_chain}{"rules"});

    my @all_rules = (@accept_rules, @redirect_rules, @existing_rules);

    $cce->set($inputchain_oid[0], "", { rules => $cce->array_to_scalar(@all_rules) } );

    # commit
    $cce->set($system_oid[0], "Firewall", { commit => time, enabled => "1" } );

}

###########################################################################
# This subroutine disables http redirect by:
#   - destroying squid owned FirewallRules in CCE
# params: CCE object (optional)
# returns: none
sub disable_cache_fw
{
    my $cce = connect_cce(shift);

    my @system_oid = $cce->find("System");
    if (@system_oid == 0) {
	$cce->bye("FAIL", "no_system_object");
	exit(1);
    }

    # Remove old squid rules 
    @squid_oids = $cce->find("FirewallRule", { owner => "squid" } ); 

    if (@squid_oids != 0) {
	foreach $squid_oid (@squid_oids) {
	    $cce->destroy($squid_oid);
	}
	# commit firewall changes
        $cce->set($system_oid[0], "Firewall", { commit => time } );
    }
}


###########################################################################
# This subroutine adds squid to startup scripts using chkconfig
# params: none
# returns: none
sub enable_cache_init
{
    if ( system("/sbin/chkconfig --add squid > /dev/null 2>&1") ) {
        return 0;
    } else { 
        return 1;
    }
}

###########################################################################
# This subroutine removes squid to startup scripts using chkconfig
# params: none
# returns: none
sub disable_cache_init
{
    if ( system("/sbin/chkconfig --del squid > /dev/null 2>&1") ) {
        return 0;
    } else {
        return 1;
    }
}

###########################################################################
# This subroutine starts squid using the init script
# params: none
# returns: none
sub start_cache
{
    if ( system("/etc/rc.d/init.d/squid start > /dev/null 2>&1") ) {
        return 0;
    } else {
        return 1;
    }
}

###########################################################################
# This subroutine stops squid using the init script
# params: none
# returns: none
sub stop_cache
{
    if ( system("/etc/rc.d/init.d/squid stop > /dev/null 2>&1") ) {
	return 0;
    } else {
	return 1;
    }
}

###########################################################################
# This subroutine checks if an object was passed in. If so, returns that
# object; otherwise connects to CCE and returns that object.
# params: CCE object (optional)
# returns: CCE object
sub connect_cce
{
    my $cce = shift;
    if (!$cce) {
	$cce = new CCE(Namespace => "", Domain => "base-cache");
	if (!$cce) {	
            die "Could not create a CCE object in Cache.pm: $! \n";
        }
        $cce->connectuds();
    }
    return $cce;
}

###########################################################################
# gives the starting and stopping ip addresses for the network
# params: ip address and netmask
# returns: first ip address, last ip address
sub get_start_and_stop
{
    my ($ip, $netmask) = (shift, shift);
    my $low_network = ip2bin($ip) &  ip2bin($netmask);
    my $hi_network = $low_network | ~ip2bin($netmask);
    return (bin2ip($low_network), bin2ip($hi_network));
}

###########################################################################
# converts ip address to binary
# params: ip address
# returns: binary version
sub bin2ip
{
    return join(".",unpack("C4",pack("N",shift)));
}

###########################################################################
# converts binary to ip address
# params: binary version of ip address
# returns: ip address (x.x.x.x)
sub ip2bin
{
    return unpack("N",pack("C4",split(/\./, shift)));
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

# $Id: Network.pm 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#
# hidden functions only used by scripts in this module

package Network;

require Exporter;

use vars qw(@ISA @EXPORT_OK);

@ISA = qw(Exporter);
@EXPORT_OK = qw(find_eth_ifaces $NET_SCRIPTS_DIR);

# files and directories
$Network::NET_SCRIPTS_DIR = '/etc/sysconfig/network-scripts';
$Network::ETC_HOSTS = '/etc/hosts';

# programs
$Network::IFCONFIG = '/sbin/ifconfig';
$Network::ROUTE = '/sbin/route';

# exportable routines

# find the interface names for all real and alias interfaces
sub find_eth_ifaces
{
    my @eth_ifaces = ();

    # first find real physical intefaces
	if (defined(open(IFCONFIG, "$Network::IFCONFIG -a 2>/dev/null |")))
	{
		while (<IFCONFIG>)
		{
			if (!/^(eth\d+)\s/) { next; }
			# found an existing interface
			push @eth_ifaces, $1;
		}
		close(IFCONFIG);
    }
	else
	{
		return ();
	}

    # now search /etc/sysconfig/network-scripts for aliases
    if (opendir(IFCFG, $Network::NET_SCRIPTS_DIR))
    {
        while (my $filename = readdir(IFCFG))
        {
            if ($filename =~ /\-(eth\d+\:\d+)$/)
            {
                push @eth_ifaces, $1;
            }
        }
        
        closedir(IFCFG);
    }
    else
    {
        return ();
    }

    return @eth_ifaces;
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

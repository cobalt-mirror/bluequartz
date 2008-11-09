#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: etchosts.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#

use Sauce::Util;
use CCE;

my $cce = new CCE;
$cce->connectfd();

# get system and network object ids:
my ($system_oid) = $cce->find('System');

# only care about real interfaces
my @network_oids = $cce->find('Network', { 'real' => 1 });

# get system object
# FIXME: should we bother checking the return status of gets
my ($ok, $obj) = $cce->get($system_oid);
if (!$ok) 
{ 
    $cce->bye('FAIL', '[[base-network.cantReadSystem]]');
    exit(1);
}

# get all network objects that are real interfaces
# @network_oids only contains the OIDs of real interfaces
my @networks = ();
for my $net_oid (@network_oids) 
{
    my ($ok, $net_obj) = $cce->get($net_oid);
    if ($ok) 
    { 
        push @networks, $net_obj;
    }
}

# munge hostname
my ($hostname, $domainname);
$hostname = $obj->{hostname};
$domainname = $obj->{domainname};

# create the /etc/hosts/file
my $etchosts = <<EOT;
# /etc/hosts
# Auto-generated file.  Keep your customizations at the bottom of this file.
127.0.0.1        localhost localhost.localdomain
EOT

# add all the real interfaces
# this is probably optional
for $net_obj (@networks) 
{
    my $dev = $net_obj->{device};
    if ($net_obj->{ipaddr}) 
    {
        $etchosts .= $net_obj->{ipaddr} . 
                        "\t\t${hostname}.${domainname} ${hostname}-${dev}.${domainname} ${hostname}-${dev}\n";
    } 
    else 
    {
        $etchosts .= "# $dev has not been configured.\n";
    }
}

# add end of CCE maintained /etc/hosts section
$etchosts .= <<EOT;
#END of auto-generated code.  Customize beneath this line.
EOT

# update /etc/hosts
my $fn = sub 
    {
        my ($fin, $fout) = (shift,shift);
        my ($text) = (shift);
        
        # print out the CCE maintained section
        print $fout $text;

        # mark whether we found the end yet
        my $flag = 0;
        while (<$fin>) 
        {
            # print out customizations after the END mark
            if ($flag) 
            {
                # need explicit $_ here, or perl gets confused with the file handle
                print $fout $_; 
            }
            else 
            {
                # remember that the END has come
                if (m/^#END/) { $flag = 1; }
            }
        }
        return 1;
    };

Sauce::Util::editfile('/etc/hosts', $fn, $etchosts);
Sauce::Util::chmodfile(0644,'/etc/hosts');

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

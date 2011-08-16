#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
# $Id: change_net_info.pl,v 1.18 2001/10/23 18:27:22 pbaltz Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# change_net_info.pl
# do things like making sure casp, httpd.conf, aliases, maillists, email info,
# etc. all get updated properly if the fqdn or ip address change for a vsite

use CCE;
use Vsite;
use Sauce::Util;
use Sauce::Config;
use Base::HomeDir qw(homedir_get_group_dir homedir_create_group_link);

my $cce = new CCE;

$cce->connectfd();

# gather some useful information
my $vsite = $cce->event_object();
my $vsite_new = $cce->event_new();
my $vsite_old = $cce->event_old();

my $msg;

# stuff to do if either the ip or fqdn has changed
if ($vsite_new->{ipaddr} || $vsite_new->{fqdn} || $vsite_new->{webAliases})
{
    # modify VirtualHost entry for this site
    my ($vhost) = $cce->find('VirtualHost', { 'name' => $vsite->{name} });

    my ($ok) = $cce->set($vhost, '', { 'ipaddr' => $vsite->{ipaddr}, 'fqdn' => $vsite->{fqdn}, 'webAliases' => $vsite->{webAliases} });

    if (not $ok)
    {
        $cce->bye('FAIL', '[[base-vsite.cantUpdateVhost]]');
        exit(1);
    }
}

if ($vsite_new->{fqdn})
{
    # set umask or symlinks get created with funky permissions
    my $old_umask = umask(000);
    
    # update symlink in the filesystem
    my ($old_link, $old_target) = homedir_create_group_link($vsite->{name}, 
                        $vsite_old->{fqdn}, $vsite->{volume});
    my ($new_link, $link_target) = homedir_create_group_link($vsite->{name},
                        $vsite->{fqdn}, $vsite->{volume});

    unlink($old_link);
    Sauce::Util::addrollbackcommand("umask 000; /bin/ln -sf \"$old_target\" \"$old_link\"");
    Sauce::Util::linkfile(
            $link_target, 
            $new_link);

    # restore umask
    umask($old_umask);
} # end of fqdn change specific

# handle ip address change
if ($vsite_new->{ipaddr})
{
    # make sure that there is a network interface for the new ip - but not on AWS:
    if (!-f "/etc/is_aws") {
	vsite_add_network_interface($cce, $vsite_new->{ipaddr});
    }

    # delete the old interface, this is a no op if another site is using the old ip still
    vsite_del_network_interface($cce, $vsite_old->{ipaddr});
} # end of ip address change specific

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

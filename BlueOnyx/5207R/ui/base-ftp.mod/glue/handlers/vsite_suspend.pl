#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/ftp
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: vsite_suspend.pl 259 2004-01-03 06:28:40Z shibuya $
# 
# Bring down anonymous ftp and also ftp for the vsite's ip address, if
# it is the only site using the ip, when a Vsite is suspended.

use CCE;

my $cce = new CCE('Domain' => 'base-ftp');
$cce->connectfd();

my $vsite = $cce->event_object();
my ($ftp_site) = $cce->find('FtpSite', 
                    { 'ipaddr' => $vsite->{ipaddr} });
my ($ok, $anon_ftp) = $cce->get($cce->event_oid(), 'AnonFtp');

# get current ftp settings
($ok, my $ftp_settings) = $cce->get($ftp_site);

my $new_settings = {};

# disable ftp on suspend
if ($vsite->{suspend})
{
    # check if anonymous ftp needs to be disabled
    if ($anon_ftp->{enabled})
    {
        $new_settings->{anonymous} = 0;
    }

    ($ok) = $cce->set($ftp_site, '', $new_settings);
    if (not $ok)
    {
        $cce->bye('FAIL', 'cantSuspendFtp');
        exit(0);
    }

}
else # must be unsuspending a site
{
    # figure out what to do with anonymous ftp
    if ($anon_ftp->{enabled})
    {
        # make sure someone else hasn't taken over
        if ($ftp_settings->{anonymous} &&
            ($ftp_settings->{anonymousOwner} ne $vsite->{name}))
        {
            # disable anonymous for this site and issue a warning
            $cce->set($cce->event_oid(), 'AnonFtp', { 'enabled' => 0 });
            $cce->info('unsuspendAnonFtpInUse', { 'ipaddr' => $vsite->{ipaddr} });
        }
        else # reclaim anonymous ftp
        {
            $new_settings->{anonymous} = 1;
            $new_settings->{anonymousOwner} = $vsite->{name};
            $new_settings->{maxConnections} = $anon_ftp->{maxConnections};
            $new_settings->{quota} = $anon_ftp->{quota};
        }
    }

    # restore settings
    ($ok) = $cce->set($ftp_site, '', $new_settings);

    if (not $ok)
    {
        $cce->bye('FAIL', 'cantUnsuspendFtp');
        exit(0);
    }
}

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

#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/shell
# $Id: vsite_shell.pl,v 1.2 2001/11/05 08:16:41 pbose Exp $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#
# toggle shell access on and off for all site users when the vsite
#

use CCE;
use Base::User qw(usermod);
use MyShell;
use Base::Vsite qw(vsite_update_site_admin_caps);

my $cce = new CCE;
$cce->connectfd();

my $vsite = $cce->event_object();
my ($ok, $shell) = $cce->get($cce->event_oid(), 'Shell');
if (!$ok)
{
    $cce->bye('FAIL', '[[base-shell.cantReadVsiteShell]]');
    exit(1);
}

# if the vsite is suspended or shell is turned off, disable shell for
# all users
my @usermods = ();
my $fail_msg = '';
if ($vsite->{suspend} || !$shell->{enabled})
{
    $fail_msg = '[[base-shell.cantDisableVsiteShell]]';
    my $new_shell = ($vsite->{suspend} 
                            ? $MyShell::SUSPEND_SHELL : $MyShell::BAD_SHELL);

    # don't toggle the User.Shell.enabled property, because it should
    # be restored if the site is given shell access again
    my @users = $cce->find('User', { 'site' => $vsite->{name} });
    for my $oid (@users)
    {
        ($ok, my $user) = $cce->get($oid);
        if (!$ok)
        {
            $cce->bye('FAIL', '[[base-shell.cantReadVsiteUser]]');
            exit(1);
        }

        push @usermods, { 'name' => $user->{name}, 'shell' => $new_shell };
    } # got all users
}
elsif ($shell->{enabled})
{
    $fail_msg = '[[base-shell.cantEnableVsiteShell]]';
    # enable shell for all users, who have been given shell access
    my @users = $cce->find('User', { 'site' => $vsite->{name} });
    for my $oid (@users)
    {
        ($ok, my $user) = $cce->get($oid);
        ($ok, my $user_shell) = $cce->get($oid, 'Shell');
        if (!$ok)
        {
            $cce->bye('FAIL', '[[base-shell.cantReadVsiteUser]]');
            exit(1);
        }
        
        my $new_shell = $MyShell::BAD_SHELL;
        if ($user_shell->{enabled})
        {
            $new_shell = $MyShell::GOOD_SHELL;
        }
        
        push @usermods, {
                            'name' => $user->{name},
                            'shell' => $new_shell
                            };
    } # got all site users
}

if (scalar(@usermods) && !(usermod(\@usermods))[0])
{
    $cce->bye('FAIL', $fail_msg);
    exit(1);
}

# update site admin caps
if (!$cce->event_is_destroy()){
	vsite_update_site_admin_caps($cce, $vsite, 'siteShell', $shell->{enabled});
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

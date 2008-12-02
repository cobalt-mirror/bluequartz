#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/shell
# $Id: user_shell.pl,v 1.3 2001/11/16 21:29:59 pbaltz Exp $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#
# toggle shell access on or off for user
#

use CCE;
use Base::User qw(usermod);
use MyShell;

my $DEBUG = 0;
if ($DEBUG) { use Data::Dumper; }

my $cce = new CCE;
$cce->connectfd();

$DEBUG && warn("$MyShell::BAD_SHELL $MyShell::GOOD_SHELL $MyShell::SUSPEND_SHELL");

my $user = $cce->event_object();
my ($ok, $shell, $shell_old, $shell_new) = $cce->get($cce->event_oid(), 'Shell');
if (!$ok)
{
    $cce->bye('FAIL', '[[base-shell.cantReadUserShell]]');
    exit(1);
}

$DEBUG && print STDERR Dumper($shell, $shell_old, $shell_new);

# run on create, so make sure we have the info needed
if ($cce->event_is_create() && !scalar(getpwnam($user->{name})))
{
    $cce->bye('DEFER');
    exit(0);
}

# if they have a site defined, verify that site is allowed shell access
# and isn't suspended
my $vsite_shell = {};
if ($user->{site})
{
    my ($site_oid) = $cce->find('Vsite', { 'name' => $user->{site} });
    ($ok, $vsite_shell) = $cce->get($site_oid, 'Shell');

    if (!$ok)
    {
        $cce->bye('FAIL', '[[base-shell.cceError]]');
        exit(1);
    }

    # only worry if they are trying to toggle from off to on for user
    # shell
    if ($shell_new->{enabled} && !$vsite_shell->{enabled})
    {
        $cce->bye('FAIL', '[[base-shell.cantEnableNoVsiteShell]]');
        exit(1);
    }
}

# if the user is suspended or shell is turned off, set shell to non-bash
my $fail_msg = '';
my $new_shell = $MyShell::BAD_SHELL;
if (!$user->{enabled} || !$shell->{enabled} ||
    (exists($vsite_shell->{enabled}) && !$vsite_shell->{enabled}))
{
    $fail_msg = '[[base-shell.cantDisableUserShell]]';
    $new_shell = (!$user->{enabled} 
                            ? $MyShell::SUSPEND_SHELL : $MyShell::BAD_SHELL);
}
elsif ($shell->{enabled})
{
    $fail_msg = '[[base-shell.cantEnableUserShell]]';
    $new_shell = $MyShell::GOOD_SHELL;
}

$DEBUG && print STDERR "new shell is $new_shell\n";

if (!(usermod({ 'name' => $user->{name}, 'shell' => $new_shell }))[0])
{
    $cce->bye('FAIL', $fail_msg);
    exit(1);
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

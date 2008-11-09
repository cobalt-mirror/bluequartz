#!/usr/bin/perl -w -I /usr/sausalito/perl -I/usr/sausalito/handlers/base/email
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: newaliases.pl 259 2004-01-03 06:28:40Z shibuya $
#
# run the newaliases command for EmailAliases with local_alias set to
# true

use CCE;
use Sauce::Util;
use Email;

my $DEBUG = 0;

my $cce = new CCE;
$cce->connectfd();

my $alias = $cce->event_object();
my $alias_old = $cce->event_old();

my $run_program = 0;

# figure out if it should actually be run
if ($cce->event_is_destroy() && $alias_old->{local_alias})
{
    $run_program = 1;
}
elsif ($alias->{local_alias})
{   # local_alias was just set or some other property the local alias
    # depends on
    $run_program = 1;
}
elsif ($alias_old->{local_alias} != $alias->{local_alias})
{   # local_alias was toggled
    $run_program = 1;
}

if ($run_program)
{
    $DEBUG && print STDERR "RUNNING NEWALIASES\n";
    Sauce::Util::modifyfile(&Email::Aliases . '.db');
    Sauce::Util::modifyfile(&Email::MajordomoAliases . '.db');
    system('/usr/bin/newaliases', '-O', 'DisableGetHostByAddr=True');

    if ($?)
    {
        $cce->bye('FAIL', '[[base-email.cantUpdateLocalAliases]]');
        exit(1);
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

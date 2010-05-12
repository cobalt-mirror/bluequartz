#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: 50_addAdmin.pl 1110 2008-05-13 00:50:49Z mstauber $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

# Author: Kevin K.M. Chiu

use strict;
use CCE;
use I18n;
use Quota;
use Base::HomeDir;

my $cce = new CCE;
$cce->connectuds();

my $i18n = new I18n;

# make sure the default directory for system users exists
if (! -d '/home/users')
{
    mkdir('/home/users', 0755);
}

# make sure /etc/shadow exists
if (! -f '/etc/shadow')
{
    system('/usr/sbin/pwconv');
}
 
# check if admin exists already
my (@oids) = $cce->find('User', { name => 'admin' });
my $success;

if($#oids >= 0) 
{
    # make sure admin has the systemAdministrator
    # field set
    my ($ok, $obj) = $cce->get($oids[0]);

    # add systemAdministrator right if adminUser right does not exist
    if(!$obj->{systemAdministrator}) 
    {   
        # reset to systemAdministrator if adminUser not found
	($success) = $cce->set($oids[0], '', 
			       { 
				   systemAdministrator => 1
				   });
        
        # turn su acces back on if resetting admin to sys_admin
        ($success) = $cce->set($oids[0], 'RootAccess', { 'enabled' => 1 });
    }

    # always reenable Shell for now
    ($success) = $cce->set($oids[0], 'Shell',
                                { 'enabled' => 1 });
}
else
{
    $i18n->setLocale(I18n::i18n_getSystemLocale($cce));

    # get system locale
    my ($sysoid) = $cce->find('System');
    my ($ok, $sysobj) = $cce->get($sysoid);
    my $locale = $sysobj->{'productLanguage'};

    # add
    # create with systemAdministrator set at first
    ($success) = $cce->create('User', 
            {
                fullName => $i18n->get('administrator', {}, 'base-user'),
                localePreference => $locale,
                name => 'admin',
                password => 'blueonyx',
                stylePreference => 'BlueOnyx',
                systemAdministrator => 1,
                volume => $Base::HomeDir::HOME_ROOT
            });

    # turn on telnet and su access by default
    if ($success)
    {
        my $oid = $cce->oid();
        ($success) = $cce->set($oid, 'Shell', 
                        { 'enabled' => 1 });
       
        # enable su access
        ($success) = $cce->set($oid, 'RootAccess', { 'enabled' => 1 });
    }
}

$cce->bye();

# failed?
if(!$success) 
{
  exit 1;
}

exit 0;
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

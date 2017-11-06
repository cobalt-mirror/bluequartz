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
if (! -d '/home/users') {
    mkdir('/home/users', 0755);
}

# make sure /etc/shadow exists
if (! -f '/etc/shadow') {
    system('/usr/sbin/pwconv');
}
 
# check if admin exists already
my (@oids) = $cce->find('User', { 'name' => 'admin' });
my $success;

if ($#oids >= 0) {
    # make sure admin has the systemAdministrator
    # field set
    my ($ok, $obj) = $cce->get($oids[0]);

    # add systemAdministrator right if adminUser right does not exist
    if(!$obj->{'systemAdministrator'}) {
        # reset to systemAdministrator if adminUser not found
        ($success) = $cce->update($oids[0], '', { 'systemAdministrator' => 1 });
        
        # turn su acces back on if resetting admin to sys_admin
        ($success) = $cce->update($oids[0], 'RootAccess', { 'enabled' => 1 });
    }

    # always reenable Shell for now
    ($success) = $cce->update($oids[0], 'Shell', { 'enabled' => 1 });
}
else {
    $i18n->setLocale(I18n::i18n_getSystemLocale($cce));

    # If no 'admin' account exists, then it is likely that the group 'site-adm'
    # doesn't exist either. So we create it here:
    system("/usr/sbin/groupadd -f site-adm");

    # get system locale
    my ($sysoid) = $cce->find('System');
    my ($ok, $sysobj) = $cce->get($sysoid);
    my $locale = $sysobj->{'productLanguage'};

    # add
    # create with systemAdministrator set at first
    ($success) = $cce->create('User', 
            {
                'fullName' => $i18n->get('administrator', {}, 'base-user'),
                'localePreference' => $locale,
                'name' => 'admin',
                'password' => 'blueonyx',
                'stylePreference' => 'BlueOnyx',
                'systemAdministrator' => 1,
                'volume' => $Base::HomeDir::HOME_ROOT
            });

    # turn on telnet and su access by default
    if ($success) {
        my $oid = $cce->oid();
        ($success) = $cce->update($oid, 'Shell', { 'enabled' => 1 });
       
        # enable su access
        ($success) = $cce->update($oid, 'RootAccess', { 'enabled' => 1 });
    }
}

$cce->bye();

# failed?
if(!$success) {
    exit 1;
}

exit 0;
# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#    notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#    notice, this list of conditions and the following disclaimer in 
#    the documentation and/or other materials provided with the 
#    distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#    contributors may be used to endorse or promote products derived 
#    from this software without specific prior written permission.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
# COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
# POSSIBILITY OF SUCH DAMAGE.
# 
# You acknowledge that this software is not designed or intended for 
# use in the design, construction, operation or maintenance of any 
# nuclear facility.
# 
#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/winshare

use Sauce::Config;
use Sauce::Service;
use CCE;
use smb;
use I18n;

my $cce = new CCE;
$cce->connectuds();

my $i18n = new I18n;

my @oids = $cce->find('System');
if (not @oids) {
        $cce->bye('FAIL');
        exit 1;
}

my ($ok, $obj) = $cce->get($oids[0], 'WinShare');
unless ($ok and $obj) {
        $cce->bye('FAIL');
        exit 1;
}

# find out if it's enabled
my $old = Sauce::Service::service_get_init('smb') ? 'on' : 'off';
my $new = $obj->{enabled} ? '1' : '0';
Sauce::Service::service_set_init('smb', $new) unless ($new eq $old);

# fix up numbers of users, locale stuff and guest user
my %settings = (
        "add user script" => "/sbin/addWindowsMachine.pl",
        "domain admin group" => "\@admin",
        "logon home" => "\\\\\%L\\\%U",
        "logon path" => "\\\\\%L\\profiles\\\%U\\profile",
        "time server" => "yes",
        "username map" => "/etc/samba/smbusers",
        "nt acl support" => "yes",
        "log file" => "/var/log/samba/log"

);
$settings{'max connections'} = $obj->{maxConnections};
$settings{'client code page'} = $i18n->getProperty("sambaCodePage","base-winshare");

if($i18n->getProperty("sambaCodingSystem","base-winshare") ne "none"){
	$settings{'codingsystem'} = $i18n->getProperty("sambaCodingSystem","base-winshare");
}

($ok, $obj) = $cce->get($oids[0], 'FileShare');
$settings{'guest account'} = $obj->{guestUser};

Sauce::Util::editfile(smb::smb_getconf, *smb::edit_global,
		      %settings);

# sync up guest share
Sauce::Util::editfile(smb::smb_getconf, *smb::edit_guest,
		      $old->{guestGroup}, $obj->{guestGroup},
		      $obj->{guestEnabled});


$cce->bye('SUCCESS');
exit 0;
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

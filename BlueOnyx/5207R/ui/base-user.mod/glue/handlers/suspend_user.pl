#!/usr/bin/perl -I/usr/sausalito/perl
#
# $Id: suspend_user.pl
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.
# Copyright 2008-2013 Team BlueOnyx. All rights reserved.
#
# Watch the ui_enabled property and toggle the enabled property provided
# that the Vsite (if any) the User belongs to is not suspended.
#

use CCE;
use Base::User::Capabilities;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $cce = new CCE;
$cce->connectfd();

my $user = $cce->event_object();
$reserve_user = $user;

my $ui_enabled = $user->{ui_enabled}; 
my $user_enabled = $user->{enabled}; 
$reserve_user_enabled = $user->{enabled};
my $enabled = 1; 

&debug_msg("Our user is: " . $reserve_user->{name} . " and his suspend status is: " . $reserve_user_enabled  . "\n");
 
my $md5_password = $user->{md5_password}; 

if ($user->{site} ne '') {
	# see if the site this user is a member of is suspended
	my @sites = $cce->find('Vsite',
			{ 'name' => $user->{site}, 'suspend' => 1 });
	if (scalar(@sites) != 0) {
		# site is suspended.  don't allow user to be enabled.
		$enabled = 0;
	}
}

if ($enabled == 0 || $user_enabled == 0 || $ui_enabled == 0) { 
        if ($md5_password !~ '^!') { 
                $md5_password = '!' . $md5_password;  
        } 
} else {  
        $md5_password =~ s|^!||;  
}  
 
if ($enabled != 0) { 
        $enabled = $ui_enabled; 
} 
 
my ($ok) = $cce->set($cce->event_oid(), '',  
        {  
                'enabled' => $enabled,  
                'md5_password' => $md5_password  
        });

if (!$ok) {
	$cce->bye('FAIL');
	exit(1);
}
else {
    # User accounts MUST be locked or unlocked. Otherwise suspended users can still use SMTP-Auth:
    if ($enabled == "0") {
	# Lock account:
	system("/usr/sbin/usermod -L $user->{name}");
    }
    else {
	# Unlock account:
	system("/usr/sbin/usermod -U $user->{name}");
    }
}

# Reseller handling:
&debug_msg("Checking if user has Capability 'manageSite'.\n");
&debug_msg("We extrapolate: " . $reserve_user->{capLevels} . ".\n");
@userCaps = $cce->scalar_to_array($reserve_user->{capLevels});
$has_manageSite = "0";
foreach my $testcap (@userCaps) {
        if ($testcap eq 'manageSite') {
                $has_manageSite = "1";
                 &debug_msg("This user has cap 'manageSite'.\n");
        }
}

if ($has_manageSite == "1") {
    &debug_msg("Checking which sites he owns - if any.\n");
    # Get a list of all sites that this reseller owns:
    my @ResellerSites = $cce->find('Vsite', { 'createdUser' => $reserve_user->{name}});
    foreach my $rsites (@ResellerSites) {
        &debug_msg("Processing Vsite with OID $rsites.\n");
        my ($ok) = $cce->set($rsites, '',
                {
                        'suspend' => $reserve_user_enabled
                });
    }
}

$cce->bye('SUCCESS');
exit(0);

sub debug_msg {
    if ($DEBUG) {
        my $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
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

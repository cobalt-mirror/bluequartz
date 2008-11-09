#!/usr/bin/perl -I/usr/sausalito/handlers/base/appleshare -I/usr/sausalito/perl
#
# Copyright(c) 2000, Cobalt Networks, Inc.
# Author: asun@cobalt.com
#
use CCE;
use Sauce::Service;
use appleshare;

my $cce = new CCE(Namespace => 'AppleShare');
$cce->connectfd(\*STDIN, \*STDOUT);

my $obj = $cce->event_object();
my $old = $cce->event_old();

my %settings;

# maximum number of users. right now, we always set this even 
# if just the service is getting toggled.
$settings{'AFPD_MAX_CLIENTS'} = $obj->{maxConnections};

# enabled
#if ($old->{enabled} ne $obj->{enabled}) {
#    $settings{'AFPD_RUN'} = $obj->{enabled} ? 'yes' : 'no';
#}

if (%settings) {
    my $err = Sauce::Util::editfile(appleshare::atalk_getnetatalk, 
				    *Sauce::Util::keyvalue_edit_fcn,
				    '#', '=', undef, %settings);
    if ($err) {
	$cce->bye('FAIL', '[[base-appleshare.cantConfigFile]]');
	exit 1;
    }
}
    
# only restart if atalk was running before or was just enabled
Sauce::Service::service_toggle_init('atalk', $obj->{enabled});
$cce->bye('SUCCESS');
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

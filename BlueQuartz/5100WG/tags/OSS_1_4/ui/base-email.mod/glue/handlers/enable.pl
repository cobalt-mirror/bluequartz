#!/usr/bin/perl -w -I/usr/sausalito/perl

use Sauce::Service;
use CCE;

my $cce = new CCE( Namespace => 'Email',
                      Domain => 'base-email' );

$cce->connectfd(\*STDIN, \*STDOUT);

my $obj = $cce->event_object();
my %settings;
$settings{'imap'} = $obj->{enableImap} ? 'on' : 'off';
if ($obj->{enablePop}) {
    $settings{'pop-3'} = 'on';
    # we never want POP2 to be on
    $settings{'pop-2'} = 'off';
} else {
    $settings{'pop-3'} = 'off';
    $settings{'pop-2'} = 'off';
}

$ret = Sauce::Service::service_set_multi_inetd(%settings);
if( $ret ) {
	$cce->warn("couldnt_enable_imap_pop");
	$cce->bye("FAIL");
	exit(1);
} 
	
# don't trust anyone on the current state. there should be no delay
# on stoppage.
Sauce::Service::service_toggle_init('sendmail', $obj->{enableSMTP});

# pop-before-smtp relaying
my $popRelay = Sauce::Service::service_get_init('poprelayd') ? 'on' : 'off';
my $newpopRelay = $obj->{popRelay} ? 'on' : 'off';
Sauce::Service::service_toggle_init('poprelayd', $obj->{popRelay}) unless $newpopRelay eq $popRelay;
if($newpopRelay eq 'on') {
	symlink('/usr/local/sbin/poprelayd.custodiat', '/etc/cron.quarter-daily/poprelayd.custodiat');
} else {
	unlink('/etc/cron.quarter-daily/poprelayd.custodiat');
}

Sauce::Service::service_restart_inetd();
$cce->bye("SUCCESS");
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

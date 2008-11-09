#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: enable.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

use Sauce::Service;
use CCE;

my $DEBUG = 0;
$DEBUG && open(STDERR, ">>/tmp/email.enable");
$DEBUG && warn `date`;

my $cce = new CCE( Namespace => 'Email',
                      Domain => 'base-email' );

$cce->connectfd();

my $obj = $cce->event_object();
my %settings;
$settings{'imap'} = $obj->{enableImap} ? 'on' : 'off';
if ($obj->{enablePop}) {
    $DEBUG && warn "Pop enabled\n";
    $settings{'pop-3'} = 'on';
    # we never want POP2 to be on
    $settings{'pop-2'} = 'off';
} else {
    $DEBUG && warn "Pop disabled\n";
    $settings{'pop-3'} = 'off';
    $settings{'pop-2'} = 'off';
}

# Obtain connection rates
my($imapRate, $popRate) = ($obj->{imapConnectRate}, $obj->{popConnectRate});
$imapRate ||= 1024;
$popRate ||= 80;

# inetd settings first
Sauce::Service::service_set_inetd('pop-3', $settings{'pop-3'}, $popRate);
Sauce::Service::service_set_inetd('pop-2', $settings{'pop-2'}, $popRate);
Sauce::Service::service_set_inetd('imap', $settings{'imap'}, $imapRate);
Sauce::Service::service_send_signal('inetd', 'HUP');

# don't trust anyone on the current state. there should be no delay
# on stoppage.
Sauce::Service::service_toggle_init('sendmail', $obj->{enableSMTP});

# pop-before-smtp relaying
my $popRelay = Sauce::Service::service_get_init('poprelayd') ? 'on' : 'off';
my $newpopRelay = $obj->{popRelay} ? 'on' : 'off';

$DEBUG && warn "Think poprelayd is running? $popRelay\nShould be? $newpopRelay\n";

Sauce::Service::service_toggle_init('poprelayd', $obj->{popRelay}); 

if($newpopRelay eq 'on') {
	$DEBUG && warn "linking custodiat into place\n";
	Sauce::Util::linkfile('/usr/local/sbin/poprelayd.custodiat', '/etc/cron.quarter-daily/poprelayd.custodiat');
} else {
	$DEBUG && warn "unlinking custodiat\n";
	Sauce::Util::unlinkfile('/etc/cron.quarter-daily/poprelayd.custodiat');
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

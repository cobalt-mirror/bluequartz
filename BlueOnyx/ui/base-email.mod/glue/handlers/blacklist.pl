#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email
# $Id: blacklist.pl

use strict;
use CCE;
use Email;
use Sauce::Util;
use Sauce::Config;

# Globals.
my $Sendmail_mc = Email::SendmailMC;

my $cce = new CCE( Domain => 'base-email' );

my $DEBUG = 0;
$DEBUG && open(STDERR, ">/tmp/email_blacklist.debug");
$DEBUG && warn `date`;

$cce->connectfd();

$DEBUG && warn "SENDMAIL: $Sendmail_mc\n";

my $obj = $cce->event_object();
my $old = $cce->event_old();
my $new = $cce->event_new();

my $ret = Sauce::Util::editfile($Sendmail_mc, *make_sendmail_mc, $obj, $old, $new );

if(! $ret ) {
    $cce->bye('FAIL', 'cantEditFile', {'file' => $Sendmail_mc});
    exit(0);
} 

# Rebuilding sendmail.cf:
system("m4 /usr/share/sendmail-cf/m4/cf.m4 /etc/mail/sendmail.mc > /etc/mail/sendmail.cf");

$cce->bye('SUCCESS');
exit(0);


sub make_sendmail_mc {
    my $in  = shift;
    my $out = shift;
    
    my $obj = shift;

    my $old = shift;
    my $new = shift;
    
    my $blacklistHost;
    my $deferTemporary;
    my $active;
    my $prefix;
    my $defer;
    my $searchString;
    my %Printed_line = ( blacklistHost => 0);
    my $mailer_lines = 0;
    my @Mailer_line = ();

    if (!$old->{active}) {
	   $prefix = "dnl ";
    }
    if (!$old->{blacklistHost}) {
	   $searchString = $prefix . "FEATURE\\(dnsbl, \\`". $new->{blacklistHost} ."\\'";
    }
    else {
	   $searchString = $prefix . "FEATURE\\(dnsbl, \\`". $old->{blacklistHost} ."\\'";
    }
    
    if ($obj->{active}) {
	   $prefix = "";
    }
    else {
	   $prefix = "dnl ";
    }
    if ($obj->{deferTemporary}) {
	   $defer = "`t'";
    }
    else {
	   $defer = "";
    }
    if ($obj->{blacklistHost}) {
	   $blacklistHost = $prefix . "FEATURE(dnsbl, `". $obj->{blacklistHost} ."',,$defer)\n";
    }
    else {
	   $blacklistHost = "";
    }
    
    select $out;
    while( <$in> ) {
        if (/^$searchString/o) {
            $Printed_line{'blacklistHost'}++;
            print $blacklistHost;
        }
        elsif ( /^MAILER\(/o ) {
            $Mailer_line[$mailer_lines] = $_;
            $mailer_lines++;
        }
        else {
            print $_;
        }
    }

    foreach my $key ( keys %Printed_line ) {
        if ($Printed_line{$key} != 1) {
            print $blacklistHost;
        }
    }
    
    if ($mailer_lines) {
        foreach my $line (@Mailer_line) {
            print $line;
        }
    }
    return 1;
}

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2009 Bluapp AB
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#     notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#     notice, this list of conditions and the following disclaimer in 
#     the documentation and/or other materials provided with the 
#     distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#     contributors may be used to endorse or promote products derived 
#     from this software without specific prior written permission.
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
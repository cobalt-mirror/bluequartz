#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email
# $Id: mailertable.pl

use strict;
use CCE;
use Email;
use Sauce::Util;

my $cce = new CCE( Domain => 'base-email' );

$cce->connectfd();

my $Sendmail_mailertable = $Email::MAILERTABLE;
my $sys_obj;
my $sys_oid;
my $mx_oids;
my $DEBUG = 0;
$DEBUG && open(STDERR, ">>/tmp/email.mailertable");

my @mx_oids = $cce->find("mx2");
$DEBUG && print STDERR "oids: @mx_oids\n";

my ($ok, $obj) = $cce->get($sys_oid, "Email");


# create the mailertable file
my $mailertable_list = &make_mailertable(\@mx_oids);

# add rollback so there is no need to copy mailertable.db for rollback
Sauce::Util::addrollbackcommand("/usr/bin/makemap hash $Sendmail_mailertable < $Sendmail_mailertable >/dev/null 2>&1");

system("/usr/bin/newaliases");

if (!Sauce::Util::replaceblock($Sendmail_mailertable, 
	'# Cobalt Mailertable Section Begin', $mailertable_list, 
	'# Cobalt Mailertable Section End')
   	) {
	$cce->warn('[[base-email.cantEditFile]]', { 'file' => $Email::MAILERTABLE });
	$cce->bye('FAIL');
	exit(1);
}

$cce->bye('SUCCESS');
exit(0);


sub make_mailertable
{
	my $mx_oids = shift;
	my $out = "";	

	# setup mailertable for all mx_oids
	for my $mx_oid (@{ $mx_oids }) {	
	    $DEBUG && print STDERR "Mailertable oid: $mx_oid\n";
	    my ($ok, $relayOid) = $cce->get($mx_oid);
	    my $domain = $relayOid->{domain};
	    my $mapto = $relayOid->{mapto};
	    $DEBUG && print STDERR "Mailertable date: $domain->$mapto\n";
	    $out .= "$domain\tsmtp:$mapto\n";
	}
	return $out;
}
# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#	 notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#	 notice, this list of conditions and the following disclaimer in 
#	 the documentation and/or other materials provided with the 
#	 distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#	 contributors may be used to endorse or promote products derived 
#	 from this software without specific prior written permission.
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
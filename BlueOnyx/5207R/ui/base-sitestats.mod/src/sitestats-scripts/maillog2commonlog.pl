#!/usr/bin/perl -I /usr/sausalito/perl
# $Id: maillog2commonlog.pl,v 1.5.2.1 2002/02/13 03:35:21 pbaltz Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
use Data::Dumper;
#
# Convert sendmail, smail, or qmail logs to common log format so they can be 
# processed by standard web log processing software.
#
# Here's a sample log entry, in common log format:
#
# someone@foo.bar - - [31/May/1996:13:55:28 -0400] "GET /fred/" 200 541
#
# Meaning that someone@foo.bar sent mail to fred, on the given date, and the 
# message was 541 k long.
#
# Only mail that was successfully sent is logged.
#
# Cobalt Nasty Hacks.
# We set the referring page to be the domain that the mail was sent from 
# and we set the browser string to be the domain that the mail was sent to.
#
# Maillog2Commonlog v. 3.2 is copyright 1995, 1996 by Joey Hess.
# May be distributed under the terms of the GPL.
#
# Usage:
# 	maillog2commonlog [sendmail|smail|newsmail|qmail] < logfile
#
# Note: if your smail is < version 3.2, then use smail. If it is 3.2 or
# greater, the logfile format changed, and you must use newsmail instead.
#
# Note: it only works for qmail if qmail is set up to log messages via
# syslog. Otherwise, it isn't going to find timestamps.

$DEBUG = 0;

use CCE;
$cce = new CCE;
$cce->connectuds();

# Set the tzoffset automagically..
$tzoffset = `/bin/date +%z`;
$tzoffset = sprintf ("%+.4d", $tzoffset );

# We're going to need to know which usernames are matched to which domains.
# Then we track any thing that comes through as
# 'accept email for this domain' email. Get's caught as well.
my(%routes, %aliases);
open(VUT, '/etc/mail/virtusertable') || die "Could not read virtusertable: $!";
while(<VUT>) {
	chomp;
	if(/^\s*\@(\S+)\s+\%1\@(.*)$/o) {
		$routes{$1} = $2;
	} elsif (/^(.+\@\S+)\s+(.+)\s*$/) {
		$aliases{$1} = $2;
	}
}
close(VUT);

$logtype = shift;
$logtype = lc $logtype;
if ($logtype ne 'sendmail' and $logtype ne 'smail' and $logtype ne 'newsmail'
	and $logtype ne 'qmail') {
	print <<eof;
Usage:
	maillog2commonlog [sendmail|smail|newsmail|qmail] < logfile
eof
	exit;
}



# Enter a list of hosts for which we will log the actual username of the people
# sending/recieving mail. Otherwise, we will just log the hostname.
sub Log { my $message_id=shift;
	local $year = $year;

	# Oops, the year rollover causes problems...
	if (($msg_buf{$message_id}{mon} eq "Dec") && ($cur_month eq "Jan")) {
		$year = $year - 1;
	}

	if (!$msg_buf{$message_id}{from}) { 
		$msg_buf{$message_id}{from}="unknown";
		$msg_buf{$message_id}{referer}="unknown";
		$msg_buf{$message_id}{size}=0;
	}

	print "$msg_buf{$message_id}{from} - - [$msg_buf{$message_id}{day}/$msg_buf{$message_id}{mon}/$year:$msg_buf{$message_id}{time}$tzoffset] \"GET /$msg_buf{$message_id}{to}/\" 200 $msg_buf{$message_id}{size} \"$msg_buf{$message_id}{referer}\" \"$msg_bug{$message_id}{agent}\"\n";

	if ( 0 == $msg_buf{$message_id}{nrcpts} ) {
		 undef $msg_buf{$message_id};
	}
}

sub FixEmail 
{
	my $email = shift;
	$email =~ s/[<|>]//g;
	my ($user, $domain) = ($email =~ m/(.*)@(.*)$/); 

	$DEBUG && print STDERR "original email: $email\t";

	# if domain wasnt logged, try to look it up
	if (!$domain) {
		# be safe
		chomp($email);

		my $old_email = $email;

		($email, $domain) = &cache_check($old_email);

		# look up user info if not in the cache
		if ($email eq '') {
			# need to find this user
			my @user_oids = $cce->find('User',
						   {'name' => $old_email});

			$email = $old_email;

			if ($user_oids[0]) {
				my ($ok, $user) = $cce->get($user_oids[0]);
				if ($user->{site}) {
					#
					# try to find the site to which this 
					# user belongs
					#
					my ($vs_oid) = $cce->find('Vsite', 
						{ 'name' => $user->{site} });
					if ($vs_oid) {
						($ok, my $vsite) =
							$cce->get($vs_oid);
						if ($vsite->{fqdn}) {
							$email = $old_email .
								"@" . 
								$vsite->{fqdn};
							$domain = $vsite->{fqdn};
						}
					}
				} # end if $user->{site}
			} # end if ($user_oids[0])

			#
			# user not found in CCE and no domain, must be 
			# localhost
			#
			if (!$domain) {
				$domain = 'localhost';
			}

			# add to cache
			$DEBUG && print STDERR "adding $email $domain to cache";
			&cache_add($email, $domain);
		} else {
			$DEBUG && print STDERR "found $email $domain in cache";
		} # end if ($email eq '')
	} # end if (!$domain)

	$DEBUG && print STDERR "\n";

	# If it's a domain we accept email for, we will get a link to it here.
	if (exists $routes{$domain}) {
		$domain = $routes{$domain};
	}

	return ($email, "http://$domain/");
}

# cheesy caching, to minimize the cpu load
my %cache;
my @cache_queue;
my $cache_size = 0;
my $cache_limit = 1000;

sub cache_add
{
	my ($email, $domain) = @_;

	# check to see if the cache is full
	if ($cache_size >= $cache_limit) {
		# remove the first entry
		my $remove = pop(@cache_queue);
		delete($cache{$remove});
		$cache_size--;
	} 
	
	$cache_size++;
	push @cache_queue, $email;
	$cache{$email} = { 'email' => $email, 'domain' => $domain };
}

sub cache_check
{
	my $item = shift;
	
	if (exists($cache{$item})) {
		return ($cache{$item}->{email}, $cache{$item}->{domain});
	} else {
		return ('', '');
	}
}

# Could use internal localtime function, but it doesn't tell century..
@_ = split/ /,`date`;
$year = $_[$#_];
$cur_month = $_[1];
chomp($year);

#
# Now on to actually processing the logs. Sendmail and smail use very 
# different file formats, sendmail is all on 1 line, smail is a muilt-
# line format that's easier to process, with \n\n seperating each multi-
# line record. And newsmail is ugly ('nuff said..)
#
if ($logtype eq 'smail') { 
	# read in a whole multi-line record at one go.
	$/="\n\n";
}

if ($logtype=~m/smail/) { 
	# Set up numeric date to Mmm date translation table for smail.
	my $i=1;
	foreach (Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec) { 
		$date_trans[$i++]=$_;
	}
}

while (<>) {
	# There are 2 distinct log lines types, either mail is being recieved or sent. 
	# We have to combine the 2 lines to get a clear picture of a mail message.
	# For qmail, there ate 3 log line types: mail recieved, delivery 
	# started, and delivery completed.

	#if ((/: from=/)) {
	if ((/: from=/ ne undef) || (/\] received\n/m ne undef) ||
	   (/\] Received / ne undef) || (/info msg .* from/ ne undef)) { # Recieved mail.
		if (/: from=/ ne undef) { # SENDMAIL
			($message_id,$from,$size,$nrcpts)=m/\w+\s+\d+\s+\d+:\d+:\d+\s+\w+\s+sendmail\[\d+\]:\s+(.*?):\s+from=(.*?),\s+size=(.*?),.*,\s+nrcpts=(.*?),/;
			$msg_buf{$message_id}{nrcpts}+=$nrcpts;
		}
		elsif (/\] received\n/m ne undef) { # SMAIL
			($message_id,$from)=m/^\d+\/\d+\/\d+\s+\d+\:\d+\:\d+\:\s+\[(.*?)\]\s+received\n\|\s+from:\s+(.*?)\n/m;
			($size)=m/\|\s+size:\s+(\d+)\s+bytes\n/m;
		}
		elsif (/\] Received / ne undef) { # NEWSMAIL
			($message_id)=m/\[(.*?)\]/;
			($from)=m/Received FROM:(.*?) /;
			($size)=m/SIZE:(\d+)\s/;
		}
		elsif (/info msg .* from/ ne undef) { # QMAIL
			($message_id,$size,$from)=m/info msg (\d+): bytes (\d+) from <(.*)>/;
		}

		if (!$from) { $from="unknown" }
		($from,$domain)=FixEmail($from);

		$msg_buf{$message_id}{from}=$from;
		$msg_buf{$message_id}{referer}=$domain;
		$msg_buf{$message_id}{size}=$size;

	}
	elsif ((/: to=.*stat=sent/i ne undef) || (/\] delivered\n/m ne undef) ||
	       (/\] Delivered / ne undef) || (/starting delivery/ ne undef)) { # The line logs mail being sent ok.
		if (/: to=.*stat=sent/i ne undef) {
			#($mon,$day,$time,$message_id,$to)=m/(\w+)\s+(\d+)\s+(\d+:\d+:\d+)\s+\w+\s+sendmail\[\.*?\]:\s+(.*?):\s+to=(.*?),/;
			($mon,$day,$time,$message_id,$to)=m/(\w+)\s+(\d+)\s+(\d+:\d+:\d+)\s+\w+\s+sendmail\[\d+\]:\s+(.*?):\s+to=(.*?),/;
			$msg_buf{$message_id}{nrcpts}-=1;
		}
		elsif (/\] delivered\n/m ne undef) {
			($mon,$day,$time,$message_id,$to)=m/(\d+)\/(\d+)\/\d+\s+(\d+:\d+:\d+):\s\[(.*?)\] delivered\n\|\s+to:\s+(.*?)\n/m;
			$mon=$date_trans[$mon]; # Translate to Mmm format.
		}
		elsif (/\] Delivered / ne undef) {
			($mon,$day,$time,$message_id)=m/(\d+)\/(\d+)\/\d+\s+(\d+:\d+:\d+):\s\[(.*?)\]/;
			($to)=m/TO:(.*?)\s/;
			$mon=$date_trans[$mon]; # Translate to Mmm format.
		}
		elsif (/starting delivery/ ne undef) {
			($mon,$day,$time,$message_id,$to)=m/^(\w+)\s+(\d+)\s+(\d+:\d+:\d+)\s+.*\s+msg\s+(\d+)\s+to\s+.*?\s+(.*)$/;
		}

		($to,$domain)=FixEmail($to);
		if (length($day) eq 1 ) { $day="0$day" }

		$msg_buf{$message_id}{mon}=$mon;
		$msg_bug{$message_id}{agent}=$domain;
		$msg_buf{$message_id}{day}=$day;
		$msg_buf{$message_id}{time}=$time;
		$msg_buf{$message_id}{to}=$to;
				
		&Log($message_id);
	}
}

# end CCE session
$cce->bye();
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

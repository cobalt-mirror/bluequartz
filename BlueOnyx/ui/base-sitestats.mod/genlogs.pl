#!/usr/bin/perl
# $Id: genlogs.pl,v 1.3 2001/09/19 17:38:34 will Exp $
# Copyright Sun Microsystems, 2001
#
# Log file generator

use strict;

my ($type, $lines, $age) = @ARGV;
$type ||= 'web';
$lines ||= 10;
$age ||= 1;
# warn "Type, lines, age: $type, $lines, $age\n";

srand($$+time());

my @browsers = (
	'Mozilla/4.75 [en] (X11; U; Linux 2.2.15-2.5.0 i686)', 
	'Mozilla/4.08 [en] (Win98; I',
	'Mozilla/4.0 (compatible; MSIE 4.01; Windows 98)',
	'Willzilla/6.66 [ca] (X11; U; Winux 6.66 athlon)',
	);
my @sites;
opendir(SITES, '/home/sites') || die "No sites: $!";
while($_ = readdir(SITES)) {
	next if (/^\.*$/);
	next if (/^server$/ || /^home$/);
	
	push(@sites, $_) if 
		((!-f '/home/sites'.$_) && (!-d '/home/sites'.$_));
}

my $tree = `cd /home/sites/$sites[0]/$type; find ./ 2>/dev/null`;
$tree =~ s/[\n\r]/ /g;
$tree =~ s/(\s)\.\//$1\//g;
$tree =~ s/^\.\//$1\//g;
my @files = split(/\s+/, $tree);

# web
# vhost36.cobalt.com 10.9.25.79 - - [06/Aug/2001:09:50:09 -0700] "GET /hello.jsp HTTP/1.0" 200 975 "-" "Mozilla/4.75 [en] (X11; U; Linux 2.2.15-2.5.0 i686)"

# mail, outgoing then incoming
# Aug  7 13:17:39 localhost sendmail[4091]: f77KHdX04091: from=<admin@vhost34.cobalt.com>, size=317, class=0, nrcpts=1, msgid=<Pine.LNX.4.33.0108071317350.4063-100000@vhost34.cobalt.com>, proto=ESMTP, relay=admin@localhost
# Aug  7 13:17:39 localhost sendmail[4092]: f77KHdX04091: to=/dev/null, ctladdr=<nobody@vhost34.cobalt.com> (8/0), delay=00:00:00, xdelay=00:00:00, mailer=*file*, pri=30027, dsn=2.0.0, stat=Sent

# ftp
# Tue Aug  7 13:31:39 2001 0 vhost44.cobalt.com 4051 /home/.users/112/admin/web/index.html b _ o r admin ftp 0 * c
# Tue Aug  7 14:33:01 2001 5 vhost34.cobalt.com 23456211 /home/.users/112/admin/vhost34.dev.tgz b _ o r admin ftp 0 * c
# Tue Sep 18 09:17:28 2001 9 psyboc.cobalt.com 20381696 /home/.sites/106/site3/ftp/common.tgz b _ o a mozilla@ ftp 0 * c

my $i = 1;

my %month = (
	'01' => 'Jan',
	'02' => 'Feb',
	'03' => 'Mar',
	'04' => 'Apr',
	'05' => 'May',
	'06' => 'Jun',
	'07' => 'Jul',
	'08' => 'Aug',
	'09' => 'Sep',
	'10' => 'Oct',
	'11' => 'Nov',
	'12' => 'Dec');

my %ram_suck;
while($i < $lines) {
	my $site = $sites[ int(rand($#sites)+0.5) ];
	my $file = $files[ int(rand($#files)+0.5) ];
	my $browser = $browsers[ int(rand($#browsers)+0.5) ];

	# my $skew = $age*31536000; # epoch year
	my $skew = $age*86400; # epoch day
	my $time = time();
	# warn "My skew, time: $skew, ".time()."\n";

	my @date = localtime( int(rand($skew)) + $time - $skew);
	$date[5] += 1900;
	$date[4]++;
	my $j = 0;
	while($j < 5) {
		$date[$j] = '0'.$date[$j] if ($date[$j] =~/^\d{1}$/);
		$j++;
	}
	my $date = $date[3].'/'.$month{$date[4]}.'/'.$date[5].":$date[2]:$date[1]:$date[0] -0700"; 

	# 47|48|03|19|6|1998|5|169|1

	my $bytes = int(rand(99999));

	my $ip = int(rand(255)).'.'.int(rand(255)).'.'.
		 int(rand(255)).'.'.int(rand(255));
	
	my $sl_time = "$date[5]/$date[4]/$date[3]";

	if($type eq 'web') {
		# $ram_suck{$sl_time} .= "$site $ip - - [$date] \"GET $file HTTP/1.0\" 200 $bytes \"-\" \"$browser\"\n";
		print "$site $ip - - [$date] \"GET $file HTTP/1.0\" 200 $bytes \"-\" \"$browser\"\n"
	} elsif ($type eq 'mail') {
	} elsif ($type eq 'ftp') {
	}

	$i++;
}

# my $datum;
# foreach $datum (keys %ram_suck) {
# 	# warn "split_logs $datum\n";
# 	# warn "split_logs $datum for:\n".$ram_suck{$datum};
# 	open(SL, "| /usr/local/sbin/split_logs web $datum") || 
# 		warn "Could not open split_logs: $!";
#  	print SL $ram_suck{$datum};
# 	close(SL);
# }

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

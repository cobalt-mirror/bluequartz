#!/usr/bin/perl 

use lib '/usr/sausalito/perl';
use CCE;

my $cce = new CCE(Namespace => 'Time');
$cce->connectfd();

my $localtime = '/etc/localtime';
my $clock = '/etc/sysconfig/clock';
my $oid = $cce->event_oid();
my $time_obj = $cce->event_object();
my $old = $cce->event_old();

# We can set the time and defer application to the OS using the
# deferCommit boolean flag.  Time chanes will be applied by a 
# constructor on CCE restart
unless($time_obj->{deferCommit})
{
	# set the timezone first
	my $zone = $time_obj->{timeZone};

	# Obnoxious glibc UTC sign swap
	if ($zone =~ /GMT\+\d+/) {
		$zone =~ s/\+/\-/;
	} elsif ($zone =~ /GMT\-\d+/) {
		$zone =~ s/\-/\+/;
	}

	my $link = '../usr/share/zoneinfo/' . $zone;
	if ($zone and (readlink($localtime) ne $link)) {
		unlink('/etc/localtime');
		symlink($link, '/etc/localtime');
	}

	# update /etc/sysconfig/clock
	my $fn = sub {
		my ($fin, $fout) = (shift,shift);
		my ($text) = (shift);

		while (<$fin>) {
			if(m/^ZONE/) {
				# print out the CCE maintained section
				print $fout "ZONE=\"$text\"\n";
			} else {
				print $fout $_;
			}
		}

		return 1;
	};

	if (!Sauce::Util::editfile($clock, $fn, $zone)) {
		$cce->warn("[[base-time.errorWritingConfFile]]");
	}

	# set the time if necessary. 
	my $time = $time_obj->{epochTime};

	if($old->{epochTime} && $time_obj->{epochOffset})
	{
		$time = $time + (time() - $time_obj->{epochOffset});
		$cce->set($oid, 'Time', {'epochOffset' => 0});
	}

	if (($time ne $old->{epochTime}) ||
	    ($old->{deferCommit})) 
	{
		
	    `/usr/sausalito/sbin/epochdate $time`;
	    # resync the hwclock with the time
	    system ("/sbin/hwclock --utc --systohc > /dev/null");
 	   unlink("/etc/adjtime"); # get rid of any clock skew
	    system ("/sbin/hwclock --utc --systohc > /dev/null");
	}

	# reload ntpd, if it's running, after the hw clock set
	Sauce::Service::service_run_init('ntpd', 'reload') if 
		($time_obj->{ntpAddress});
}

$cce->bye("SUCCESS");
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

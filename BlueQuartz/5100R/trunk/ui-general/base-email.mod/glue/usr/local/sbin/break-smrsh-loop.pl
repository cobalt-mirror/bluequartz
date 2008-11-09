#!/usr/bin/perl  
#
# By patricko@staff.singnet.com.sg, 20060801
#
# Make sendmail mailer: smrsh bounce mails instead of EX_TEMP - retry
#
# [Description]
# Why? procmail have a minus -t option to bounce mails 
# However there isnt such option in smrsh. 
# Therefore mailer:smrsh return EX_TEMP instead 
# and retry these mails (auto-reply) later.
# This script try to make mailer return error and
# break the mail loop. 
#

my @list_of_blue_users = `/usr/sausalito/sbin/get_quotas.pl`;

foreach $kiss_this_blue (@list_of_blue_users) {
	($a, $b, $c) = split( '\s+', $kiss_this_blue);
	# print "DEBUG -- " . $a . " " . $b . " " . $c ;
	# skip non-quota users
	if (scalar($c) != 0) {
		# overquota users
		if (scalar($b) >= scalar($c)) {
			print "removing .$a.db - " . $a . " " . $b . " " . $c . "\n";
			system("cd ~$a;rm -f .$a.db");
		}
	}
}

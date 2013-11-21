#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
# $Id: fixproftpd_conf.pl Thu 06 Aug 2009 09:03:51 PM EDT mstauber $
#
# This script outputs the VirtualHost containers that are supposed to be 
# present in your /etc/proftpd.conf - based on the IPs your sites are
# using.

use CCE;
my $cce = new CCE;
$cce->connectuds();

# Root check:
my $id = `id -u`;
chomp($id);
if ($id ne "0") {
    print "$0 must be run by user 'root'!\n";

    $cce->bye('FAIL');
    exit(1);
}

# Find all Vsites:
my @vhosts = ();
my (@vhosts) = $cce->findx('Vsite');

# Walk through Vsites:
for my $vsite (@vhosts) {
    ($ok, my $my_vsite) = $cce->get($vsite);

	# Build an array containing all IPs of all Vsites:
	push(@ips, $my_vsite->{ipaddr});

	# Remove IP's that are listed more than once:
	@unique_ips=&del_double(@ips);
}

foreach $ip (@unique_ips) {
	if (($ip) && ($ip ne "127.0.0.1")) {
	    print "<VirtualHost $ip>\n";
	    print "    DefaultRoot     / wheel\n";
	    print "        DefaultRoot             / admin-users\n";
	    print "        DefaultRoot             ~/../../.. site-adm\n";
	    print "        DefaultRoot             ~ !site-adm\n";
	    print "        AllowOverwrite  on\n";
	    print "        DefaultChdir            /web\n";
	    print "        DisplayLogin    .ftphelp\n";
	    print "</VirtualHost>\n";
	    print "\n"
	};
}

# Function to remove dublettes from an array:
sub del_double {
	my %all;
	$all{$_}=0 for @_;
	return (keys %all);
} 

$cce->bye('SUCCESS');
exit(0);

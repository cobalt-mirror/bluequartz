#!/usr/bin/perl
# $Id: configure_mailman.pl Sun 24 Apr 2011 11:30:45 PM CEST mstauber $
# Copyright 2011, Team BlueOnyx. All rights reserved.

use lib '/usr/sausalito/perl';
use Sauce::Util;
use Sauce::Config;
use CCE;

my $cce = new CCE;
$cce->connectuds();

# sync up system with cce
@OIDS = $cce->find('System');
if (@OIDS) {
    my ($ok, $obj) = $cce->get($OIDS[0], 'MailListStatus');
    my $force_update = $obj->{force_update};
    my $enabled = $obj->{enabled};
    my $configured = $obj->{configured};
    my $admin_pw = $obj->{enabled};

	# If MailMan hasn't been configured yet, do it now:
	if ($configured ne "1") {

	    # Get FQDN:
	    my $myhost = `/bin/hostname -s`;
	    chomp ($myhost);
	    if ( $myhost eq "" ) {
    	        $myhost = "localhost";
	    }
	    my $mydomain = `/bin/hostname -d`;
	    chomp ($mydomain);
	    if ( $mydomain eq "" ) {
    	        $mydomain = "localdomain";
	    }
	    $fqdn = $myhost . "." . "$mydomain";

	    # Generate random admin password for MailMan:
	    my $admin_pw = &generate_random_pass(11);

	    # Make sure there is no mailman list already:
	    system("/usr/lib/mailman/bin/rmlist -a mailman > /dev/null 2>&1");
	    sleep(3);

	    # Generate initial mandatory mailman list:
	    system("/usr/lib/mailman/bin/newlist -q --urlhost=$fqdn --emailhost=$fqdn mailman admin\@$fqdn $admin_pw >/dev/null 2>&1");
	    sleep(3);

	    # Create default alias-list for mailman:
	    ${list} = "mailman";
	    my %aliases = (
    		$list => "\"|/usr/lib/mailman/mail/mailman post ${list}\"",
    		$list.'-admin' => "\"|/usr/lib/mailman/mail/mailman post ${list}\"",
    		$list.'-bounces' => "\"|/usr/lib/mailman/mail/mailman bounces ${list}\"",
    		$list.'-confirm' => "\"|/usr/lib/mailman/mail/mailman confirm ${list}\"",
    		$list.'-join' => "\"|/usr/lib/mailman/mail/mailman join ${list}\"",
    		$list.'-leave' => "\"|/usr/lib/mailman/mail/mailman leave ${list}\"",
    		$list.'-owner' => "\"|/usr/lib/mailman/mail/mailman owner ${list}\"",
    		$list.'-request' => "\"|/usr/lib/mailman/mail/mailman request ${list}\"",
    		$list.'-subscribe' => "\"|/usr/lib/mailman/mail/mailman subscribe ${list}\"",
    		$list.'-unsubscribe' => "\"|/usr/lib/mailman/mail/mailman unsubscribe ${list}\"",
	    );

	    # Update /etc/mail/aliases.mailman
	    my $ok = Sauce::Util::editfile('/etc/mail/aliases.mailman',
	      \&Sauce::Util::replace_unique_entries, $oid, \%aliases);
        
	    if (!$ok || ($ok eq 'FAIL')) {
	      $cce->warn("Mail-alias-already-taken");
	      $cce->bye("FAIL");
	      exit(1);
	    }

	    # Run newaliases:
	    system("/usr/bin/newaliases >/dev/null 2>&1");

	    # Update CCE with the new info so that this snippet only runs once:
	    $cce->set($OIDS[0], 'MailListStatus', { 
		'configured' => "1",
		'enabled' => "1", 
		'admin_pw' => $admin_pw, 
		'force_update' => time()
		});
	}
}

$cce->bye('SUCCESS');

# This function generates random password of a given length
sub generate_random_pass {
	my $length_of_random_pass=shift;

	my @chars=('a'..'z','A'..'Z','0'..'9','_', ".");
	my $random_string;
	foreach (1..$length_of_random_pass) {
		# rand @chars will generate a random 
		# number between 0 and scalar @chars
		$random_string.=$chars[rand @chars];
	}
	return $random_string;
}

exit 0;

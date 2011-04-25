#!/usr/bin/perl -w -I/usr/sausalito/perl -I. -I/usr/sausalito/handlers/base/mailman
# $Id: sync_subscribers.pl,v 1.0.0-1 Sun 24 Apr 2011 07:03:22 PM CEST
# Copyright 2011 Team BlueOnyx. All rights reserved.
#
# Counts subscribers for mailing lists GUI
# System.MailList .site .commit

my $DEBUG = 0;
$DEBUG && warn `date`."$0\n";

use MailMan; # should be a local file
use CCE;
my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my($ok, $obj) = $cce->get($oid, 'MailList');

my %crit;
$crit{'site'} = $obj->{site} if ($obj->{site});

# get the fqdn so, local subscribers get counted correctly
my $fqdn = '';
if ($obj->{site}) {
	my ($vs_oid) = $cce->find('Vsite', { 'name' => $obj->{site} });
	($ok, my $vsite) = $cce->get($vs_oid);
	if (!$ok) {
		$cce->bye('FAIL', '[[base-mailman.systemError]]');
		exit(1);
	}
	$fqdn = $vsite->{fqdn};
} else {
	my ($sys_oid) = $cce->find('System');
	($ok, my $sys) = $cce->get($sys_oid);
	if (!$ok) {
		$cce->bye('FAIL', '[[base-mailman.systemError]]');
		exit(1);
	}
	$fqdn = $sys->{hostname} . '.' . $sys->{domainname};
}

$DEBUG && warn "Site: $obj->{site}\n";

my (@lists) = $cce->find('MailList', { 'site' => $obj->{site} });
foreach my $oid (@lists) {
	$DEBUG && warn "Fetching oid: $oid\n";

	my($ok, $ml) = $cce->get($oid);
	next unless ($ok);

	$listname = $ml->{internal_name};
	my $generate_list = `/usr/lib/mailman/bin/list_members -r $listname > /tmp/mailman-$listname.lst`;
	my $list = "/tmp/mailman-$listname.lst";
	my $generate_digest_list = `/usr/lib/mailman/bin/list_members -d $listname > /tmp/mailman-$listname-digest.lst`;
	my $list_digest = "/tmp/mailman-$listname-digest.lst";

	next unless (-r $list);
	open(LIST, $list) || next;
	open(LISTDIG, $list_digest) || next;

	my($local, $remote, $remote_digest) = ('&', '&', '&');

	while (<LIST>) {
		chomp;
		next if (/^\s*$/o);
		next if (/\&/o); # control character

		#
		# listings with this fqdn are local if the specified user
		# name is a site member.  Server administrators can end up
		# having an email with the same fqdn as the site this list
		# belongs too, but they should show up as remote subscribers.
		#
		my $is_local = 0;
		if (/^([^\@]+)\@$fqdn$/) {
			#
			# make sure the email is a site member before assuming
			# they are local
			#
			my ($uoid) = $cce->find('User',
						{
							'name' => $1,
							'site' => $ml->{site}
						});
			if ($uoid || ($1 eq 'nobody')) {
				# really is a local user
				$_ = $1;
				$is_local = 1;
			} else {
				# not a site member
				$_ = "$1\@$fqdn";
			}
		} elsif (!/^([^\@]+)\@(\S+)$/) {
			# still might not be a user from this site
			# check a user without fqdn
			my $user = $_;
			my ($uoid) = $cce->find('User',
						{
							'name' => $_,
							'site' => $ml->{site}
						});
			$_ = $user;
			if ($uoid || ($user eq 'nobody')) {
				# local user who should be local for this list
				$is_local = 1;
			}
		}
		if(!$is_local) {
			$remote .= $_ . '&';
		} else {
			$local .= $_ . '&';
		}
	}

	while (<LISTDIG>) {
		chomp;
		next if (/^\s*$/o);
		next if (/\&/o); # control character

		$remote_digest .= $_ . '&';
	}
	close(LISTDIG);
	unlink($list_digest);

	close(LIST);
	unlink($list);

	$local = '' if ($local eq '&');
	$remote = '' if ($remote eq '&');
	$remote_digest = '' if ($remote_digest eq '&');

	$DEBUG && warn "List oid: $oid\nLocal:\n$local\nRemote:\n$remote\nRemote Digest:\$remote_digest";

	my ($aok) = $cce->set($oid, '',
			      {
				'remote_recips_digest'=>$remote_digest,
				'remote_recips'=>$remote,
				'local_recips'=>$local,
			      });
}

$cce->bye('SUCCESS');
exit(0);


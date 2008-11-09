#!/usr/bin/perl -w -I/usr/sausalito/perl -I. -I/usr/sausalito/handlers/base/maillist
# $Id: sync_subscribers.pl 462 2005-03-29 06:22:59Z shibuya $
#
# Counts subscribers for mailing lists GUI
# System.MailList .site .commit

my $DEBUG = 0;
$DEBUG && warn `date`."$0\n";

use MailList; # should be a local file
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
		$cce->bye('FAIL', '[[base-maillist.systemError]]');
		exit(1);
	}
	$fqdn = $vsite->{fqdn};
} else {
	my ($sys_oid) = $cce->find('System');
	($ok, my $sys) = $cce->get($sys_oid);
	if (!$ok) {
		$cce->bye('FAIL', '[[base-maillist.systemError]]');
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

	my $list = '/usr/local/majordomo/sites/'.
		$ml->{site} . '/lists/' . $ml->{name};

	next unless (-r $list);
	open(LIST, $list) || next;

	my($local, $remote) = ('&', '&');

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
	close(LIST);

	$local = '' if ($local eq '&');
	$remote = '' if ($remote eq '&');

	$DEBUG && warn "List oid: $oid\nLocal:\n$local\nRemote:\n$remote\n";

	my ($aok) = $cce->set($oid, '',
			      {
				'remote_recips'=>$remote,
				'local_recips'=>$local,
			      });
}

$cce->bye('SUCCESS');
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

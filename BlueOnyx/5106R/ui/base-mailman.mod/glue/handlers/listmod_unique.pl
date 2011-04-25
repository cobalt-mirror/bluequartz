#!/usr/bin/perl -w -I/usr/sausalito/perl -I. -I/usr/sausalito/handlers/base/mailman
# $Id: listmod_unique,v 1.0.0-1 Sun 24 Apr 2011 07:03:22 PM CEST
# Copyright 2011 Team BlueOnyx. All rights reserved.
#
# listmod_unique checks for the uniqueness of the list name.
#		name
#		_CREATE

my $DEBUG = 0;
$DEBUG && warn `date`;

use CCE;
my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();

if ($obj->{site} ne '') {
	# RaQ site-level mailling list
  	$DEBUG && warn "per-site list detected, site: " . $obj->{site} . ', ' .
		       $obj->{name};

	# Check for mailman->{name} usage in this site
	my(@ml_oids) = $cce->find('MailMan', { 'name' => $obj->{name}, 'site' => $obj->{site} });
	&bail() if ($ml_oids[1]);

	# make sure there is no alias
	my ($vs_oid) = $cce->find('Vsite', { 'name' => $obj->{site} });
	my ($ok, $vsite) = $cce->get($vs_oid);
	
	# check for aliases that would conflict with this MailMan
	my @conflicts = $cce->find('EmailAlias',
				   {
				   	'alias' => $obj->{name},
					'fqdn' => $vsite->{fqdn}
				   });
	push @conflicts, $cce->find('ProtectedEmailAlias',
				    {
				    	'alias' => $obj->{name},
					'fqdn' => $vsite->{fqdn}
				    });
	if (scalar(@conflicts)) {
		&bail('[[base-mailman.name-already-taken-by-user]]');
	}

	# Generate internal encoded name, mailman->{internal_name}
	# prepended oid, oids are unique, how convenient
	my $internal_name = $oid . '-' . $obj->{name};
	$ok = $cce->set($oid, '', { 'internal_name' => $internal_name });
	$DEBUG && warn "Set internal_name to ".$internal_name;

} else { # Qube-style server-level mailling list

  # make sure list names are unique:
  my @matches = $cce->find('MailMan', { 'name' => $obj->{name} } );
  if ($#matches > 0) {
    $cce->baddata($oid, 'name', '[[base-mailman.name-already-taken]]');
    &bail();
  }
}

$cce->bye('SUCCESS');
exit(0);

sub bail
{ 
  my $msg = shift;
  $msg ||= '[[base-mailman.name-already-taken]]';
  $cce->bye('FAIL', $msg);
  exit(1);
}

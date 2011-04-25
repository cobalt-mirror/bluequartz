#!/usr/bin/perl -w -I/usr/sausalito/perl -I. -I/usr/sausalito/handlers/base/mailman
# $Id: listmod_aliases,v 1.0.0-1 Sun 24 Apr 2011 07:03:22 PM CEST
# Copyright 2011 Team BlueOnyx. All rights reserved.
#
# listmod_aliases depends on:
#		name
#		enabled
#		_CREATE

use MailMan; # should be a local file
use CCE;
my $cce = new CCE( 'Domain' => 'base-mailman' );
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();
my $new = $cce->event_new();
my $old = $cce->event_old();

my($vsite_obj, $aok);
if($obj->{site}) {
	($aok, $vsite_obj) = $cce->get( 
		($cce->find('Vsite', {'name' => $obj->{site}}))[0]);
}

my $mod = $obj->{moderator};
if ($mod !~ m/\@/) {
	if (!getpwnam($mod)) {
		$cce->baddata(0, 'moderator', 'Moderator-does-not-exist', { 'moderator' => $mod });
		$cce->warn('Could-not-create-mailman');
		$cce->bye('FAIL');
		exit(1);
	}
}

# create aliases:
# extract information about the list...
my $list_external = $obj->{name};
my $list = $obj->{internal_name};
$list ||= $list_external;

my $enabled = $obj->{enabled}; 
if (!defined($enabled) || $enabled =~ m/^\s*$/) { $enabled = 1; }

my $owner = $obj->{moderator}; # FIXME: can we do better than this?

# configure resend options:
my @resend_opts = (
  '-l', $list_external	# the list name
);
    
# If reply to list is set, then set reply-to header
# Else, the header defaults to original sender, or the original sender's
# reply-to header.
my $replyToList = $obj->{replyToList};
if ($replyToList) {
	my $replyaddr = $list_external.'@'.$vsite_obj->{fqdn} if($vsite_obj->{fqdn});
	push @resend_opts, ( '-r', $replyaddr);
}

# what aliases shall i write today?
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

# print STDERR "names: $old->{name} -> $obj->{name}\n";

# my $ok = modify_aliases(\%aliases, $old->{name}, $obj->{name});
my $ok = Sauce::Util::editfile('/etc/mail/aliases.mailman', 
  \&Sauce::Util::replace_unique_entries, $oid, \%aliases);
  
if (!$ok || ($ok eq 'FAIL')) {
  $cce->warn("Mail-alias-already-taken");
  $cce->bye("FAIL");
  exit(1);
}


# re-parse aliases:
Sauce::Util::modifyfile('/etc/mail/aliases.db');
system("/usr/bin/newaliases");

$cce->bye('SUCCESS');
exit(0);

sub modify_aliases
{
  my ($aliases, $oldname, $newname) = (@_);
  my $k;
  my $ok = 1;
  my %oldoids = ();
  foreach $k (keys %$aliases) {
    my $orig = $k;
    my $v = $aliases->{$k};
    $oldk = $k;
    $oldk =~ s/\{list\}/$oldname/g;
    $k =~ s/\{list\}/$newname/g;
    $v =~ s/\{list\}/$newname/g;
    my @oids = ();
    if (defined($oldname)) {
      @oids = $cce->find("EmailAlias", { 'name' => $oldk });
      # print STDERR "find name=$oldk -> @oids\n";
    }
    if (@oids) {
      ($ok) = $cce->set($oids[0], "", {
      	'name' => $k,
	'action' => $v,
      } );
      if ($ok) { $oldoids{$oids[0]} = undef; }
    } else {
      ($ok) = $cce->create("EmailAlias", {
        'name' => $k,
        'action' => $v,
      } );
      if (!$ok) { print STDERR "failed to create $k, $v: $ok\n"; }
      if ($ok) { $oldoids{$cce->oid()} = $orig; }
    }
    last if (!$ok);
  }
  
  if (!$ok) {
    # back out changes
    foreach my $k (keys %oldoids) {
      if (!defined($oldoids{$k})) {
      	$cce->destroy($k);
      } else {
      	my $key = $oldoids{$k};
	my $val = $aliases->{$key};
	$key =~ s/\{list\}/$oldname/;
	$val =~ s/\{list\}/$oldname/;
	$cce->set($k, "", { 'name' => $key, 'action' => $val });
      }
    }
  }

  return $ok;
}


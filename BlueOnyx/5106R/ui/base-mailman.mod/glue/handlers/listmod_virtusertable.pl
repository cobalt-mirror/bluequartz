#!/usr/bin/perl -w -I/usr/sausalito/perl -I. -I/usr/sausalito/handlers/base/mailman
# $Id: listmod_virtusertable,v 1.0.0-1 Sun 24 Apr 2011 07:03:22 PM CEST
# Copyright 2011 Team BlueOnyx. All rights reserved.
#
# listmod_virtusertable depends on:
#		name
#		_CREATE
#		_DESTROY

# golden virtusertable
# bbspot-approval@vhost42.cobalt.com      52-6800bbspot-approval
# bbspot-request@vhost42.cobalt.com       52-6800bbspot-request
# owner-bbspot@vhost42.cobalt.com owner-52-6800bbspot
# bbspot-owner@vhost42.cobalt.com 52-6800bbspot-owner

use CCE;
my $cce = new CCE('Domain' => 'base-mailman');
$cce->connectfd();

my $DEBUG = 0;
$DEBUG && warn "$0 ".`date`;

my $oid = $cce->event_oid();
my $obj = $cce->event_object();
my $new = $cce->event_new();
my $old = $cce->event_old();

# We substitute LIST out of the equation
# Thanks Patrick
my @alii = ('LIST-admin', 'LIST-bounces', 'LIST-confirm',
            'LIST-join', 'LIST-leave', 'LIST-owner',
            'LIST-request', 'LIST-subscribe', 'LIST-unsubscribe',
            'LIST');

my $ret = 1;

if (!$new->{site} && !$obj->{site} && !$old->{site}) {
	$DEBUG && warn "No site affiliation found";
	# do nothing for non-virutal site products (aliases is enough)
} elsif ($cce->event_is_create()) {
	$DEBUG && warn "Adding list... ";
	$DEBUG && warn $new->{name} . ', ' . $new->{internal_name};

	my @site_oids = $cce->find('Vsite', { 'name' => $new->{site} });
	if ($site_oids[0]) {
		my ($ok, $site) = $cce->get($site_oids[0]);

		$ret = &edit_alii($cce, 1, $new->{name}, $site->{fqdn},
				  $new->{internal_name}, $new->{site});
		$ret = &edit_alii_internal($cce, 1, $new->{internal_name}, $site->{fqdn},
				  $new->{internal_name}, $new->{site});
	} else {
		$cce->bye('DEFER');
	}
} elsif ($cce->event_is_destroy()) {
	$DEBUG && warn "Deleting list...";

	$ret = &edit_alii($cce, 0, $old->{name}, 'XXXnodomainXXX',
			  $old->{internal_name});
	$ret = &edit_alii_internal($cce, 0, $old->{internal_name}, 'XXXnodomainXXX',
			  $old->{internal_name});
} elsif ($new->{name} && $old->{name}) {
	$DEBUG && warn "List changing names...";
	my @site_oids = $cce->find('Vsite', { 'name' => $obj->{site} });
	if ($site_oids[0]) {
		my ($ok, $site) = $cce->get($site_oids[0]);

		# Ignore return value on alias deletion
		&edit_alii($cce, 0, $old->{name}, $site->{fqdn},
			   $old->{internal_name});
		$ret = &edit_alii($cce, 1, $new->{name}, $site->{fqdn},
				  $new->{internal_name}, $obj->{site});
		&edit_alii_internal($cce, 0, $old->{internal_name}, $site->{fqdn},
			   $old->{internal_name});
		$ret = &edit_alii_internal($cce, 1, $new->{internal_name}, $site->{fqdn},
				  $new->{internal_name}, $obj->{site});
	}
} else {
	# This is not my beautiful house, this is not my beautiful car...
	$DEBUG && warn "Could not find purpose, motivation.";
}

if ($ret) {
	$DEBUG && warn "disconnect cced, success";
	$cce->bye('SUCCESS');
	exit 0;
} else {
	$DEBUG && warn "disconnect cced, failure";
	$cce->bye('FAIL');
	exit 1;
}

# Subs
sub edit_alii
{
	my ($cce, $add, $name, $domain, $internal_name, $site) = @_;
	$internal_name ||= $name;
	$DEBUG && warn "edit_alii invoked: " . join(', ', @_) . "\n";

	my ($line_config, %config, $instance, %internal_name);

	my @these_alii = @alii;
	for (my $i = 0; $i < scalar(@these_alii); $i++) {
		$instance = $these_alii[$i];

		# internal addressing
		my $internal = $instance;
		$internal =~ s/LIST/$internal_name/;

		# external, worldly addressing

                if ($i > 0){ $instance =~ s/LIST/$name/; }else
                {
                 my $mailmanindex = substr($internal_name, 0, index($internal_name, "-") );
                 my $fullname = join "", $mailmanindex, "-", $name, "-list";
                 $instance =~ s/LIST-list/$fullname/g; 
                }

		$instance =~ s/LIST/$name/;

		# $config{$instance . '@' . $domain} = 1;
		# $internal_name{$internal} = 1;
		# $line_config .= $instance . '@' . $domain . "\t$internal\n";
		if ($add) {
			my $props = {
					'alias' => $instance,
					'fqdn' => $domain,
					'action' => $internal,
					'site' => $site
				    };

			# set last alias so maps get rebuilt
			$props->{build_maps} = ($i == $#these_alii) ? 1 : 0;
			my ($ok) = $cce->create('EmailAlias', $props);
			if (!$ok) {
				return 0;
			}

		} else {
			# destroy the aliases for this list
			my ($alias) = $cce->find('EmailAlias',
						 {
						     'alias' => $instance,
						     'action' => $internal
						 });
			if (!$alias) {
				next;
			}


			unless ($cce->event_is_destroy()) { # on event destroy we don't rebuild the individual objects,
							# because by the time that mapmaker gets there, the objects
							# are already gone.
			
			    # turn off build_maps for every one but the last
			    if ($i < $#these_alii) {
				$cce->set($alias, '', { 'build_maps' => 0 });
			    }
			}
			my ($ok) = $cce->destroy($alias);
			if (!$ok) {
				return 0;
			}
		}
	}
	$DEBUG && warn "Line Config:\n$line_config\n";
	return 1;	
}

sub edit_alii_internal
{
	my ($cce, $add, $name, $domain, $internal_name, $site) = @_;
	$internal_name ||= $name;
	$DEBUG && warn "edit_alii invoked: " . join(', ', @_) . "\n";

	my ($line_config, %config, $instance, %internal_name);

	my @these_alii = @alii;
	for (my $i = 0; $i < scalar(@these_alii); $i++) {
		$instance = $these_alii[$i];

		# internal addressing
		my $internal = $instance;
		$internal =~ s/LIST/$internal_name/;

		# external, worldly addressing

                if ($i > 0){ $instance =~ s/LIST/$name/; }else
                {
                 my $mailmanindex = substr($internal_name, 0, index($internal_name, "-") );
                 my $fullname = join "", $mailmanindex, "-", $name, "-list";
                 $instance =~ s/LIST-list/$fullname/g; 
                }

		$instance =~ s/LIST/$internal_name/;

		# $config{$instance . '@' . $domain} = 1;
		# $internal_name{$internal} = 1;
		# $line_config .= $instance . '@' . $domain . "\t$internal\n";
		if ($add) {
			my $props = {
					'alias' => $instance,
					'fqdn' => $domain,
					'action' => $internal,
					'site' => $site
				    };

			# set last alias so maps get rebuilt
			$props->{build_maps} = ($i == $#these_alii) ? 1 : 0;
			my ($ok) = $cce->create('EmailAlias', $props);
			if (!$ok) {
				return 0;
			}

		} else {
			# destroy the aliases for this list
			my ($alias) = $cce->find('EmailAlias',
						 {
						     'alias' => $instance,
						     'action' => $internal
						 });
			if (!$alias) {
				next;
			}
			
			# turn off build_maps for every one but the last
			unless ($cce->event_is_destroy()) { # on event destroy we don't rebuild the individual objects,
							# because by the time that mapmaker gets there, the objects
							# are already gone.
			    if ($i < $#these_alii) {
				$cce->set($alias, '', { 'build_maps' => 0 });
			    }
			}
			my ($ok) = $cce->destroy($alias);
			if (!$ok) {
				return 0;
			}
		}
	}
	$DEBUG && warn "Line Config:\n$line_config\n";
	return 1;	
}


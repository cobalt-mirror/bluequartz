#!/usr/bin/perl -I/usr/sausalito/perl -I.
# $Id: handle_vsite.pl,v 1.0.0-4 Sun 01 May 2011 01:49:36 AM CEST
# Copyright 2011 Team BlueOnyx. All rights reserved.
#
# handles the creation of a virtual site's mailman infrastructure

use lib qw( /usr/sausalito/perl );
use CCE;
use Sauce::Util;

my $DEBUG = 0;
$DEBUG && warn `date`." $0\n";

my $aliasfile = '/etc/mail/aliases.mailman';

my($UID, $GID) = ( (getpwnam('mail'))[2], (getgrnam('daemon'))[2] );
my($filemod, $dirmod) = (0640, 0700);

my $cce = new CCE; 
$cce->connectfd();

my $errors = '';

my $obj = $cce->event_object();
my $old = $cce->event_old();
my $new = $cce->event_new();
my $oid = $cce->event_oid();

# this runs in the CONFIGURE stage which is the same stage as the handler
# that sets the Vsite.name property, so make sure on create the name
# property is defined before proceeding
if ($cce->event_is_create() && ($obj->{name} eq '')) {
	$cce->bye('DEFER');
	exit(0);
}

if($cce->event_is_destroy()) {

	@oids = $cce->find('MailList', {'site' => $old->{name}});
	if ($#oids < 0) {
	    # Object not found in CCE
	}
	else {
	    foreach $oid (@oids) {
		$cce->destroy($oid); # ignore return on destructive ops
	    }

	    # Destroy per-site mailman aliases
	    foreach my $alias (@mailman_aliases) {
		my(@marked_for_death) = $cce->find('ProtectedEmailAlias', {
			'alias'=>$alias,
			'fqdn'=>$old->{fqdn},
			});
		foreach my $oid (@marked_for_death) {
			my ($ret) = $cce->destroy($oid);
		}
	    }

	    # Destroy aliases.mailman entries for mailman aliases
	    my($ok) = Sauce::Util::editfile(
		$aliasfile,
		*edit_mailmanalias,
		0,
		$old->{name},
		\@mailman_aliases
		);
	    system('/bin/rm -f /etc/mail/aliases.mailman.backup.*');
	} # from else
}
elsif($new->{fqdn}) {
		# Get the name of the list we need to rename
		my (@oidsx) = $cce->find("MailList", {'site' => $old->{name}});
		my ($okx, $objx) = $cce->get($oidsx[0]);
		$internal_name = $objx->{internal_name};

		# New FQDN:
		$new_fqdn = $new->{fqdn};

		if ($new_fqdn ne "") {
		    # Rename a list on FQDN change:
		    system("/usr/lib/mailman/bin/withlist -q -l -r fix_url $internal_name --urlhost=$new_fqdn");
		}

}

if($cce->event_is_create()) {
	foreach my $alias (@mailman_aliases) {
		my $action = 
		my(@oids) = $cce->find('ProtectedEmailAlias', {
			'alias'=>$alias,
			'fqdn'=>$new->{fqdn},
			});
		my ($ret) = $cce->create('ProtectedEmailAlias', {
			'alias'=>$alias,
			'fqdn'=>$new->{fqdn},
			'site'=>$new->{name},
			'action'=>$new->{name}.'-'.$alias,
			}) unless($oids[0]);
	}
}

if($errors) {
	$cce->bye('FAIL', $errors);
	exit 1;
}

$cce->bye("SUCCESS");
exit 0;



sub edit_mailmanalias {
	my($in, $out, $enable, $site, $aliasref) = @_;
	$DEBUG && warn "edit_mailmanalias invoked: ".join(@_)."\n";
	my ($config, %public_alii);
	foreach my $alias (@{$aliasref}) {
		$public_alii{$site.'-'.$alias} = 1;

		if($alias =~ /owner/i) {
			$config .= $site.'-'.$alias.":\tadmin\n";
		}
	}
	$DEBUG && warn "aliases config:\n$config";

	while(<$in>) {
		if(/^(\S+):\s/) {
			if ($public_alii{$1}) {
				$DEBUG && warn "Skipping: $_";
				next;
			}
		}
		print $out $_;
	}
	print $out $config if ($enable);
	return 1;
}

sub edit_list_members {
	my ($in, $out, $old_fqdn, $new_fqdn) = @_;

	while (<$in>) {
		s/^([^\@]+)\@$old_fqdn$/$1\@$new_fqdn/;
		print $out $_;
	}

	return 1;
}

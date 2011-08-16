#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: aliases.pl,v 1.15.2.2 2002/02/21 23:53:21 pbaltz Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# handle any configuration for the mailAliases and webAliases fields
# like adding mail alias routes to the virtusertable and mail aliases 
# to sendmail's localhost file

use CCE;
use Sauce::Util;
use Sauce::Config;
use Base::Httpd qw(httpd_set_server_aliases);
use Sauce::Service qw(service_run_init);

my $DEBUG = 0;
if ($DEBUG) { use Data::Dumper; }

my $cce = new CCE('Domain' => 'base-vsite');
$cce->connectfd();

my $vsite = $cce->event_object();
my $vsite_new = $cce->event_new();
my $vsite_old = $cce->event_old();

if ($cce->event_is_create() && !$vsite->{name})
{
	$cce->bye('DEFER');
	exit(0);
}

if ($cce->event_is_destroy())
{
	# delete all aliases for this site
	my @aliases = $cce->find('ProtectedEmailAlias', 
					{ 'site' => $vsite_old->{name} });
	push @aliases, 
		$cce->find('EmailAlias', { 'site' => $vsite_old->{name} });

	for my $alias (@aliases) {
		my ($ok) = $cce->destroy($alias);
		if (!$ok) {
			$cce->bye('FAIL');
			exit(1);
		}
	}
} else {
	my %new_aliases;
	map { $new_aliases{$_} = '%1@' . $vsite->{fqdn} } 
			$cce->scalar_to_array($vsite->{mailAliases});

	# add catchall email alias
	if ($vsite->{mailCatchAll})
	{
		$new_aliases{$vsite->{fqdn}} = $vsite->{mailCatchAll};
	}
	else
	{
		$new_aliases{$vsite->{fqdn}} = 'error:nouser No such user here';
	}

	# go through new aliases and create and destroy as necessary
	for my $alias (keys(%new_aliases))
	{
		my ($oid) = $cce->find('ProtectedEmailAlias', 
						{ 
							'site' => $vsite->{name},
							'fqdn' => $alias,
							'alias' => ''
						});

		if (!$oid)
		{
			# need to create
			my ($ok) = $cce->create('ProtectedEmailAlias',
							{
								'site' => $vsite->{name},
								'fqdn' => $alias,
								'action' => $new_aliases{$alias},
								'build_maps' => 0
							});
			if (!$ok)
			{
				$cce->bye('FAIL', 'cantCreateMailAlias',
							{ 'alias' => $alias });
				exit(1);
			}
		}
		else
		{
			# make sure the alias is up to date
			my ($ok) = $cce->set($oid, '',
							{
								'site' => $vsite->{name},
								'fqdn' => $alias,
								'action' => $new_aliases{$alias}
							});
			if (!$ok)
			{
				$cce->bye('FAIL', 'cantUpdateMailAlias',
						{ 'alias' => $alias });
				exit(1);
			}
		}
	}
   
	# delete old aliases that are no longer needed
	if (exists($vsite_old->{mailAliases}))
	{
		my @old_aliases = $cce->scalar_to_array($vsite_old->{mailAliases});

		# delete old catch all if fqdn changed
		if ($vsite_old->{fqdn})
		{
			push @old_aliases, $vsite_old->{fqdn};
		}

		for my $alias (@old_aliases)
		{
			if (!exists($new_aliases{$alias}))
			{
				my ($destroy_oid) = $cce->find('ProtectedEmailAlias',
								{
									'site' => $vsite->{name},
									'fqdn' => $alias,
									'alias' => ''
								});
				my ($ok) = $cce->destroy($destroy_oid);
				if (!$ok) {
					$cce->bye('FAIL');
					exit(1);
				}
			}
		}
	}

	# update all user aliases associated with this site if the fqdn changed
	if (!$cce->event_is_create() && $vsite_new->{fqdn})
	{
		my @aliases = $cce->find('ProtectedEmailAlias',
							{
								'site' => $vsite->{name},
								'fqdn' => $vsite_old->{fqdn}
							});

		push @aliases, 
			$cce->find('EmailAlias',
				{
					'site' => $vsite->{name},
					'fqdn' => $vsite_old->{fqdn}
				});
	
		$DEBUG && print STDERR 'oids: ', join(', ', @aliases), "\n";
		for my $alias (@aliases)
		{
			my ($ok, $badkeys, @info) = $cce->set($alias, '', 
										{ 'fqdn' => $vsite->{fqdn} });
			$DEBUG && print STDERR "set $alias, ok = $ok\n";
			$DEBUG && print STDERR Dumper($badkeys, \@info);
			if (!$ok)
			{
				$cce->bye('FAIL', '[[base-vsite.cantUpdateUserMailAliases]]');
				exit(1);
			}
		}
	}

	# update web aliases, if necessary
	if (exists($vsite_new->{webAliases}))
	{
		# map into a hash and then take the keys to avoid duplicates
		my %web_aliases = map { $_ => 1 } $cce->scalar_to_array($vsite->{webAliases});
		my @web_aliases = keys %web_aliases;
		if (!httpd_set_server_aliases(\@web_aliases, $vsite->{name}))
		{
			$cce->bye('FAIL', '[[base-vsite.cantUpdateWebAliases]]');
			exit(1);
		}

		service_run_init('httpd', 'reload');
	}
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

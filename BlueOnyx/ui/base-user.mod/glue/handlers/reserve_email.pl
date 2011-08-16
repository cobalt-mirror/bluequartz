#!/usr/bin/perl -I/usr/sausalito/perl
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: reserve_email.pl,v 1.4.2.1 2002/03/01 02:17:00 pbaltz Exp $
# 
# reserve the user alias for this user

use CCE;

my $cce = new CCE;
$cce->connectfd();

my $user = $cce->event_object();
my $user_new = $cce->event_new();
my $user_old = $cce->event_old();

my $ok = 0;
my $vsite = {};

# get vsite info for creates and modifies
if (!$cce->event_is_destroy() && $user->{site})
{
	my ($vsoid) = $cce->find('Vsite', { 'name' => $user->{site} });
	if ($vsoid)
	{
		($ok, $vsite) = $cce->get($vsoid);

		if (!$ok)
		{
			$cce->bye('FAIL', '[[base-user.cantReadVsite]]');
			exit(1);
		}
	}
}

my $oid = 0;

if (!$cce->event_is_create() 
	&& (exists($user_new->{name}) || exists($user_new->{site}) 
		|| $cce->event_is_destroy()))
{
	($oid) = $cce->find('EmailAlias',
					{
						'alias' => $user_old->{name},
						'site' => $user_old->{site}
					});
}

# do what is supposed to happen
if ($cce->event_is_destroy())
{
	($ok) = $cce->destroy($oid);

	if ($oid && !$ok)
	{
		$cce->bye('FAIL', '[[base-user.cantDeleteEmailAlias]]');
		exit(1);
	}
}
elsif (!$oid)
{
	# create
	($ok) = $cce->create('EmailAlias',
						{
							'alias' => $user->{name},
							'site' => $user->{site},
							'action' => $user->{name},
							'fqdn' => $vsite->{fqdn}
						});
	if (!$ok) 
	{
		$cce->bye('FAIL');
		exit(1);
	}
}
else
{
	# modify
	($ok) = $cce->set($oid, '',
					{
						'alias' => $user->{name},
						'site' => $user->{site},
						'action' => $user->{name},
						'fqdn' => $vsite->{fqdn}
					});

	if (!$ok)
	{
		$cce->bye('FAIL');
		exit(1);
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

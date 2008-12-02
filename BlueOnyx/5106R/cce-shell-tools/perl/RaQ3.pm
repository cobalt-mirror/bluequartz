# $Id: RaQ3.pm,v 1.4 2002/04/26 21:04:49 jeffb Exp $ 
# Cobalt Networks, Inc http://www.cobalt.com
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

package RaQ3;

use Configurator;
use vars qw(@ISA);
@ISA = qw(Configurator);

sub new
{
	my $proto = shift;
	my $class = ref($proto) || $proto;
	my $self = Configurator->new(@_);
	bless($self, $class);

	return $self;
}


sub fqdnToGroup
# Argument: fqdn (fully qualified domain name)
# Side Effects: None
# Return: group belonging to the FQDN
{
	my $self = shift;
	my $fqdn = shift || $self->{fqdn};

	require Cobalt::Meta;
	my ($group) = Cobalt::Meta::query(type  => "vsite",
		"keys"  => ["name"],
		"where" => ["name", "<>", "default",
		"and", "fqdn", "=", "$fqdn"]);
	if (!$group) { die "Cannot find fqdn: $fqdn\n"; } 
	else { return($group) }
}

sub getUserFqdn
{
	my $self = shift;
	my $user = shift || $self->{name};

	require Cobalt::Meta;
	my $obj = Cobalt::Meta->create("type" => "users");
    $obj->retrieve($user);

	return $obj->get('fqdn');
}

sub clistvsite
{
	my $self = shift;

	my @info = $self->listVsites();
	foreach my $obj (@info) {
		print $obj->{fqdn}, "\t", $obj->{name}, "\t", $obj->{ipaddr}, "\n";
	}
}


sub modForwardUser
{
	my $self = shift;
	my $user = shift;
	my $val = shift || $self->{forward};

	require Cobalt::List;
	require Cobalt::Netutil;
	require Cobalt::Meta;
	my $ret;
	
	my $obj = Cobalt::Meta->create("type" => "users");
    $obj->retrieve($user);

	if($val eq 'off') {
		$obj->put(forward => 'off');
		Cobalt::List::alias_delete($user);
	} else {
		$obj->put(forward => $val);
		Cobalt::List::alias_set($user, $val);
	}
	$obj->save;
}

sub modAliasesUser
{
	my $self = shift;
	my $type = shift;
	my $user = shift || $self->{name};

	require Cobalt::Meta;
	require Cobalt::Email;
	my $obj = Cobalt::Meta->create("type" => "users");
    $obj->retrieve($user);

	my ($alii,@alii);
	if($type eq 'set') {
		if($self->checkUserAliases == 1) {
			$alii = join(' ', @{ $self->{aliases} });
			$obj->put(aliases => $alii);
			foreach my $a (@{ $self->{aliases} }) {
			    push @alii, (/\@/) ? "$a" : "$a\@$fqdn";
			}
		}
	}

	# add would take the orignal and add them

	$ret = Cobalt::Email::mail_virtuser_set_byuser($user, @alii);
	if($ret!~/^2/o) { $err=1; }
	$obj->save();
	# add an alias to self
	push @alii, "$userName\@$fqdn";


	} elsif($type eq 'set') {

	} elsif($type eq 'remove') {

	} else { print "Unknown type, I don't wtf is going on\n"; }
	return 1;
}

sub checkUserAliases 
{
	my $self = shift;

	# this is just setup in case it wasn't done
	my $fqdn;
	if(defined $self->{fqdn}) { $fqdn = $self->{fqdn} }	
	else { $fqdn = $self->getUserFqdn }

	my $group;
	if(defined $self->{group}) { $group = $self->{group} }
	} else { $group = $self->groupFqdn }

	# stolen from siteUserEmail.cgi
	my (@resultAliases, $alias, @badalii, %aliasHash);
	my @aliases = @{ $self->{aliases} };
	my $err = 1;

	map { map { $aliasHash{$_} = 1; }
		split(' ', $_); } Cobalt::Meta::query("type"  => "users",
					"keys"  => ["aliases"],
				    "where" => ["vsite", "=", "$group",
					"and", "name", "<>", "$userName"]);
	foreach $alias (@aliases) {
		next unless ($alias);
		if($alias =~ /^\@(.+)/) {
			if($1 !~ /\Q$fqdn\E$/ || exists $aliasHash{$alias}) {
				push @badalii, $alias;
				$err = -1;
			} else { push @resultAliases, $alias; }
		} else {
			$alias =~ s/^([^@]+)\@.*/$1/;
			if(exists $aliasHash{$alias} || 
				$alias !~ /^[a-zA-Z0-9\@\.\-\_]+$/o) {
				push @badalii, $alias;
				$err = -1;
			} else { push @resultAliases, $alias; }
    	}
	}
	if($err == -1) {
		print "These aliases are not valid: @badalii\n"
	}
	return $err;	
}

sub cadduser
{
	my $self = shift;
	my $data = {};

	require Cobalt::Meta;
	require Cobalt::User;

	# set the user name
	$data->{name} = lc($self->{name});
	if(Cobalt::User::user_exist($data->{name})) {
		die $data->{name}, " already exists\n\n";
	}

	# set the user site
	if(defined $self->{group}) {
		$data->{group} = $self->{group};
	} elsif(defined $self->{fqdn}) {
		$data->{group} = $self->fqdnToGroup($self->{fqdn});
	} else { 
		my $str = "Add the user to base site: ".$self->defaultFqdn."?\n";
		if($self->getInputBool($str) eq 'y') {
			$data->{group} = 'home'; 
		} else {
			die "You must provide a site that the user will be added to.\n";
		}
	}

	# user Full Name
	if(defined $self->{fullName}) {
		$data->{fullName} = $self->{fullName};
	} else {
		$data->{fullName} = $data->{name};
		print "Using user name for user full name\n";
	}
	
	# user password
	if(defined $self->{password}) {
		$data->{password1} = $data->{password2} = $self->{password};
	} else {
		$data->{password1} = $data->{password2} = $self->defaultPassword;
		print "Using default password\n";
	}
	# Get the vsite defaults
	my $defaults = Cobalt::Meta->create("type" => "vsite");
	$defaults->retrieve($self->{group});

	# user Quota
	if(defined $self->{quota}) { $data->{quota} = $self->{quota}; } 
	else {
		$data->{quota} = $defaults->get('user_quota');
		print "Using default for quota: ", $data->{quota}, "\n";
	}

	# user Shell
	if(defined $self->{shell}) { $data->{shell} = 'on' } 
	else {
		$data->{shell} = $defaults->get('user_shell');
		print "Using default for shell access: ", $data->{shell}, "\n";
	}

	# user Frontpage
	if(defined $self->{fpx}) { $data->{fpx} = 'on' } 
	else {
		$data->{fpx} = $defaults->get('user_fpx');
		print "Using default for front page access: ", $data->{fpx}, "\n";
	}

	# user Apop
	if(defined $self->{apop}) { $data->{apop} = 'on' } 
	else {
		$data->{apop} = $defaults->get('user_apop');
		print "Using default for apop access: ", $data->{apop}, "\n";
	}

	# user Aliases
	if(defined $self->{aliases}) { 
		if(ref $self->{aliases} eq 'ARRAY') {
        	$data->{aliases} = join(' ', @{ $self->{aliases} });
		} else { $data->{aliases} =  $self->{aliases}; }
	} else { $data->{aliases} = '' }

	# user Email forward
	if(defined $self->{forward}) { $data->{forward} = $self->{forward} } 
	else { $data->{forward} = 'off' }

	$data->{suspend} = 'off';
	$data->{vacation} = 'off';
	$data->{vacationmsg} = '';
	my $obj = Cobalt::Meta->new(%{ $data });
	my $ret = &Cobalt::User::site_user_add($obj);

	if ($ret) { print "$ret\n"; }
	else { 
		print $self->{name}, " sucessfully created\n";
		$ret = $self->setUserForward($data->{forward});
		if($ret) { "Could not set user forward email\n"; }
	}
}

1;
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

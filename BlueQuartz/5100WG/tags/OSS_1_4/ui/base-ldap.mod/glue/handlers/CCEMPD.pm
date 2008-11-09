#
# This is a subclass of CCE.pm that suffers from MPD.
# Depending on whether it is acting as a handler or as
# a clinet ther error, connect and event_oid functions
# are different.
#
# This provides a convenient methods for ldap import
# to run both at the users discretion from CCE and 
# as controlled by cron.
#
# In one case it acts as a handler, connecting to stdin
# stdout, grabbing the event_oid() from the line and sending
# errors to CCE.
#
# If the other it connects by UDS, grabs the event_oid by a 
# search of it's own and sends errors to an internal buffer that
# it will later send as an email message to admin.
#
# Author: Harris Vaegan-Lloyd.
#

package CCEMPD;
use CCE;
use I18n;
@ISA = ("CCE");

sub connect {
	my $self = shift;
	if( $self->{Client} ) {
		my $ret = $self->connectuds(@_);
		$self->setLocale();
		$self->{event_oid} = $self->event_oid();
		return $ret;
	} else {
		return $self->SUPER::connectfd();
	}
}

sub init {
	my $self = shift;
	$self->{I18n} = new I18n();
	return $self->SUPER::init(@_);
}

sub client {
	my $self = shift;
	my $on = shift;
	if( defined($on) ) {
		$self->{Client} = $on;
	}

	return $self->{Client};
}

sub handler {
	my $self = shift;
	my $on = shift;
	if( defined($on) ) {
		$self->{Client} = (! $on );
	}

	return (! $self->{Client});
}

sub event_object {
	my $self = shift;
	my ($success, $obj) = $self->get($self->event_oid(),$self->{event_namespace});
	return $obj;
}

sub event_oid {
	my $self = shift;
	if( $self->{Client} ) {
		my @oids = $self->SUPER::find("System");
		return $oids[0];
	} else {
		return $self->SUPER::event_oid(@_);
	}
}

sub warn {
	my ($self, @args) = @_;
	if( $self->client() ) {
		print STDERR $self->{I18n}->get(
			$self->msg_create(@args)
		);
	} else {
		return $self->SUPER::warn(@args);
	}
}

sub fail {
	my $self = shift;

	$self->warn(@_);

	if( $self->handler() ) {
		$self->bye('FAIL');
	} else {
		$self->bye();
	}
	exit 1;
}

sub setLocale {
	my $self = shift;
	my ($success, $obj) = $self->get(
		($self->find('User', { name => 'admin' } ))[0]
	);

	$ENV{'LANG'} = $obj->{localePreference};
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

# $Id: Configurator.pm,v 1.2 2002/04/26 21:04:49 jeffb Exp $ 
# Cobalt Networks, Inc http://www.cobalt.com
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

package Configurator;

require Exporter;
require RaQUtil;
require QubeUtil;

use vars qw(@ISA @EXPORT @EXPORT_OK);
@ISA = qw(Exporter RaQUtil QubeUtil);
@EXPORT = qw();
@EXPORT_OK = qw();

sub new
{
    my $proto = shift;
    my $class = ref($proto) || $proto;
    my $self  = {};
    bless($self, $class);

	$self->init(@_);
	return $self;

}

sub init 
{
	my $self = shift;
	if(@_) {
		my %params = (@_);
		foreach my $param (keys %params) { $self->{$param} = $params{$param} }
	} else {
		die "You must pass some data to create a Configurator\n";
	}
}

# to change the default password for the user change the line below
sub defaultPasswd { return 'changeme' }

sub defaultFqdn
{ 
	my $fqdn = qx(/bin/hostname);
	chomp($fqdn);
	return $fqdn;
}

sub getInputBool
{
	my $self = shift;
	my $string = shift;

	my $input;
	my $bool = '[yes|y|no|n]';
	while(1) {
		print $string;
		print '[yes|y|no|n]: ';
		$input = <STDIN>;
		chomp($input);
		if($input =~ /^(y|yes)/i) { return 'y'; } 
		elsif($input =~ /^(n|no)/i) { return 'n'; } 
		elsif($input =~ /^(q|quit)/i) { die "Program Abort."; } 
		else {
			print "Invalid input: $input\n";
		}
	}	
}

sub vsiteNameConvert
{
	my $self = shift;
	my ($ok, @sites);
	
	foreach my $name (@{ $self->{name} }) {
		if($name =~ /\./) {
			# we are a domain convert
			$ok = $self->fqdnToGroup($name);
			if($ok) { push @sites, $ok  }
		} else { push @sites, $name }
	}
	return @sites;
}


sub errorExit
{
	my $self = shift;
	my $msg = shift;

	print $msg;
	if(ref $cce) { $cce->bye('later'); }
	exit 1;

}



sub test 
{ 
	my $self = shift;
	use Data::Dumper;
	warn Dumper(@_);
	warn "it is: ", $_[3], "\n";
	print "test\n";
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

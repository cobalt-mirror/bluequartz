# $Id: Shell.pm,v 1.15 2003/05/01 17:37:10 jeffb Exp $
# Cobalt Networks, Inc http://www.cobalt.com
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# written by: Jeff Bilicki

package Shell;

use Data::Dumper;
require ShellOpts;
require Script;
use Getopt::Mixed "nextOption";
use strict;

use vars qw($confDir);
#my $confDir = '/home/cce-shell-tools/conf';
my $confDir = '/cvs/cce-shell-tools/conf';

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

	$self->setSignals;
	$self->getBuild;
	$self->getProg;
	$self->{opts} = ShellOpts->new(
		build => $self->build,
		prog => $self->prog
	);
	$self->{scripts} = Script->new();
	Getopt::Mixed::init($self->{opts}->stringOpts);
	while(my ($opt, $val) = nextOption) {
		$self->convertOpts($opt, $val);
	}
	Getopt::Mixed::cleanup;
	if($self->isObj('help')) { $self->{opts}->usage;exit 0 } 
	if($self->isObj('quick')) { $self->{opts}->usage(1);exit 0 } 
	$self->convertRef;
	$self->checkRequires;
	$self->checkValidators;
	
}

##################
# handle signals #
sub setSignals {
	my $self = shift;
	$SIG{INT} = sub { $self->signalINT() };
	$SIG{TERM} = sub { $self->signalINT() };
	$SIG{HUP} = sub { $self->signalHUP() };
}
sub signalINT {
	my $self = shift;
	print "Got SIGINT... Exiting..";
	exit 1;
}
sub signalHUP {
	my $self = shift;
	print "Got SIGHUP, I am not a daemon.\n";
	return;
}

sub obj { 
	my $self = shift; my $label = shift; my $space = shift || 0;

	if($space && defined $self->{obj}->{$space}) {
		return $self->{obj}->{$space}->{$label};
	} else { return $self->{obj}->{$label} }
}

sub isObj { 
	my $self = shift; my $label = shift; my $space = shift || 0;

	if($space) { 
		return 0 unless defined($self->{obj}->{$space});
		(defined $self->{obj}->{$space}->{$label}) ? (return 1) : (return 0);
	} else { (defined $self->{obj}->{$label}) ? (return 1) : (return 0); }
}
sub putObj {
	my $self = shift;
	my $label = shift;
	my $val = shift;
	my $space = shift || 0;
	my $array = shift || 0;
	
	if($space) { 
		if($array) { 
			if($val == 1) {
				$self->{obj}->{$space}->{$label} = ();
			} else {
				push @{ $self->{obj}->{$space}->{$label} }, $val 
			}
		} else { $self->{obj}->{$space}->{$label} = $val }
	} else {
		if($array) { 
			if($val == 1) {
				 $self->{obj}->{$label} = ();
			} else { 
				push @{ $self->{obj}->{$label} }, $val;
			}
		} else { $self->{obj}->{$label} = $val }
	}
}

sub delObj {
	my $self = shift; my $label = shift; my $space = shift || 0;

	if($space && defined $self->{obj}->{$space}) {
		delete $self->{obj}->{$space}->{$label};
	} else { delete $self->{obj}->{$label} }
}

sub build {
	$_[0]->{build} = $_[1] if($_[1]);
	return $_[0]->{build} if(defined $_[0]->{build}); 
}

sub prog {
	$_[0]->{prog} = $_[1] if($_[1]);
	return $_[0]->{prog} if(defined $_[0]->{prog}); 
}

##############################################################################
# Methods that run 
##############################################################################
# converts the option into the data structure. 
sub convertOpts 
{
	my $self = shift;
	my $opt = shift; 
	my $val = shift || 1;

	# if labelValue is defined, make value this setting
	if($self->{opts}->isLabelValue($opt)) {
		$val = $self->{opts}->labelValue($opt);
	}
	if($val == 1 && $self->build !~ /^(RaQ550|BQ5100R|TLAS2|Qube3)$/) {
		$val = $self->convertBool($val);
	}
	
	# do we really want to convert to arrays here?
	# do I expect each module to do it?
	# handling the same opt as an array and as string
	# UPDATE: yes each module should handle it since they prototype the data

	my $label = $self->{opts}->label($opt);
	my $type = $self->{opts}->type($opt);
	my $space = $self->{opts}->nameSpace($opt);

	if($self->{opts}->isScript($opt)) {
		$self->{scripts}->putScript($label, $self->{opts}->script($opt));
	} else { 
		if($type =~ /^array$/i) {
			foreach my $item (split(/[\s,&]/, $val)) {
				next if(!$item);
				$self->putObj($label, $item, $space, 1);
			}
		} else { $self->putObj($label, $val, $space) }
	}
	$self->{opts}->active($opt);
}

sub convertBool
{
	my $self = shift;
	my $val = shift || return;
	
	if($self->build =~ /^RaQ3$/) {
		return 'on' if($val =~ /^(1|true|t|on)$/);
		return 'off' if($val =~ /^(0|false|f|off)$/);
	} elsif($self->build =~ /^(RaQ4|RaQXTR)$/) {
		return 't' if($val =~ /^(1|true|t|on)$/);
		return 'f' if($val =~ /^(0|false|f|off)$/);
	} else { return $val }
}

# takes all of the opts and makes one string out of them
sub convertRef
{
	my $self = shift;

	my ($val, $label, $space);
	foreach my $opt (keys %{ $self->{opts} }) {
		next if(!$self->{opts}->isActive($opt));
		next if(!$self->{opts}->isRef($opt));
		$label = $self->{opts}->label($opt);
		$space = $self->{opts}->nameSpace($opt);
		
		# don't convert refs unless option is provided
		next unless $self->isObj($label, $space);

		my $ref = $self->{opts}->getRef($opt);
		if(ref($ref) eq 'HASH') {
			if(defined $ref->{source}) { 
				$val = $self->obj($ref->{source}, $ref->{sourceSpace})
			} elsif(defined $ref->{labelValue}) { $val = $ref->{labelValue} }
			$self->putObj($ref->{label}, $val, $space)
		} elsif(ref($ref) eq 'ARRAY') {
			foreach my $arr (@{ $ref }) {
				if(defined $arr->{source}) { 
					$val = $self->obj($arr->{source}, $arr->{sourceSpace})
				} elsif(defined $arr->{labelValue}) { 
					$val = $arr->{labelValue}
				}
				$self->putObj($arr->{label}, $val, $space)
			}
		} else { print "Unkwown ref type in option: $opt\n" }
	}
}

sub checkRequires
{
	my $self = shift;

	foreach my $opt (keys %{ $self->{opts} }) {
		next unless($self->{opts}->isRequired($opt));
		if(!$self->isObj(
			$self->{opts}->label($opt),
			$self->{opts}->nameSpace($opt))
		) { 
			print "You must provite the following option:\n";
			print $self->{opts}->helpOpts($opt), "\n";
			exit 1;
		}
	}
}

sub checkValidators
{
	my $self = shift;
	
	my ($label, $space, $validator, $val);
	foreach my $opt (keys %{ $self->{opts} }) {
		next unless($self->{opts}->isValidator($opt));
		$validator = $self->{opts}->validator($opt);	
		$label = $self->{opts}->label($opt);
		$space = $self->{opts}->nameSpace($opt);
		next unless $self->isObj($label, $space);
		$val = $self->obj($label, $space);
		if($self->$validator($val)) {
			print "$val is not valid input for the option:\n";
			print $self->{opts}->helpOpts($opt), "\n";
			exit 1;
		}
	}
}

sub runScripts
{
	my $self = shift;

	my @keys = sort keys %{ $self->{scripts} };
	return if(scalar @keys == 0);
	foreach my $opt (@keys) {
		#$self->{scripts}->runScript($opt, $self->{opts}->rebuildOpts($opt));
		$self->{scripts}->runScript($opt);
	}
}


# most validators where stolen from CCE, stop thief!
sub isFqdn
{
	my $self = shift;
	my $val = shift;

	return 1 if($val =~ /\..?$/o);
    my @subs = split(/\./, $val);
    return 1 if($#subs < 2);
    return 1 if($val !~ /^[a-zA-Z0-9\-\.]*$/);

	return(0);
}

sub isIpAddr
{
	my $self = shift;
	my $ip = shift;

	# stolen and compacted from Validators.pm in sausalito
	my @numbers = split(/\./, $ip);
	foreach my $num ( @numbers ) {
		# Make sure the block is three numbers.
		if( $num !~ /^\d{1,3}$/o ) { return 1; }
		if( $num > 255 ) { return 1; }
	}
	return 0;
}

sub isUser
{
	my $self = shift;
	my $user = shift;

	return 1 if(length($user) > 12);
	return 1 if($user !~ /^[a-z0-9][a-z0-9\.\-\_][a-z0-9\.\-\_]+$/o);
	return 0;
}

sub isGroup
{
	my $self = shift;
	my $group = shift;

	return 1 if(length($group) > 12);
	return 1 if($group !~ /^[a-z0-9][a-z0-9\.\-\_]*$/o);
	return 0;
}

sub isUserQube
{
	my $self = shift;
	my $user = shift;

	return 1 if(length($user) > 12);
	return 1 if($user !~ /^[a-z][a-z0-9\.\-\_][a-z0-9\.\-\_]+$/o);
	return 0;
}

sub isGroupQube
{
	my $self = shift;
	my $group = shift;

	return 1 if(length($group) > 12);
	return 1 if($group !~ /^[a-z][a-z0-9\.\-\_]*$/o);
	return 0;
}

sub isInt
{
	my $self = shift;
	my $int = shift;
	return 1 if($int eq '0');
	return 1 if($int !~ /^[0-9]+$/);
	return 0;
}

sub isBool
{
	my $self = shift;
	my $bool = shift;
	
	return 0 if($bool =~ /(t|true|on|yes|1)/oi);
	return 0 if($bool =~ /(f|false|off|no|0)/oi);
	return 1;
}

sub getProg
{
	my $self = shift;
	my $prog = qx(/bin/basename $0);
	chomp($prog);
	$self->prog($prog);
	return $prog;
}

sub getBuild
# Translates all of build tags into basic product names
# Arguments: none
# Returns: name of build
# Side Effects: selling out
{
	my $self = shift;
	my $bldFile = "/etc/build";
	my %bldHash = ( 
		# Qube Builds
		"2800WG",		"Qube2",
		"4000WG",		"Qube3",
		"4010WG",		"Qube3",
		"4100WG",		"Qube3",
		# RaQ Builds
		"2700R",			"RaQ1",
		"2799R",			"RaQ2",
		"2800R",			"RaQ2",
		"3000R",			"RaQ3",
		"3001R",			"RaQ4",
		"3100R",			"RaQ4",
		"3500R",			"RaQXTR",
		"3599R",			"RaQXTR",
		"4100R",			"RaQ550",
		# BlueQuartz builds
		"5100BQ",			"Qube3",
		"5100WG",			"Qube3",
		"4200R",			"RaQ550",
		"5100R",			"BQ5100R",
		"5101R",			"BQ5100R",
		"5102R",			"BQ5100R",
		"5103R",			"BQ5100R",
		"5104R",			"BQ5100R",
		"5105R",			"BQ5100R",
		# TLAS
		"TLAS2",			"TLAS2",
	);
 
	open(FH, "< $bldFile") 
		|| die "Error opening file $bldFile: $!";
	my $data;
	while(<FH>) { $data .= $_; }
	close(FH);
	foreach my $bld (sort keys %bldHash) {
		if ($data =~ /\Q$bld\E/) {
			$self->build($bldHash{$bld});
			return $bldHash{$bld};
		}		
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

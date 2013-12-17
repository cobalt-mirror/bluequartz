# $Id: ShellOpts.pm,v 1.9 2002/04/26 21:04:49 jeffb Exp $
# Cobalt Networks, Inc http://www.cobalt.com
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

package ShellOpts;

use Data::Dumper;
require ConfXml;
use strict;

use vars qw($confDir $findCmd $baseName);
my $confDir = '/usr/shell-tools/conf';
my $findCmd = 'find';
my $baseName = '/bin/basename';

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
	my %params = (@_);

	my ($build, $prog);
	if(defined $params{prog}) { $prog = $params{prog} }
	else { die "ShellOpts::init no program name\n" }

	if(defined $params{build}) { $build = $params{build} }
	else { die "ShellOpts::init no build name\n" }

        my @xmlFiles = qx($findCmd $confDir -nowarn -type f -maxdepth 1 -name "*.xml");
	my ($confData, $baseName);
	# should read base.xml in first
	foreach my $file (@xmlFiles) {
		chomp($file);
		$baseName = qx(/bin/basename $file);
		chomp($baseName);
		
		$confData = eval { ConfXml::readXml($file, 0); };
		if(!ref $confData) {
			print "Syntax error in $file ...skipping\n";
			next;
		}

		if(ref $confData->{product} ne 'ARRAY') {
			my $tmp = $confData->{product};
			delete $confData->{product};
			push @{ $confData->{product} }, $tmp;
		}
		next unless(grep(/^$build$/, @{ $confData->{product} }));
		next unless(defined $confData->{$prog});
		foreach my $opt (keys %{ $confData->{$prog} }) {
			$self->{$opt} = $confData->{$prog}->{$opt};	
		}
	}
}

sub getMap { 
	my $self = shift; 
	my $opt = shift;
	my $attr = shift || return;
	return if(!defined $self->{$opt});
	return $self->{$opt}->{$attr} if(defined $self->{$opt}->{$attr}); 
	return;
		
}
sub isMap { 
	my $self = shift; 
	my $opt = shift;
	my $attr = shift || return;
	return 0 if(!defined $self->{$opt});
	(defined $self->{$opt}->{$attr}) ? (return 1) : (return 0);
}
sub label { return $_[0]->getMap($_[1], 'label') }
sub nameSpace { return $_[0]->getMap($_[1], 'nameSpace') }
sub isNameSpace { ($_[0]->isMap($_[1], 'nameSpace')) ? (return 1) : (return 0) }

# type is used to defined data types of values.
# used in $->normalize
sub type { return $_[0]->getMap($_[1], 'type') }
sub isType { ($_[0]->isMap($_[1], 'type')) ? (return 1) : (return 0) }

sub required { return $_[0]->getMap($_[1], 'required') }
sub isRequired { ($_[0]->isMap($_[1], 'required')) ? (return 1) : (return 0) }

sub validator { return $_[0]->getMap($_[1], 'validator') }
sub isValidator { ($_[0]->isMap($_[1], 'validator')) ? (return 1) : (return 0) }

sub script { return $_[0]->getMap($_[1], 'script') }
sub isScript { ($_[0]->isMap($_[1], 'script')) ? (return 1) : (return 0) }

sub labelValue { return $_[0]->getMap($_[1], 'labelValue') }
sub isLabelValue { ($_[0]->isMap($_[1], 'labelValue')) ? (return 1) : (return 0) }

sub getRef { return $_[0]->getMap($_[1], 'ref') }
sub isRef { ($_[0]->isMap($_[1], 'ref')) ? (return 1) : (return 0) }

sub active { 
	my $self = shift;
	my $opt = shift;
	$self->{$opt}->{active} = 1 if($opt);
	return $self->{$opt}->{active};
}
sub isActive { ($_[0]->isMap($_[1], 'active')) ? (return 1) : (return 0) }

# opts is where the config for Getopt::Mixed is stored
sub opts { return $_[0]->getMap($_[1], 'opts') }

# stores the help text
sub help 
{
	my $self = shift;
	my $opt = shift;
	if($self->isMap($opt, 'help')) { 
		return $self->getMap($opt, 'help');
	} else { return "No help text defined"; }
}

##############################################################################
# Methods that run 
##############################################################################
sub stringOpts
{
	my $self = shift;
	my $ret;
	foreach my $key (keys %{ $self }) {
		$ret .= $self->opts($key)." ";
	}	
	return $ret;
}

# formats the opts for help text
sub helpOpts 
{
	my $self = shift;
	my $opt = shift;
	my $quick = shift || 0;
	my ($ret, $alii, $sp);
	
	# defined some formatting
	if($quick) { $sp = ''; $ret = '['; } 
	else { $ret = ''; $sp = '  '; }

	my $opts = $self->opts($opt);
	my $type = $self->type($opt);
	if($opts =~ /^(\S+)=([s|i|f])\s+(.*)/) {
		$ret .= $sp."--$1=";
		$alii = $3;
		if($2 eq 's') { 
			if($type =~ /^array$/i) { $ret .= "list" }
			else { $ret .= "string" }
		} elsif($2 eq 'i') { $ret .= "integer" }
		elsif($2 eq 'f') { $ret .= "float" }
	} elsif($opts =~ /^(\S+)\s+(.*)/) {
		$ret .= $sp."--$1";
		$alii = $2;
	}
	foreach my $other (split(" ", $alii)) {
		$ret .= ",";
		my $oth = (split(/>/, $other))[0];
		if(length($oth) == 1) { $ret .= " -$oth"; }
		 else { $ret .= " --$oth"; } 
	}
	if($quick) { $ret .= '] '; } 
	# print the whole thing
	else { $ret .= "\n".$sp.$sp.$sp.$self->help($opt)."\n";	}
	return $ret;
}

sub usage
{
	my $self = shift;
	my $quick = shift || 0;
	
	my $progname = $0;
	print "Usage $0 [OPTION]...\n";
	my @keys = sort keys %{ $self };
	foreach my $key (@keys) { print $self->helpOpts($key, $quick); }
	print "\n";
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

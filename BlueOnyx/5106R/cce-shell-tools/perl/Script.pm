# $Id: Script.pm,v 1.2 2001/12/11 20:04:05 jeffb Exp $
# Cobalt Networks, Inc http://www.cobalt.com
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# written by: Jeff Bilicki

package Script;

sub new
{
	my $proto = shift;
	my $class = ref($proto) || $proto;
	my $self  = {};
	bless($self, $class);
	
	return $self;
}

sub scriptLoc 
{ 
	my $self = shift;
	my $name = shift;

	return $self->{$name};
}

sub putScript
{
	my $self = shift;
	my $name = shift;
	my $loc = shift;

	if(!defined $name || !defined $loc) {
		warn "You need to pass a tag and the script location";
	} else { $self->{$name} = $loc }
}

sub isScript 
{ 
	my $self = shift;
	my $name = shift;

	(defined $self->{$name}) ? (return 1) : (return 0);
}

sub canScript 
{ 
	my $self = shift;
	my $name = shift;

	my $loc = $self->scriptLoc($name);
	return 0 if(! -f $loc);
	return 0 if(! -x $loc);
	return 1;
}

sub runScript
{
	my $self = shift;
	my $name = shift;
	my $opts = shift;

	my $loc = $self->scriptLoc($name);	
	if(!$self->canScript($name)) { 
		print "Cannot process tag $name calling $loc\n";
	}
	print "Label $name: $loc $opts\n";
	# I think i should use fopen so I can waitpid
	my $ret = system("$loc $opts");
	if($ret != 0) {
		print "Error running script $loc\n"; 
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

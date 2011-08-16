# File handle that uses a string internally and can seek
# This is given as a demo for getting a zip file written
# to a string.
# I probably should just use IO::Scalar instead.
# Ned Konz, March 2000
#
# $Revision: 922 $

use strict;
package Archive::Zip::BufferedFileHandle;
use FileHandle ();
use Carp;

sub new
{
	my $class = shift || __PACKAGE__;
	$class = ref($class) || $class;
	my $self = bless( { 
		content => '', 
		position => 0, 
		size => 0
	}, $class );
	return $self;
}

# Utility method to read entire file
sub readFromFile
{
	my $self = shift;
	my $fileName = shift;
	my $fh = FileHandle->new($fileName, "r");
	if (! $fh)
	{
		Carp::carp("Can't open $fileName: $!\n");
		return undef;
	}
	local $/ = undef;
	$self->{content} = <$fh>;
	$self->{size} = length($self->{content});
	return $self;
}

sub contents
{
	my $self = shift;
	if (@_)
	{
		$self->{content} = shift;
		$self->{size} = length($self->{content});
	}
	return $self->{content};
}

sub binmode
{ 1 }

sub close
{ 1 }

sub eof
{
	my $self = shift;
	return $self->{position} >= $self->{size};
}

sub seek
{
	my $self = shift;
	my $pos = shift;
	my $whence = shift;

	# SEEK_SET
	if ($whence == 0) { $self->{position} = $pos; }
	# SEEK_CUR
	elsif ($whence == 1) { $self->{position} += $pos; }
	# SEEK_END
	elsif ($whence == 2) { $self->{position} = $self->{size} + $pos; }
	else { return 0; }

	return 1;
}

sub tell
{ return shift->{position}; }

# Copy my data to given buffer
sub read
{
	my $self = shift;
	my $buf = \($_[0]); shift;
	my $len = shift;
	my $offset = shift || 0;

	$$buf = '' if not defined($$buf);
	my $bytesRead = ($self->{position} + $len > $self->{size})
		? ($self->{size} - $self->{position})
		: $len;
	substr($$buf, $offset, $bytesRead) 
		= substr($self->{content}, $self->{position}, $bytesRead);
	$self->{position} += $bytesRead;
	return $bytesRead;
}

# Copy given buffer to me
sub write
{
	my $self = shift;
	my $buf = \($_[0]); shift;
	my $len = shift;
	my $offset = shift || 0;

	$$buf = '' if not defined($$buf);
	my $bufLen = length($$buf);
	my $bytesWritten = ($offset + $len > $bufLen)
		? $bufLen - $offset
		: $len;
	substr($self->{content}, $self->{position}, $bytesWritten)
		= substr($$buf, $offset, $bytesWritten);
	$self->{size} = length($self->{content});
	return $bytesWritten;
}

sub clearerr() { 1 }

# vim: ts=4 sw=4
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

# Output file handle that calls a custom write routine
# Ned Konz, March 2000
# This is provided to help with writing zip files
# when you have to process them a chunk at a time.
#
# See the examples.
#
# $Revision: 922 $

use strict;
package Archive::Zip::MockFileHandle;

sub new
{
	my $class = shift || __PACKAGE__;
	$class = ref($class) || $class;
	my $self = bless( { 
		'position' => 0, 
		'size' => 0
	}, $class );
	return $self;
}

sub eof
{
	my $self = shift;
	return $self->{'position'} >= $self->{'size'};
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
	$bytesWritten = $self->writeHook(substr($$buf, $offset, $bytesWritten));
	if ($self->{'position'} + $bytesWritten > $self->{'size'})
	{
		$self->{'size'} = $self->{'position'} + $bytesWritten
	}
	$self->{'position'} += $bytesWritten;
	return $bytesWritten;
}

# Called on each write.
# Override in subclasses.
# Return number of bytes written (0 on error).
sub writeHook
{
	my $self = shift;
	my $bytes = shift;
	return length($bytes);
}

sub binmode { 1 } 

sub close { 1 } 

sub clearerr { 1 } 

# I'm write-only!
sub read { 0 } 

sub tell { return shift->{'position'} }

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

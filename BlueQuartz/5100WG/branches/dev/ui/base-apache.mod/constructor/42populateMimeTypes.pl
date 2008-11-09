#!/usr/bin/perl -I /usr/sausalito/perl
# $Id: 42populateMimeTypes.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2002 Sun Microsystems, Inc., All rights reserved.

use CCE;
my $cce = new CCE;
$cce->connectuds();

use Base::Httpd;
my $mime_config = $Base::Httpd::mime_types_file;

#    name="MimeType" version="1.0"
#        name="application" type="scalar"
#        name="extension" type="scalar"
#        name="initialized" type="boolean" default="0"

my @mimes = $cce->find('MimeType');

if ((-r $mime_config) && ($#mimes < 0))
{
	my %mt; # type -> file extension hash

	open(MIME, $mime_config);
	while(<MIME>) 
	{
		# Skip comments, whitespace
		next if (/^\s*\#/ || /^\s*$/);

		chomp;
		my($type, $ext);
		if($_ =~ /^([\S\/]+)\s+(.*)$/)
		{
			($type, $ext) = ($1, $2);
		}
	
		$mt{$type} = $ext if ($type);
	}
	close(MIME);

	# $mime_config must be closed before we create CCE objects as
	# each will rewrite $mime_config
	foreach my $t (keys %mt)
	{
		$cce->create('MimeType', {
			'application' => $t,
			'extension' => $mt{$t}
			});
	}
}

$cce->bye('SUCCESS');
exit 0;

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

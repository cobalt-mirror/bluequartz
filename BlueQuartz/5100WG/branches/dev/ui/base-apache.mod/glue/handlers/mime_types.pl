#!/usr/bin/perl -I /usr/sausalito/perl
# $Id: mime_types.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2002 Sun Microsystems, Inc., All rights reserved.

use Base::Httpd;
my $mime_types = $Base::Httpd::mime_types_file;

use CCE;
my $cce = new CCE;
$cce->connectfd();

use Sauce::Util;

my $mime = $cce->event_object();
my $old = $cce->event_old();

if($cce->event_is_destroy) { # Delete
	if (!Sauce::Util::editfile($mime_types, *munge_config, 
		undef, undef,
		$old->{application})) {
		$cce->warn('[[base-email.cantEditFile]]', 
			{ 'file' => $mime_types });        
		$cce->bye('FAIL');
		exit(1);
	}
} else { # Append or Modify
	if (!Sauce::Util::editfile($mime_types, *munge_config, 
		$mime->{application}, $mime->{extension},
		$old->{application})) {
		$cce->warn('[[base-email.cantEditFile]]', 
			{ 'file' => $mime_types });        
		$cce->bye('FAIL');
		exit(1);
	}
}


$cce->bye('SUCCESS');
exit 0;

# Subs

sub munge_config {
	my($in, $out, $app, $ext, $oldapp) = @_;
	$ext =~ s/\,/ /g; # Tolerate comma delimiter
	$ext =~ s/\s+/ /g; # Reduce whitespace

	$oldapp ||= $app; # avoid duplicate entries

	while(<$in>) { print $out $_ unless (/^$oldapp\s/); }
	print $out "$app\t$ext\n" if ($app);

	return 1;
}


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

#!/usr/bin/perl -I /usr/sausalito/perl
#
# $Id: wizard_splash.pl,v 1.1.2.1 2002/02/23 03:06:35 pbaltz Exp $
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.
#
# copy in a default wizard welcome page based on the current productLanguage
#

use CCE;

# set umask properly, so files are readable
umask(002);

my $cce = new CCE;
$cce->connectfd();

# possibilities for splash screens
my @splashes = ({
			'dir' => '/usr/sausalito/ui/web/base/wizard',
			'file' => 'start.html'
		},
		{
			'dir' => '/usr/sausalito/ui/web',
			'file' => 'intro.html'
		});

# this never runs on DESTROY
my $system = $cce->event_object();

# the default is english
my $locale = 'en';

# use productLanguage if it contains something
if ($system->{productLanguage} ne '') {
	$locale = $system->{productLanguage};
}

# deal with all possibilities
for my $splash (@splashes) {
	# see if the file for the given locale exists
	my $loc_file = &find_locale($splash, $locale);
	if ($loc_file ne '') {
		Sauce::Util::copyfile($loc_file,
			"$splash->{dir}/$splash->{file}");
	} else {
		# just use english
		$loc_file = &find_locale($splash, 'en');
		if ($loc_file ne '') {
			Sauce::Util::copyfile($loc_file,
				"$splash->{dir}/$splash->{file}");
		}
	}
}

$cce->bye('SUCCESS');
exit(0);

sub find_locale
{
	my ($splash, $locale) = @_;

	my $loc_file = '';

	opendir(SPLASH, $splash->{dir});
	while (my $entry = readdir(SPLASH)) {
		if ($entry =~ /^$splash->{file}\.(.+)$/) {
			# possible match, check locale
			my $file_locale = $1;
			
			if (($file_locale =~ /^$locale/i) ||
			    ($locale =~ /^$file_locale/i)) {
				$loc_file = "$splash->{dir}/$entry";
				last;
			}
		}
	}
	closedir(SPLASH);

	return $loc_file;
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

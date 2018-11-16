#!/usr/bin/perl
#
# $Id: reencode.pl 3 2003-07-17 15:19:15Z will $
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.
#
# Reencodes strings in given file in specified character encoding.
# Currently only really works for euc-jp.
#

use POSIX qw(locale_h setlocale);
use Jcode;
use Getopt::Long;

my $encoding = 'iso-8859-1';
my $locale = 'en';

GetOptions('encoding=s' => \$encoding, 'locale=s' => \$locale);

# Pbaltz says no longer necessary
#if (!set_posix_locale($locale)) {
#	die "Can't set posix locale!\n";
#}

open(POFILE, $ARGV[0]) or die "Can't open $ARGV[0]: $!\n";
my @args = ();
push @args, $encoding, '', $locale;

while (my $line = <POFILE>) {
	print encode_string($line, @args);
}

close(POFILE);
exit(0);

sub set_posix_locale
{
	my ($locale) = @_;

	#
	# translations to system locales to pass to the setlocale function
	# if the language doesn't exist in this hash, nothing is done to it
	# and we can even claim we support the euro character (sort of)
	#
	my $sys_langs = {
				'ja' => 'ja_JP.eucjp',
				'en' => 'en_US',
				'de' => 'de_DE@euro',
				'es' => 'es_ES@euro',
				'it' => 'it_IT@euro',
				'nl' => 'nl_NL@euro',
				'pt' => 'pt_PT@euro',
				'da' => 'da_DK@euro',
				'fr' => 'fr_FR@euro'
				# does chinese need any special treatment?
			};

	# check if we need to map this locale to something glibc supports
	my $ret;
	if (exists($sys_langs->{$locale})) {
		$DEBUG && print STDERR "mapping $locale to $sys_langs->{$locale}\n";
		$ret = setlocale(LC_ALL, $sys_langs->{$locale});
	} else {
		# use whatever they gave us
		$DEBUG && print STDERR "using passed in locale, $locale\n";
		$ret = setlocale(LC_ALL, $locale);
	}

	$DEBUG && print STDERR "setlocale ",
		(defined($ret) ? "succeeded" : "failed"), "\n";

	return defined($ret);
}

sub encode_string 
{
	my ($string, $to_encoding, $from_encoding, $locale) = @_;

	if (!defined($locale)) {
		die "No locale specified for encode_string!\n";
	}

	#
	# what the local encodings are for locales
	# only has entries for locales that require conversion
	#
	my $encodings = {
			'ja' => 'euc'
			};

	# this is kind of a hack, but at least it hides the hack here.
	if ($locale =~ /^ja/i) {
		my @args = ($string);
		if ($to_encoding ne '') {
			push @args, $to_encoding;
		} else {
			push @args, $encodings->{ja};
		}

		if ($from_encoding ne '') {
			push @args, $from_encoding;
		}

		return Jcode::convert(@args);
	} else {
		#
		# okay, so this is only japanese for now, but one day this
		# could be useful for unicode
		#
		return $string;
	}
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

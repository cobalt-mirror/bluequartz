#
# $Id: I18n.pm 1248 2009-09-15 08:16:01Z shibuya $
#
# Copyright 2000-2002 Sun Microsystems, Inc.  All rights reserved.
#
# A perl interface to i18n library

package I18n;

use IO::Handle;
use IPC::Open2;
use Jcode;
use POSIX qw(locale_h setlocale);

use vars qw($VERSION $DEBUG);
$VERSION = 1.2;
$DEBUG = 0;

#
# what the local encodings are for locales
# only has entries for locales that require conversion
#
my $encodings = {
			'ja' => 'utf8'
		};

#
# translations to system locales to pass to the setlocale function
# if the language doesn't exist in this hash, nothing is done to it
# and we can even claim we support the euro character (sort of)
#
my $sys_langs = {
			'ja' => 'ja_JP.UTF-8',
			'en' => 'en_US',
			'de' => 'de_DE@euro',
			'es' => 'es_ES@euro',
			'fr' => 'fr_FR@euro'
			# does chinese need any special treatment?
		};

sub new
# Doesn't do anything special yet. 
{
	my ($class) = @_;
	my $self = {};
	bless($self, $class);
	return $self;
}

sub setLocale
{
	my $self = shift;
	$self->{locale} = shift;
	$ENV{"LANGUAGE"} = $self->{locale};
	$ENV{"LANG"} = $self->{locale};
}

#
# set perl's idea of the locale, and the corresponding environment vars
# essentially calls setlocale, translating, if necessary, the passed in locale
# to one of the locales supplied with glibc (ie en => en_US)
# returns true for success, false for failure
#
sub setPosixLocale
{
	my ($self, $locale) = @_;

	# check if we need to map this locale to something glibc supports
	my $ret;
	if (exists($sys_langs->{$locale})) {
		$DEBUG && print STDERR "mapping $locale to $sys_langs->{$locale}\n";
		$ret = setlocale(LC_ALL, $sys_langs->{$locale});
		if (defined($ret)) {
			$self->setLocale($sys_langs->{$locale});
		}
	} else {
		# use whatever they gave us
		$DEBUG && print STDERR "using passed in locale, $locale\n";
		$ret = setlocale(LC_ALL, $locale);
		if (defined($ret)) {
			$self->setLocale($locale);
		}
	}

	$DEBUG && print STDERR "setlocale ",
		(defined($ret) ? "succeeded" : "failed"), "\n";

	return defined($ret);
}

#
# returns a list of the locales available on the system
# if a text domain is specified (optional), it only looks for the available
# locales for that domain
# if no args are given, it returns all available locales on the system
# This is a static routine and requires no object.
#
sub getAvailableLocales
{
	my ($domain) = @_;

	my @locales = ();
	my @cmd = ('/usr/sausalito/bin/i18n_locales', $domain);

	# need to make sure the lang is defined or the i18n library bombs
	my $lang_defined = 0;
	if (!defined($ENV{LANG})) {
		$lang_defined = 1;
		$ENV{LANG} = 'en';
	}

	# safe pipe read to prevent running via the shell
	open(LOCALES, "-|") || exec(@cmd);
	while (my $locale = <LOCALES>) {
		chomp($locale);
		push @locales, $locale;
	}
	close(LOCALES);

	if ($lang_defined) {
		$ENV{LANG} = undef;
	}

	return @locales;
}
		
sub get
# this does an i18n get via a system call
# pass in the tag in format [[domain.tag]] 
# Also can take a hash of key/value pairs for 
# variable substitution 
{
    shift;
    my ($tag, $vars, $domain) = @_;

    $varstring = "";
    foreach $key (keys %{$vars}) {
	$$vars{$key} =~ s/\"/\\"/g;
	$varstring = join (" ", $varstring, "\"".$key."\"", 
		"\"".$$vars{$key}."\"", "");
    }
    if (defined $domain) {
	return join("",`/usr/sausalito/bin/i18n_get "[[$domain.$tag]]" $varstring`);
	
    }
    else {
	return join("",`/usr/sausalito/bin/i18n_get "$tag" $varstring`);
    }
}

sub interpolate
# This calls i18n_translate to interpolate a string
# slightly simpler but mostly the same as get
{
    my $self = shift;
    my $str = shift || return "";
    my $vars = shift;

    $varstring = "";
    foreach $key (keys %{$vars}) {
	$$vars{$key} =~ s/\"/\\"/g;
	$varstring = join (" ", $varstring, "\"".$key."\"", 
		"\"".$$vars{$key}."\"", "");
    }

    my ($RD, $WR) = (IO::Handle->new, IO::Handle->new);
    eval {
      open2($RD, $WR, "/usr/sausalito/bin/i18n_translate $varstring");
    };
    if ($@) {
      # open2 failed
      print STDERR "open2 failed: $!\n$@\n";
      return "(Internal Error)";
    } else {
      print $WR $str;
      close($WR);
      my @data = <$RD>;
      close($RD);
      return join("", @data);
    }
}


# get the locale from cce. fallback on /etc/build if necessary
# you can either pass cce in or let getSystemLocale do it for you.
# NOTE: this is a static method
sub i18n_getSystemLocale {
    use lib '/usr/sausalito/perl';
    use CCE;

    my $passedin = shift;
    my $cce;
    my ($locale, $ok);

    if ($passedin) {
	$cce = $passedin;
	$ok = 1;
    } else {
	$cce = new CCE;
	$ok = eval { $cce->connectuds() };
    }

    if (defined($ok)) {
    	my ($oid) = $cce->find('System');
    	if ($oid) {
		my ($ok, $obj) = $cce->get($oid);
		$locale = $obj->{productLanguage};
		$cce->bye('SUCCESS') unless $passedin;

		return $locale if ($locale ne "" && -d "/usr/share/locale/$locale");
    	} elsif (not $passedin) {
		$cce->bye('SUCCESS');
	}
    }

    if (open(FILE, '/etc/cobalt/locale')) {
	while (<FILE>) {
	    $locale = $_;
	    chomp($locale);
	    last;
	}
	close(FILE);
    }

    if (not $locale and open(FILE, '/etc/build')) {
	while (<FILE>) {
	    next unless /in (\S+)$/;
	    $locale = $1;
	    last;
	}
	close(FILE);
    }
    return $locale ? $locale : 'en';
}

####################################

# set the locale to cce, file flags
# single argument of the locale code, eg "en" or "de"
sub i18n_setSystemLocale {
    my $locale = shift;

    use lib '/usr/sausalito/perl';
    use CCE;

    my $cce;
    my $ok;

    $cce = new CCE;
    $ok = eval { $cce->connectuds() };

    if (defined($ok)) {
    	my ($oid) = $cce->find('System');
    	if ($oid) {
		my ($ok, $obj) = $cce->get($oid);
		# $cce->set($oids[0], "Cache", {enabled => $newval});
		$cce->set($oid, "", {productLanguage => $locale});
		$obj->{productLanguage} = $locale;
		$cce->commit();
	} else {
		return 0;
	}
    } else {
	return 1;
    }

    $cce->bye('SUCCESS');
    return 1;
}

####################################

sub getProperty{
	my($self,$key,$domain,$lang)=@_;
	$lang=$lang||$self->{locale}||i18n_getSystemLocale();
	
	return "ERROR" unless $key && $domain && $lang;
	if (! -f "/usr/share/locale/$lang/$domain.prop") {
		$lang =~ /([^_]*)_.*/;
		$lang = $1;
	}
	return "ERROR" unless -f "/usr/share/locale/$lang/$domain.prop";
	open(PROP,"/usr/share/locale/$lang/$domain.prop") || return "ERROR";
	@prop=<PROP>;
	close PROP;
	my $prop=(split/:/,(grep {/^$key:/} @prop)[0])[1];
	$prop=~s/(?:^\s+|\s+$)//g;
	return $prop;
}

#
# Encode a string properly based on the current locale.
# args:
#	string to encode
#	To encoding (optional).  Encoding to convert string to.
#	From encoding (optional).  Assume the passed in string is in this
#	encoding rather than determining the encoding automatically.
#	locale (optional) to encode for.  If not specified, the locale of the
#	I18n object is used.  If setLocale hasn't been called, the system locale
#	is used.

#	
# returns:
#	The encoded string if successful.
#	undefined if there is an error.
#
sub encodeString 
{
	my ($self, $string, $to_encoding, $from_encoding, $locale) = @_;

	$locale = $locale || $self->{locale} || i18n_getSystemLocale();
	if ($locale eq '') {
		return undef;
	}

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

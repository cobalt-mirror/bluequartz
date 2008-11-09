#
# $Id: I18n.pm 3 2003-07-17 15:19:15Z will $
#
# Copyright 2000 Cobalt Networks
#
# A perl interface to i18n library

package I18n;

use IO::Handle;
use IPC::Open2;
use vars qw( $VERSION );
$VERSION = 1.1;

sub new
# Doesn't do anything special yet. 
{
    my ($class) = @_;
    my $self = {};
    bless($self, $class);
    return $self;
}

sub setLocale {
    my $self = shift;
    $self->{locale} = shift;
    $ENV{"LANGUAGE"} = $self->{locale};
    $ENV{"LANG"} = $self->{locale};
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

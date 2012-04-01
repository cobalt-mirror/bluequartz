#!/usr/bin/perl -I/usr/sausalito/perl
#
# $Id: 14_set_login_locale.pl
# 
# Update /etc/sysconfig/i18n on CCEd restarts
#

use CCE;
use Sauce::Util;

# translation table to convert our generic locales to full glibc locales
my $langs = {
		'en' => 'en_US',
		'de' => 'de_DE@euro',
		'ja' => 'ja_JP.eucJP',
		'ja_JP' => 'ja_JP.eucJP',
		'es' => 'es_ES@euro',
		'fr' => 'fr_FR@euro',
		# these may be wrong, but I don't know chinese
		'zh_CN' => 'zh_CN', 
		'zh_TW' => 'zh_TW'
	    };

my $cce = new CCE;
$cce->connectuds();

# Find out which platform we're on:
my ($fullbuild) = `cat /etc/build`;
chomp($fullbuild);

# figure out our product
my ($product, $build, $lang) = ($fullbuild =~ m/^build (\S+) for a (\S+) in (\S+)/);

# Get configured Language:
(my $sys_oid) = $cce->find('System', '');
($ok, $sys) = $cce->get($sys_oid);

# Set locale to productLanguage:
$locale = $sys->{productLanguage};

Sauce::Util::editfile('/etc/sysconfig/i18n', *update_i18n, $locale);
system("/bin/chmod 644 /etc/sysconfig/i18n");

$cce->bye('SUCCESS');
exit(0);

sub update_i18n
{
	my ($in, $out, $locale) = @_;

        my $locales = $sys->{"locales"};
        $locales =~ s/^&//;     ##remove the front '&' before split
        my @langlist = split('&', $locales);

        #get the current lang code from "en_US" format
        my $curlangcode = substr($sys->{productLanguage}, 0, 2);

	# On 5107R and 5108R we need to switch from 'en' to 'en_US':
	if (($build eq "5106R") || ($build eq "5107R") || ($build eq "5108R")) {
	    if ($curlangcode eq "en") {
		$curlangcode = "en_US";
	    }
	    # LC_ALL on SL6 must be a bit more specific:
	    $locale_LC_ALL = "en_US.UTF-8";
	}
	else {
	    # LC_ALL on CentOS5 can be a bit more simplicistic:
	    $locale_LC_ALL = $locale;
	}
        # Same for Japanese:
        if (($build eq "5106R") || ($build eq "5107R") || ($build eq "5108R")) {
            if (($curlangcode eq "ja") || ($curlangcode eq "ja_JP")) {
                $curlangcode = "ja_JP";
        	# LC_ALL on SL6 must be a bit more specific:
        	$locale_LC_ALL = "en_US.UTF-8";
            }
        }
	else {
	    # LC_ALL on CentOS5 can be a bit more simplicistic:
	    $locale_LC_ALL = $locale;
	}

	if ($locale eq "ja_JP") {
        	$locale = "ja_JP.eucJP";
	}

	#we need to move the current lang code to ahead of the rest
	my $linguas = $curlangcode;
	foreach my $tmp (@langlist) {
		if ($tmp ne $curlangcode) {
			$linguas = $linguas . " " . $tmp;
		}
	}

	print $out <<LOCALE;
LANG=$locale
LC_ALL=$locale_LC_ALL
LINGUAS="$linguas"
LOCALE

system("export LANG=$locale");
system("export LC_ALL=$locale_LC_ALL");
system("/bin/rm -f /etc/sysconfig/i18n.backup.*");
	return 1;
}


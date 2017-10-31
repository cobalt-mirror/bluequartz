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
		'ja' => 'ja_JP.utf8',
		'ja_JP' => 'ja_JP.utf8',
		'es' => 'es_ES@euro',
		'fr' => 'fr_FR@euro',
		'pt' => 'pt_PT@euro',
		'it' => 'it_IT@euro',
		'nl' => 'nl_NL@euro',
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
        my $curlangcode = $sys->{productLanguage};

	# On 5107R and 5108R we need to switch from 'en' to 'en_US':
	if (($build eq "5106R") || ($build eq "5107R") || ($build eq "5108R")) {
	    if ($curlangcode eq "en") {
		$curlangcode = "en_US";
	    }
	    # LC_ALL on SL6 must be a bit more specific:
	    $locale_LC_ALL = "en_US.utf8";
	}
	else {
	    # LC_ALL on CentOS5 can be a bit more simplicistic:
	    $locale_LC_ALL = $locale;
	}
        # Same for Japanese:
        if (($build eq "5106R") || ($build eq "5107R") || ($build eq "5108R")) {
            if (($curlangcode eq "ja") || ($curlangcode eq "ja_JP")) {
                $curlangcode = "ja_JP.utf8";
        	# LC_ALL on SL6 must be a bit more specific:
        	$locale_LC_ALL = "en_US.utf8";
            }
        }
	else {
	    # LC_ALL on CentOS5 can be a bit more simplicistic:
	    $locale_LC_ALL = $locale;
	}

	if ($locale eq "ja_JP") {
        	$locale = "ja_JP.utf8";
	}
	if ($locale eq "da_DK") {
        	$locale = "da_DK.utf8";
	}
	if ($locale eq "de_DE") {
        	$locale = "de_DE.utf8";
	}
	if ($locale eq "es_ES") {
        	$locale = "es_ES.utf8";
	}
	if ($locale eq "fr_FR") {
        	$locale = "fr_FR.utf8";
	}
	if ($locale eq "it_IT") {
        	$locale = "it_IT.utf8";
	}
	if ($locale eq "pt_PT") {
        	$locale = "pt_PT.utf8";
	}
	if ($locale eq "nl_NL") {
        	$locale = "nl_NL.utf8";
	}
	if ($locale eq "en") {
        	$locale = "en_US";
	}

	#we need to move the current lang code to ahead of the rest
#	my $linguas = $curlangcode;
#	foreach my $tmp (@langlist) {
#		if ($tmp ne $curlangcode) {
#			$linguas = $linguas . " " . $tmp;
#		}
#	}

	# Sort in a way that 'en_US' always comes first:
	@sorted_linguas = sort {
	    if ($a eq 'en_US') {
		return -1;
	    } elsif ($b eq 'en_US') {
		return 1;
	    } 
	    else {
		return $a cmp $b;
	    }
	} @langlist;
	$linguas = join(" ",@sorted_linguas);

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

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#	 notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#	 notice, this list of conditions and the following disclaimer in 
#	 the documentation and/or other materials provided with the 
#	 distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#	 contributors may be used to endorse or promote products derived 
#	 from this software without specific prior written permission.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
# COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
# POSSIBILITY OF SUCH DAMAGE.
# 
# You acknowledge that this software is not designed or intended for 
# use in the design, construction, operation or maintenance of any 
# nuclear facility.
# 
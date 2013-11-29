#!/usr/bin/perl -I/usr/sausalito/perl
#
# $Id: set_login_locale.pl,v 1.1.2.2 2002/03/21 21:37:23 naroori Exp $
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.
# 
# Update /etc/sysconfig/i18n when the productLanguage changes.
#

use CCE;
use Sauce::Util;

# translation table to convert our generic locales to full glibc locales
my $langs = {
		'en' => 'en_US',
		'de' => 'de_DE@euro',
		'ja' => 'ja_JP.eucJP',
		'es' => 'es_ES@euro',
		'fr' => 'fr_FR@euro',
		# these may be wrong, but I don't know chinese
		'zh_CN' => 'zh_CN', 
		'zh_TW' => 'zh_TW'
	    };

my $cce = new CCE;
$cce->connectfd();

my $sys = $cce->event_object();

my $locale = $sys->{productLanguage};
if (exists($langs->{$locale})) {
	$locale = $langs->{$locale};
}

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
        if ($locale eq "en") {
                $locale = "en_US";
        }

        #we need to move the current lang code to ahead of the rest
#       my $linguas = $curlangcode;
#       foreach my $tmp (@langlist) {
#               if ($tmp ne $curlangcode) {
#                       $linguas = $linguas . " " . $tmp;
#               }
#       }
         
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
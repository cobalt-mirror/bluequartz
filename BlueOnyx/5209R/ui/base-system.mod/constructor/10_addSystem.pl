#!/usr/bin/perl -I/usr/sausalito/perl -I.
# $Id: 10_addSystem.pl

#use strict;
use CCE;
use I18n;
use Sys::Hostname::FQDN qw(
        asciihostinfo
        gethostinfo
        fqdn
        short
  );

my $errors = 0;

my $cce = new CCE;
$cce->connectuds();

my ($fullbuild) = `cat /etc/build`;
chomp($fullbuild);

# figure out our product
my ($product, $build, $lang) = ($fullbuild =~ m/^build (\S+) for a (\S+) in (\S+)/);

# Supported languages:
my %locales = (  
    "en_US" => "&en_US&",
    "da_DK" => "&da_DK&",
    "de_DE" => "&de_DE&",
    "es_ES" => "&es_ES&",
    "fr_FR" => "&fr_FR&",
    "it_IT" => "&it_IT&",
    "ja_JP" => "&ja_JP&",
    "nl_NL" => "&nl_NL&",
    "pt_PT" => "&pt_PT&"
);


my ($i18n) = `grep LANG /etc/sysconfig/i18n`;
if ($i18n =~ m/^LANG="(.*)"/) {
        $lang = $1;
}
if ($i18n =~ m/^LANG=(.*)/) {
        $lang = $1;
}

if ($lang =~ /^ja/) {
    $lang = 'ja_JP';
}
elsif ($lang =~ /^da_DK/) { 
    $lang = 'da_DK';
}
elsif ($lang =~ /^de_DE/) { 
    $lang = 'de_DE';
}
elsif ($lang =~ /^es_ES/) { 
    $lang = 'es_ES';
}
elsif ($lang =~ /^fr_FR/) { 
    $lang = 'fr_FR';
}
elsif ($lang =~ /^it_IT/) { 
    $lang = 'it_IT';
}
elsif ($lang =~ /^pt_PT/) { 
    $lang = 'pt_PT';
}
elsif ($lang =~ /^nl_NL/) { 
    $lang = 'nl_NL';
}
else {
    $lang = 'en_US';
}

($name,$aliases,$addrtype,$length,@addrs)=gethostinfo();
$myhost = short();
$fqdn = fqdn();
@hlist = split(/\s/, $aliases) ;
foreach $line (@hlist) {
    if ($line =~ m/^$myhost\.(.*)$/ig ) {
        unless (($line =~ m/localhost/ig) || ($line =~ m/localdomain/ig)) {
            $fqdn = $line;
        }
    }
}
$mydomain = $fqdn;
$mydomain =~ s/^$myhost\.//;

if ( $myhost eq "" ) {
    $myhost = "localhost";
}
if ( $mydomain eq "" ) {
    $mydomain = "localdomain";
}

my @nss = ();
if (open (FILE, '< /etc/resolv.conf')) {
    while ( defined($_ = <FILE>) ) {
        chomp ();
        # print $_, "\n";
        if (/nameserver\s*(\S.*)/) {
            if (defined ($1)) {
                if (in_array(\@nss, $1)) {
                    # Nada
                }
                else {
                    push @nss, $1;
                }
            }
        }
    }
    close (FILE);
}
my $nsscalar = "";
if ( @nss ) {
    $nsscalar = CCE->array_to_scalar (@nss)
}

#
# figure out which locales the machine has available.  Just look for this
# module, because it will have all the supported locales.
#
my $available_langs = $cce->array_to_scalar(I18n::getAvailableLocales('base-system'));

# count the systems;
my @oids = $cce->find("System");
if ($#oids == 0) {
    # We have only one System object - update it:
        ($sys_oid) = $cce->find('System', '');
        ($ok) = $cce->update($sys_oid, '',{
                'hostname' => $myhost,
                'domainname' => $mydomain,
                'dns' => $nsscalar,
                'productBuildString'=>$fullbuild,
                'productIdentity' => $product,
                'productBuild' => $build,
                'productLanguage' => $lang,
                'console' =>"0",
                'locales' => $available_langs
        });
        $oids[0] = $cce->oid();
} elsif ($#oids < 0) {
    # we must create a System object with no properties.
    $cce->create("System", {
        'hostname' => $myhost,
        'domainname' => $mydomain,
        'dns' => $nsscalar,
        'productBuildString'=>$fullbuild,
        'productIdentity' => $product,
        'productBuild' => $build,
        'productLanguage' => $lang,
        'console' =>"0",
        'locales' => $available_langs
    });
    $oids[0] = $cce->oid();
} else { # we have more than one System object.
    print STDERR "ERROR: Multiple system objects detected!\n";
    print STDERR "       attempting to repair...\n";
    shift(@oids); # don't delete this one.
    foreach $_ (@oids) {
        my ($success) = $cce->destroy($_);
        if ($success) {
            print STDERR "       Deleted System object $_\n";
        } else {
            print STDERR "       FAILED to delete System object $_\n";
            $errors++;
        }
    }
}

# sync console (redundant on first create, safe on all others)
# we do it this way, because the console flag may change at powerup
#my $conval = `/sbin/nvram -c console`;
#chomp($conval);
#$cce->update($oids[0], "", { console => $conval eq "on" ? "1" : "0"}) if $oids[0];

# update System.locales everytime in case we add/remove a locale
if ($oids[0]) {
    $cce->update($oids[0], '', { 'locales' => $available_langs });
}

$serverName = $myhost . '.' . $mydomain;
system("/usr/bin/hostnamectl set-hostname $serverName");

$cce->bye();
exit($errors);

sub in_array {
    my ($arr,$search_for) = @_;
    my %items = map {$_ => 1} @$arr; # create a hash out of the array values
    return (exists($items{$search_for}))?1:0;
}

# 
# Copyright (c) 2014-2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014-2018 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#    notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#    notice, this list of conditions and the following disclaimer in 
#    the documentation and/or other materials provided with the 
#    distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#    contributors may be used to endorse or promote products derived 
#    from this software without specific prior written permission.
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

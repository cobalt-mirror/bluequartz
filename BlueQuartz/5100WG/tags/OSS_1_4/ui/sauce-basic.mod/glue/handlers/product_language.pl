#!/usr/bin/perl -w -I/usr/sausalito/perl -I. 
# $Id: 10_locale_fallback.pl,v 1.1.2.1 2001/03/27 21:58:56 will Exp 
# Depends on:
#		System.productLanguage
#
# MPBug fixed.

#use strict;
use lib '/usr/sausalito/handlers/base/winshare';
use Sauce::Util;
use Sauce::Config;
use FileHandle;
use File::Copy;
use CCE;
use I18n;
use smb;

my $cce = new CCE;
$cce->connectfd(\*STDIN,\*STDOUT);
#$cce->connectuds();

my ($oid) = $cce->find("System");
my ($ok, $obj) = $cce->get($oid);

my $locale = $obj->{productLanguage};

# Fix admin's locale
my ($uoid) = $cce->find("User", {name => 'admin'});

my ($uok, $uobj) = $cce->get($uoid);
if($locale ne $uobj->{localePreference}) {
	$cce->set($uoid, '', {localePreference => $locale});
	$cce->commit();
}

$cce->bye("SUCCESS");

$ENV{LANG}=$locale;
my $i18n = new I18n;
$i18n->setLocale($locale);

my %settings=();

$settings{'client code page'} = $i18n->getProperty("sambaCodePage","base-winshare");

if($i18n->getProperty("sambaCodingSystem","base-winshare") ne "none"){
        $settings{'codingsystem'} = $i18n->getProperty("sambaCodingSystem","base-winshare");
}else{
	$settings{'codingsystem'} = undef;
}

Sauce::Util::editfile(smb::smb_getconf, *smb::edit_global,
                      %settings);

if(-e "/var/lock/subsys/smb"){
        `/etc/rc.d/init.d/smb restart`;
}


umask(0077);

# legacy and modern Cobalt locale stamps
my $stage;

my $real = "/etc/cobalt/locale";
$stage = $real.'~';
unlink($stage);
sysopen(STAGE, $stage, 1|O_CREAT|O_EXCL, 0600) || die;
print STAGE $locale."\n";
close(STAGE);

chmod(0644, $stage);
if(-s $stage) {
  move($stage,$real);
  chmod(0644,$real); # paranoia
} 

$real = "/usr/sausalito/locale";
$stage = $real.'~';
unlink($stage);
sysopen(STAGE, $stage, 1|O_CREAT|O_EXCL, 0600) || die;
print STAGE $locale."\n";
close(STAGE);

chmod(0644, $stage);
if(-s $stage) {
  move($stage,$real);
  chmod(0644,$real); # paranoia
} 

my @fall_back_html = ('/usr/sausalito/ui/web/error/authorizationRequired.html',
                      '/usr/sausalito/ui/web/error/fileNotFound.html',
                      '/usr/sausalito/ui/web/error/forbidden.html',
                      '/usr/sausalito/ui/web/error/internalServerError.html');
my($page);
foreach $page (@fall_back_html) {
  my $fall_back = $page.'.'.$locale;
  next unless (-r $fall_back);
  unlink($page);
  copy($fall_back, $page);
  chmod(0644,$page);
}

exit(0);


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

#!/usr/bin/perl
# $Id: Qube3scanout.pl 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http://www.cobalt.com
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
use strict;

# If you are not toor, go away :)
die "You must run this script as root\n" if ($< != 0);

use lib "/usr/cmu/perl";
use lib "/usr/sausalito/perl";
require CmuCfg;

my $cfg = CmuCfg->new(type => 'scanout');
$cfg->parseOpts();

use cmuCCE;
use I18n;
use TreeXml;
require Archive; 

if(!-d $cfg->destDir) {
	warn "ERROR: destination directory does not exsist: ", $cfg->destDir, "\n";
	exit 1;
}
if(!$cfg->glb('outFile')) { $cfg->putGlb('outFile', $cfg->cmuXml) }

my %defaultGroups = (
		     'home'         => 1,
		     'guest-share'  => 1,
		     'restore'      => 1);		     

my %defaultUsers  = ('admin' => 1);


my %arrayVal = (
	local_recips => 'lrecips',
	remote_recips => 'xrecips',
	aliases => 'alias',
	members => 'member',
	capabilities => 'cap',
	capLevels => 'cap',
	forwardEmail => 'forwardEmail'
);
		
my %encodeAttr = (
	fullName  => 1,
	sortName  => 1,
	description	=> 1,
	vacationMsg	=> 1
);

my %classes = (
	MailList =>	[],
	System => [qw(serialNumber SWUpdate productSerialNumber RAID)],
	Network => [qw(mac)],
);

my $smbPasswd;
if(-f "/etc/samba/smbpasswd") { $smbPasswd = "/etc/samba/smbpasswd" }
else { $smbPasswd = "/etc/smbpasswd" }


my $cce = new cmuCCE;
$cce->connectuds();

my $i18n = new I18n;
$i18n->setLocale(I18n::i18n_getSystemLocale($cce));

%{ $cce->{_arrayVal} } = %arrayVal;
%{ $cce->{_encodeAttr} } = %encodeAttr;
%{ $cce->{_classes} } = %classes;


# data structure to build 
my $tree = {}; 
# A bunch of reused vars in loops 
my ($ok, @oid, $data, $cceObj, $arch);

$tree->{migrate}->{exportPlatform} = "Qube3";
$tree->{migrate}->{adjustPlatform} = "Qube3";
$tree->{migrate}->{cmuVersion} = $VERSION;
$tree->{migrate}->{cmuDate} = time();

my $baseFqdn = `/bin/uname -n`;
chomp($baseFqdn);
$tree->{migrate}->{baseHost} = $baseFqdn;

# create the default archive objects
my $garch = Archive->new(type => 'groups', destDir => $cfg->destDir);
my $uarch = Archive->new(type => 'users', destDir => $cfg->destDir);


# first we get users
@oid = $cce->find("User");
foreach my $oid (@oid) {
	($ok, $cceObj) = $cce->get($oid);
	if($ok == 0) { warn "bad find on User\n";next; }
	
	# key is the user name
    my $key = $cceObj->{name};
	if(defined $defaultUsers{$key}) { next; }

	my $data = $cce->loadHash($cceObj);
	$cce->loadNamespace($data, $oid);

	if($cfg->noPasswd eq 't') {
		delete($data->{crypt_password}) 
			if(defined($data->{crypt_password}));
		delete($data->{md5_password})
			if(defined($data->{md5_password}));
	} else {
		if(!defined($data->{crypt_password}) &&
		!defined($data->{md5_password})) {
			my $line = `grep ^$key: /etc/shadow`;
			$data->{md5_password} = (split(":",$line))[1];
		}
		my $line = `grep ^$key: $smbPasswd`;
		if($line) {
			$data->{smb_password} = join(":",(split(":",$line))[2,3]);	
		}
	}

	if($cfg->confOnly eq 'f') {
		warn "INFO: building archive for $key\n";
		$uarch->setName($key);
		$uarch->buildTar();
		$data->{archives} = $uarch->archives;
    }
	$data->{id} = $key;
	push @{ $tree->{migrate}->{user} }, $data;
}

# handle admin's files
if($cfg->confOnly eq 'f' && $cfg->adminFiles eq 't') {
	my $adm = {}; $adm->{id} = 'admin'; $adm->{merge} = 't';
	warn "INFO: building archive for user admin\n";
	
	$uarch->setName('admin');
	$uarch->buildTar();
	$adm->{archives} = $uarch->archives;
	push @{ $tree->{migrate}->{user} }, $adm;
}

# then groups
@oid = $cce->find("Workgroup");

foreach my $oid (@oid) {
    ($ok, $cceObj) = $cce->get($oid);
	if($ok == 0) { warn "cannot get oid $oid\n";next; }
    my $key = $cceObj->{name};
	warn "INFO: exporting group $key\n";

	# Pull the real message
	# why did I need to do this again?
	#if($object->{description} =~ /^\[\[/) {
	#	$object->{description} = $i18n->get($object->{description});
	#}

	my $data = $cce->loadHash($cceObj);
	$cce->loadNamespace($data, $oid);

	if($cfg->confOnly eq 'f') {
		warn "INFO: building archive for $key\n";
		$garch->setName($key);
		$garch->buildTar();
		$data->{archives} = $garch->archives;
	}

	$data->{id} = $key;
	push @{ $tree->{migrate}->{group} }, $data;
}
  
@oid = $cce->find("MailList");
foreach my $oid (@oid) {
    ($ok, $cceObj) = $cce->get($oid);
	if($ok == 0) { warn "cannot get oid $oid\n";next; }
    my $key = $cceObj->{name};
	warn "INFO: exporting mailing list $key\n";

	my $data = $cce->loadHash($cceObj);
	$cce->loadNamespace($data, $oid);
	$data->{id} = $key;
	push @{ $tree->{migrate}->{list} }, $data;
}


# and finally mailing lists and others
#foreach my $cl (keys %classes) {
#	$cce->exportClass($tree,$cl);
#}


$cce->bye("bye bye");

warn "INFO: We exported", TreeXml::getStats($tree->{migrate});
if ($cfg->isGlb('outFile')) {
    TreeXml::writeXml($tree, $cfg->glb('outFile'));
} else {
	# this is needed for open3
	close(STDERR);
    print TreeXml::writeXml($tree);
}

exit 0;
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

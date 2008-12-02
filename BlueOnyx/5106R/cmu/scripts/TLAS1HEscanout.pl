#!/usr/bin/perl
# $Id: TLAS1HEscanout.pl 945 2005-10-09 12:26:12Z shibuya $
# Cobalt Networks, Inc http://www.cobalt.com
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
use strict;

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

my %defaultUsers  = ('admin' => 1);

my %encodeAttr = (
	fullName => 1,
	sortName => 1,
	description => 1,
	apop_password => 1,
	vacationMsg => 1
);

my %arrayVal = (
	aliases	=> 'alias',
	capabilities => 'cap',
	capLevels => 'cap',
	siteAdminCaps => 'cap',
	mailAliases => 'domain',
	webAliases => 'domain',
	forwardEmail =>	'forward',
	local_recips =>	'recip',
	remote_recips => 'recip',
	dns => 'dns',
	locales => 'locale',
	acceptFor => 'accept',
	relayFor =>	'relay',
);

my %classes = (
    DnsRecord => [qw()],
    DnsSlaveZone => [qw()],

	#System => [qw(serialNumber SWUpdate productSerialNumber RAID)],
	#Network => [qw(mac)],
);


my $cce = new cmuCCE;
$cce->connectuds();

my $i18n = new I18n;
$i18n->setLocale(I18n::i18n_getSystemLocale($cce));

%{ $cce->{_arrayVal} } = %arrayVal;
%{ $cce->{_encodeAttr} } = %encodeAttr;
%{ $cce->{_classes} } = %classes;


# data structure to build 
my $tree = {}; 
my ($ok, @oids, $cceRef, $fqdn);

$tree->{migrate}->{exportPlatform} = "TLAS1HE";
$tree->{migrate}->{adjustPlatform} = "TLAS1HE";
$tree->{migrate}->{cmuVersion} = $VERSION;
$tree->{migrate}->{cmuDate} = time();

my $baseFqdn = `/bin/uname -n`;
chomp($baseFqdn);
$tree->{migrate}->{baseHost} = $baseFqdn;


# first we get vsites
@oids = $cce->find("Vsite");
foreach my $oid (@oids) {
    ($ok, $cceRef) = $cce->get($oid);
	if($ok == 0) { warn "cannot get oid $oid\n";next; }
    my $key = $cceRef->{fqdn};
	warn "INFO: exporting vsite $key\n";

	if($cfg->isSubSet) { next unless($cfg->isSubSet($key)) }
	if($cfg->isIpaddr) { $cceRef->{ipaddr} = $cfg->ipaddr }

	my $vobj = $cce->loadHash($cceRef);
	$cce->loadNamespace($vobj, $oid);
	$vobj->{id} = $key;

	#warn "obj baseDir is: ", $vobj->{basedir}, "\n";

	if($cfg->confOnly eq 'f') {
		warn "INFO: building archive for $key\n";
		my $arch = Archive->new(type => 'groups', 
			baseDir => $vobj->{basedir}, name => $key, 
			destDir => $cfg->destDir);
		$arch->buildTar();
		$vobj->{archives} = $arch->archives;
	}
	push @{ $tree->{migrate}->{vsite} }, $vobj;
}

@oids = $cce->find("User");
foreach my $oid (@oids) {
    ($ok, $cceRef) = $cce->get($oid);
	if($ok == 0) { warn "bad find on User\n";next; }
	# skip admin for now
	if($cceRef->{name} eq 'admin') { next; }

    my $key = $cceRef->{name};
	warn "INFO: exporting user $key\n";
	($fqdn) = $cce->findMember("Vsite", 
		{ name => $cceRef->{site} }, 
		undef, 
		'fqdn'
	);
	if(!$fqdn) {
		warn "ERROR: cannot retrieve virtual site for user ",
			$cceRef->{name}, "\n";
	}
	
	if($cfg->isSubSet) { next unless($cfg->isSubSet($fqdn)) }

	my $uobj = $cce->loadHash($cceRef);
	$cce->loadNamespace($uobj, $oid);
	if($cfg->noPasswd eq 't') {
		delete($uobj->{crypt_password}) if(defined $uobj->{crypt_password});
		delete($uobj->{md5_password}) if(defined $uobj->{md5_password});
		delete($uobj->{APOP}->{apop_password}) 
			if(defined $uobj->{APOP}->{apop_password});
	} 
	$uobj->{id} = $key;
	$uobj->{fqdn} = $fqdn;

	if($cfg->confOnly eq 'f') {
		warn "INFO: building archive for $key\n";
		my $arch = Archive->new( type => 'users',
			name => $key, destDir => $cfg->destDir);
		$arch->buildTar();
		$uobj->{archives} = $arch->archives;
	}
	push @{ $tree->{migrate}->{user} }, $uobj;
}
# handle admin's files
if($cfg->confOnly eq 'f' && $cfg->adminFiles eq 't') {
	my $arch = Archive->new( type => 'users', name => 'admin',
		destDir => $cfg->destDir);
	$arch->buildTar();
}

@oids = $cce->find("MailList");
foreach my $oid (@oids) {
    ($ok, $cceRef) = $cce->get($oid);
	if($ok == 0) { warn "bad find on mailList\n";next; }
	unless(defined $cceRef->{site}) {
		warn "ERROR: mailing list $cceRef->{name} does not have a site group\n";
		next;
	}
	($fqdn) = $cce->findMember("Vsite", { name => $cceRef->{site} }, undef, 'fqdn');
	unless($fqdn) {
		warn "ERROR: cannot retrieve virtual site for mailing list $cceRef->{name}\n";
		next;
	}
	if($cfg->isSubSet) { next unless($cfg->isSubSet($fqdn)) }
	
    my $key = $fqdn."-".$cceRef->{name};
	my $mobj = $cce->loadHash($cceRef);
	$cce->loadNamespace($mobj, $oid);

	# remove some unwanted stuff
	if($cfg->noPasswd eq 't') {
		delete($mobj->{apassword}) if(defined $mobj->{apassword});
	} 
	delete($mobj->{internal_name}) if(defined $mobj->{internal_name});
	delete($mobj->{site}) if(defined $mobj->{site});

	$mobj->{id} = $key;
	$mobj->{fqdn} = $fqdn;
	push @{ $tree->{migrate}->{list} }, $mobj;
}


# for all other classes
foreach my $cl (keys %classes) {
	$cce->exportClass($tree,$cl);
}

$cce->bye("bye bye american pie");

warn "INFO: We exported", TreeXml::getStats($tree->{migrate});
if($cfg->isGlb('outFile')) {
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

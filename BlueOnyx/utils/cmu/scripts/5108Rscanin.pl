#!/usr/bin/perl
# $Id: 5108Rscanin.pl Sun 05 Feb 2012 04:58:56 AM CET mstauber $
# Cobalt Networks, Inc http://www.cobalt.com
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# C. Hemsing: minor repair on tilde expansion
use strict;

use lib "/usr/cmu/perl";
use lib "/usr/sausalito/perl";
require CmuCfg;
use Base::User qw(usermod);

my $cfg = CmuCfg->new(type => 'scanin');
$cfg->parseOpts();

use cmuCCE;
use I18n;
use TreeXml;
require Archive;
require RaQUtil;

if(!$cfg->isDestDir) {
	my $dir = `pwd`; chomp($dir);
	$cfg->putDestDir($dir);
}
if(!-f $cfg->glb('inFile')) { die "$0: You must pass a valid file name\n" }

my $tree = readXml($cfg->glb('inFile'), 0);
my $puretree = readXml($cfg->glb('inFile'), 0);
if($cfg->isGlb('readConfig')) {
	if($cfg->isGlb('subsetNames')) { $tree = $cfg->removeNamesVsite($tree) }
	if($cfg->isIpaddr) { $tree = $cfg->convertIpaddr($tree) }
}
unless(defined $tree->{vsite}) {
	warn "ERROR: No virtual sites to import\n";
	exit 1;
}

my %encodeAttr = (
	fullName          => 1,
	sortName 	      => 1,
	description	      => 1,
	apop_password     => 1,
	vacationMsg	      => 1
);

my %arrayVal = (
	aliases => 'alias',
	mailAliases => 'domain',
	webAliases => 'domain',
	forwardEmail =>	'forward',
	local_recips =>	'recip',
	remote_recips => 'recip',
	capLevels => 'cap',
);

my %classes = (
	System		=>	[qw(serialNumber SWUpdate productSerialNumber RAID)],
	Network		=>	[qw(mac)],
);

my $cce = new cmuCCE;
$cce->connectuds();
$cce->auth('admin', $cfg->glb('adminPassword'));

my $i18n = new I18n;
$i18n->setLocale(I18n::i18n_getSystemLocale($cce));

%{ $cce->{_arrayVal} } = %arrayVal;
%{ $cce->{_encodeAttr} } = %encodeAttr;
%{ $cce->{_classes} } = %classes;


# setup the default archive objects
my $varch = Archive->new(type => 'groups', destDir => $cfg->destDir, 
	sessID => $cfg->sess
);
my $uarch = Archive->new(type => 'users', destDir => $cfg->destDir,
	sessID => $cfg->sess
);

# we start with the vsites baby....
my ($ok, $bad, @info, $oid, @keys, $cceRef);
my @vsiteNames = RaQUtil::orderVsites($tree);
foreach my $fqdn (@vsiteNames) {
	warn "INFO: Creating virtual site: $fqdn\n";
	my $vTree =  $tree->{vsite}->{$fqdn};

	my $vRef = $cce->unLoadHash($vTree);
	$vRef->{fqdn} = $fqdn;

	# delete unwanted stuff;
	delete $vRef->{type} if(defined $vRef->{type});
	delete $vRef->{name} if(defined $vRef->{name});
	delete $vRef->{gid} if(defined $vRef->{gid});
	delete $vRef->{user_fpx} if(defined $vRef->{user_fpx});
	delete $vRef->{user_quota} if(defined $vRef->{user_quota});
	delete $vRef->{user_shell} if(defined $vRef->{user_shell});
	delete $vRef->{user_casp} if(defined $vRef->{user_casp});
	delete $vRef->{user_apop} if(defined $vRef->{user_apop});
	delete $vRef->{bwlimit} if(defined $vRef->{bwlimit});
	delete $vRef->{basedir} if(defined $vRef->{basedir});

	# default to /home
	$vRef->{volume} = '/home';

	# I always hated the null as false crap in CCE
	if(!defined $vRef->{dns_auto}) { $vRef->{dns_auto} = 0; }

	# make sure that if we are importing dns we turn auto OFF
	if($cfg->dns eq 't') {
		$vRef->{dns_auto} = 0;
	}

	# Delete references for PHP (DSO) + mod_ruid2 and PHP-FPM, as pre-5209R platforms
	# don't have it yet. This makes the Vsite . PHP settings fall back to regular PHP:
	#
	# Step #1: Remove the "Vsite" . "PHP" subclass entirely. Or the create Vsite fails:
	delete $vRef->{PHP} if(defined $vRef->{PHP});
	# Step #2: Remove the vTree "Vsite" . "PHP" key/value pairs for mod_ruid2 and FPM:
	if (defined $vTree->{PHP}->{mod_ruid_enabled}) {
		delete $vTree->{PHP}->{mod_ruid_enabled};
		# Fall back to the next best thing:
		$vTree->{PHP}->{suPHP_enabled} = '1';
	}
	if (defined $vTree->{PHP}->{fpm_enabled}) {
		delete $vTree->{PHP}->{fpm_enabled};
		# Fall back to the next best thing:
		$vTree->{PHP}->{suPHP_enabled} = '1';
	}
	# Step #3: Delete the new 'version' tag that's only valid for 5209R:
	if (defined $vTree->{PHP}->{version}) {
		delete $vTree->{PHP}->{version};
	}

	####################
	## We set the quota to an insanely high value during this stage and later 
	# on reset it to the desired quota amount:
	$vTree->{Disk}->{quota} = "500000000";
	####################

	if(!defined($vTree->{merge})) {
		($ok, $bad, @info) = $cce->create('Vsite', $vRef);
		if($ok == 0) {
			$cce->printReturn($ok, $bad, @info);
			warn "INFO: ERROR: Vsite $fqdn was not created properly. \n";
			if (-e "/proc/user_beancounters") {
			    warn "INFO: ERROR: You may have attempted to import a site with an IP address which has not been assigned to this OpenVZ VPS. \n";
			    warn "INFO: ERROR: Please assign that Vsites IP to this VPS first, or import to a different IP using the '-i <IP-Address>' switch. \n";
			}
			delete $tree->{vsite}->{$fqdn};
			next;
		} else { warn "Virtual site $vRef->{fqdn} OK=$ok \n" }
		$oid = $cce->oid();
	} else {
		($oid) = $cce->find("Vsite", { fqdn => $fqdn });
	}

	($ok, $cceRef) = $cce->get($oid);
	if($ok == 1) {
		$tree->{vsite}->{$fqdn}->{newGroup} = $cceRef->{name};
		$tree->{vsite}->{$fqdn}->{newObj} = $cceRef;
	} else { 
		warn "INFO: ERROR: Could not find vsite name (site1, site2, etc)\n";
		delete $tree->{vsite}->{$fqdn};
		next;
	}
	$cce->unLoadNamespace($vTree, $oid);

#	if($vTree->{Frontpage}->{enabled} && $cfg->noPasswd eq 'f' && 
#		defined $vTree->{Frontpage}->{passwordWebmaster}
#	) { 
#		RaQUtil::setFpxPass("/home/sites/".$fqdn, 
#			$vTree->{Frontpage}->{passwordWebmaster}) 
#	}

	# remove the default page
	if(-f "/home/sites/".$fqdn."/web/index.html") {
		unlink("/home/sites/".$fqdn."/web/index.html")
	}

} # end of vsite 

if(defined $tree->{user}) {
@keys = keys %{ $tree->{user} };
foreach my $user (@keys) {
	next if($user eq 'admin');
	warn "INFO: Creating user: $user\n";
	my $uTree = $tree->{user}->{$user};
	if(!defined $uTree->{name}) {
		$uTree->{name} = $user;
	}

	my $uRef = $cce->unLoadHash($uTree);
	# convert into fqdn into site
	my $vsites;

	# check adminUser
	my $admin;
	if (defined $uTree->{capLevels}) {
		my @arr = @{ $uTree->{capLevels}->{cap} };

		for(my $i = 0; $i < @arr; $i++) {
			if($uTree->{capLevels}->{cap}->[$i] eq 'adminUser') {
				$admin = 1;
			}
		}
	}

	if(defined $uRef->{fqdn}) {
		($uRef->{site}) = $cce->findMember("Vsite", 
			{ fqdn => $uRef->{fqdn} }, undef, 'name');
		if(! defined $uRef->{site}) {
			warn "INFO: ERROR: Cannot find site group for user: $user fqdn: ", 
				$uRef->{fqdn}, "\n";
			delete $tree->{user}->{$user};
			next;
		}
	} elsif(defined $uTree->{RootAccess}->{enabled}) {
		if($cfg->superUsers eq 't') { warn "INFO: Creating super user $user\n";
		} else {
			warn "INFO: Skipping Server Administrator $user (use -s to import)\n";
			delete $tree->{user}->{$user};
			next;
		}
	} elsif ($admin != 1) {
		warn "INFO: ERROR: Cannot add user $uRef->{name} with out site fqdn\n";
		delete $tree->{user}->{$user};
		next;
	}

	# delete unwanted stuff
	delete $uRef->{fqdn} if(defined $uRef->{fqdn});
	delete $uRef->{vsite} if(defined $uRef->{vsite});
	delete $uRef->{type} if(defined $uRef->{type});
	delete $uRef->{uid} if(defined $uRef->{uid});
	delete $uRef->{volume} if(defined $uRef->{volume});
	delete $uRef->{suspend} if(defined $uRef->{suspend});

	if(!defined $uRef->{stylePreference}) {
		$uRef->{stylePreference} = 'trueBlue';
	}

	# Remove ChorizoStyle from User as pre-Chorizo doesn't have it:
	delete $uRef->{ChorizoStyle} if(defined $uRef->{ChorizoStyle});

	#########
	# We set the user's disk quota to unlimited during this stage and later on reset it to
	# the desired quota amount:
	$uTree->{Disk}->{quota} = "-1";
	#########

	($ok, $bad, @info) = $cce->create('User', $uRef);
	if($ok == 0) {
		$cce->printReturn($ok, $bad, @info);
		delete $tree->{user}->{$user};
		next;
	} else { warn "User $uRef->{name} OK=$ok \n" }
	$oid = $cce->oid();
	$cce->unLoadNamespace($uTree, $oid);

	# note: for tilde expansion to work you need glob()
	unlink(glob('~'.$user.'/web/index.html'));

	# slap the crypt and md5 if it is around
	if($cfg->noPasswd eq 't') {
		($ok, $bad, @info) = $cce->set($oid, '', 
			{ password => $cfg->glb('userPasswd') });
		if($ok == 0) { $cce->printReturn($ok, $bad, @info) }
		warn "Setting default password for $uTree->{name} OK=$ok\n";
	} else {
		if(defined($uTree->{crypt_password})) {
			($ok, $bad, @info) = $cce->set($oid, '', 
				{ crypt_password => $uTree->{crypt_password} });
			if($ok == 0) { $cce->printReturn($ok, $bad, @info) }
			($ok, $bad, @info) = usermod({ name => $user, 
				password => $uTree->{crypt_password} });
			if($ok == 0) { $cce->printReturn($ok, $bad, @info) }
			warn "User $uTree->{name} setting crypt passwd OK=$ok\n";
		}
		if(defined($uTree->{md5_password})) {
			($ok, $bad, @info) = $cce->set($oid, '', 
				{ md5_password => $uTree->{md5_password} });
			if($ok == 0) { $cce->printReturn($ok, $bad, @info) }
			($ok, $bad, @info) = usermod({ name => $user, 
				password => $uTree->{md5_password} });
			if($ok == 0) { $cce->printReturn($ok, $bad, @info) }
			warn "User $uTree->{name} setting md5 passwd OK=$ok\n";
		} elsif(defined($uTree->{crypt_password})) {
			($ok, $bad, @info) = $cce->set($oid, '', 
				{ md5_password => $uTree->{crypt_password} });
			if($ok == 0) { $cce->printReturn($ok, $bad, @info) }
			($ok, $bad, @info) = usermod({ name => $user, 
				password => $uTree->{crypt_password} });
			if($ok == 0) { $cce->printReturn($ok, $bad, @info) }
			warn "User $uTree->{name} setting md5 passwd with crypt passwd OK=$ok\n";
		} 
	}

        if (($tree->{exportPlatform} =~ /(RaQ)/) || ($tree->{exportPlatform} =~ /(Qube)/)) {
                # Skip locking of accounts if we import from a RaQ or Qube. The suspend status
		# doesn't get translated correctly - yet.
        }
        else {
	        # Check if user is suspended. If so, lock the account:
        	if (($uTree->{enabled} eq "0") || ($uTree->{ui_enabled} eq "0")) {
                	system("/usr/sbin/usermod -L $uTree->{name}");
                	warn "User $uTree->{name} is suspended. Locking the account.\n";
        	}
	}

	# do file stuff
	if($cfg->confOnly eq 'f') {
		warn "INFO: restoring archive for $user\n";
		if(defined $uTree->{archives}) { 
			$uarch->setName($user);
			$uarch->setArchive($uTree->{archives});
			$uarch->setGid($uRef->{site});
			$uarch->extractTar();
		} else { warn "INFO: no archives defined for $user\n" }
	}
}
} # end of user

# move admin's files back
if($cfg->confOnly eq 'f' && $cfg->adminFiles eq 't') {
	warn "INFO: restoring archive for user admin\n";
	if(defined $tree->{user}->{admin}->{archives}) { 
		$uarch->setName("admin");
		$uarch->setArchive($tree->{user}->{admin}->{archives});
		$uarch->setGid("users");
		$uarch->extractTar();
	} else { warn "INFO: no archives defined for admin\n" }
}

# move the group files back after users are restored
if($cfg->confOnly eq 'f') {
	@keys = keys %{ $tree->{vsite} };
	foreach my $fqdn (@keys) {
		warn "INFO: restoring archive for $fqdn\n";
		if(defined $tree->{vsite}->{$fqdn}->{archives}) { 
			$varch->setName($fqdn);
			$varch->setArchive($tree->{vsite}->{$fqdn}->{archives});
			$varch->setGid($tree->{vsite}->{$fqdn}->{newGroup});
			$varch->extractTar();
			# chown SITEX-logs logs data
			my $log_uid = uc($tree->{vsite}->{$fqdn}->{newGroup}) . "-logs";
			my $log_dir = $tree->{vsite}->{$fqdn}->{newObj}->{basedir} . "/logs";
			system("/bin/chown -R $log_uid $log_dir");
		} else { warn "INFO: no archives defined for $fqdn\n" }
	}
}

if(defined $tree->{list}) {
@keys = keys %{ $tree->{list} };
foreach my $list (@keys) {
	warn "INFO: creating mailing list: $list\n";
	my $mTree = $tree->{list}->{$list};
	my $mRef = $cce->unLoadHash($mTree);

	if(defined $mRef->{fqdn}) {
		($mRef->{site}) = $cce->findMember("Vsite", 
			{ fqdn => $mRef->{fqdn} }, undef, 'name');
		if(!defined $mRef->{site}) {
			warn "INFO: ERROR: Cannot find site group for mailing list: $list fqdn: ", 
				$mRef->{fqdn}, "\n";
			delete $tree->{list}->{$list};
			next;
		}
	} else {	
		warn "INFO: ERROR: Cannot add mailing list $mRef->{name} with out site fqdn\n";
		delete $tree->{list}->{$list};
		next;
	}
	delete $mRef->{fqdn} if(defined $mRef->{fqdn});
	delete $mRef->{group} if(defined $mRef->{group});
	if(!defined $mRef->{postPolicy}) {
		$mRef->{postPolicy} = 'any';
	}

	($ok, $bad, @info) = $cce->create('MailList', $mRef);
	if($ok == 0) {
		warn "INFO: ERROR: Could not create mailing list: $list\n";
		$cce->printReturn($ok, $bad, @info);
		delete $tree->{list}->{$list};
		next;
	} else { warn "Mailing List $mRef->{name} OK=$ok \n" }
} # end of mail list
}

my $cmd;
if($cfg->dns eq 't') {
	warn "INFO: Importing DNS records\n";
	if($tree->{exportPlatform} =~ /(RaQ550|5100R|5200R|TLAS1HE|Qube3|510[6-8]R|520[7-9]R|516[0-1]R)/) {
		warn "INFO: DNS migration for this platform is handled separately and not by CMU.\n"
	} elsif(-f $cfg->destDir.'/records') {
		$cmd = '/usr/cmu/scripts/dnsImport '.$cfg->destDir.'/records';
		system($cmd);
	} else {
		warn "ERROR: Could not import DNS server settings\n";
	}
}

$cce->suspendAll($tree);
$cce->importCerts($tree);

if (-f "/usr/sausalito/sbin/fix_user_UID_and_GID.pl") {
        warn "INFO: Fixing UIDs and GIDs of all users by running /usr/sausalito/sbin/fix_user_UID_and_GID.pl.\n";
        system("/usr/sausalito/sbin/fix_user_UID_and_GID.pl >/dev/null 2>&1");
}

#################

# Set quota for the vsites:
my ($ok, $bad, @info, $oid, @keys, $cceRef);
my @vsiteNames = RaQUtil::orderVsites($puretree);
foreach my $fqdn (@vsiteNames) {
	my $qTree =  $puretree->{vsite}->{$fqdn};
	my $qtree_quota = $qTree->{Disk}->{quota};

	warn "INFO: Setting disk quota of $qtree_quota MB for virtual site: $fqdn\n";

	($oid) = $cce->find("Vsite", { fqdn => $fqdn });
        ($ok) = $cce->set($oid, 'Disk',{
            'quota' => $qtree_quota
        });

	delete $puretree->{vsite}->{$fqdn};
	next;
} # end of vsite quota 

# Set quota for the users:
if(defined $puretree->{user}) {
    @keys = keys %{ $puretree->{user} };
    foreach my $user (@keys) {
	next if($user eq 'admin');
	my $quTree = $puretree->{user}->{$user};
	if(!defined $quTree->{name}) {
		$quTree->{name} = $user;
	}
	my $user_quota = $quTree->{Disk}->{quota};

	warn "INFO: Setting disk quota of $user_quota MB for user: $user\n";

	($oid) = $cce->find("User", { name => $user });
        ($ok) = $cce->set($oid, 'Disk',{
            'quota' => $user_quota
        });

	delete $puretree->{user}->{$user};
	next;
    }
} # end of user quota 

#################

warn "INFO: We imported", TreeXml::getStats($tree);
$cce->bye("bye");
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

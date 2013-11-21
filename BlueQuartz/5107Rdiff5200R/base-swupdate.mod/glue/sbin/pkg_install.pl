#!/usr/bin/perl
# $Id: pkg_install.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
##########################################################################
#                                                                        #
# Filename: pkg_prepare.pl                                               #
#                                                                        #
# Author(s): Moshen Chan <mchan@cobalt.com>, Kevin K.M. Chiu             #
#            severely mangled by asun@cobalt.com                         #
#                                                                        #
# Description: Install software package. the information is specified by #
#              the location field in the Package object.                 #
#              for manual install, use the pkg_prepare script before     #
#              calling pkg_install.                                      # 
#                                                                        #
# Usage: $0 <package OID>                                                #
#                                                                        #
##########################################################################

use lib '/usr/sausalito/perl';
use POSIX;
use SWUpdate;
use CCE;
use Sauce::Service;

my $rcfile = "/usr/lib/rpm/rpmrc";
my $tmpdir = swupdate_tmpdir;
my $install_log = $tmpdir . '/package.log';
my $install_path = swupdate_tmpfile('package');

##############################################################################
#
# main()
#
##############################################################################
my ($sysOID, $ret);

unless ($#ARGV gt -1) {
    print <<EOF;
Usage: $0 <package_oid> [-R]

Option  	Description
-R      	Do not reboot automatically

EOF

    exit 1;
}

# Open the log
`mkdir -p $tmpdir` unless -d $tmpdir;
unlink($install_log) if -l $install_log;
open(LOG, ">> $install_log") || die "Cannot open log file. Run as Root?";

# Open a CCE connection
my $cce = new CCE;
$cce->connectuds();
($sysOID) = $cce->find('System');

# see if we're locked. if so, report an error
my ($ok, $sysobj) = $cce->get($sysOID, 'SWUpdate');
my $sigp = $sysobj->{requireSignature};

if ($sysobj->{installLock} and ($sysobj->{installLock} =~ /^\d+$/)) {
    # make sure that we're really locked.
    `ps --no-headers -p $sysobj->{installLock} > /dev/null 2>&1`;
    unless ($?) {
	setProgress($sysOID, "[[base-swupdate.installLock]]", 100);
	exitScript($cce, 'nolock', "installation already occurring");
    }
}

# Clear the install state and lock it up.
$cce->set($sysOID, 'SWUpdate', { 'message' => '[[base-swupdate.initializing]]',
				 'progress' => '0',
				 'installLock' => $$
				 });

# see if the package oid really exists
my $packageOID = $ARGV[0];
my $obj;
($ret, $obj) = $cce->get($packageOID);
unless ($ret and $obj and ($obj->{CLASS} eq 'Package')) {
    setProgress($sysOID, '[[base-swupdate.pkgOIDmissing]]', 100);
    exitScript($cce, 1, "can't find package oid.");
}
  
# check if package installState is Available & if there is a location
# note: we delete the package file on completion.
my $pkgname = "$obj->{vendor}-$obj->{name}-$obj->{version}";
if ($obj->{installState} eq 'Installed') {
    setProgress($sysOID, "[[base-swupdate.packageAlreadyInstalled]]", 100);
    exitScript($cce, 1, "$pkgname: specified package is already installed.");
} elsif (not $obj->{location} ) {
    setProgress($sysOID, "[[base-swupdate.noLocation]]", 100);
    exitScript($cce, 'destroy', "$pkgname: no location specified.");
}

my $url = $obj->{location};
print LOG "unpacking location: $url\n";
# download the package unless it's already loaded
if ($url !~ /^file:/) {
    ($ret) = swupdate_download($install_path, $url);
    if ($ret < 0) {
	setProgress($sysOID, "[[base-swupdate.cannotDownloadUrl]]", 100);
	exitScript($cce, 1, "$pkgname: can't download!");
    }

    ($ok, $obj) = swupdate_unpack($install_path, $cce, $sysOID, $sigp,
				  $packageOID);
    exitScript($cce, 'destroy', "$pkgname: can't unpack!") if ($ok lt 0);

    $url =~ /^file:\s*(.+)/;
    $install_path = $1;
}

# check package dependencies
if ($ok = swupdate_checkdepend($obj, $cce)) {
    if ($ok eq -2) {
	setProgress($sysOID, "[[base-swupdate.obsoletePkg,name=$pkgname]]", 100);
	exitScript($cce, 'destroy', "$pkgname: obsolete package");
    } elsif ($ok eq -3) {
	setProgress($sysOID, "[[base-swupdate.badProduct,name=$pkgname]]", 100);
	exitScript($cce, 'destroy', "$pkgname: bad product");
    } else {
    	my @list = $cce->scalar_to_array($obj->{dependencyList});
    	push @list, $cce->scalar_to_array($obj->{visibleList});
    	my $string = join(',', @list);
    	$string =~ s/:/ /g;
    	setProgress($sysOID, "[[base-swupdate.failedDep,dep=\"$string\"]]", 100);
    	exitScript($cce, 1, "$pkgname: failed dependencies: $string");
    }
}

# see if we have a subpackage
my $path = $obj->{location};
$path =~ s/^file://;
my $filename = $obj->{fileName};
if ($filename and (-e "$path/$filename")) {
    $err = swupdate_verifyuntar("$path/$filename", $path);
    if ($err) {
	setProgress($sysOID, "[[base-swupdate.badFormat]]", 100);
	exitScript($cce, 'destroy', "$pkgname: bad format");
    }
}

print LOG "Installation of $pkgname --------------------------------------\n";
setProgress($sysOID, "[[base-swupdate.installing,package=$pkgname]]", 0);
    
my @rpmFiles = $cce->scalar_to_array($obj->{RPMList});
my @srpmFiles = $cce->scalar_to_array($obj->{SRPMList});
my %installedHash = $cce->scalar_to_array($sysobj->{rpmsInstalled});

###########################################################################
#
#  Verify and install the rpm components of the package
#
###########################################################################
my $rpm;
# Verify contents of package
foreach $rpm (@rpmFiles) {
    unless (-e "$path/RPMS/$rpm") {
	setProgress($sysOID, "[[base-swupdate.missingRPM,package=$pkgname]]", 100);
	exitScript($cce, 1, "$pkgname: Bad Package file - missing RPM(s)");
    }
}

foreach $rpm (@srpmFiles) {
    unless (-e "$path/SRPMS/$rpm") {
	setProgress($sysOID, "[[base-swupdate.missingRPM,package=$pkgname]]", 100);
	exitScript($cce, 1, "$pkgname: Bad Package file - missing SRPM(s)");
    }
}
    
setProgress($sysOID, "[[base-swupdate.executingPreInstallScript,package=$pkgname]]", 85);

# Execute pre-install scripts if they exist
$err = swupdate_runscripts($path, 'scripts/pre-install');
if ($err) {
    setProgress($sysOID, "[[base-swupdate.preInstallFailed,package=$pkgname]]", 100);
    exitScript($cce, 1, "$pkgname: pre-install scripts failed.");
}
    
# Figure out RPMS to install and install them.  Don't install already
# installed RPMS and newer version RPMS
my $filesCount = 0;
my $installed = 0;
my $totalfiles = scalar(@rpmFiles) + scalar(@srpmFiles);
my @installedrpms;
foreach $rpm (@rpmFiles) {
    setProgress($sysOID, "[[base-swupdate.installingRpm,package=$pkgname,rpm=$rpm]]", (10)*$filesCount/($totalfiles+1) + 85);
    $error = install_rpm('RPMS', $path, $rpm);
    unless ($error) {
	print LOG "$rpm successfully installed.\n";
	$installed++;    
    } elsif ($error eq 'alreadyinstalled') {
	print LOG "$rpm: already installed on system. Skipping.\n";
    } elsif ($error eq 'newer') {
	print LOG "$rpm: newer rpm already installed on system. Skipping.\n";
    } else {
	print LOG "ERROR: Problem installing RPM: $rpm.  Aborting.\n";
	setProgress("[[base-swupdate.rpmInstallError,package=$packageFilename,rpm=$rpm]]", 100);
	# We should not do this in the case of an upgrade
	# TODO: but we should do it in the case of a fresh install.
	# for now, let's not do it.
	#uninstall_rpms(\%installedHash, \@installedrpms);
	exitScript($cce, 1, "$pkgname: Error installing RPM: $rpm.");
    }
    $rpm =~ /^(\S+)-\S+?\.\S+?\.rpm$/;
    push @installedrpms, $1;
    $filesCount++;
}

# screwing up here isn't as fatal
foreach $rpm (@srpmFiles) {
    setProgress($sysOID, "[[base-swupdate.installingRpm,package=$pkgname,rpm=$rpm]]", (10)*$filesCount/($totalfiles+1) + 85);
    $error = install_rpm('SRPMS', $path, $rpm);
    unless ($error) {
	print LOG "$rpm successfully installed.\n";
	$installed++;    
    } elsif ($error eq 'alreadyinstalled') {
	print LOG "$rpm: already installed on system. Skipping.\n";
    } elsif ($error eq 'newer') {
	print LOG "$rpm: newer rpm already stalled on system. Skipping.\n";
    } else {
	print LOG "ERROR: Problem installing RPM: $rpm.  Skipping.\n";
    }
    $filesCount++;
}
    
if ($installed == 0) {
    print LOG "Nothing installed from $packageFilename.  Previously Installed? Still running  post-install scripts\n";
}
    
setProgress($sysOID, "[[base-swupdate.executingPostInstallScript]]", 95);

# Execute post-install scripts if they exist
$err = swupdate_runscripts($path, 'scripts/post-install');
if ($err) {
    setProgress($sysOID, "[[base-swupdate.postInstallFailed,package=$pkgname]]", 100);
    # We should not do this in the case of an upgrade
    # TODO: but we should do it in the case of a fresh install.
    # for now, let's not do it.
    #uninstall_rpms(\%installedHash, \@installedrpms);
    exitScript($cce, 1, "$pkgname: post-install scripts failed.");
}

my $preun = swupdate_scriptdir($obj->{vendor}, $obj->{name},
			       $obj->{version}, 'pre-uninstall');
my $postun = swupdate_scriptdir($obj->{vendor}, $obj->{name},
				$obj->{version}, 'post-uninstall');
    
system("mkdir -p $preun");
system("mkdir -p $postun");
system("cp $path/scripts/pre-uninstall/* $preun > /dev/null 2>&1");
system("cp $path/scripts/post-uninstall/* $postun > /dev/null 2>&1");
system("chown -R 0700 $preun");
system("chown -R 0700 $postun");

# everything installed correctly. increment the rpm counts
foreach $rpm (@installedrpms) {
	++$installedHash{$rpm};
}

# there can be only one! get the list of packages. install the new
# one, and destroy the old one. we also destroy any obsoleted packages
# and decrement the rpm count.
my ($oid, $obsoletedesc, $pkgobj);
my @obsoletes = $cce->scalar_to_array($obj->{obsoleteList});
foreach $obsoletedesc (@obsoletes) {
    $obsoletedesc =~ /^(\S+):(\S+):\S*:\S*/;
    my ($vendor, $name) = ($1, $2);
    my @pkgoids = $cce->find('Package', { vendor => $vendor,
					  name => $name });
    foreach $oid (@pkgoids) {
	my %compare;
	($ret, $pkgobj) = $cce->get($oid);
	$compare{$vendor}{$name} = $pkgobj->{version};
	if (swupdate_compare([$obsoletedesc], \%compare) eq 0) {
	    $cce->destroy($oid);
	    next unless $pkgobj->{installState} eq 'Installed';
	    @rpmList = $cce->scalar_to_array($pkgobj->{RPMList});
	    foreach $rpm (@rpmList) {
		$rpm =~ /^(\S+)-\S+?\.\S+?\.rpm$/;	
		# decrement but don't delete.
		--$installedHash{$1} if exists($installedHash{$1});
	    }
	}
    }
}

# do reference counting on installed versions
my @oids = $cce->find('Package', { vendor => $obj->{vendor},
				   name => $obj->{name} } );
# oids of not installed packages
my @notInstalledOids = ();
foreach $oid (@oids) {
	($ret, $pkgobj) = $cce->get($oid);

	# we are only interested in installed packages here
	if( $pkgobj->{installState} ne 'Installed') {
	    push @notInstalledOids, $oid;
	    next;
	}

	@rpmList = $cce->scalar_to_array($pkgobj->{RPMList});
	foreach $rpm (@rpmList) {
		$rpm =~ /^(\S+)-\S+?\.\S+?\.rpm$/;
		--$installedHash{$1} if exists($installedHash{$1});
	}

	# destroy all objects of the package with different versions
	$cce->destroy($oid);
}

# now, destroy objects of not installed packages except the one we're
# installing
foreach $oid (@notInstalledOids) {
    # do not destroy the object of the package to be installed because doing
    # so triggers the destroy handler which removes the package .mo files
    ($ret, $pkgobj) = $cce->get($oid);
    if ($pkgobj->{vendor} eq $obj->{vendor} &&
	$pkgobj->{name} eq $obj->{name} &&
	$pkgobj->{version} eq $obj->{version} ) {
	next;
    }

    $cce->destroy($oid);
}


$cce->set($sysOID, 'SWUpdate', { 'rpmsInstalled' =>
				 $cce->array_to_scalar(%installedHash) });
$cce->set($packageOID, '', { location => '', installState => 'Installed'});

# see if we need to re-create the package object
($packageOID) = $cce->find('Package', { vendor => $obj->{vendor},
	                    name => $obj->{name}, version => $obj->{version}});
unless ($packageOID) {
	$obj->{location} = '';
	$obj->{installState} = 'Installed';
	$cce->create('Package', $obj);
}

setProgress($sysOID, "[[base-swupdate.packageInstallSuccess,package=$pkgname]]", 100);
    
system("rm -rf $path");

# reboot called. this overrides everything else.
my $refreshcce;
if ($ARGV[1] =~ /r/i) {
	# don't do anything if the -R flag is set
} elsif ($obj->{options} =~ /\breboot\b/) {
    my $systime = time();
    $cce->set($sysOID, 'SWUpdate', { 'uiCMD' => 'reboot' });
    sleep 4;
    $cce->set($sysOID, 'Power', { reboot => $systime });
    exitScript($cce, 'reboot', "Installation successful.");
} else {
    $refreshcce = 'refresh' if ($obj->{options} =~ /\brefreshcce\b/);
    if ($obj->{options} =~ /\brefreshui\b/) { # refresh requested
        Sauce::Service::service_run_init('admserv', 'reload');
	$cce->set($sysOID, 'SWUpdate', { 'uiCMD' => 'install,refreshui' });
    }
}

exitScript($cce, $refreshcce, "Installation successful.");


################################################################
#   SUBROUTINES
################################################################


sub exitScript {
  my ($cce, $value, $exitMessage) = @_;
  
  print LOG "$exitMessage\n";
  close(LOG);

  system("rm -rf $install_path") if $install_path;
  $cce->set($sysOID, 'SWUpdate', { 'installLock' => 0, progress => '100' }) 
      unless ($value =~ /\bnolock\b/);
  $cce->destroy($packageOID) if ($value =~ /\bdestroy\b/);
  $cce->bye("SUCCESS") if $cce;
  if ($value =~ /\brefresh\b/) {
  	sleep 4; # this needs to happen before a browser refresh
  	Sauce::Service::service_run_init('cced.init', 'restart'); 
	$value = 0;
  } elsif ($value =~ /\breboot\b/) {
	sleep 30;
	system("/sbin/shutdown -r now");
	$value = 0;
  }
  print "$exitMessage\n";
  exit $value ? -1 : 0;
}

sub install_rpm
{
    my ($type, $path, $rpm) = @_;

    if (open(RPMOUT, "rpm -U $path/$type/$rpm --test 2>&1 |")) {
    	while (<RPMOUT>) {
		return 'alreadyinstalled' if /already/;
		return 'newer' if /newer/;
		return 'dependency' if /dependencies/;
   	 }
    	close(RPMOUT);
    }

    # If RPM is not an older version, or already installed
    my $rc = system("rpm -U $path/$type/$rpm --force >> $install_log 2>&1");
    return 'rpmerror' if $rc;
    return undef;
}

sub uninstall_rpms
{
	my ($installed, $rpms) = @_;
	my $rpm;

	foreach $rpm (reverse @$rpms) {
		if (not exists($installed->{$rpm}) or $installed->{$rpm} lt 1) {
			system("rpm --erase $rpm > /dev/null 2>&1");
		}
	}
}

sub setProgress {
  my ($oid, $message, $progress) = @_;
  my %settings;

  $settings{message} = $message if defined($message);
  $settings{progress} = $progress if defined($progress);
  my ($ok) = $cce->set($oid, 'SWUpdate', \%settings);
  return $ok;
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

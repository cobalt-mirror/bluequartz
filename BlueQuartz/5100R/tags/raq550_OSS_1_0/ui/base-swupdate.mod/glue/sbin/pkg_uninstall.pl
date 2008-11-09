#!/usr/bin/perl 
# $Id: pkg_uninstall.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
##########################################################################
#                                                                        #
# Filename: uninstallPackage                                             #
#                                                                        #
# Author(s): Moshen Chan <mchan@cobalt.com>                              #
#            modified to work with new swupdate mechanism
#                                                                        #
# Removes list of RPM's installed from package from the system           #
# Trusts the package maintainers for RPM dependencies (does not do a     #
# forced erase)                              
#                                                                        #
# Runs uninstall script from uninstall directory                         #
#                                                                        #
# Logs everything in $install_log                                        #
# -mchan                                                                 #
#                                                                        #
##########################################################################


use lib '/usr/sausalito/perl';
use CCE;
use SWUpdate;
use Sauce::Service;

my $tmpdir = swupdate_tmpdir;
my $install_log = $tmpdir . '/package.log';

my ($sysOID, $sysobj);
my $cce = new CCE;

########################################
# main()
########################################
# Make sure the package object we're passed exists
my $packageOID = $ARGV[0];

if (@ARGV != 1) {
  print "Please specify package oid to uninstall.\n";
  exit 1;
}

`mkdir -p $tmpdir` unless -d $tmpdir;
`chmod -R 0700 $tmpdir`;
unlink($install_log) if -l $install_log;
open(LOG, ">> $install_log") || die "Can't open uninstall log. Run as Root?";

# open CCE connection
$cce->connectuds();

($sysOID) = $cce->find('System');
# Clear the install state
$cce->set($sysOID, "SWUpdate", { "message" => "[[base-swupdate.initializing]]",
				 "progress" => "0"});

# check if package exists with specified filename
my ($success, $obj) = $cce->get($packageOID);
unless ($success and $obj and $obj->{CLASS} eq 'Package' and 
	$obj->{installState} eq 'Installed') {
    setProgress($cce, $sysOID, '[[base-swupdate.pkgOIDmissing]]', 100);
    exitScript($cce, "can't find package oid.", 1);
}

# make sure CCE says we can uninstall this package
unless ($obj->{options} =~ /\buninstallable\b/) { 
    setProgress($cce, $sysOID, '[[base-swupdate.cantUninstall]]', 100);
    exitScript($cce, '[[base-swupdate.cantUninstall]]', 1);
}

# see if we're locked
($success, $sysobj) = $cce->get($sysOID, 'SWUpdate');
if ($sysobj->{installLock} and ($sysobj->{installLock} =~ /^\d+$/)) {
    # make sure that we're really locked.
    `ps --no-headers -p $obj->{installLock} > /dev/null 2>&1`;
    unless ($?) {
	setProgress($cce, $sysOID, "[[base-swupdate.installLock]]", 100);
	exitScript($cce, "install/uninstall already occurring", 'nolock');
    }
}

# Clear the install state and lock it up.
$cce->set($sysOID, 'SWUpdate', { 'message' => '[[base-swupdate.initializing]]',
                                 'progress' => '0',
                                 'installLock' => $$
                                 });

# make sure there are no other packages that depend on this one
my @installedID = $cce->find("Package", {"installState" => "Installed"});
my $name = "$obj->{vendor}:$obj->{name}:";
foreach my $ID (@installedID) {
    my ($ok, $packageObj) = $cce->get($ID, "");

    # check for circular references
    next if (($packageObj->{vendor} eq $obj->{vendor}) and
	     ($packageObj->{name} eq $obj->{name}));
    my @depends = $cce->scalar_to_array($packageObj->{dependencyList});
    push @depends, $cce->scalar_to_array($packageObj->{visibleList});

    my ($dep, @deplist);

    foreach $dep (@depends) {
	push @deplist, $packageObj->{vendor} . ' ' . $packageObj->{name}
	    if ($dep =~ /^$name/);
    }
      
    if (@deplist) {
	my $string = join(',', @deplist);
	$string =~ s/:/ /g;
	setProgress($cce, $sysOID, 
		    "[[base-swupdate.dependent,depend=$string]]", 100);
	exitScript($cce, "[[base-swupdate.dependent,depend=$string]]", 1);
    }
}
  

my @rpmlist = $cce->scalar_to_array($obj->{RPMList});
push @rpmlist, $cce->scalar_to_array($obj->{SRPMList});

my @identity = ($obj->{vendor}, $obj->{name}, $obj->{version});
my $scriptdir = swupdate_scriptdir(@identity, 'pre-uninstall');
swupdate_runscripts($scriptdir);

# Remove each rpm from package.
# (dependant RPM's are not erased)
setProgress($cce, $sysOID, '[[base-swupdate.removingRPM]]', 50);
print LOG "Removal of $obj->{vendor} $obj->{name} --------------------------------------\n";

# get the hash of rpms that are installed
my %installedHash = $cce->scalar_to_array($sysobj->{rpmsInstalled});
my $rpm;
foreach $rpm (reverse @rpmlist) {
  $rpm =~ /^(\S+)-\S+?\.\S+?\.rpm$/;
  if (not exists($installedHash{$1}) or (--$installedHash{$1} lt 1)) {
	delete $installedHash{$1};
  	$return = `rpm --erase $1 2>&1`;
  }
  
  print LOG "$rpm successfully removed.\n" unless $return;
  print LOG "$rpm would break dependency. Not removed.\n" if ($return =~ /break\s+dependencies/);
  print LOG "$rpm not installed.  Not removed.\n" if ($return =~ /not\s+installed/);
}

$cce->set($sysOID, 'SWUpdate', { 'rpmsInstalled' => 
				 $cce->array_to_scalar(%installedHash) });

$scriptdir = swupdate_scriptdir(@identity, 'post-uninstall');
swupdate_runscripts($scriptdir);

# see if we should refresh the ui
if ($obj->{options} =~ /\brefreshui\b/) { # refresh ui
    Sauce::Service::service_run_init('admserv', 'reload');
    $cce->set($sysOID, 'SWUpdate', { 'uiCMD' => 'uninstall,refreshui' });
} else {
    setProgress($cce, $sysOID, "[[base-swupdate.uninstalled]]", 100);
} 

# now, destroy the object
$cce->destroy($packageOID);

# restart cce if necessary 
my $restart = ($obj->{options} =~ /\brefreshcce\b/) ? 'restart' : 0;
print LOG "Uninstall of $obj->{vendor}->$obj->{name}-$obj->{version} complete\n";
exitScript($cce, "[[base-swupdate.uninstalled]]", $restart);


sub setState {
  my ($packageOid, $installState) = @_;
  my ($ok) = $cce->set($packageOid, "", {"installState" => $installState}); 
  return $ok;
}

sub setProgress {
  my ($cce, $oid, $message, $progress) = @_;
  my %settings;
  $settings{message} = $message if defined($message);
  $settings{progress} = $progress if defined($progress);
  
  my ($ok) = $cce->set($oid, "SWUpdate", \%settings);
  return $ok;
}

# exitScript()
#
# exits with $message and 
# associated OID (if applicable)
sub exitScript {
  my ($cce, $message, $value) = @_;
  print "$message\n";
  $cce->set($sysOID, 'SWUpdate', {'installLock' => 0}) 
	unless $value =~ /\bnolock\b/;
  $cce->bye('SUCCESS');
  if ($value =~ /\brestart\b/) {
	sleep 4;
  	Sauce::Service::service_run_init('cced.init', 'restart'); 
	$value = 0;
  }
  close(LOG);
  exit $value ? -1 : 0;
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

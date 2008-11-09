#!/usr/bin/perl -I/usr/sausalito/perl
##########################################################################
#                                                                        #
#              Copyright (C) 2001, Sun Microsystems, Inc                 #
#                          All rights reserved                           #
#                                                                        #
# Filename: autoupdate.pl                                                #
#                                                                        #
# Author: Philip Martin <philip.martin@sun.com>                          #
#                                                                        #
# Description: Automaticly update the Qube3 on first boot                #
#		(code mostly taken from grab_updates.pl)                 #
#                                                                        #
#                                                                        #
# 1. Gather system info (product, serialnumber)                          #
# 2. Get Software Update settings from CCE                               #
# 3. Query update server and parse data                                  #
# 4. Install                                                             #
#                                                                        #
##########################################################################

########################################
#TODO
############
#
# Better error reporting code.  mail errors to admin.
# Add error messages to the PO file.
# Finish code cleanup.
#
#

use CCE;
use SWUpdate;
use Sauce::Service;
use Data::Dumper;
use I18n;
use SendEmail;

my $DEBUG=0;

if($DEBUG) {
open D, "+>/tmp/autoupdate.$$";
print D scalar localtime;
print D "\n";
}

my $cce;
$cce = new CCE;
$cce->connectuds;

my $i18n=new I18n;
$i18n->setLocale(I18n::i18n_getSystemLocale());

# where is everything?
my $tar_cmd = '/bin/tar';
my $tmpdir = swupdate_tmpdir;
my $packagedir = $tmpdir . '/info.' . $$;
my $package_tmp = "$tmpdir/package.$$.tmp";
my $package_list = "$packagedir/package_list";

################################################################
#   OBTAIN SYSTEM INFORMATION
################################################################

# initialize system information. swupdate_init will spit out
# error messages if appropriate
my %settings;
$settings{autoupdate} = 1;
my ($ret, $status, $info) = swupdate_getsettings(\%settings);
if ($ret) {
    throw_error('nosettings');
    exit 1;
}

# Run a wget query to the update server (we can use the HTTP module
# but this is easier to debug). also, the HTTP module has nasty 
# memory usage characteristics
my ($get, $query, @locations, @location_oids, @stat);

@locations = @{$settings{location}};
@location_oids = @{$settings{location_oid}};
@stat = @{$settings{location_status}};
$swversion = swupdate_version;

# double escape & to prevent wget from unescaping on me. ugh.
my $installed = $settings{installed};
$installed =~ s/\&/sprintf("%%%02X",ord('&'))/ge;
$installed =~ s/([^A-z0-9+\_\-])/sprintf("%%%02X",ord($1))/ge;

$get ='?ui=no';
$get .= "&version=$swversion";
$get .= "&product=$settings{product}";
$get .= "&build=$settings{build}";
$get .= "&serialnum=$settings{serialNumber}";
$get .= "&installed=$installed";
$get .= "&updates=all";
$get .= "&notificationMode=$settings{notifymode}";

cleanup($packagedir);
`mkdir -p $packagedir`;
`chmod -R 700 $packagedir`;

my @packages = ();
my $i;
for($i = 0; $i <= $#locations; $i++) {
    my $location = $locations[$i];
    my $location_oid = $location_oids[$i];
    my $local_get=$get;

    my (undef,$obj) = $cce->get($location_oid);

    if($obj->{httppass}){
	$local_get.="&token=$obj->{httppass}";
    }

    my($error, $state, $message, $status) = swupdate_download($package_tmp, $location, $local_get);


    if ($error) {
	throw_error('cantdownload,badHost="' . $location . '"');
	unlink($package_tmp);
	cleanup($packagedir);
	if ($state eq 'up') {
	    destroy_pkgs('autoupdate');
	    exit 0;
	}
	exit 1;
    }

    # check the package type
    ($error, $state) = swupdate_verifyuntar($package_tmp, $packagedir);
    unlink($package_tmp);
    if ($error lt 0) {
	throw_error('badpackage');
	cleanup($packagedir);
	exit 1;
    }

    # the server is apparently okay. 
    my @packageList = ();
    $count = swupdate_parselist($package_list, \@packageList);
    push @packages, @packageList;

    # darn. it really wasn't okay.
    if ($count < 0) {
	throw_error('badpkgfmt');
	cleanup($packagedir);
	exit 1;
    }
}

@packages = map {$_->{updateServer}="autoupdate";$_} @packages;

print D "found @{[scalar @packages]} packages\n" if $DEBUG;

################################################################
#   UPDATE CCE WITH THE NEW INFO
################################################################

# destroy existing packages
my @destroyed = destroy_pkgs('autoupdate', $cce);

# add the packages to cce. if it's okay, save the relevant information
foreach $package (@packages) {
    my $id = $package->{vendor} . ':' . $package->{name} . ':' . $package->{version};
    my $isOld = grep /^$id$/, @destroyed;
    $package->{new} = '' if $isOld;
    if(swupdate_checkdepend($package,$cce) != -3){
	    my ($err, $info) = swupdate_add_pkginfo($cce, $package);
	    if ($err > -1) {
		$version = swupdate_fromccevers($package->{version});
		my $path = "$packagedir/$package->{vendor}-$package->{name}-$version";
		swupdate_install_pkginfo($path, $package->{vendor}, $package->{name}, 
					 $package->{version});
		$package->{OID}=$err;
	    }else{
		#harmless error, only report if we're debugging
		throw_error('cantaddpackage') if $DEBUG;
		$package->{OID} = -1;
	    }
    }
}

print D Dumper @packages if $DEBUG;

#install packages
$i=0;
my $install_count=0;
my @installed;
while(@packages){
	my $pkg=$packages[$i];
	my $ret=swupdate_checkdepend($pkg, $cce);
	print D "index: $i, $install_count pkgs installed this pass, $pkg->{name} = $ret\n" if $DEBUG;
	if($ret==0 && $pkg->{OID} > -1){  #installable
		if(install($pkg) ==0 ){;
			splice(@packages, $i, 1);
			$i--; $install_count++;
			push(@installed,$pkg->{nameTag});
		}else{
			throw_error('badinstall,name="'.$pkg->{vendor}.':'.$pkg->{name}.'"');
			splice(@packages, $i, 1);
	                $i--;
		}
	}elsif($ret==-2 || $pkg->{OID} == -1){  #obsolete or already
						#installed
                splice(@packages, $i, 1);
                $i--;
	}elsif($ret==-3){			#not for this product
		splice(@packages, $i, 1);
                $i--;
	} #else has unresolved deps.

        #exit the loop if we've just finished the last package
        last unless scalar @packages;	

	if($i == -1){ #we just spliced out the first element
		$i=0; #because there was a bad install or it is
	}else{        #obsolete.
		$i=($i+1)%(scalar @packages);
		#exit the loop if we've made one pass without installing anything
		last if($i==0 && $install_count==0);
		#reset the installed counter if we are starting a new pass
		$install_count=0 if $i==0;
	}
}

destroy_pkgs('autoupdate') unless $DEBUG; #get rid of any uninstallable packages

#delete autoupdate server
my ($oid)=$cce->find("SWUpdateServer", {name=>'autoupdate'});
$cce->destroy($oid);


my $installed.= join "\n",@installed;
my $message = "[[base-autoupdate.statusEmailBody]]\n $installed";
my $subject = "[[base-autoupdate.statusEmailSubject]]";

SendEmail::sendEmail("admin", "admin <admin>", $subject, $message);


unless($ARGV[0] eq "-R"){
	my (@sys)=$cce->find("System");
	$cce->set($sys[0], "Power", {reboot => time()});
}

$cce->bye("SUCCESS");

exit 0;

sub cleanup
{
    my ($dir, $status, $oid) = @_;

    # a little sanity checking is good
    return unless $dir =~ /^$tmpdir\/\S+$/;
    `rm -rf $dir`;

    # fix up status
    if ($status and $oid) {
	my $cce = new CCE;
	$cce->connectuds;
	$cce->set($oid, '', { 'status' => $status });
	$cce->bye('SUCCESS');
    }
}


# Destroy all previous available packages (Done to refresh corrections to
# package availability status, location, etc..in case they change)
sub destroy_pkgs
{
    my ($server, $cceobj) = @_;
    my $cce;

    unless ($cceobj) {
	$cce = new CCE;
	$cce->connectuds;
    } else {
	$cce = $cceobj;
    }
    my @returnArray = $cce->find('Package',
				 {'updateServer' => $server,
				  'installState' => 'Available'});

    my @destroyed = ();
    foreach my $objectID (@returnArray) {
	# save destroyed package list
	my ($success, $obj) = $cce->get($objectID);
	push @destroyed, $obj->{vendor} . ':' . $obj->{name} . ':' . $obj->{version} 	if $obj->{isVisible};
	$cce->destroy($objectID);
    }
    $cce->bye('SUCCESS') unless $cceobj;
    return @destroyed;
}

sub throw_error{
	my $err=shift;
	my $message = "[[base-autoupdate.errorEmailBody]]\n\n [[base-autoupdate.".$err."]]";
	my $subject = "[[base-autoupdate.errorEmailSubject]]";
	
	SendEmail::sendEmail("admin", "admin <admin>", $subject, $message);

	print D $err if $DEBUG;
}


sub install{
	my($obj,$uname,$passwd) = @_;
	my $install_path = swupdate_tmpfile('package');
	my $url = $obj->{location};

	my @server = $cce->find("SWUpdateServer", {"name"=>$obj->{updateServer}});
	(undef, $server)= $cce->get($server[0]);

	#download
	my $error  = swupdate_download(
		$install_path, 
		$url, 
		"", 
		"", 
		$server->{username}, 
		$server->{httppass}
	);

	if($error < 0){
		print D "can't download ($error)\n" if $DEBUG;
		return -1;
	}


	#unpack
	my($ok, $obj2) = swupdate_unpack(
		$install_path, 
		undef, 
		"",
		0,
		$obj->{OID}
	);

	if($ok < 0){
                print D "can't unpack ($ok, $obj2)\n" if $DEBUG;
                return -1;
        }
	
	my $m=`/usr/sausalito/sbin/pkg_install.pl $obj->{OID} -R`;
	print D "install (oid $obj->{OID}):\n" if $DEBUG;
	print D $m if $DEBUG;
	return ($? >> 8);
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

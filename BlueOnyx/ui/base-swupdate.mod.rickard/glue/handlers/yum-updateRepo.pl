#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# Author: Rickard Osser
# Copyright 2011, Bluapp AB.  All rights reserved. 

use CCE;
use Sauce::Util;
use Sauce::Service;
use Data::Dumper;
use diagnostics;

my $DEBUG = 0;
$DEBUG && open(STDERR, ">/tmp/yum-updateRepo.debug");
$DEBUG && warn `date`;


my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();
my $new = $cce->event_new();
my $old = $cce->event_old();

#my @oids = $cce->find('System');
#if (!defined($oids[0])) {
#	print STDERR "Sorry, no System object in CCE found!\n";
#	exit 0;
#}

$repoDir = "/etc/yum.repos.d";

$DEBUG && warn "new: ".Dumper($new);
$DEBUG && warn "obj: ".Dumper($obj);
$DEBUG && warn "old: ".Dumper($old);

$DEBUG && warn "NEWFILE: $repoDir/$new->{repoName}.repo\n";

if( -f "$repoDir/$new->{repoName}.repo") {
    $DEBUG && warn "REPOFILE exists!";
    # Repo already exists
    $cce->warn("[[base-yum.repoFilePreExist]]");
    $cce->bye('FAIL');
    exit(1);
}


if ($new->{repoName}) {
    $DEBUG && warn "ALPHA\n";
    # Repo changed name
    if ( "$old->{repoName}" ne "$new-{repoName}") {	
	# Checked for old file
	$DEBUG && warn "$repoDir/$old->{repoName}.repo\n";
	if ( -f "$repoDir/$old->{repoName}.repo") {
	    $DEBUG && warn "BETA\n";
	    Sauce::Util::editfile("$repoDir/$old->{repoName}.repo", *editRepoName, "$old->{repoName}", "$new->{repoName}");
	    $repoFile = "$new->{repoName}.repo";
	    Sauce::Util::renamefile("$repoDir/$old->{repoName}.repo", "$repoDir/$repoFile");
	} else {
	    # Repo file doesn't exists, create it!}
	    if ( ! -f "$repoDir/$new->{repoName}.repo") {
		$repoFile = "$new->{repoName}.repo";
		$DEBUG && warn "$repoDir/$repoFile\n";
		$fileData = "#\n# This file is automatically created by Sausalito,\n# any manual changes are subject to be removed,\n# use the GUI to change the file.\n#\n[$new->{repoName}]\n";
		$DEBUG && warn "GAMMA\n";
		
		if( !open(REPO, ">$repoDir/$repoFile")) {
		    $cce->warn("[[base-yum.repoFileCreationFailed]]");
		    $cce->bye('FAIL');
		    exit(1);
		}
	    
		print REPO $fileData;
		close(REPO);
	    }
	}
    }
}

if (! $repoFile) {
    $repoFile = $obj->{repoName} . ".repo";
}

$DEBUG && warn "DELTA\n";
$DEBUG && warn "$repoDir/$repoFile\n";


$settings = {
    'name' =>$obj->{name},
    'baseurl' => $obj->{baseurl},
    'mirrorlist' => $obj->{mirrorlist},
    'gpgcheck' => $obj->{gpgcheck},
    'enabled' => $obj->{enabled},
    'gpgkey' => $obj->{gpgkey}
};
    
$ret = Sauce::Util::editfile(
			     "$repoDir/$repoFile",
			     *Sauce::Util::hash_edit_function,
			     '#',
			     { 're' => '=', 'val' => ' = ' },
			     $settings,
			     "true"
			     );

unless ($ret) {
    $cce->bye('FAIL', '[[base-yum.cantRepoConf]]');
    exit 1;
}


$cce->bye('SUCCESS');
exit(0);


sub editRepoName
# Edit repo name in repo file
{
    my($fin , $fout, $oldRepoName, $newRepoName) = ( shift, shift, shift, shift);
    while ($_ = <$fin>) {
	if(m/^\[$oldRepoName\]/) {
	    $_ = "[$newRepoName]\n";
	}
	print $fout $_;
    }
    return 1;
}

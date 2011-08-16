#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# Copyright 2011 Bluapp AB., All rights reserved.
#
# Author: Rickard Osser <rickard.osser@bluapp.com>

use strict;
use CCE;
use File::Basename;
use Sauce::Util;
use Data::Dumper;
use diagnostics;

my $cce = new CCE;
$cce->connectuds();


my $repoDir = "/etc/yum.repos.d";
my ($file, @files, $dir, $repoName, $key, $value, $owned, $ok, $found, $repoData);
my %repos;
my $systemRepo = 0;

#### Add all present repos to codb
opendir(DIR, $repoDir) || die "can't opendir $dir: $!";
@files = readdir(DIR);
closedir DIR;
foreach $file (@files) {
    next if ($file eq "." or $file eq "..");
    next if ( $file !~ /\.repo$/i);
    open(FILE, "<", "$repoDir/$file");
    while(<FILE>) {
	chomp($_);
	if ( /^\[(.*)\]/)  {
	    $_ =~ s/\[//g;
	    $_ =~ s/\]//g;
	    $_ =~ s/ /_/g;
	    $repoName = $_;
	} elsif ($repoName) {
	    ($key, $value) = /^(.*?)=(.*)/ ;
	    if ($key) {
		chomp($key);
		chomp($value);
		if ("$key" eq "ggpgkey") {
		    $key = "gpgkey";
		}
		$repos{$repoName}{"$key"} = $value;
	    }
	}
    }
    close FILE;

    foreach $repoName (keys %repos) {
	($found) = $cce->find("yumRepo", { 'repoName' => $repoName });
	if( ! $found) {
	    if( ! -d "$repoDir/oldrepofiles") {
		Sauce::Util::makedirectory("$repoDir/oldrepofiles",0755);
	      }
	    if( -f  "$repoDir/$file") {
		Sauce::Util::renamefile("$repoDir/$file", "$repoDir/oldrepofiles/$file");
	      }
	    if ($file =~ /^Blu(.*)\.repo/) {
		$systemRepo = 1;
	    }

	    if (! $repos{$repoName}{mirrorlist}) {
		$repos{$repoName}{mirrorlist} = "";
	    } else {
		$repos{$repoName}{baseurl} = "";
	    }
	    if (! $repos{$repoName}{includepkgs}) {
		$repos{$repoName}{includepkgs} = "";
	    }
	    if (! $repos{$repoName}{exclude}) {
		$repos{$repoName}{exclude} = "";
	    }
	    my $createData = {
		'repoName' => "$repoName",
		'enabled' => "$repos{$repoName}{enabled}",
		'name' => "$repos{$repoName}{name}",
		'baseurl' => "$repos{$repoName}{baseurl}",
		'mirrorlist' => "$repos{$repoName}{mirrorlist}",
		'gpgkey' => "$repos{$repoName}{gpgkey}",
		'gpgcheck' => "$repos{$repoName}{gpgcheck}",
		'includepkgs' => "$repos{$repoName}{includepkgs}",
		'exclude' => "$repos{$repoName}{exclude}",
		'systemRepo' => "$systemRepo"
		};
	    $cce->create('yumRepo', $createData);
	}
    }
    
    
}

$cce->bye('SUCCESS');
exit 0;

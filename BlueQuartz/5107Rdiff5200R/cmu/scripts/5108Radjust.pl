#!/usr/bin/perl
# $Id: 5108Radjust.pl Sat Jul 11 17:24:57 2009 mstauber $
use strict;

# If you are not toor, go away :)
die "You must run this script as root\n" if ($< != 0);

use lib "/usr/cmu/perl";
require CmuCfg;

my $cfg = CmuCfg->new(type => 'adjust');
$cfg->parseOpts();

require TreeXml;

my ($cmuXml, $outFile);
if($cfg->isSess) {
        $cmuXml = $cfg->cmuXml;
        $outFile = $cfg->sessXml;
        if(-f $outFile) { $cmuXml = $outFile; }
} else { die "ERROR: no session id given, cannot adjust files\n" }

my $tree = TreeXml::readXml($cmuXml, 0);

$tree->{adjustPlatform} = "5108R";

if(defined $tree->{user}) {
my($uTree, $fqdn, @arr);
my @keys = keys %{ $tree->{user} };
foreach my $user (@keys) {
	$uTree = $tree->{user}->{$user};

	if (($tree->{exportPlatform} =~ /(RaQ)/) || ($tree->{exportPlatform} =~ /(Qube)/)) {
		# Assume the users are NOT suspended, because we can't yet get the suspend 
		# status on older platforms carried over:
		$uTree->{enabled} = 1;
		$uTree->{ui_enabled} = 1;
	}
	else {
		# Check suspend user
		if (!defined($uTree->{enabled})) {
			$uTree->{enabled} = 0;
		}
		if (!defined($uTree->{ui_enabled})) {
			$uTree->{ui_enabled} = 0;
		}
	}
}
}

if(defined $tree->{list}) {
my($lTree, $fqdn, @arr);
my @keys = keys %{ $tree->{list} };
foreach my $list (@keys) {
	$lTree = $tree->{list}->{$list};

	# Check reply policy
	if (!defined($lTree->{replyToList})) {
		$lTree->{replyToList} = 0;
	}
}
}


if(defined $tree->{vsite}) {
my $vTree;
my @keys = keys %{ $tree->{vsite} };
foreach my $vsite (@keys) {
	$vTree = $tree->{vsite}->{$vsite};
	if(defined $vTree->{SSL}->{importCert}) {
		delete $vTree->{SSL}->{importCert}
	}
}
}

my $migrate = {};
TreeXml::addNode('migrate', $tree, $migrate);
TreeXml::writeXml($migrate, $outFile);
exit 0;


#!/usr/bin/perl -w -I/usr/sausalito/perl
# Copyright 2011, Bluapp AB, All rights reserved.

use CCE;
use Data::Dumper;
use Sauce::Util;

# Globals.
my $DEBUG = 0;
$DEBUG && open(STDERR, ">/tmp/yum-removeRepo.debug");
$DEBUG && warn `date`;

# These should be globals..

my $cce = new CCE (Domain => 'base-yum' );


$cce->connectfd();
my $old = $cce->event_old();
$DEBUG && warn "old: ".Dumper($old);

my $repoFile = $old->{repoName}.".repo";

my $repoPath = "/etc/yum.repos.d";
$DEBUG && warn "FILE: $repoPath/$repoFile";

$ret = Sauce::Util::unlinkfile("$repoPath/$repoFile");
unless ($ret) {
    $cce->bye('FAIL', '[[base-yum.cantRemoveRepo]]');
    exit 1;
}

$cce->bye('SUCCESS');
exit 0;


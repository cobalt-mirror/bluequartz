#!/usr/bin/perl
#
# $Id: packsort.pl, v 1.0.0-1 Mo 06 Aug 2012 22:49:03 CEST mstauber Exp $
# Copyright 2006-2012 Stauber Multimedia Design. All rights reserved.


my $path = $ARGV[0];
if ( $path eq "" ) {
    print "\nNo file name specified.\n";
    exit 1;
} 
elsif (!-e $path) {
    print "\nFile does not exist.\n";
    exit 1;
}
else {
    open(FILE,"$path");
    @contents = <FILE>;
    close FILE;
    chomp @contents;
    $num = 0;
    %HoA = ('capstone' => [], 'locale' => [], 'glue' => [], 'ui' => [], 'other' => [], 'filler' => []);
    foreach $line (@contents) {
	if ($line =~ m/^RPM: (.*)$/) {
	    $rpmname = $1;
	    if ($rpmname =~ m/-capstone-/g) {
		push(@{$HoA{'capstone'}}, $rpmname);
		$num++;
	    }
	    elsif ($rpmname =~ m/-locale-/g) {
		push(@{$HoA{'locale'}}, $rpmname);
		$num++;
	    }
	    elsif ($rpmname =~ m/-glue-/g) {
		push(@{$HoA{'glue'}}, $rpmname);
		$num++;
	    }
	    elsif ($rpmname =~ m/-ui-/g) {
		push(@{$HoA{'ui'}}, $rpmname);
		$num++;
	    }
	    elsif ($rpmname =~ m/\.rpm/g) {
		push(@{$HoA{'other'}}, $rpmname);
		$num++;
	    }
	}
	    elsif ($line =~ m/^\[\/Package\]$/g) {
		break;
	    }
	    else {
		push(@{$HoA{'filler'}}, $line);
		$num++;
	    }
    }

    $i = 0;
    foreach $line (@{$HoA{'filler'}}) {
	print $HoA{'filler'}->[$i] . "\n"; 
	$i++;
    }
    $i = 0;
    foreach $line (@{$HoA{'other'}}) {
	print "RPM: " . $HoA{'other'}->[$i] . "\n"; 
	$i++;
    }
    $i = 0;
    foreach $line (@{$HoA{'locale'}}) {
	print "RPM: " . $HoA{'locale'}->[$i] . "\n"; 
	$i++;
    }
    $i = 0;
    foreach $line (@{$HoA{'locale'}}) {
	print "RPM: " . $HoA{'locale'}->[$i] . "\n"; 
	$i++;
    }
    $i = 0;
    foreach $line (@{$HoA{'ui'}}) {
	print "RPM: " . $HoA{'ui'}->[$i] . "\n"; 
	$i++;
    }
    $i = 0;
    foreach $line (@{$HoA{'glue'}}) {
	print "RPM: " . $HoA{'glue'}->[$i] . "\n"; 
	$i++;
    }
    $i = 0;
    foreach $line (@{$HoA{'capstone'}}) {
    	print "RPM: " . $HoA{'capstone'}->[$i] . "\n"; 
	$i++;
    }
    print "[/Package]\n";
}
exit 0;


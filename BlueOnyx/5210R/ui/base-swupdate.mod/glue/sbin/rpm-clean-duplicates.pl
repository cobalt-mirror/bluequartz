#!/usr/bin/perl
# id: rpm-clean-duplicates.pl
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
#
# This is a repair/maintenance script. Sometimes we end up with the same
# non-multilib RPM installed more than once with different version numbers.
# This shouldn't be the case as each non-multilib RPM should only be
# installed once.
#
# This script dumps the RPM database into a textfile in an easily parse-
# able fashion. Then it reads it in and determines which non-multilib
# RPMs are present more than once. It then shows info about detected
# problems and doublettes. It also dumps command line commands onto
# the screen that allow to rectify these problems by removing the
# newer RPMs from the database without actually deleting any files.
#
# However, these commands should be reviewed before executing them.
# After all, this is just a dumb script and not an all-knowing
# entity that never makes mistakes.
#
# So use this with a bit of caution. After removing doublettes you
# should run "yum update" again to get back to a current state of
# things.
#

use Sort::Versions;
use Data::Dumper;

system("/bin/rpm -qa --qf '%{name}|%{version}-%{release}|%{arch}\n' | /bin/sort > /tmp/rpmdatabase.txt");

# Ignore-Items:
@rpmstoignore = ("gpg-pubkey", "kernel", "kernel-devel", "kernel-firmware", "kernel-headers", "vzdummy-kernel", "vzdummy-kernel-el6");

# Pull the full vzlist-dump into a Matrix:
open (F, '/tmp/rpmdatabase.txt') || die "Could not open /tmp/rpmdatabase.txt: $!";
$a = 0;
@duplicates = ();
while ($line = <F>) {

    chomp($line);
    next if $line =~ /^\s*$/;               # skip blank lines
    $matrix_name = 'RPMLIST';
    $newline = $a . '|' . $line;
    my (@row) = split (/\|/, $newline);

    # Check if an item already exists in the Matrix:
	$b = 0;
	foreach $RPM (@RPMLIST) {
		if (($RPMLIST[$b][1] eq $row[1]) && ($RPMLIST[$b][3] eq $row[3])) {
			if (in_array(\@rpmstoignore,$RPMLIST[$b][1])) {
				#print "Ignoring " . $RPMLIST[$b][1] . "\n";
			}
			else {
				# This is a duplicate. Mark it as such:
				push (@duplicates, $RPMLIST[$b][1]);
			}
		}
		$b++;
	}

	# Map all items anyway:
    push (@{$matrix_name}, \@row);
    $a++;
}  
close(F);

$n = @duplicates;
if ($n gt "0") {
	print "#\n";
	print "### Detected $n duplicate RPMs:\n";
	print "#\n";
}
else {
	print "#\n";
	print "### No RPM duplicates found!\n";
	print "#\n";
}

#
## Do the version comparing:
#
foreach	$doble (@duplicates) {
	$c = 0;
	while ($a ne $c) {
		if ($RPMLIST[$c][1] eq $doble) {
			push(@$doble, $RPMLIST[$c][2])
		}
		$c++;
	}
	print "RPM $doble is listed with these version numbers: \n";
	foreach $ver (@$doble) {
		print $ver . "\n";
	}
	# Clone the array:
	$n = @$doble;
	$deleteitem = ();
	@tmp = @$doble;

	while ($n gt 1) {
		$first = pop(tmp);
		$second = pop(tmp);
		if (versioncmp($first, $second) == 1) {
			push(@deleteitem, $doble."-".$first);
		}
		$n--;
	}
}

$x = @deleteitem;
if ($x gt "0") {
	print "#\n";
	print "### Run the following commands (at your own risk!) to remove the newer RPMs from the database:\n";
	print "#\n";

	# Handle the deletion:
	foreach $entry (@deleteitem) {
		print "rpm -e --justdb $entry\n";
		# If you trust the script enough to do the dirty work for you,
		# then uncomment the next line to actually let it delete the 
		# double entries:
		#system("rpm -e --justdb $entry");
	}
}

exit;

#
### Subroutines:
#

sub in_array {
    my ($arr,$search_for) = @_;
    return grep {$search_for eq $_} @$arr;
}

#!/usr/bin/perl

use File::Copy;

$conf = "/etc/nsswitch.conf";
$bak = "/etc/nsswitch.conf.save";

copy $conf, $bak or die $!;

open INPUT, "< $bak";
open OUTPUT, "> $conf";

while (<INPUT>) {
  if ((/^passwd:/ || /^shadow:/ || /^group:/) && (/\bdb\b/)) {
    s/^([a-z]+:[ \t]*)db (.*)/\1\2/;
  }
  print OUTPUT;
}

close INPUT;
close OUTPUT;


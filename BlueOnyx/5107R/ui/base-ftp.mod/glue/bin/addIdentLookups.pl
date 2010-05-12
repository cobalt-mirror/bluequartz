#!/usr/bin/perl
use lib qw(/usr/sausalito/perl);
 
use strict;
use Sauce::Util;
use CCE;
my $proftpd_conf = "/etc/proftpd.conf";
my $new_file = $proftpd_conf . "~";

if (!-f $proftpd_conf) {
  exit 0;
}

my $open_global = 0;
my $set_ident = 0;

open(IN, "< $proftpd_conf");
open(OUT, "> $new_file");
while (my $line = <IN>) {
  if ($line =~ /(\<Global\>)/) {
    $open_global = 1;
  } elsif ($line =~ /IdentLookups/) {
    $set_ident = 1;
  } elsif ($line =~ /(\<\/Global\>)/) {
    if (!$set_ident) {
      print OUT "   IdentLookups			off\n";
    }
  }
  print OUT $line;
}
close(OUT);
close(IN);

system("mv $new_file $proftpd_conf");


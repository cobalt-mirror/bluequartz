#!/usr/bin/perl
# $Id: generateGraph.pl,v 1.3 2001/12/14 02:29:10 pbaltz Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# 
#use GIFgraph::bars;
use GD::Graph::bars;
use GD::Graph::Data;
use Getopt::Std;

getopts('f:');

my $graph = GD::Graph::bars->new();
my (%options, @sets, $line);

while (<STDIN>) {
	$line = $_;
	if ( $line =~ /^dataset\s(.*)/ ) {
		@sets = split ( /:/, $1);
		next;
	}

	if ( $line =~ /^dclrs\s(.*)/ ) {
		@dclrs = split ( /,/, $1);
		$options{'dclrs'}=\@dclrs;
		next;
	}

	if ( $line =~ /(\S+)\s+(.+)/ ) {
		$options{$1}=$2;
	}

}
my @data = ();

for ($i=0; $i<@sets; $i++) {
	my @set = split( /\s+/, $sets[$i] );
	push( @data, \@set );
}

$options{'x_labels_vertical'}="1";

$graph->set( %options ) or warn $graph->error;
my $GDdata = GD::Graph::Data->new(\@data) or die GD::Graph::Data->error;
$graph->plot($GDdata) or die $graph->error;

if ($opt_f) {
  local(*OUT);

  my $ext = $graph->export_format;

  open(OUT, ">$opt_f") or
    die "Cannot open $opt_f for write: $!";
  binmode OUT;
  print OUT $graph->gd->$ext();
  close OUT;
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

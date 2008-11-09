#!/usr/bin/perl

use Text::Iconv;
use strict;

my $DEBUG = 0;

my $charCodes = {
	"gb2312" => {
		name => "gb2312",
		file => "test.gb2312"
		},
	"big5" => {
		name => "big5",
		file => "test.big5"
		},
	"iso-8859-1" => {
		name => "iso-8859-1",
		file => "test.iso-8859-1"
		}
};

# get the converters
print "Initializing Converters...\n";
map {$charCodes->{$_}->{converter} = Text::Iconv->new($_, "utf8")} keys %$charCodes;

# load the data set
print "Loading Dataset:\n";
map {$charCodes->{$_}->{data} = [split /[\n\s]/, `cat $charCodes->{$_}->{file}`]; print "\t$_ [".@{$charCodes->{$_}->{data}}." entries]\n"} keys %$charCodes;
print "\n";

my $oldConvert = sub {
	my $code = shift;
	my $var = shift;
	my ($res) = `/bin/echo '$var' | /usr/bin/iconv -f '$code->{name}' -t utf8`;
	chomp $res;
	return $res;
};

my $newConvert = sub {
	return $_[0]->{converter}->convert($_[1]);
};

# Counts the amount of errors:
my $errorCount = 0;

# convert the old/new fashion way 
foreach my $codekey (keys %$charCodes) {
  my $code = $charCodes->{$codekey};
  convertStrings($code, $oldConvert, 'oldway'); 
  convertStrings($code, $newConvert, 'newway');

  #check for deltas
  for (my $i=0; $i < @{$code->{data}}; $i++) {
    if ($code->{oldway}->[$i] ne $code->{newway}->[$i]) {
      print "Something is different:\n\t\torig: '$code->{data}->[$i]'\n\t\told:  '$code->{oldway}->[$i]'\n\t\tnew:  '$code->{newway}->[$i]' when doing $code->{name}\n";
      $errorCount++;
    } else {
      $DEBUG && print "OK: $code->{oldway}->[$i]\n";
    }
  }
}

print "There were $errorCount errors\n";

# helper functions:
sub convertStrings {
	my $code = shift;
	my $func = shift;
	my $store = shift;

	my $data = $code->{data};
	$code->{$store} = [];
	my $count = 0;
	my $totalSize = scalar @$data;

	print "\n";
	foreach (@$data) {
		print "\ch\rConverting [$code->{name}:$store]: $count / $totalSize\n";
		push @{$code->{$store}}, &$func($code, $_);
		$count++;
	}

};


# dump the char codes structure for debugging
use Data::Dumper;
$DEBUG && print Dumper $charCodes;
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

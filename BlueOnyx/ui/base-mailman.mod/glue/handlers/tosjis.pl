#!/usr/bin/perl
# $Id: tosjis.pl,v 1.0.0-1 Sun 24 Apr 2011 07:03:22 PM CEST
# Copyright 2011 Team BlueOnyx. All rights reserved.

package toSjis;

use Encode;
use Encode::Guess;
use Encode::JP;
use Encode::CN;
use Encode::TW;
use Encode::KR;
#using Glob
use File::Glob;


use Jcode;
#require 'iso2022jp';

sub toSjis{
	my($str,$charset)=@_;
	my $ret=iso_2022_jp::str2html($str,$charset);
	return Jcode->new($ret)->sjis();
}

#!/usr/bin/perl
# $Id: handle_password.pl 3 2003-07-17 15:19:15Z will $

use strict;
use lib qw( /usr/sausalito/perl );
use CCE;
use Sauce::Util;

my $cce = new CCE; $cce->connectfd();
my $obj = $cce->event_object();

my $name = $obj->{name};
my $md5_pw = $obj->{md5_password};

my $today = int(time() / 60 / 24);

# precompile regexes:
my ($re1, $re2);
$re1 = qr/^${name}:[^:]*:\d*:/;
if ($name eq 'admin') {
  $re2 = qr/^root:[^:]*:\d*:/;
} else {
  $re2 = qr/^dont-match-anything$/;
}

my $done = 0;

my $fn = sub {
  my ($fin, $fout) = (shift, shift);
  while (defined($_ = <$fin>)) {
    if (s/$re1/${name}:${md5_pw}:${today}:/) { $done = 1; }
    s/$re2/root:${md5_pw}:${today}:/;
    print $fout $_;
  }
};

Sauce::Util::editfile('/etc/shadow', $fn);

if ($done) {
  $cce->bye("SUCCESS");
  exit(0);
} else {
  print STDERR "Could not change ${name}'s password.\n";
  $cce->bye("FAIL");
  exit(1);
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

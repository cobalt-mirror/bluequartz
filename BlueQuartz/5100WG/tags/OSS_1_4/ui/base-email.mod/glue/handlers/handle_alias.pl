#!/usr/bin/perl -w
#
# handles creation, modification, and deletion of Email Aliases.
# ie. maintains the /etc/mail/aliases file.

my $p;

BEGIN: {
use strict;
use lib qw( /usr/sausalito/perl );
use Profile; $p = new Profile;
use CCE; $p->emit('CCE loaded');
use Sauce::Util; $p->emit('Sauce::Util loaded');
}

my %reserved = map { $_ => 1 } qw(
mailer-daemon
abuse
postmaster
bin
daemon
games
ingres
system
toor
uucp
manager
dumper
operator
decode
nobody
root
);

my $cce = new CCE( 'Domain' => 'base-email' );
$cce->connectfd();
$p->emit('connected');

my $obj = $cce->event_object();
my $new = $cce->event_new();
my $old = $cce->event_old();

if ($new->{name}) {
  #verify uniquness
  my $fail = 0;
  $p->emit('find0');
  my @oids = $cce->find("EmailAlias", { 'name' => $obj->{name} } );
  if ($#oids > 0) { 
	print STDERR "oids: @oids\n";
	$fail = 1; 
  }
  $p->emit('find1');
  
  my $lcname = $obj->{name}; $lcname =~ tr/A-Z/a-z/;
  if ($reserved{$lcname}) { $fail = 2; }
  if ($fail) {
    $cce->warn("aliasInUse", { 'name' => $obj->{name}, 'code' => $fail });
    $cce->bye("FAIL");
    exit(1);
  }
}

my $name = $old->{name} || $obj->{name};
my $from = qr/^$name:\s+.*/;
my $to = undef;
if ($obj->{name} && $obj->{action} && ($obj->{action} ne "*RESERVED*")) {
  $to = "$obj->{name}:\t$obj->{action}\n";
}

$p->emit('editfile0');
Sauce::Util::editfile("/etc/mail/aliases", sub {
  my ($fin, $fout) = (shift,shift);
  while ($_ = <$fin>) {
    if (m/$from/) {
      if ($to) { print $fout $to; $to = undef; }
    } else {
      print $fout $_;
    }
  }
  if ($to) { print $fout $to; }
  return 1;
});
$p->emit('editfile1');

$cce->bye("SUCCESS");
exit(0);

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

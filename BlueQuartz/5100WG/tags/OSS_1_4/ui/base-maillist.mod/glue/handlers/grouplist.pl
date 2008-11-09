#!/usr/bin/perl -w
#
# ensure that a mailing list exists for each group.

use lib qw( /usr/sausalito/perl );
use CCE;
use Sauce::Util;

my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();
my $new = $cce->event_new();
my $old = $cce->event_old();

my $oldname = $old->{name} || $new->{name};
my ($mloid) = $cce->find("MailList", { 'group' => $oldname });

if ($mloid && $cce->event_is_destroy()) {
  $cce->set($mloid, "", { 'group' => '' }); # clear group association
  $cce->destroy($mloid); # destroy list
  $cce->bye("SUCCESS");
  exit(0);
}

my $listname = $obj->{name};
#if ($listname eq 'home') { $listname = 'all'; }

if ($mloid && $new->{name}) {
  $cce->set($mloid, "", { 
    'group' => $obj->{name},
    'name' => $listname,
  });
}

if (!$mloid) {
  $cce->create("MailList", {
    'name' => $obj->{name},
    'group' => $listname,
    'enabled' => '1',
    'update' => '1' });
}

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

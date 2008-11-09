#!/usr/bin/perl -w

use lib qw( /usr/sausalito/perl );
use CCE;
use Sauce::Util;
use FileHandle;

my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();
my $new = $cce->event_new();
my $old = $cce->event_old();

if ($new->{name} || $new->{members}) {
  if (! -d "/etc/group.d" ) { 
	mkdir ("/etc/group.d", 0755);
  }
  chmod(0755, "/etc/group.d");
  my $fn = "/etc/group.d/" . $obj->{name};
  my $fh = new FileHandle(">$fn");
  if ($fh) {
	my @members = $cce->scalar_to_array($obj->{members});
	print $fh join("\n",@members),"\n";
	$fh->close();
	chmod(0755, $fn);
  } else {
    	print STDERR "Could not open $fn: $!\n";
  }
}

if (
  $cce->event_is_destroy() || 
  ($old->{name} && $new->{name} && ($new->{name} ne $old->{name})) 
) {
	unlink ("/etc/group.d/".$old->{name});
}

if ($new->{name} || $new->{members}) {
  my @members = $cce->scalar_to_array($obj->{members});

  if ($#members < 0) { push (@members, 'nobody'); }
  
  Sauce::Util::editfile('/etc/mail/aliases',
	\&Sauce::Util::replace_unique_entries,
	$oid, { 
		$obj->{name} . "_alias" => join(",",@members)
	});
}

if (
	($cce->event_is_destroy) ||
  	($old->{name} && $new->{name} && ($new->{name} ne $old->{name})) 
   )
{
  Sauce::Util::editfile('/etc/mail/aliases',
	\&Sauce::Util::replace_unique_entries,
	$oid, {} );
}

system("/usr/bin/newaliases &> /dev/null");
if ($?>>8 != 0) {
      print STDERR "newaliases failed ... I don't know what to do.\n";
      # FIXME: handle failure
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

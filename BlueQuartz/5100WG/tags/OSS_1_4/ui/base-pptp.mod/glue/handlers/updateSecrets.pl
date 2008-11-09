#!/usr/bin/perl -I/usr/sausalito/perl

use strict;
use CCE;
use Sauce::Util;

my $cce = new CCE(Namespace=>'Pptp');
$cce->connectfd();

my @sysOids = $cce->find("System");
my ($ok, $pptp) = $cce->get($sysOids[0], "Pptp");

my %enabled;
my $updateString = "";

# pick up the workgroup name...
my ($ok2, $winshare) = $cce->get($sysOids[0], "WinNetwork");
my $workgroup = lc($winshare->{workgroup});

if ($pptp->{allowType} eq "all" && $pptp->{enabled}) {
  # gotta check ALL the users
  my @userOids = $cce->find("User");
  foreach my $userOid (@userOids) {
    my ($ok, $userPptp) = $cce->get($userOid, "Pptp");
    my ($ok2, $user) = $cce->get($userOid);
    $updateString .= addUpdate($workgroup, $user, $userPptp);
    enableRemoteAccessTab($cce, $user);
    $enabled{$userOid} = 1; 
  }
} elsif ($pptp->{allowType} eq "some" && $pptp->{enabled}) {
  my @userNames = $cce->scalar_to_array($pptp->{allowData});
  foreach my $username (@userNames) {
    my @userOid = $cce->find("User", {name=>$username});
    my ($ok, $userPptp) = $cce->get($userOid[0], "Pptp");
    my ($ok2, $user) = $cce->get($userOid[0]);
    $updateString .= addUpdate($workgroup, $user, $userPptp);
    enableRemoteAccessTab($cce, $user);
    # maintain a list of users that are enabled, so we can disable
    # those which aren't
    $enabled{$userOid[0]} = 1;
  }
} 

# loop and find all those users that aren't enabled and remove
# their uiright
my @allUserOids = $cce->find("User");
foreach my $userOid (@allUserOids) {
  if (!$enabled{$userOid}) {
    disableRemoteAccessTab($cce, $userOid);
  }
}

sub addUpdate {
  my $workgroup = shift;
  my $user = shift;
  my $userPptp = shift;

  my $workgroup_uc = uc($workgroup);

  my $username = $user->{name};
  my $password = $userPptp->{secret};

  # escape
  $username =~ s/"/\\"/g;
  $password =~ s/"/\\"/g;
  $username =~ s/\n/\\n/g;
  $password =~ s/\n/\\n/g;
  if ($userPptp->{secret}) {
    return "\"$username\"\tpptp\t\"$password\"\t*\n"
          ."\"$workgroup\\\\$username\"\tpptp\t\"$password\"\t*\n"
          ."\"$workgroup_uc\\\\$username\"\tpptp\t\"$password\"\t*\n";
  }
  return "";
}

sub enableRemoteAccessTab {
  my $cce = shift;
  my $obj = shift;
  my $ok;

  if (ref $obj ne "HASH") {
    ($ok, $obj) = $cce->($obj);
  }
  my @uirights = $cce->scalar_to_array($obj->{uiRights});

  my $seen_flag = 0;
  foreach my $uiright (@uirights) {
    if ($uiright eq "enableRemoteAccess") {
      $seen_flag = 1;
    }
  }

  if (!$seen_flag) {
    push @uirights, "enableRemoteAccess";
  }
  $cce->set($obj->{OID}, "", {uiRights => $cce->array_to_scalar(@uirights)});

}

sub disableRemoteAccessTab {
  my $cce = shift;
  my $obj = shift;
  my $ok;

  if (ref $obj ne "HASH") {
    ($ok, $obj) = $cce->get($obj);
  }

  my @uirights = $cce->scalar_to_array($obj->{uiRights});
  my @newUirights;
  foreach my $uiright (@uirights) {
    if ($uiright ne "enableRemoteAccess") {
      push @newUirights, $uiright;
    }
  }
  $cce->set($obj->{OID}, "", {uiRights => $cce->array_to_scalar(@newUirights)});
}




my $writeFunc = sub {
  my ($fin, $fout, $updateString) = @_;
  print $fout $updateString;
};

Sauce::Util::editblock(
	"/etc/ppp/chap-secrets",
	$writeFunc,
	"# AUTOGENERATE PPTP START",
	"# AUTOGENERATE PPTP STOP",
	$updateString);

$cce->bye("SUCCESS");
1;
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

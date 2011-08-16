#!/usr/bin/perl -w -I/usr/sausalito/perl -I. -I/usr/sausalito/handlers/base/mailman
# $Id: listmod_import,v 1.0.0-1 Sun 24 Apr 2011 07:03:22 PM CEST
# Copyright 2011 Team BlueOnyx. All rights reserved.
#
# listmod_members depends on:
#     	      	update

use MailMan; # should be a local file
use Sauce::Util;
use CCE;
my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();

# import:
import_members($cce, $oid, $obj);

$cce->bye('SUCCESS');
exit(0);

sub import_members
{
  my $cce = shift;
  my $oid = shift;
  my $obj = shift;
  my $changes = {};
  
  # extract information about the list...
  my $list = $obj->{name};
  # sorry about the ugly perl code here, it's efficient:
  my @local_recips = ();
  my @remote_recips = ();
  my $listfile = MailMan::get_listfile($obj);

  # lock the members list:
  Sauce::Util::lockfile($listfile);

  my $hn = `/bin/hostname`; chomp($hn);

  my $fh = new FileHandle("<$listfile");
  if ($fh) {
    my @data = <$fh>;
    $fh->close();
    Sauce::Util::unlockfile($listfile);
    
    foreach $_ (@data) {
      chomp($_);
      next if (m/^\s*nobody\s*$/); # who's that?  oh, nobody.
      next if (m/^\s*\w+_alias\s*$/); # just a group.  ignore.
      s/\@$hn\s*$//;
      if (m/\@/) {
      	# is a remote recipient:
	push (@remote_recips, $_);
      } else {
      	push (@local_recips, $_);
      }
    }
    
    $changes->{remote_recips} = $cce->array_to_scalar(sort @remote_recips);
    $changes->{local_recips} = $cce->array_to_scalar(sort @local_recips);

  } else {
    Sauce::Util::unlockfile($listfile);
  }

  $cce->set($oid, "", $changes);
}


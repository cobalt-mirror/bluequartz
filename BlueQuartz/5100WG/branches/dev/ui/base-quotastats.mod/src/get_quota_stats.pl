#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: get_quota_stats.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

use CCE;
use strict;
use SendEmail;
use Quota;
use POSIX qw( strftime );

#
# Constants
#

my @thresh =
(
  {'code' => 2, 'pct' => 95, 'free' => 10, 'message' => "Error"},
  {'code' => 1, 'pct' => 85, 'free' => 20, 'message' => "Warning"}
);

my %dontEmailMe = ("admin" => 1);

my %fsCode =
(
  '/'     => 'rootFiles',
  '/var'  => 'varFiles',
  '/home' => 'userFiles'
);

my $metaFilePath       = "/var/local";
my $dateFile           = "quotastats_run.dat";
my $diskUsageFileName  = "disk_usage.dat";
my $userQuotaFileName  = "user_quotas.dat";
my $groupQuotaFileName = "group_quotas.dat";
my $userStateFileName  = "user_state.dat";
my $groupStateFileName = "group_state.dat";
my $groupsFile         = "/etc/group";

my $tailBin            = "/usr/bin/tail";
my $dfBin              = "/bin/df -m";
my $dateBin            = "/bin/date +%s";
my $grepBin            = "/bin/grep";
my $awkBin             = "/usr/bin/awk";
my $hostnameBin        = "/bin/hostname";

my $mailFrom           = "admin";

#################################################################
#
# Main
#
my $emailTheOffenders = 0;
if (defined ($ARGV[0]))
{
  if ($ARGV[0] eq "-h")
  {
    print STDERR<<end_of_help;
$0 : Go through and collect statistics on the quotas
Usage : $0 [-m | -h]
 -h : print this help
 -m : email users and groups whose quotas are too high
end_of_help
    exit(0);
  }
  $emailTheOffenders = 1 if ($ARGV[0] eq "-m");
}

my $cce = new CCE;
$cce->connectuds();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();

open(DATE, ">$metaFilePath/$dateFile");
print DATE time();
close(DATE);
chmod 0644, "$metaFilePath/$dateFile";

getDiskInfo();
my $userInfo = collectQuotaInfo('User');
my $groupInfo = collectQuotaInfo('Workgroup', '-g');
outputStats($userQuotaFileName,  $userInfo);
outputStats($groupQuotaFileName, $groupInfo);
if ($emailTheOffenders)
{
  my $userStates     = readQuotaStates($userStateFileName);
  my $groupStates    = readQuotaStates($groupStateFileName);
  my $userOffenders  = getOffenderList($userInfo, $userStates);
  my $groupOffenders = getOffenderList($groupInfo, $groupStates);
  emailOffenders($userOffenders,  $userStates,  $userInfo,  'users');
  emailOffenders($groupOffenders, $groupStates, $groupInfo, 'groups');
  dumpQuotaStates($userStates,  $userStateFileName);
  dumpQuotaStates($groupStates, $groupStateFileName);
}

$cce->bye('SUCCESS');
exit(0);

#
##################################################################

my %fsName;
my %fsUsage;

#
# Get the disk usage info and output it to a meta file
#
sub getDiskInfo
{
  my ($execString, $result, @lines, $line, @tokens);

  $execString = $dfBin . ' -lP ' . ' | ' . $tailBin . ' +2';
  $result = `$execString`;
  $fsName{'bogus'} = '/home';
  @lines = split(/\n/, $result);
  foreach $line (@lines)
  {
    @tokens = split(/\s+/, $line);
    $fsName{$tokens[0]} = $tokens[5];

    $fsUsage{$fsCode{$tokens[5]}}->{'total'}     = $tokens[1];
    $fsUsage{$fsCode{$tokens[5]}}->{'used'}      = $tokens[2];
    $fsUsage{$fsCode{$tokens[5]}}->{'available'} = $tokens[3];

    $fsUsage{'totals'}->{'total'}      += $tokens[1];
    $fsUsage{'totals'}->{'used'}       += $tokens[2];
    $fsUsage{'totals'}->{'available'}  += $tokens[3];
  }

  open OUTFILE, ">$metaFilePath/$diskUsageFileName"
        || die "Couldnt open $metaFilePath/$diskUsageFileName for writing";
  foreach (qw(rootFiles varFiles userFiles totals))
  {
    printf OUTFILE "%20s | %6s | %6s | %6s | %3s\n",
                   $_,
                   $fsUsage{$_}->{'used'},
                   $fsUsage{$_}->{'available'},
                   $fsUsage{$_}->{'total'},
                   int($fsUsage{$_}->{'used'} / $fsUsage{$_}->{'total'} * 100);
  }
  close (OUTFILE);
  chmod 0644, "$metaFilePath/$diskUsageFileName";
}


#
# Collect the quota info in a hash of filesystems
#
# cceClassName: always either User or Workgroup
sub collectQuotaInfo
{
  my ($cceClassName) = @_;
  my (%fileSystem, $ok, $entity, $execString, $result, @lines, $line, @tokens);

  my $is_group = ($cceClassName eq "Workgroup") ? "1" : "";

  my @partitions = qw( /home );

  my @entityOIDs = $cce->find($cceClassName);
  %fileSystem = ();
  foreach (@entityOIDs)
  {
    ($ok, $entity) = $cce->get($_);
    next if (!$ok);
    # print "User : " . $entity->{'name'} . "#\n";
    
    # look up the uid or gid:
    my $uid = ($cceClassName eq "Workgroup")
      ? getgrnam($entity->{name})
      : getpwnam($entity->{name});
    
    foreach my $partition (@partitions) {
      my $device = Quota::getqcarg($partition);
      Quota::sync($device);
      my ($block_curr, $block_soft, $block_hard, $block_timelimit,
          $inode_curr, $inode_soft, $inode_hard, $inode_timelimit) 
	    = Quota::query($device, $uid, $is_group);

      if ($block_soft == 0) { $block_soft = -1; }
      
      $fileSystem{$device}->{$entity->{name}}->{'blocks'} = $block_curr;
      $fileSystem{$device}->{$entity->{name}}->{'quota'} = $block_soft;
    }

  }
  return \%fileSystem;
}

#
# Go through and output the results
#
sub outputStats
{
  my ($fileName, $hashRef) = @_;

  open (OUTFILE, ">$metaFilePath/$fileName") ||
              die ("Could not open meta file $metaFilePath/$fileName");
  my $fs;
  foreach $fs (keys %{$hashRef})
  {
    print OUTFILE $fsName{$fs} . "\n" . ('-' x 10) . "\n";
    foreach (keys %{$hashRef->{$fs}})
    {
      my $blocks = $hashRef->{$fs}->{$_}->{blocks};
      my $quota = $hashRef->{$fs}->{$_}->{quota};
      if ($blocks > -1) { $blocks = int($blocks / 1024);};
      if ($quota > -1) { $quota = int($quota / 1024); };
      printf OUTFILE "%-10s | %8s | %8s\n", $_, $blocks, $quota;
    }
    print OUTFILE ('-' x 10);
    print OUTFILE "\n";
  }
  close (OUTFILE);
  chmod 0644, "$metaFilePath/$fileName";
}

#
# Read the last quota states
#
sub readQuotaStates
{
  my $fileName = shift;
  my %state = ();

  if (open (STATE, "$metaFilePath/$fileName"))
  {
    my @tok;
    my $line = <STATE>;
    while (defined($line) && ($line ne ""))
    {
      chomp($line);
      @tok = split(/\|/, $line);
      $state{$tok[1]} = $tok[0];
      $line = <STATE>;
    }
    close (STATE);
  }

  return \%state;
}

#
# Write out the states
#
sub dumpQuotaStates
{
  my ($state, $fileName) = @_;

  open(OUTFILE, ">$metaFilePath/$fileName")
    || die("Could not write to $metaFilePath/$fileName");

  foreach (keys %{$state})
  {
    print OUTFILE $state->{$_} . '|' . $_ . "\n";
  }

  close(OUTFILE);
  chmod 0644, "$metaFilePath/$fileName";

}

#
# Get list of entities who need to be emailed
#
sub getOffenderList
{
  my ($quotas, $states) = @_;
  my @emailList = ();

  my ($fs, $ent, $newCode, $pct, $free);
  foreach $fs (keys %{$quotas})
  {
    foreach $ent (keys %{$quotas->{$fs}})
    {
      $pct = int($quotas->{$fs}->{$ent}->{'blocks'} /
                 $quotas->{$fs}->{$ent}->{'quota'} * 100);
      $free = int(($quotas->{$fs}->{$ent}->{'quota'} -
                   $quotas->{$fs}->{$ent}->{'blocks'}) / 1024);
      $newCode = 0;
      foreach (@thresh)
      {
        if (($pct > $_->{'pct'}) && ($free < $_->{'free'}))
        {
          $newCode = $_->{'code'};
          last;
        }
      }
      $states->{$ent} = 0 if (! exists($states->{$ent}));
      if ($states->{$ent} != $newCode)
      {
        # Only send email for worsening states
        push (@emailList, $ent) if ($newCode > $states->{$ent});
        $states->{$ent} = $newCode;
      }
    }
  }

  return \@emailList;
}

#
# Go through and send the emails
#
sub emailOffenders
{
  my ($offenders, $states, $quotas, $type) = @_;
  my ($o, $users, @oList, $subject, $body);
  my ($host, $pct, $free);

  $host = `$hostnameBin`;
  chomp($host);

  foreach $o (@{$offenders})
  {
    $pct  = $thresh[@thresh - $states->{$o}]->{'pct'};
    $free = $thresh[@thresh - $states->{$o}]->{'free'};

    if ($type eq 'groups')
    {
      $users = `$grepBin ^$o $groupsFile | $awkBin -F: {'print \$4'}`;
      chomp($users);
      @oList = split(/,/, $users);

      my $date = strftime "%x", localtime;

      $subject = '[[base-quotastats.mailQuotaGroupSubject' 
	       . ',date="' . $date . '"]]';
      $body    = '[[base-quotastats.mailQuotaGroupBody'
               . ',group="' . $o    . '"'
               . ',host="'  . $host . '"'
               . ',pct="'   . $pct  . '"'
               . ',free="'  . $free . '"'
               . ']]';
      foreach (@oList)
      {
        if (! exists($dontEmailMe{$_}))
        {
          SendEmail::sendEmail($_, $mailFrom, $subject, $body);
        }
      }
    }
    else
    {
      if (! exists($dontEmailMe{$o}))
      {
        $subject = '[[base-quotastats.mailQuotaUserSubject]]';
        $body    = '[[base-quotastats.mailQuotaUserBody'
                 . ',user="'  . $o    . '"'
                 . ',host="'  . $host . '"'
                 . ',pct="'   . $pct  . '"'
                 . ',free="'  . $free . '"'
                 . ']]';
        SendEmail::sendEmail($o, $mailFrom, $subject, $body);
      }
    }
  }
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

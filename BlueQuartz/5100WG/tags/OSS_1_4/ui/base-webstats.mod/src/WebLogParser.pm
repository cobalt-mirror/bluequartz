package WebLogParser;

# Author: Phil Ploquin
# Copyright 2000, Cobalt Networks.  All rights reserved.

use Socket;   # for the reverse lookup routines

#############################################################
#
# Constants
#
#############################################################

my $slashIsActually     = "/index.html";

my $metaFilePath        = "/var/local";
my $totalsFileName      = "web_totals.dat";
my $bySourceIPFileName  = "by_ip.dat";
my $byReferrerFileName  = "by_ref.dat";
my $byFileFileName      = "by_file.dat";
my $byHourFileName      = "by_hour.dat";
my $byDateFileName      = "by_date.dat";

my $tmpFile             = "/tmp/webby.tmp";
my $sortProg            = "/bin/sort";
my $mvProg              = "/bin/mv";
my $dateProg            = "/bin/date +%s";
my $maxLookups          = 30;  # reverse lookups can be time consuming

##############################################################
#
# Vars
#
##############################################################
my $line;
my @line;
my (%totals, %byIp, %byReferrer, %byFile, %byHour, %byDate);
my ($maxHits, $found);

%totals =
(
  'genDate'     => "",
  'periodStart' => "",
  'periodEnd'   => "",
  'totalHits'   => 0,
  'badHits'     => 0,
  'numIPs'      => 0,
  'numFiles'    => 0,
  'numRefs'     => 0,
  'numBytes'    => 0
);

my $i;
for ($i=0; $i < 24; $i++)
{
  $byHour{sprintf("%02d", $i)} = 0;
}

my @excludeExtensions = qw (jpg gif png bmp ico);

###############################################################
#
# read in the last recorded values
#
###############################################################
sub readBySomething
{
  my $fileName = shift;
  my $populateFunction = shift;

  if (open (INFILE, "$metaFilePath/$fileName"))
  {
    $line = <INFILE>;
    while (defined ($line) && $line ne "")
    {
      chomp($line);
      @line = split(/\|/, $line);
      foreach (@line)
      {
        $_ =~ s/^\s+//;  # strip leading whitespace
        $_ =~ s/\s+$//;  # strip trailing whitespace
      }
      $populateFunction->(@line);
      $line = <INFILE>;
      chomp($line) if defined $line;
    }
    close (INFILE);
  }
}

sub populateTotals
{
  my @line = @_;

  $totals{$line[0]} = $line[1];
}

sub populateByIp
{
  my @line = @_;

  my $ip = $line[2];
  $byIp{$ip}->{'pages'}      = $line[1];
  $byIp{$ip}->{'bytes'}      = $line[3];
  $byIp{$ip}->{'first_time'} = $line[4];
  $byIp{$ip}->{'last_time'}  = $line[5];
  $byIp{$ip}->{'referrer'}   = $line[6];
  $byIp{$ip}->{'hostname'}   = $line[7] || '_';
}

sub populateByFile
{
  my @line = @_;

  $byFile{$line[2]} = $line[1];
}

sub populateByReferrer
{
  my @line = @_;

  $byReferrer{$line[2]} = $line[1];
}

sub populateByHour
{
  my @line = @_;

  $byHour{$line[2]} = $line[1];
}

sub populateByDate
{
  my @line = @_;

  $byDate{$line[2]} = $line[1];
}

#
# Call this to get it all done
#
sub readLastValues
{
  readBySomething($totalsFileName,     \&populateTotals);
  readBySomething($bySourceIPFileName, \&populateByIp);
  readBySomething($byFileFileName,     \&populateByFile);
  readBySomething($byReferrerFileName, \&populateByReferrer);
  readBySomething($byHourFileName,     \&populateByHour);
  readBySomething($byDateFileName,     \&populateByDate);
}

###############################################################
#
# Loop through the log file to add to stats
#
###############################################################
sub readNewLog
{
  my $logFileName = shift;

  my ($ip, $firstDateTime, $lastDateTime, $date, $lastD,
      $seconds, $time, $file, $size, $referrer, $exclude);

  $firstDateTime = $lastDateTime = $lastTime = $date = "";
  $lastD = "foo";  # different from $date to start

  $totals{'genDate'} = time();

  open (LOGFILE, "<$logFileName")
    || die ("Couldn't open log file $logFileName\n");
  $line = <LOGFILE>;
  chomp($line) if (defined $line);
  while ((defined $line) && ($line ne ""))
  {
    $line =~ s/\[//g;   # Get rid of left brackets
    $line =~ s/\"//g;   # Get rid of quotes
    @line = split(/ /, $line);

    if ($line[8] eq '404')
    {
      ++$totals{'badHits'};
      $line = <LOGFILE>;
      chomp($line) if (defined $line);
      next;
    }

    if (($line[5] eq "-") && ($line[6] eq "408"))
    {
      $line = <LOGFILE>;
      chomp($line) if (defined $line);
      next;
    }

    $ip       = $line[0];
    $date     = substr($line[3], 0, 11);
    $time     = substr($line[3], 12);
    $file     = ($line[6] eq "/")?$slashIsActually:$line[6];
    $size     = ($line[9] eq "-")?0:$line[9];
    $referrer = $line[10];

    # Get rid of GET string
    @line = split(/\?/, $file);
    $file = $line[0] if (defined ($line[0]));

    # See if this is a file to exlude from most stats
    $exclude = 0;
    foreach (@excludeExtensions)
    {
      if ($file =~ /$_$/)
      {
        $exclude = 1;
        last;
      }
    }

    #
    # Totals
    #
    $firstDateTime = "$date $time" if ($firstDateTime eq "");
    $lastDateTime  = "$date $time";
    ++$totals{'totalHits'} if (! $exclude);
    $totals{'numBytes'} += $size;

    #
    # Track by file
    #
    if (! $exclude)
    {
      if (! exists $byFile{$file})
      {
        $byFile{$file} = 1;
        ++$totals{'numFiles'};
      }
      else
      {
        ++$byFile{$file};
      }
    }

    #
    # Track by IP
    #
    if (exists $byIp{$ip})
    {
      ++$byIp{$ip}->{'pages'} if (! $exclude);
      $byIp{$ip}->{'last_time'} = $time;
      $byIp{$ip}->{'bytes'} += $size;
    }
    else
    {
      ++$totals{'numIPs'};
      $byIp{$ip}->{'pages'}      = 1 - $exclude;
      $byIp{$ip}->{'bytes'}      = $size;
      $byIp{$ip}->{'first_time'} = $byIp{$ip}->{'last_time'} = $time;
      $byIp{$ip}->{'hostname'}   = '_';

      #
      # Track by referrer
      #
      if (defined ($referrer))
      {
        # get rid of any GET method string
        @line = split(/\?/, $referrer);
        $referrer = $line[0] if (defined ($line[0]));
      }
      if (!defined ($referrer))
      {
        $referrer = '_';
      }
      else
      {
        $referrer = substr($referrer, 7) if ($referrer =~ /^http/);
      }

      $byIp{$ip}->{'referrer'} = $referrer;

      ++$totals{'numRefs'} if (! exists $byReferrer{$referrer});
      ++$byReferrer{$referrer} if (! $exclude);
    }

    if (! $exclude)
    {
      #
      # Track by hour
      #
      @line = split(/:/, $time);
      ++$byHour{$line[0]};

      #
      # Track by date
      #
      if ($date ne $lastD)
      {
        # 'cache' the time_t date
        $lastD = $date;
        $seconds = $date;
        $seconds =~ s/\// /g;
        $seconds = `$dateProg -d "$seconds"`;
        chomp($seconds);
      }
      ++$byDate{$seconds};
    }

    $line = <LOGFILE>;
    chomp($line) if (defined $line);
  }

  if ($firstDateTime ne "")
  {
    @line = split(/\/| /, $firstDateTime);
    $line = "$dateProg -d " .
                 '"'.$line[1].' '.$line[0].' '.$line[3].' '.$line[2].'"';
    $firstDateTime = `$line`;
    chomp($firstDateTime);
    $totals{'periodStart'} = $firstDateTime
       if (($totals{'periodStart'} eq "") ||
           ($firstDateTime < $totals{'periodStart'}));
  }

  if ($lastDateTime ne "")
  {
    @line = split(/\/| /, $lastDateTime);
    $line = "$dateProg -d " .
                '"'.$line[1].' '.$line[0].' '.$line[3].' '.$line[2].'"';
    $lastDateTime = `$line`;
    chomp($lastDateTime);
    $totals{'periodEnd'} = $lastDateTime
       if (($totals{'periodEnd'} eq "") ||
           ($lastDateTime > $totals{'periodEnd'}));
  }
}

#################################################################
#
# Go through and output our meta files
#
#################################################################
sub getMaxHits
{
  my $infoHash = shift;
  my $field = shift;

  $maxHits = 0;
  if (defined $field)
  {
    foreach (keys %{$infoHash})
    {
      $maxHits = $infoHash->{$_}->{$field}
        if ($infoHash->{$_}->{$field} > $maxHits);
    }
  }
  else
  {
    foreach (keys %{$infoHash})
    {
      $maxHits = $infoHash->{$_}
        if ($infoHash->{$_} > $maxHits);
    }
  }

  $maxHits = 1 if (! $maxHits);
}

sub dumpMetaFile
{
  my $fileName = shift;
  my $infoHash = shift;
  my $dumpFunction = shift;

  open (OUTFILE, ">$metaFilePath/$fileName") ||
    die ("Could not open meta file $metaFilePath/$fileName");
  foreach (keys %{$infoHash})
  {
    if (ref($infoHash->{$_}) eq 'HASH')
    {
      print OUTFILE $dumpFunction->($_, \%{$infoHash->{$_}});
    }
    else
    {
      print OUTFILE $dumpFunction->($_, $infoHash->{$_});
    }
    print OUTFILE "\n";
  }
  close (OUTFILE);
  chmod 0644, "$metaFilePath/$fileName";
}

sub dumpTotals
{
  # This is a special case, a certain order is needed
  open (OUTFILE, ">$metaFilePath/$totalsFileName") ||
    die ("Could not open meta file $metaFilePath/$totalsFileName");
  foreach (qw(genDate periodStart periodEnd totalHits badHits numIPs numFiles numRefs numBytes))
  {
    printf OUTFILE "%-15s | %s\n", $_, $totals{$_};
  }
  close OUTFILE;
  chmod 0644, "$metaFilePath/$totalsFileName";
}

sub dumpSimple
{
  my ($thing, $hits) = @_;

  return (sprintf "%6s | %6s | %s",
                   int($hits / $maxHits * 100), $hits, $thing);
}

sub dumpDate
{
  my ($thing, $hits) = @_;

  return (sprintf "%6s | %6s | %s",
                   int($hits / $maxHits * 100), $hits, $thing);
}

sub dumpByIp
{
  my ($ip, $ipH) = @_;

  return (sprintf
           "%6s | %6s | %-15s | %10s | %s | %s | %-30s | %s",
           int($ipH->{'pages'} / $maxHits * 100),
           $ipH->{'pages'},
           $ip,
           $ipH->{'bytes'},
           $ipH->{'first_time'},
           $ipH->{'last_time'},
           $ipH->{'referrer'},
           $ipH->{'hostname'});
}

sub resolveIPs
{
  if (open (INFILE, "$metaFilePath/$bySourceIPFileName"))
  {
    my $counter = 0;
    open (OUTFILE, ">$tmpFile") || die("Could not open $tmpFile");
    $line = <INFILE>;
    chomp($line) if defined ($line);
    while (defined $line)
    {
      @line = split(/\|/, $line);
      foreach (@line)
      {
        $_ =~ s/^\s+//;  # strip leading whitespace
        $_ =~ s/\s+$//;  # strip trailing whitespace
      }
      ++$counter;
      if (($counter < $maxLookups) && ($line[7] eq '_'))
      {
        $line[7] = gethostbyaddr(inet_aton($line[2]), AF_INET) || 'Unknown';
      }
      printf OUTFILE "%6s | %6s | %-15s | %10s | %s | %s | %-30s | %s\n",
                     $line[0], $line[1], $line[2], $line[3],
                     $line[4], $line[5], $line[6], $line[7];
      $line = <INFILE>;
      chomp($line) if defined ($line);
    }
    close (OUTFILE);
    close (INFILE);
    system ("mv $tmpFile $metaFilePath/$bySourceIPFileName");
    chmod 0644, "$metaFilePath/$bySourceIPFileName";
  }
}

sub dumpMetaFiles
{
  dumpTotals;
  getMaxHits(\%byIp, 'pages');
  dumpMetaFile($bySourceIPFileName, \%byIp, \&dumpByIp);
  getMaxHits(\%byReferrer);
  dumpMetaFile($byReferrerFileName, \%byReferrer, \&dumpSimple);
  getMaxHits(\%byFile);
  dumpMetaFile($byFileFileName, \%byFile, \&dumpSimple);
  getMaxHits(\%byHour);
  dumpMetaFile($byHourFileName, \%byHour, \&dumpSimple);
  getMaxHits(\%byDate);
  dumpMetaFile($byDateFileName, \%byDate, \&dumpDate);
}

sub sortFiles
{
  #
  # Sort our files (cheesy)
  #
  foreach ($byFileFileName, $byReferrerFileName, $bySourceIPFileName)
  {
    system ("$sortProg -r $metaFilePath/$_ > $tmpFile");
    system ("mv $tmpFile $metaFilePath/$_");
  }

  foreach ($byHourFileName, $byDateFileName)
  {
    system ("$sortProg +4 $metaFilePath/$_ > $tmpFile");
    system ("$mvProg $tmpFile $metaFilePath/$_");
  }
}

#
# The rest here is no longer used
#
######################################################################
#
# Add the days' total hits to the by date file
#
######################################################################
sub tallyLastDay
{
  my $dateMaxHits = 0;
  my @byDate;

  if (open (INFILE, "$metaFilePath/$byDateFileName"))
  {
    # First, count the number of lines we have
    my $lineCount = 0;
    $line = <INFILE>;
    while (defined ($line) && $line ne "")
    {
      chomp($line);
      ++$lineCount;
      $line = <INFILE>;
      chomp($line) if defined $line;
    }
    close (INFILE);

    if ($lineCount > 0)
    {
      open (INFILE, "$metaFilePath/$byDateFileName");

      # Burn the extra lines
      while ($lineCount > $maxItems - 1)
      {
        $line = <INFILE>;
        --$lineCount;
      }

      # Now go through and get the data we want
      $line = <INFILE>;
      chomp($line);
      while (defined ($line) && $line ne "")
      {
        @line = split(/\|/, $line);
        foreach (@line)
        {
          $_ =~ s/^\s+//;  # strip leading whitespace
          $_ =~ s/\s+$//;  # strip trailing whitespace
        }
        my %thisDate;
        $thisDate{'date'} = $line[0];
        $thisDate{'hits'} = $line[2];
        push(@byDate, \%thisDate);
        $dateMaxHits = $line[2] if ($line[2] > $dateMaxHits);
        $line = <INFILE>;
        chomp($line) if defined $line;
      }
      close (INFILE);
    }
  }

  #
  # Now use today's data
  #
  readBySomething($totalsFileName, \&populateTotals);

  if (defined $date)
  {
    my %thisDate;
    $thisDate{'date'} = $date;
    $thisDate{'hits'} = $totalPages;
    push(@byDate, \%thisDate);
    $dateMaxHits = $totalPages if ($totalPages > $dateMaxHits);
  }

  #
  # Output the file
  #
  open (OUTFILE, ">$metaFilePath/$byDateFileName") ||
    die ("Could not open meta file $metaFilePath/$byDateFileName");
  foreach (@byDate)
  {
    printf(OUTFILE "%10s | %6s | %6s\n",
                   $_->{'date'},
                   $dateMaxHits ? int($_->{'hits'} / $dateMaxHits * 100) : 0,
                   $_->{'hits'});
  }
  close (OUTFILE);
  chmod 0644, "$metaFilePath/$byDateFileName";
}

#
# Clean up files so a new day can begin
#
sub resetMetaFiles
{
  foreach ($totalsFileName, $bySourceIPFileName, $byReferrerFileName, $byFileFileName, $byHourFileName)
  {
    unlink("$metaFilePath/$_");
  }
}

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

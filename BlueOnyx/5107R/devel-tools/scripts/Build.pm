# common functions used between the build scripts

package Build;

use strict;
use lib qw(/usr/sausalito/perl);
use FileHandle;
use File::Copy;
use Exporter;
use Devel;

use vars qw(@ISA @EXPORT);

@ISA = qw(Exporter);
@EXPORT = qw(install_Develtools parseBuildConf mailReport 
	     installCrontab confirmServer getCVSversion 
	     getDateVersion getHarnessReport);

 
##########################################
#
# checks out and installs devel-tools
# takes the dir to check it out in
#
sub install_Develtools {
  my ($DIR) = @_;

  chdir ($DIR) || die "Error on chdir $DIR\n";
  # Checkout devel-tools and install
  if(-d 'devel-tools'){
    if (!cvs_cmd("update -PAd devel-tools")) {
      die "Error on devel-tools update.\n";
    }
  }else{
    if (!cvs_cmd("co devel-tools")) {
      die "Error on devel-tools checkout.\n";
    }
  }
  make_cmd("-C devel-tools install") || die "Error on installing devel-tools\n";
}

##################################
#
# parse the build.conf file passed in
#
sub parseBuildConf {
  my $CONF = shift;
  my %SETTINGS = ();
  

  open(CONFFILE, $CONF) || die "Can't read configfile: $CONF\n";
  while(<CONFFILE>) {

    if (/^Name:\s*(.+)/i) {
      $SETTINGS{NAME} = $1;
      next;
    }

    if (/^Product:\s*(.+)/i) {
      $SETTINGS{PRODUCT} = $1;
      next;
    }
    
    if (/^ExcludeLocale:\s*(.+)/i) {
      $SETTINGS{EXCLUDELOCALE} = $1;
      next;
    }

    if (/^BuildServer:\s*(.+)/i) {
      $SETTINGS{BUILDSERVER} = $1;
      next;
    }

    if (/^PushToBTO:\s*(.+)/i) {
      $SETTINGS{PUSHTOBTO} = $1;
      next;
    }
    
    if (/^BTO:\s*(.+)/i) {
      $SETTINGS{BTO} = $1;
      next;
    }

    if (/^BTOtype:\s*(.+)/i) {
      $SETTINGS{BTOTYPE} = $1;
      next;
    }

    if (/^SetVersion:\s*(.+)/i) {
      $SETTINGS{SETVERSION} = $1;
      next;
    }

    if (/^RunTestHarness:\s*(.+)/i) {
      $SETTINGS{RUNTESTHARNESS} = $1;
      next;
    }

    if (/^Schedule:\s*(.+)/i) {
      my $schedule = $1;
      $schedule =~ s/\s+/ /g;
      $SETTINGS{SCHEDULE} = $schedule;
      next;
    }

    if (/^MailTo:\s*(.+)/i) {
      $SETTINGS{MAILTO} = $1;
      next;
    }
  }
  close(CONFFILE);
  
  return %SETTINGS;
}


###############################################################
#
# $version = 0 if build failed, otherwise its the build version
# $cvs is cvs version of build
# $errorString is type of error
# $configfile, location of the build.conf
# $logFile, location of build log file
# $testHarnessFile, location of test harness log if applicable
# @mailto, array of email addresses
#
sub mailReport {
  my ($version, 
      $cvs, 
      $errorString, 
      $configFile, 
      $logFile,
      $testHarnessFile) = @_;

  my $MAILBIN = "/bin/mail";
  my %CONFIG = parseBuildConf($configFile);
  my @MAILTO = returnMailArray($CONFIG{MAILTO});
  
  # build success
  if ($version ne "none") {
    foreach my $address (@MAILTO) {
      open(BUFFER, "|$MAILBIN -s \"Build Success for: $CONFIG{NAME}.\" $address");
      print BUFFER "
Name:              $CONFIG{NAME}
Product:           $CONFIG{PRODUCT}
Exclude Locales:   $CONFIG{EXCLUDELOCALE}
Build Version:     $version (CVS: $cvs)
Build Type On BTO: $CONFIG{BTOTYPE}
Push To BTO:       $CONFIG{PUSHTOBTO}
BTO Server:        $CONFIG{BTO}
Build Config:      $configFile";
      
      # See if we should append the test harness report
      if (-f $testHarnessFile) {
	open(TH, "$testHarnessFile");
	print BUFFER "\n\n-------- QA TEST HARNESS REPORT FOR A BUILT $cvs RELEASE -------\n";
	while(<TH>) {
	  print BUFFER;
	}
	close(TH);
      }
      close(BUFFER);
    }
  }
  # build failure
  else {
    foreach my $address (@MAILTO) {
      open(BUFFER, "|$MAILBIN -s \"Build failure for: $CONFIG{NAME}.\" $address");
      print BUFFER "
Name:              $CONFIG{NAME}
Product:           $CONFIG{PRODUCT}
Exclude Locales:   $CONFIG{EXCLUDELOCALE}
Build Config:      $configFile

Error Type:        $errorString";
      
      # print out the error log
      print BUFFER "\n-------------------- ERROR LOG --------------------\n";
      open(LOG, "$logFile");
      while (<LOG>) {
	print BUFFER;
      }
      
      close(BUFFER);
    }
  }
}

sub returnMailArray {
  my $MAILTOSTRING = shift;

  $MAILTOSTRING =~ s/ //g;
  return split(/,/, $MAILTOSTRING);
}

############################################################
#
# wait for test harness report file from outbox of 
# QA test harness driver

sub getHarnessReport {
  my ($PRODUCT, $VERSION, $DRIVERIP) = @_;

  my $ERRORFILE = "/tmp/TestHarnessError-$PRODUCT\_$VERSION";
  my $MNTDIR = "/mnt";
  my $TESTHFILE = "$MNTDIR/outbox/testreport-$PRODUCT\_$VERSION";
  my $LOCALTESTHFILE = "/tmp/testreport-$PRODUCT\_$VERSION";
  # timeout in minutes before giving up in looking for test harness report
  # currently takes about 2 hours to run
  my $timeout = 430;
  my $timer = $timeout;

  open(HARNESS_ERROR, ">> $ERRORFILE");

  system("mount -t nfs $DRIVERIP:/harness $MNTDIR");
  if ($? != 0) {
    print HARNESS_ERROR "Error in contacting QA Test Harness driver: $DRIVERIP\n";
    print HARNESS_ERROR "No QA Test Harness report\n";
    close(HARNESS_ERROR);
    system("unmount $MNTDIR");
    return $ERRORFILE;
  }
  
  print STDOUT "Waiting for QA Test Harness report: looking for $TESTHFILE\n";
  # wait for the test harness report
  while (! -e "$TESTHFILE") {
    sleep 60;
    $timer--;
    if ($timer <= 0) {
      print HARNESS_ERROR "Test Harness driver: $DRIVERIP did not have a test report in\n";
      print HARNESS_ERROR "$timeout minutes. No QA Test harness report\n";
      close(HARNESS_ERROR);
      system("umount $MNTDIR");
      return $ERRORFILE;
    }
  }
  close(HARNESS_ERROR);
  copy("$TESTHFILE", "$LOCALTESTHFILE") && chmod(0644, "$LOCALTESTHFILE");
  system("umount $MNTDIR");
  return "$LOCALTESTHFILE";
}

############################################################
#
# Takes in MAILTO and array of cronlines and installs 
# the crontab in crontab(5) format.
#

sub installCrontab {
  my ($MAILTO, @CRONSCHEDULES) = @_;
  my $CRONBIN = "/usr/bin/crontab";
  my $TEMPFILE = "/tmp/crontmp";
  
  open(CRONFILE, "> $TEMPFILE");
  print CRONFILE "MAILTO=$MAILTO\n";
  
  foreach my $LINE (@CRONSCHEDULES) {
    print CRONFILE $LINE;
  }
  close(CRONFILE);

  system("$CRONBIN $TEMPFILE");
}

##########################################
# returns 1 if I am the buildserver 
# passed in argument
#

sub confirmServer {
  my $BUILDONTHIS = shift;
  my $HOSTNAME = `hostname`;
  
  chop $HOSTNAME;
  # get hostname
  $BUILDONTHIS =~ /\s*([^\.]+)\.*/;
  $BUILDONTHIS = $1;
  $HOSTNAME =~ /\s*([^\.]+)\.*/;
  $HOSTNAME = $1;
    
  if (($HOSTNAME =~ /$BUILDONTHIS/) && ($BUILDONTHIS ne "")) {
    return 1;
  } else {
    return 0;
  }
}

##########################################
# returns CVS version of release_spec 
# passed in argument
#

sub getCVSversion {
  my $RELEASE_SPEC = shift;
  my $VERSION = 0;

  open(RS, "$RELEASE_SPEC") || die "Can't find $RELEASE_SPEC\n";
  while (<RS>) {
    if (/v\s(\d+\.\d+)\s/) {
      $VERSION = $1;
    }
  }
  close(RS);
  return $VERSION;
}

##########################################
#
# returns date string used for version in
# daily builds
#

sub getDateVersion {
  (my $day, my $month, my $year) = (localtime)[3..5];
  $month++;
  $year += 1900;
  $year = sprintf("%02d", $year % 100);
  $month = sprintf("%02d", $month);
  $day = sprintf("%02d", $day);
  return "$year\.$month\.$day";
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

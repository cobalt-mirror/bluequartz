#!/usr/bin/perl
package TestCcewrap;

use strict;
my $testdatacounter = 0;
my $testappcounter = 0;
my $testusercounter = 0;

1;

sub new {
  my $self = {};
  my $proto = shift;
  my $class = ref($proto) || $proto;

  bless ($self, $class);
  
  $self->init(@_);
  return $self;
}

sub init {
  my $self = shift;
  my $cce = shift;
  my $name = shift;
  $self->{cce} = $cce;
  $self->{apps} = [];
  $self->{data} = [];
  $self->{users} = [];
  $self->{name} = $name;
  print "Starting $name\n";
  `mkdir -p /etc/ccewrap.d/tests`;
  `mkdir -p /tmp/tests`;
  `chmod 777 /tmp/tests`;
  `touch /tmp/tests/results`;
  `chmod 777 /tmp/tests/results`;
}

sub create_testdata {
  my ($self, $name) = (shift, shift);

  my $commaflag = 0;


  my $datafile = "/etc/ccewrap.d/tests/test$testdatacounter.xml";
  push @{$self->{data}}, $datafile;

  open (FILE, "> $datafile");
  print FILE "<program name=\"$name\">\n";

  print "Creating Test Data: $datafile\n";
  print "\tProgram: $name\n";

  my ($cap, $user);
  while (($cap, $user) = (shift, shift), defined($cap)) {
    ($commaflag) && print "\t\t,\n";
    if (ref $user eq "HASH") { $user = $user->{name} };
    print "\t\tCapability: " . ($cap?$cap:"<any capability>") . "\n";
    print "\t\tRun As:     " . ($user?$user:"<any user>"). "\n";
    print FILE "\t<capability";
    (defined($cap) && ($cap || $cap eq "")) &&
      print FILE " requires=\"$cap\"";
    (defined($user) && ($user || $user eq "")) &&
      print FILE " user=\"$user\"";
    print FILE "/>\n";
    undef($cap);undef($user);
    $commaflag = 1;
  }
  print "}\n";
  print FILE "</program>";

  close FILE;
  $testdatacounter++;
}

sub create_testapp {
  my $self = shift;
  my $path = shift || "/tmp/tests" ;

  my $appfile = "$path/test$testappcounter";
  $testappcounter++;

  print "Creating new Application: $appfile\n";

  push @{$self->{apps}}, $appfile;

  open (FILE, "> $appfile");
  print FILE <<EOF;
#!/bin/sh
echo \$0 `whoami` >> $path/results
EOF
  close (FILE);
  chmod 0755, $appfile;
  return $appfile;
}

sub create_user {
  my $self = shift;
  my $capabilities = shift || [];
  my $cce = $self->{cce};


  my $username = "ccewrap$testusercounter";
  $testusercounter++;

  print "Creating user: $username\n";
  if (scalar @$capabilities) {
    print "\tCapabilities: " . join(",", @$capabilities). "\n";
  }

  push @{$self->{users}}, $username;

  my ($ok) = $cce->create("User", {name => $username, password => $username,
    capabilities=>$cce->array_to_scalar(@$capabilities)});

  (!$ok) &&
    print "Error creating user: $username\n";

  return {name=>$username, oid=>$cce->oid()};
}

sub cleanup {
  my $self = shift;

  my $cce = $self->{cce};

  print "Cleaning up\n";

  # delete files 
  foreach my $file (@{$self->{data}}) { 
    `rm $file`;
  }

  foreach my $user (@{$self->{users}}) {
    my @oids = $cce->find("User", {name=>$user});
    if (scalar @oids == 0) {
      print "Error find user: $user when cleaning up\n";
    }
    $cce->destroy($oids[0]);
  }

  # remove any old ccewrap.conf entries 
  open OLDFILE, "< /etc/ccewrap.conf";
  open NEWFILE, "> /etc/ccewrap.conf-new"; 
  while (my $line = <OLDFILE>) {
    ($line =~ m/^\#TESTSCRIPT/) &&
      last;
    print NEWFILE $line;
  }
  close OLDFILE;
  close NEWFILE;
  `mv /etc/ccewrap.conf-new /etc/ccewrap.conf`;
}

sub run_test {
  my ($self, $user, $app, $runas) = @_;
  my $runasString = "";
  my $request = "";
  if ($runas) { 
    if (ref $runas eq "HASH") {
      $runas = $runas->{name};
    }
    $runasString = "export CCE_REQUESTUSER=$runas; ";
    $request = " requesting $runas";  
  };
  my $msg = "Running $app as $user->{name}$request";
  print $msg . "\n";
  my @vals = split '\n', `su - httpd -c 'export CCE_USERNAME=$user->{name}; export CCE_PASSWORD=$user->{name}; $runasString /usr/sausalito/bin/ccewrap $app 2>&1 > /dev/null ; echo \$?'`;
  print "\ch\r$msg returned: " . $vals[$#vals] . "\n";

  # check the actual user this program ran as 
  if (!$vals[$#vals]) { 
    my @inf = split ' ', `tail -n 1 /tmp/tests/results`;
    if (!$runas) {
      $runas = "";
    }
    my $msg = "Checking if this user ran as the requested user:";
    print $msg."\n";
    if ($runas ne $inf[1]
        && ($runas eq "" && $user->{name} ne $inf[1])) {
      print "FAILED, program ran as $inf[1]\n";
      return -1;
    } else {
      print "\ch\r$msg passed ($inf[1])\n";
    }
  }
  return $vals[$#vals];
}

sub succeed {
  my ($self, $val) = @_;

  print $self->{name};
  if ($val) { 
    print ": SUCCEEDED\n";
  } else {
    print ": FAILED\n";
    $self->cleanup();
    exit;
  }
}

sub create_oldtestdata {
  my $self = shift;
  my $app = shift;
  open FILE, ">> /etc/ccewrap.conf";
  print FILE "#TESTSCRIPT\n$app\n";
  close FILE;
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

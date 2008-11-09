#!/usr/bin/perl -I/usr/sausalito/perl

use strict;
use CCE;
use TestCcewrap;

my $cce = new CCE();
$cce->connectuds();
$cce->auth("admin", "admin");

my $BINPATH = "/tmp/tests";

`mkdir -p /home/httpd`;
`chown httpd.httpd /home/httpd`;
`mkdir -p $BINPATH`;

# 0) - whether a user with systemAdministrator set can run a
# defined program as any user he wishes
{
  my $test = new TestCcewrap($cce, "Test 0");
  my $app1 = $test->create_testapp($BINPATH);
  my $app2 = $test->create_testapp($BINPATH); 
  my $app3 = $test->create_testapp($BINPATH);
  my $user1 = $test->create_user();
  my $user2 = $test->create_user();
  $cce->set($user1->{oid}, "", {systemAdministrator=>1});
  # should work as anyone can run this as self
  $test->create_oldtestdata($app1);
  $test->create_testdata($app2, "capfoo", "nobody");

  my @rets1 = split '\n',  `su - httpd -c 'export CCE_USERNAME=$user1->{name}; export CCE_PASSWORD=$user1->{name}; /usr/sausalito/bin/ccewrap $app1 2>&1 /dev/null ; echo \$?'`;
  print "Call returned $rets1[$#rets1]\n";
  my @inf1 = split ' ', `tail -n 1 /tmp/tests/results`;
  print "ran as: $inf1[1]\n";
  my @rets2 = split '\n',  `su - httpd -c 'export CCE_USERNAME=$user1->{name}; export CCE_PASSWORD=$user1->{name}; /usr/sausalito/bin/ccewrap $app2 2>&1 /dev/null ; echo \$?'`;
  print "Call returned $rets2[$#rets2]\n";
  my @inf2 = split ' ', `tail -n 1 /tmp/tests/results`;
  print "ran as: $inf2[1]\n";

  $test->succeed(
    !$rets1[$#rets1] && $inf1[1] eq "root"
    && !$rets2[$#rets2] && $inf2[1] eq "root"
    && !$test->run_test($user1, $app1, "nobody")
    && !$test->run_test($user1, $app1, "root")
    && !$test->run_test($user1, $app1, $user2)
    && !$test->run_test($user1, $app2, "nobody")
    && !$test->run_test($user1, $app2, "root")
    && !$test->run_test($user1, $app2, $user2)
    && $test->run_test($user1, $app3)
    && $test->run_test($user1, $app3, "nobody")
    && $test->run_test($user1, $app3, "root")
    && $test->run_test($user1, $app3, $user2)
  );
  $test->cleanup();
}

# 1) - whether a program can run the old entries from 
# ccewrap.conf as the calling user..
{
  my $test = new TestCcewrap($cce, "Test 1");
  my $app = $test->create_testapp($BINPATH);
  my $user1 = $test->create_user();
  my $user2 = $test->create_user();
  $test->create_oldtestdata($app);

  $test->succeed(
    !$test->run_test($user1, $app)
    && !$test->run_test($user1, $app, $user1)
    && $test->run_test($user1, $app, $user2)
    && $test->run_test($user1, $app, "root")
    && !$test->run_test($user2, $app)
    && $test->run_test($user2, $app, $user1)
    && !$test->run_test($user2, $app, $user2)
    && $test->run_test($user2, $app, "root")
  );
  $test->cleanup();
}

# 2) - whether a user with sysadmin that calls a program from 
# ccewrap.conf is run as root by default.

{
  my $test = new TestCcewrap($cce, "Test 2");
  my $app = $test->create_testapp($BINPATH);
  my $user1 = $test->create_user();
  $test->create_oldtestdata($app);
  $cce->set($user1->{oid}, "", {systemAdministrator=>1});

  my @rets = split '\n',  `su - httpd -c 'export CCE_USERNAME=$user1->{name}; export CCE_PASSWORD=$user1->{name}; /usr/sausalito/bin/ccewrap $app 2>&1 /dev/null ; echo \$?'`;
  print "Call returned $rets[$#rets]\n";
  my @inf = split ' ', `tail -n 1 /tmp/tests/results`;
  $test->succeed(!$rets[$#rets] && $inf[1] eq "root");
  $test->cleanup();
}
    
# 3) - whether a program from ccewrap.conf is run as the 
# authenticating user regardless of if CCE_REQUESTUSER is set
{
  my $test = new TestCcewrap($cce, "Test 3");
  my $app = $test->create_testapp($BINPATH);
  my $user1 = $test->create_user();
  $test->create_oldtestdata($app);

  my @rets = split '\n',  `su - httpd -c 'export CCE_USERNAME=$user1->{name}; export CCE_PASSWORD=$user1->{name}; export CCE_REQUESTUSER=root; /usr/sausalito/bin/ccewrap $app 2>&1 /dev/null ; echo \$?'`;
  $test->succeed($rets[$#rets]);
  $test->cleanup();
}

# 4) - whether a program from ccewrap.conf is run as root when 
# CCE_REQUESTUSER is "" from a user with 
# .systemAdministrator set
{
  my $test = new TestCcewrap($cce, "Test 4");
  my $app = $test->create_testapp($BINPATH);
  my $user1 = $test->create_user();
  $test->create_oldtestdata($app);
  $cce->set($user1->{oid}, "", {systemAdministrator=>1});

  my @rets = split '\n',  `su - httpd -c 'export CCE_USERNAME=$user1->{name}; export CCE_PASSWORD=$user1->{name}; export CCE_REQUESTUSER="$user1->{name}"; /usr/sausalito/bin/ccewrap $app 2>&1 /dev/null ; echo \$?'`;
  print "Call returned $rets[$#rets]\n";
  my @inf = split ' ', `tail -n 1 /tmp/tests/results`;
  print "Ran as $inf[1]\n";
  $test->succeed(!$rets[$#rets] && $inf[1] eq $user1->{name});
  $test->cleanup();
}
    

# 5) - Have two new program entries with the same name, one can 
# run as user1, the other as user2 with no capability(anybody), 
# try runs agains user1(pass), user2(pass), and user3(fail)
{ # new test
  my $test = new TestCcewrap($cce, "Test 5");

  my $app = $test->create_testapp($BINPATH);
  my $user1 = $test->create_user();
  my $user2 = $test->create_user();
  my $user3 = $test->create_user();
  $test->create_testdata($app, 0, $user1);
  $test->create_testdata($app, 0, $user2);


  $test->succeed(
    !$test->run_test($user1, $app)
    && !$test->run_test($user2, $app)
    &&  $test->run_test($user3, $app)
  );

  $test->cleanup();
}

# 6) - Have two new program entries with the same name, one 
# needs a capability, the other doesn't, both should run as self.
# Test with a user that has the capability, and again without.

{ # new test
  my $test = new TestCcewrap($cce, "Test 6");

  my $app = $test->create_testapp($BINPATH);
  my $user1 = $test->create_user(["testcap"]);
  my $user2 = $test->create_user();
  
  $test->create_testdata($app, "testcap");
  $test->create_testdata($app, 0);

  $test->succeed(
    !$test->run_test($user1, $app)
    && !$test->run_test($user2, $app)
  );

  $test->cleanup();
}

# 6b) - Have two new program entries with the same name, one 
# needs a capability, the other doesn't, both should run as root 
# root. Test with a user that has the capability, and 
# again without.

{ # new test
  my $test = new TestCcewrap($cce, "Test 6b");

  my $app = $test->create_testapp($BINPATH);
  my $user1 = $test->create_user(["testcap"]);
  my $user2 = $test->create_user();
  
  $test->create_testdata($app, "testcap", "root");
  $test->create_testdata($app, 0, "root");

  $test->succeed(
    !$test->run_test($user1, $app, "root")
    && !$test->run_test($user2, $app, "root")
    && $test->run_test($user1, $app)
    && $test->run_test($user1, $app)
  );

  $test->cleanup();
}

# 7) - Have two new program entries with the same name, one 
# needs a capability and can run as user1, the other doesn't 
# have a capability and should run as user2.  Test this works as 
# designed.

{ # new test
  my $test = new TestCcewrap($cce, "Test 7");

  my $app = $test->create_testapp();
  
  my $user1 = $test->create_user(["testcap7"]);
  my $user2 = $test->create_user();

  my $user3 = $test->create_user();
  my $user4 = $test->create_user();

  $test->create_testdata($app, "testcap7", $user3);
  $test->create_testdata($app, 0, $user4);

  $test->succeed(
    !$test->run_test($user1, $app, $user3) 
    && !$test->run_test($user1, $app, $user4)
    && $test->run_test($user1, $app, $user1)
    && $test->run_test($user1, $app, $user2)
    &&
    !$test->run_test($user2, $app, $user4)
    && $test->run_test($user2, $app, $user1)
    && $test->run_test($user2, $app, $user2)
    && $test->run_test($user2, $app, $user3)
  );

  $test->cleanup();
}


# 8) - Have a program that has no definitions, (should run as 
# self)

{ # new test
  my $test = new TestCcewrap($cce, "Test 8");
  my $app = $test->create_testapp($BINPATH);
  
  my $user1 = $test->create_user();
  my $user2 = $test->create_user();

  # no defs should allow users to run these programs as themselves
  $test->create_testdata($app);

  $test->succeed( 
    !$test->run_test($user1, $app, $user1)
    && $test->run_test($user1, $app, $user2)
    && !$test->run_test($user2, $app, $user2)
    && $test->run_test($user2, $app, $user1)
  );

  $test->cleanup();
}

# 9) - Have a program that only defines the capability, (should 
# run as self ONLY if they have the capability)

{ # new test
  my $test = new TestCcewrap($cce, "Test 9");
  my $app = $test->create_testapp();
  my $user1 = $test->create_user(["testcap9"]);
  my $user2 = $test->create_user();

  # should run as self if and only if they have this cap
  $test->create_testdata($app, "testcap9");

  $test->succeed(
    !$test->run_test($user1, $app, $user1)
    && !$test->run_test($user1, $app)
    && $test->run_test($user1, $app, $user2)
    && $test->run_test($user2, $app)
    && $test->run_test($user2, $app, $user2)
    && $test->run_test($user2, $app, $user1)
  );

  $test->cleanup();
}

# 10) - Have a program that only defines a user to run as, 
# (should allow everyone to run that program as the given user)
{ # new test
  my $test = new TestCcewrap($cce, "Test 10");
  my $app = $test->create_testapp($BINPATH);
  my $user1 = $test->create_user(["testdummycap"]);
  my $user2 = $test->create_user();

  $test->create_testdata($app, 0, $user1);
  $test->succeed(
    !$test->run_test($user1, $app)
    && !$test->run_test($user1, $app, $user1)
    && $test->run_test($user1, $app, $user2)
    && $test->run_test($user2, $app)
    && !$test->run_test($user2, $app, $user1)
    && $test->run_test($user2, $app, $user2)
  );
  $test->cleanup();
}

# 11) - Have a program that defines the user to run as '<self>'. 
# (should allow all users to run this program as themselves).

{ 
  my $test = new TestCcewrap($cce, "Test 11");
  my $app = $test->create_testapp($BINPATH);
  my $user1 = $test->create_user();
  my $user2 = $test->create_user();
  $test->create_testdata($app, 0, "");
  $test->succeed(
    !$test->run_test($user1, $app)
    && !$test->run_test($user1, $app, $user1)
    && $test->run_test($user1, $app, $user2)
    && !$test->run_test($user2, $app)
    && !$test->run_test($user2, $app, $user2)
    && $test->run_test($user2, $app, $user1)
  );
  $test->cleanup();
}

# 12) - Have a program that defines the user to run as '', with 
# a capability.  (only users with that capability are allowed to 
# run the program, and then only as themselves).

{
  my $test = new TestCcewrap($cce, "Test 12");
  my $app = $test->create_testapp($BINPATH);
  my $user1 = $test->create_user(["testcap12"]);
  my $user2 = $test->create_user();
  my $user3 = $test->create_user();
  # users with testcap12 can run this program as themselves
  $test->create_testdata($app, "testcap12", "");

  $test->succeed(
    !$test->run_test($user1, $app)
    && !$test->run_test($user1, $app, $user1)
    && $test->run_test($user1, $app, $user2)
    && $test->run_test($user1, $app, $user3)
    && $test->run_test($user1, $app, "root")
    && $test->run_test($user2, $app)
    && $test->run_test($user2, $app, $user1)
    && $test->run_test($user2, $app, $user2)
    && $test->run_test($user2, $app, $user3)
    && $test->run_test($user2, $app, "root")
    && $test->run_test($user3, $app)
    && $test->run_test($user3, $app, $user1)
    && $test->run_test($user3, $app, $user2)
    && $test->run_test($user3, $app, $user3)
    && $test->run_test($user3, $app, "root")
  );
  $test->cleanup();
}

# 13) - Have a program with two capabilities associated with it.

{
  my $test = new TestCcewrap($cce, "Test 13");
  my $app = $test->create_testapp();
  my $user1 = $test->create_user(["testcap13a"]);
  my $user2 = $test->create_user(["testcap13b"]);
  my $user3 = $test->create_user();
  my $user4 = $test->create_user();
  $test->create_testdata($app, "testcap13a", $user2);
  $test->create_testdata($app, "testcap13b", $user3);
  $test->create_testdata($app, 0, $user4);

  $test->succeed(
    $test->run_test($user1, $app, $user1)
    && !$test->run_test($user1, $app, $user2)
    && $test->run_test($user1, $app, $user3)
    && !$test->run_test($user1, $app, $user4)
    && $test->run_test($user2, $app, $user1)
    && $test->run_test($user2, $app, $user2)
    && !$test->run_test($user2, $app, $user3)
    && !$test->run_test($user2, $app, $user4)
    && $test->run_test($user3, $app, $user1)
    && $test->run_test($user3, $app, $user2)
    && $test->run_test($user3, $app, $user3)
    && !$test->run_test($user3, $app, $user4)
    && $test->run_test($user4, $app, $user1)
    && $test->run_test($user4, $app, $user2)
    && $test->run_test($user4, $app, $user3)
    && !$test->run_test($user4, $app, $user4)
  );
  $test->cleanup();
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

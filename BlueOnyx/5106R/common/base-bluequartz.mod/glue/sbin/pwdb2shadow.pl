#!/usr/bin/perl -I. -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email
# $Id: $
# Copyright 2008 Project BlueOnyx, All rights reserved.

use Sauce::Util;
use Fcntl;
use DB_File;

my $db_passwd = '/var/db/passwd.db';
my $db_shadow = '/var/db/shadow.db';
my $db_group = '/var/db/group.db';
my %db;
my $file_passwd = '/etc/passwd';
my $file_shadow = '/etc/shadow';
my $file_group = '/etc/group';

my %db;
my %account_passwd;
my %account_group;
my %data_passwd;
my %data_shadow;
my %data_group;

# read account information in /etc/passwd
open (PASS, "<$file_passwd");
while (<PASS>) {
  my($uname, $dummy) = split(':', $_);
  $account_passwd{$uname} = $uname;
}
close (PASS);

# read account inoformation in passwd.db
tie %db, 'DB_File', $db_passwd, O_RDWR, 0600, $DB_BTREE or die;
foreach my $key (keys %db) {
  if ($key =~ /^\.(.*)/) {
    my $id = $1;
    if ($account_passwd{$id}) {
      next;
    }
    $db{$key} =~ s/\x00//g;
    my @array = split(":", $db{$key});
    $data_passwd{$id}{'line'} = $db{$key};
    $data_passwd{$id}{'id'} = $array[2];
  } else {
    next;
  }
}
untie %db;

# write account information to passwd file
Sauce::Util::editfile($file_passwd, *write_file, %data_passwd);
system('/bin/rm -f /etc/passwd.backup.*');

# read account information in shadow.db
tie %db, 'DB_File', $db_shadow, O_RDWR, 0600, $DB_BTREE or die;
foreach my $key (keys %db) {
  if ($key =~ /^\.(.*)/) {
    my $id = $1;
    if ($account_passwd{$id}) {
      next;
    }
    $db{$key} =~ s/\x00//g;
    $data_shadow{$id}{'line'} = $db{$key};
    $data_shadow{$id}{'id'} = $data_passwd{$id}{'id'};
  } else {
    next;
  }
}
untie %db;


# write account information to shadow file
Sauce::Util::editfile($file_shadow, *write_file, %data_shadow);
system('/bin/rm -f /etc/shadow.backup.*');


# read account information in /etc/group
open (GROUP, "<$file_group");
while (<GROUP>) {
  my($uname, $dummy) = split(':', $_);
  $account_group{$uname} = $uname;
}
close (GROUP);

# read account inoformation in group.db
tie %db, 'DB_File', $db_group, O_RDWR, 0600, $DB_BTREE or die;
foreach my $key (keys %db) {
  if ($key =~ /^\.(.*)/) {
    my $id = $1;
    if ($account_group{$id}) {
      next;
    }
    $db{$key} =~ s/\x00//g;
    my @array = split(":", $db{$key});
    $data_group{$id}{'line'} = $db{$key};
    $data_group{$id}{'id'} = $array[2];
  } else {
    next;
  }
}
untie %db;

# write account information to group file
Sauce::Util::editfile($file_group, *write_file, %data_group);
system('/bin/rm -f /etc/group.backup.*');

exit(0);


sub write_file
{
  my ($in, $out, %data)  = @_;

  select $out;
  while (<$in>) {
    print $_;
  }

  foreach my $key (sort {$data{$a}{'id'} <=> $data{$b}{'id'}} keys %data) {
    print $data{$key}{'line'} . "\n";
  }

  return 1;
}


#!/usr/bin/perl
# Author: Brian N. Smith
# Copyright 2006, NuOnce Networks, Inc.  All rights reserved.
# $Id: dnsImport.pl,v 2.1-2 Fri 29 Feb 2008 10:46:06 PM EST mstauber

# This file is based off of Jeff Bilicki's dnsImport.  It has been modified in order
# to allow you to import files from a RaQ550 / TLAS or even CentOS.

use lib "/usr/cmu/perl";
use Global;
use Switch;
use vars qw($cce);
require cmuCCE;
my $cce = new cmuCCE;

my $path = $ARGV[0];
if ( $path eq "" ) {
        print "\n\n";
        print "                         dnsImport.pl v2.1\n\n";
        print "Orginal written by; Jeff Bilicki \n";
        print "Modifications by;   Brian N. Smith - NuOnce Networks\n\n";
        print "dnsImport.pl will import records from\n";
        print "\t\tCobalt RaQ 550\n";
        print "\t\tTurbo Linux Appliance Server (TLAS)\n";
        print "\t\tArgon Appliance Server\n";
        print "\t\tBlue Quartz Server\n\n";
        print "To Import records, you must copy Bind's zone files from your old server\n";
        print "and put them into a directory on this server.  Then run dnsImport.pl\n";
        print "against that directory\n\n";
        print $0 . " /path/to/bind/zone/files\n\n";
} else {
        $cce->connectuds();
        read_dir($path);
        commit_changes();
        $cce->bye("bye-bye miss american pie");
}

exit 0;

sub read_dir {
        my $dir = shift;
        if ( ! ($dir =~ m/\/$/) ) { $dir .= "/"; }

        if ( ! -d $dir ) {
                print "Path much be a directory!\n";
                return;
        }
        opendir(D, $dir);
        my @f = readdir(D);
        closedir(D);
        foreach my $file (@f) {
                if ( $file ne "." and $file ne ".." ) {
                        if ( !($file =~ m/\.include$/) and !($file =~ m/~$/) ) {
                                my $filename = $dir . $file;
                                import_record($dir, $file);
                        }
                }
        }
}

sub import_record {
        my $dir = shift;
        my $file = shift;
        $domainname = $file;
        if ( $domainname =~ /^db/ ) {
                $domainname =~ s/db\.//;
        } else {
                $domainname =~ s/pri\.//;
        }
        $records_file = $dir . $file;
        my @records = read_records($records_file);
        my $soa = "";
        foreach my $rec (@records) {
                chomp $rec;
                $rec =~ s/\s+/ /g;
                $rec =~ tr/[A-Z]/[a-z]/;
                my @line = split(/ /, $rec);
                if ( $line[2] eq "ptr" ) { add_PTR(@line); }
                switch ( $line[2] ) {
                        case "a" { add_A(@line); }
                        case "ns" { add_NS(@line); }
                        case "mx" { add_MX(@line); }
                        case "cname" { add_CNAME(@line); }
                }
                if ( $rec =~ m/in soa/ ) { $start = "1"; }
                if ( ($rec eq "" and $start) or ( $rec =~/end soa header/ and $start) ) { $start = "0"; }
                if ( $start ) { $soa .= $rec . "\n"; }
        }
        ($s, $n) = split(/\)/, $soa);
        $s =~ s/;.*//g; $s =~ s/\n//g; $s =~ s/\s+/ /g;
        mod_SOA($s, $n);
        @records = ""; $n = $s = "";
}

sub mod_SOA {
        my $sa = shift;
        my $dns = shift;
        my $hash = {};
        my $base = $domainname;

        if ( $base =~ /(\d+\.\d+\.\d+\.\d+)(\/\d+)/ ) {
                $base = $1;
                ($oid) = $cce->find('DnsSOA', { ipaddr => $base });
        } else {
                ($oid) = $cce->find('DnsSOA', { domainname => $base });
        }
        if(!$oid) {
                print "Cannot find DNS SOA record for $base\n";
                return;
        }

        my @soa = split(/ /, $sa);

        $email = ""; $email = $soa[4]; $email =~ s/\./\@/; $email =~ s/\.$//;
        $dns1 = ""; $dns1 = $soa[3];$dns1 =~ s/\.$//;

        @dns_servers = split("\n", $dns);
        foreach $server(@dns_servers) {
                $server =~ chomp;
                $server =~ s/\s+/ /g;
                $server =~ tr/[A-Z]/[a-z]/;
                if ( $server =~ /in ns/ ) {
                        @list = split(/ /, $server);
                        $ds = $list[3];
                        $ds =~ s/\.$//;
                        if ( $ds ne $dns1 ) {
                                $hash->{secondary_dns} = $ds;
                        }
                }
        }
        @dns_servers = ""; $dns = "";

        $hash->{primary_dns} = $dns1;
        $hash->{domain_admin} = $email;
        $hash->{refresh} = $soa[7];
        $hash->{retry} = $soa[8];
        $hash->{expire} = $soa[9];
        $hash->{ttl} = $soa[10];

        my ($ok, $bad, @info) = $cce->set($oid, '', $hash);
        if($ok == 0) {
                # $cce->printReturn($ok, $bad, @info);
        } else {
                print "SOA record ", $base, " has been modified sucessfully\n";
        }
}

sub add_A {
        my @record = @_;
        my $hash = {};

        $hostname = $record[0];
        $hostname =~ s/\.$domainname\.//;
        if ($hostname eq "$domainname." ) { $hostname = ""; }

        $hash->{type} = 'A';
        $hash->{hostname} = $hostname;
        $hash->{domainname} = $domainname;
        $hash->{ipaddr} = $record[3];

        my ($ok, $bad, @info) = $cce->create('DnsRecord', $hash);
        if($ok == 0) {
                 # $cce->printReturn($ok, $bad, @info);
        } else {
                print "A record ", $hash->{hostname}, " ", $hash->{domainname},
                        " => ", $hash->{ipaddr}, " has been created sucessfully\n";
        }
}

sub add_MX {
        my @record = @_;
        my $hash = {};

        $hostname = $record[0];
        $hostname =~ s/\.$domainname\.//;
        if ($hostname eq "$domainname." ) { $hostname = ""; }

        $msn = $record[4];
        $msn =~ s/\.$//;

        switch ($record[3]) {
                case "20" { $priority = "very_high"; }
                case "30" { $priority = "high"; }
                case "40" { $priority = "low"; }
                case "50" { $priority = "very_low"; }
        }

        $hash->{type} = 'MX';
        $hash->{hostname} = $hostname;
        $hash->{domainname} = $domainname;
        $hash->{mail_server_priority} = $priority;
        $hash->{mail_server_name} = $msn;

        my ($ok, $bad, @info) = $cce->create('DnsRecord', $hash);
        if($ok == 0) {
                # $cce->printReturn($ok, $bad, @info);
        } else {
                print "MX record ", $hash->{hostname}, " ", $hash->{domainname},
                        " => ", $hash->{mail_server_name}, " with priority ",
                        $hash->{mail_server_priority}, " has been created sucessfully\n";
        }
}

sub add_CNAME {
        my @record = @_;
        my $hash = {};

        $hostname = $record[0];
        $hostname =~ s/\.$domainname\.//;
        if ($hostname eq "$domainname." ) { $hostname = ""; }

        $alias = $record[3];
        ($host, $domain) = split(/\./, $alias, 2);
        $domain =~ s/\.$//;

        $hash->{type} = 'CNAME';
        $hash->{hostname} = $hostname;
        $hash->{domainname} = $domainname;
        $hash->{alias_hostname} = $host;
        $hash->{alias_domainname} = $domain;

        my ($ok, $bad, @info) = $cce->create('DnsRecord', $hash);
        if($ok == 0) {
                # $cce->printReturn($ok, $bad, @info);
        } else { 
                print "CNAME record ", $hash->{hostname}, " ", $hash->{domainname},
                        " => ", $hash->{alias_hostname}, " ", $hash->{alias_domainname},
                        " has been created sucessfully\n";
        }
}

sub add_PTR {
        my @record = @_;
        my $hash = {};

        $hostname = $record[3];
        ($host, $domain) = split(/\./, $hostname, 2);
        $domain =~ s/\.$//;

        $net = $domainname;
        $net =~ s/\.in-addr\.arpa//;
        @ar = split(/\./, $net);
        @ar = reverse(@ar);
        $net = "";
        foreach $i(@ar) {
                $net = $net . "." . $i;
        }
        $net =~ s/^\.//;

        $hash->{type} = 'PTR';
        $hash->{ipaddr} = $net . "." . $record[0];
        $hash->{hostname} = $host;
        $hash->{domainname} = $domain;
        $hash->{netmask} = get_netmask();

        my ($ok, $bad, @info) = $cce->create('DnsRecord', $hash);
        if($ok == 0) {
                # $cce->printReturn($ok, $bad, @info);
        } else {
                print "PTR record ", $hash->{ipaddr}, " => ", $hash->{hostname},
                        " ", $hash->{domainname}, " has been created sucessfully\n";
        }
}

sub add_NS {
        my @record = @_;
        my $hash = {};

        $hostname = $record[0];
        $hostname =~ s/\.$domainname\.//;
        if ($hostname eq "$domainname." ) { return; }

        $dds = $record[3];
        $dds =~ s/\.$//;

        $hash->{type} = 'SN';
        $hash->{hostname} = $hostname;
        $hash->{domainname} = $domainname;
        $hash->{delegate_dns_servers} = $dds;

        my ($ok, $bad, @info) = $cce->create('DnsRecord', $hash);
        if($ok == 0) {
                # $cce->printReturn($ok, $bad, @info);
        } else {
                print "NS record ", $hash->{hostname}, ".", $hash->{domainname}, " => ", $hash->{delegate_dns_servers},
                        " has been created sucessfully\n";
        }
}

sub get_netmask {
        #my $netmask = `ifconfig eth0 | grep "inet addr" | cut -f 4 -d":" | cut -f 1 -d" "`;
        my $netmask = `ifconfig venet0:0 | grep "inet addr" | cut -f 5 -d":" | cut -f 1 -d" "`;
        chomp($netmask);
        return $netmask;
}

sub read_records { 
        my $records = shift;
        if(!open(FH, "<$records")) {
                print "Could open records file: $records\n";
                exit 1;
        }
        my @data = <FH>;
        close(FH);
        return @data;
}

sub commit_changes {
        my $time = time();
        my ($oid) = $cce->find('System');
        if(!$oid) {
                print "Could not find System OID, cannot commit changes\n";
                return;
        }
        $cce->set($oid, 'DNS', { commit => $time });
        $cce->commit();
}

#!/usr/bin/perl -I/usr/sausalito/perl                                                                                                                                                                                                                                 
#                                                                                                                                                                                                                                                                     
# $Id: dnsDeleteAllRecords.pl, Thu 19 Mar 2009 10:24:41 AM EDT mstauber Exp $                                                                                                                                                                                            
# Copyright 2007-2009 Solarspeed Ltd.                                                                                                                                                                                                                                      
#                                                                                                                                                                                                                                                                     
# Script which removes all DNS records from CCE                                                                                                                                                                                                                      
#                                                                                                                                                                                                                                                                     

use lib "/usr/cmu/perl";
use CCE;
use POSIX qw(isalpha);
use Switch;
use vars qw($cce);
use IPC::Open3;
require CmuCfg;
my $cfg = CmuCfg->new(type => 'import');

my $CONFIRM = $ARGV[0];
        print "\n";
        print "dnsDeleteAllRecords.pl V2.0\n";
        print "===========================\n\n";
        print "Author: Michael Stauber\n\n";
        print "*** This script will delete ALL DNS records from your system. Use with care !!! ***\n\n";
if ( $CONFIRM eq "" ) {
	print "To use it, run it with the followinng parameter:\n\n";
        print $0 . " --delete-confirm\n\n";
	exit 1;
} elsif ($CONFIRM eq "--delete-confirm") {
	print "Examining CCE to find all DNS records in preparation for deletion ... \n\n";
	&do_the_dirty_deeds;
} else {
	print "Aborting without doing anything ...\n\n";
	exit 1;
}

##
# DNS stuff:
##          

sub do_the_dirty_deeds {

    my $DEBUG = 0;
    my $cce = new CCE('Domain' => 'base-vsite');
    $cce->connectuds();

    # Find the relevant objects:
    @oids = $cce->find('DnsRecord', '');
    if ($#oids == 0) {                  
	print "No DNS records of type 'DnsRecord' found in CCE. (Good!)\n";       
	exit(1);                        
    }                                   
    else {                              
	# DNSID found in CCE.           
	foreach $line (@oids) {         
            ($ok, $system) = $cce->get($line);
            $func = push(@ips, $line);        
	}                                         
	# How many entries we got?                   
	$num = $#ips; 
	if ($num == "-1") {                            
	    $num++;
	}
    	print "Found a total of $num 'DnsRecord' entries in CCE.\n";
	# Sort the entries:
	@sorted_ips = sort { $a cmp $b } @ips;    
	# Line them up in the single string $iplist, separated by spaces:
	foreach $value (@sorted_ips) {                                   
    	    if ($num > 0) {                                              
        	$iplist .= $value . ";";                                 
        	$num--;                                                  
    	    }                                                            
    	    else {                                                       
        	$iplist .= $value;                                       
    	    }                                                            
	}                                                                
    }                                                                    

    @oids = $cce->find('DnsSOA', '');
    if ($#oids == 0) {
        print "No DNS records of type 'DnsSOA' found in CCE. (Good!)\n";
	exit(1);
    }
    else {
	# DNSID found in CCE.
	foreach $line (@oids) {
            ($ok, $system) = $cce->get($line);
            #print "Found: $line \n";
            $func = push(@ips, $line);
	}
	# How many entries we got?
	$num = $#ips;
	if ($num == "-1") {
	    $num++;
	}
    	print "Found a total of $num 'DnsSOA' records in CCE.\n";
	# Sort the entries:
	@sorted_ips = sort { $a cmp $b } @ips;
	# Line them up in the single string $iplist, separated by spaces:
	foreach $value (@sorted_ips) {
    	    if ($num > 0) {
        	$iplist .= $value . ";";
        	$num--;
    	    }
    	    else {
        	$iplist .= $value;
    	    }
	}
    }

    @oids = $cce->find('DnsSlaveZone', '');
    if ($#oids == 0) {
	print "No DNS records of type 'DnsSlaveZone' found in CCE. (Good!)\n";
	exit(1);
    }
    else {
	# DNSID found in CCE.
	foreach $line (@oids) {
            ($ok, $system) = $cce->get($line);
            #print "Found: $line \n";
            $func = push(@ips, $line);
	}
	# How many IP's we got?
	$num = $#ips;
	if ($num == "-1") {
	    $num++;
	}
    	print "Found a total of $num 'DnsSlaveZone' records in CCE.\n";
	# Sort the entries:
	@sorted_ips = sort { $a cmp $b } @ips;
	# Line them up in the single string $iplist, separated by spaces:
	foreach $value (@sorted_ips) {
    	    if ($num > 0) {
        	$iplist .= $value . ";";
        	$num--;
    	    }
    	    else {
        	$iplist .= $value;
    	    }
	}
    }

    ## Delete:
    if ($#sorted_ips == -1) {                  
	print "\nThere are no DNS records in CCE that need to be deleted ...\n\n";
    }
    else {
	print "\nDeleting the actual DNS entries from CCE:\n\n";

	# get the admin password to make sure this user is allowed to do it:
	my $password;
	$password = checkPass();
	$cfg->putGlb('adminPassword', $password);

	foreach $value (@sorted_ips) {
    	    print "Destroying OID: $value \n";
    	    ($ok) = $cce->destroy($value);
	}
    }
    $cce->bye('SUCCESS');
    exit(0);
}

sub checkPass
{
        require cmuCCE;

        my $cce = new cmuCCE;
        $cce->connectuds();

        my $password;
        my $retry = 3;
        for(my $i = 0; $i < $retry; $i++) {
                print "Enter admin's password: ";
                system "stty -echo";
                chop($password = <STDIN>);
                system "stty echo";
                if($cce->auth('admin', $password)) { last; }
                else { $password = 0 }
                print "\nInvalid password\n";
        }
        if(!$password) {
                warn "Cannot delete ... exiting.\n";
                exit 1;
        } else {
                print "\nPassword ok.\n";
                return $password;
        }
}


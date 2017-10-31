#!/usr/bin/perl
#
# Origin: https://alteeve.ca/w/Changing_Ethernet_Device_Names_in_EL7_and_Fedora_15%2B
#

use strict;
use warnings;
use IO::Handle;
my $conf = {};

# Read the 'ifconfig -a'
my $dev = "";
my $mac = "";
my $fh  = IO::Handle->new();
my $sc  = "ifconfig -a";
open ($fh, "$sc 2>&1 |") or die "Failed to call: [$sc], error: $!\n";
while (<$fh>)
{
        chomp;
        my $line = $_;
        if ($line =~ /^(\S+):/)
        {
                $dev = $1;
                next if $dev eq "lo";
                $mac = "";
                next;
        }
        if ($line =~ /ether (.*?) /)
        {
                $mac = $1;
                next if not $dev;
                $conf->{$dev} = $mac;
        }
}

foreach my $dev (sort {$a cmp $b} keys %{$conf})
{
        my $if_num = 0;
        my $say_dev = lc($dev);
        my $say_mac = lc($conf->{$dev});
        print "\n# Added by 'write_udev' for detected device '$dev'.\n";
        print "SUBSYSTEM==\"net\", ACTION==\"add\", DRIVERS==\"?*\", ATTR{address}==\"$say_mac\", NAME=\"eth$if_num\"\n";
}

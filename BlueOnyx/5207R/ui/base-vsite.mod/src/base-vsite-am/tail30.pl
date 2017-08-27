#!/usr/bin/perl -w

use strict;
use Date::Parse;
my $ref;                 # The only variable I will use in this.

open IN,"<".$ARGV[0];    # Open (READ) file submited as 1st argument
seek IN,-200,2;          # Jump to 200 character before end of logfile. (This
                         # could not suffice if log file hold very log lines! )
while (<IN>) {           # Until end of logfile...
    $ref=str2time($1) if /^(\S+\s+\d+\s+\d+:\d+:\d+)/;
};                       # store time into $ref variable.
seek IN,0,0;             # Jump back to the begin of file
while (<IN>) {
    print if /^(.{15})\s/&&str2time($1)>$ref-1800;
}


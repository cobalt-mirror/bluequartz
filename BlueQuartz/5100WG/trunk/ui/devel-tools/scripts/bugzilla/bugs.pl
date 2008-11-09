#!/usr/bin/perl
#
# $Id: bugs.pl 3 2003-07-17 15:19:15Z will $
# Duncan Laurie (void@sun.com)
#
# command line wrapper for querying bugzilla
#
## QUERY OPTIONS
#
# status, resolution, platform, os|opsys, priority,
# severity, owner|assignedto, reporter, qa|qacontact, cc,
# product, version, component, milestone, default|summary,
# description|longdesc, url, whiteboard, keywords,
# attachdesc, attachdata, attachmime
#
##

use Getopt::Std;
use vars qw{ $query $querylist %bugs $buglist $q };
use vars qw{ $opt_q $opt_c $opt_g $opt_o };

my $p = $0;
$p =~ s,/[^/]+$,,;
$buglist = "$p/buglist";
$defserver = "bugzilla.sfbay.sun.com";
$defquery  = "platform";
$querylist = {
    platform => {
	-host     => q[bugzilla.sfbay.sun.com],
	component => [qw[rom kernel otherhw health monitor]],
	status    => [qw[NEW ASSIGNED REOPENED]],
	product   => [qw[alpine platform]],
    },
    alpine => {
	-host     => q[mothra.sfbay.sun.com],
	-cgidir   => q[bugzilla-2.10],
	status    => [qw[NEW ASSIGNED REOPENED]],
	product   => [qw[alpine]],
    },
    mine => {
	-host     => q[bugzilla.sfbay.sun.com],
	-cgidir   => q[bugs],
	status    => [qw[NEW ASSIGNED REOPENED]],
	owner     => [qq[duncan.laurie\@sun.com]],
    },
    cc => {
	-host     => q[bugzilla.sfbay.sun.com],
	-cgidir   => q[bugs],
	status    => [qw[NEW ASSIGNED REOPENED]],
	cc        => [qq[duncan.laurie\@sun.com]],
    },
};
$ENV{'COLUMNLIST'} = "changeddate priority resolution owner status component summary";

unless (-x $buglist) {
    print "unable to find buglist: $buglist\n";
    exit 1;
}

getopts('o:c:q:g:');

my $server = $defserver;
my $cgidir = "bugs";

if ($opt_c) {
    $query = join ' ', @ARGV;
} else {
    $opt_q = $defquery unless $opt_q;
    if (defined $querylist->{$opt_q}) {
	my($f,$l);
	for $f (keys %{$querylist->{$opt_q}}) {
	    if ($f =~ m{^-}) {
		if ($f eq '-host') {
		    $server = $querylist->{$opt_q}{$f};
		    $ENV{'BUG_SERVER'} = $server
		}
		elsif ($f eq '-cgidir') {
		    $cgidir = $querylist->{$opt_q}{$f};
		    $ENV{'BUG_CGIDIR'} = $cgidir;
		}
		next;
	    }
	    $l = join ',', @{$querylist->{$opt_q}{$f}};
	    $l =~ s{ }{+}g;
	    $query .= "--$f=$l ";
	}
    } else {
	die "default query not found.\n";
    }
}

if ($opt_o) {
    exec (qq[echo "http://$server/$cgidir/show_bug.cgi?id=$opt_o" | urlview]) or
	print STDERR "couldn't launch browser.\n";
}

if ($opt_g) {
    open WGET, "wget -q -O /dev/stdout http://$server/$cgidir/show_bug.cgi?id=$opt_g |" or
	die "unable to connect to $server\n";
    while (<WGET>) {
	if (m{<B>Description:</B></td>} ... m{<A HREF=query.cgi>Query page</A>}) {
	    s{<.*?>}{}g;
	    s{&\#013;}{\n}gi;
	    s{&quot;}{\"}g;
	    s{&nbsp;}{ }g;
	    s{&lt;}{<}g;
	    s{&gt;}{>}g;
	    s{\s*Description:\s*}{};
	    s{\s*Query page\s*}{};
	    if (m{------}) {
		s{Additional Comments From }{};
	    }
	    print;
	}
    }
    close WGET;
    exit 0;
}

open BUGZ, "$buglist $query |" or die "unable to open buglist: $!\n";

while (<BUGZ>) {
    next unless (m{
	<TR\sVALIGN=TOP\sALIGN=LEFT\sCLASS=.*?><TD>
	<A\sHREF="show_bug.cgi\?id=\d+">(\d+)</A>\s*       # bug id
	<td\sclass=changeddate><nobr>(.*?)</nobr>          # last change
	<td\sclass=priority><nobr>(.*?)</nobr>             # priority
	<td\sclass=resolution><nobr>(.*?)</nobr>           # resolution
	<td\sclass=owner><nobr>(.*?)</nobr>                # owner
	<td\sclass=status><nobr>(.*?)</nobr>               # status
	<td\sclass=component><nobr>(.*?)</nobr>            # component
	<td\sclass=summary>(.*)                            # summary
    }ix);
    my $id = $1;

    $bugs{$id}{id}         = $id;
    $bugs{$id}{changed}    = $2;
    $bugs{$id}{priority}   = $3;
    $bugs{$id}{resolution} = $4;
    $bugs{$id}{owner}      = $5;
    $bugs{$id}{owneremail} = $5;
    $bugs{$id}{status}     = $6;
    $bugs{$id}{component}  = $7;
    $bugs{$id}{summary}    = $8;

    $bugs{$id}{owner} =~ s/\..*//;
}
close BUGZ;

my $id;
my $bug;

# BUG OWNER PRIORITY STATUS COMPONENT SUMMARY
format PRINT_BUG_FULL =
@<<<< @< @<<<<< @<<<<< @<<<<<<<<< ^<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<...
$bug->{id}, $bug->{priority}, $bug->{owner}, $bug->{component}, $bug->{changed}, $bug->{summary}
.

foreach $id (keys %bugs) {
    $bug = $bugs{$id};
    $~ = 'PRINT_BUG_FULL';
    write();
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

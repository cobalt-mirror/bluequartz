#!/usr/bin/perl
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: ssl_get.pl,v 1.6 2001/11/04 22:11:54 pbaltz Exp $
# output the csr or full key and cert to stdout for the specified group
# or the admin server by default

use strict;
use lib qw(/usr/sausalito/perl /usr/sausalito/handlers/base/ssl);
use CCE;
use SSL;
use Base::HomeDir qw(homedir_get_group_dir);

my $DEBUG = 0;
if ($DEBUG) { use Data::Dumper; }

$DEBUG && print STDERR join(' : ', @ARGV), "\n";

# set a sane umask
umask(022);

# files to output
my @files = ();

if ($ARGV[0] eq 'cert')
{
    push @files, 'key', 'certificate';
}
elsif ($ARGV[0] eq 'csr')
{
    push @files, 'request';
}
else
{
    exit(4);
}

# default to reading the admin server cert info
my $cert_dir = '/etc/admserv/certs';

# only bother finding the vsite if a group was passed as the second argument
if ($ARGV[1] ne '')
{
    my $cce = new CCE;
    $cce->connectuds();
  
    $cce->authkey($ENV{CCE_USERNAME}, $ENV{CCE_SESSIONID});

    my ($oid, $ok);
    if ($ARGV[1])
    {
        ($oid) = $cce->find('Vsite', { 'name' => $ARGV[1] });
    }

    if ($ARGV[1])
    {
        ($ok, my $vsite) = $cce->get($oid);
        if (not $ok)
        {
            $cce->bye();
            exit(1);
        }

        if ($vsite->{basedir})
        {
            $cert_dir = "$vsite->{basedir}/$SSL::CERT_DIR";
        }
        else
        {
            $cert_dir = homedir_get_group_dir($ARGV[1], $vsite->{volume}) . '/' 
                            . $SSL::CERT_DIR;
        }
    }
    else # no group, do System
    {
        $cert_dir = '/etc/admserv/certs';
    }

    $cce->bye();
} # end if ($ARGV[1])

for my $file (@files)
{
    if (!open(FILE, "$cert_dir/$file"))
    {
        exit(2);
    }
    my @lines = <FILE>;
    close FILE;

    print @lines;
    print "\n"; # make sure lines get seperated correctly
}

exit(0);
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

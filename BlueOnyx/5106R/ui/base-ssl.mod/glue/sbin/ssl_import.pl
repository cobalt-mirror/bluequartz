#!/usr/bin/perl -w
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: ssl_import.pl,v 1.8.2.1 2002/02/05 21:47:18 pbaltz Exp $
# import an uploaded signed certificate and optionally a private key

use strict;
use lib qw(/usr/sausalito/perl /usr/sausalito/handlers/base/ssl);
use Getopt::Long;
use CCE;
use Base::HomeDir qw(homedir_get_group_dir);
use SSL qw(ssl_get_cert_info ssl_create_directory);

my $DEBUG = 0;

my $group = '';
my $type = '';
my $ca_ident = '';

GetOptions(
    'group:s', \$group,
    'type=s', \$type,
    'ca-ident=s', \$ca_ident
    );

$DEBUG && print STDERR "group=$group type=$type ca-ident=$ca_ident\n";
# make sure the file to look in is actually there
if (! -f $ARGV[0])
{
    $DEBUG && print STDERR "File, '$ARGV[0]', does not exist!\n";
    exit(1);
}

my $cce = new CCE;
$cce->connectuds();

$cce->authkey($ENV{CCE_USERNAME}, $ENV{CCE_SESSIONID});

# start by assuming admin server and then change if necessary
my $cert_dir = '/etc/admserv/certs';
my $oid = 0;
if ($group)
{
    ($oid) = $cce->find('Vsite', { 'name' => $group });
    if (not $oid)
    {
        $DEBUG && print STDERR "Couldn't find vsite with name = $group\n";
        $cce->bye();
        exit(2);
    }

    my ($ok, $vsite) = $cce->get($oid);
    if (not $ok)
    {
        $cce->bye();
        $DEBUG && print STDERR "Couldn't read vsite with oid = $oid\n";
        exit(3);
    }
    
    if ($vsite->{basedir})
    {
        $cert_dir = "$vsite->{basedir}/$SSL::CERT_DIR";
    }
    else
    {
        $cert_dir = homedir_get_group_dir($group, $vsite->{volume}) . '/' .
                    $SSL::CERT_DIR;
    }

    if (!ssl_create_directory(02770, scalar(getgrnam($group)), $cert_dir))
    {
        $DEBUG && print STDERR "Couldn't create $cert_dir!\n";
        $cce->bye();
        exit(13);
    }
}
else
{
    ($oid) = $cce->find('System');
    if (!ssl_create_directory(0700, 0, $cert_dir))
    {
        $DEBUG && print STDERR "Couldn't create $cert_dir!\n";
        $cce->bye();
        exit(13);
    }
}

# now read in uploaded file
my $cert = '';
my $saw_begin = 0;

if ($type eq 'caCert')
{
    $cert .= "# $ca_ident BEGIN\n";
}

$DEBUG && print STDERR "Reading certificate...\n";
open(CERT, "<$ARGV[0]") or exit(4);
my $key_part = '';
my $cert_part = '';
my ($in_key, $in_cert) = (0, 0);
while (my $line = <CERT>)
{
    # skip blank lines
    next if ($line =~ /^\s*$/);
   
    # clean up \r if necessary
    my @lines = split(/[\r\n]+/, $line);
   
    for my $part (@lines)
    {
        if ($part =~ /BEGIN RSA PRIVATE KEY/)
        {
            # hard code because of simpletext ie 5.1.3 issues
            # under OS X
            $key_part .= "-----BEGIN RSA PRIVATE KEY-----\n";
            $in_key = 1;
        }
        elsif ($part =~ /END RSA PRIVATE KEY/)
        {
            # same as BEGIN
            $key_part .= "-----END RSA PRIVATE KEY-----\n";
            $in_key = 0;
        }
        elsif ($in_key)
        {
            $key_part .= "$part\n";
        }
        elsif ($part =~ /\-BEGIN CERTIFICATE\-/)
        {
            $saw_begin++;
            # hard code the begin line due to mac issues
            # with simpletext files
            $cert_part .= "-----BEGIN CERTIFICATE-----\n";
            $in_cert = 1;
        }
        elsif ($part =~ /\-END CERTIFICATE\-/)
        {
            # hard code same as above
            $cert_part .= "-----END CERTIFICATE-----\n";
            $in_cert = 0;
        }
        elsif ($in_cert)
        {
            $cert_part .= "$part\n";
        }
    } # end for my $part (@lines)
} # end while
close(CERT);

$DEBUG && print STDERR "KEY PART:\n$key_part";
$DEBUG && print STDERR "CERT PART:\n$cert_part";

if ($type ne 'caCert')
{
    $cert .= $key_part;
}

$cert .= $cert_part;

if ($type eq 'caCert')
{
    $cert .= "# $ca_ident END\n";
}

# clean up for security reasons, making sure the imported cert
# is in /tmp so we don't blow away anything important
if (!$DEBUG && $ARGV[0] =~ /^\/tmp/)
{
    unlink($ARGV[0]);
}
elsif ($DEBUG)
{
    print STDERR "imported file is $ARGV[0]\n";
}

# make sure there is at most one cert in the file
if ($saw_begin == 0)
{
    # no cert
    $cce->bye();
    exit(11);
}
elsif ($saw_begin > 1)
{
    # too many certs
    $cce->bye();
    exit(12);
}

# ready the import files
my $import_file;
if ($type eq 'caCert')
{
    $import_file = "$cert_dir/.import_ca_cert";
}
else
{
    $import_file = "$cert_dir/.import_cert";
}
    
Sauce::Util::lockfile($import_file);
if (!open(IMPORT, ">$import_file"))
{
    Sauce::Util::unlockfile($import_file);
    $cce->bye();
    exit(15);
}
print IMPORT $cert;
close(IMPORT);

my $cert_info = {};

# if its a ca cert check for duplicate ca identifiers before importing
if ($type eq 'caCert')
{
    # update cce info
    my ($ok, $ssl_info) = $cce->get($oid, 'SSL');
    if (not $ok)
    {
        $DEBUG && print STDERR "cce get failed for SSL\n";
        Sauce::Util::unlockfile($import_file);
        $cce->bye();
        exit(9);
    }

    my @ca_ids = $cce->scalar_to_array($ssl_info->{caCerts});
    if (grep(/^$ca_ident$/, @ca_ids))
    {
        Sauce::Util::unlockfile($import_file);
        $cce->bye();
        exit(10);
    }
    $cert_info->{caCerts} = $cce->array_to_scalar(@ca_ids, $ca_ident);
}

# tell CCE to import the certificate
my ($ok, $badkeys, @info) = $cce->set($oid, 'SSL', { 'importCert' => time() });
Sauce::Util::unlockfile($import_file);
if (!$ok)
{
    $DEBUG && print STDERR join(':', @info), "\n";
    $cce->bye();
    $info[0] =~ /base\-ssl\.(\d+)\]\]/;
    exit($1);
}

# for server certs get the cert info from the uploaded cert
if ($type ne 'caCert')
{
    # fill in fields in cce
    my ($subject, $issuer, $expires) = ssl_get_cert_info($cert_dir);
    
    # munge date because they changed the strtotime function in php
    $expires =~ s/(\d{1,2}:\d{2}:\d{2})(\s+)(\d{4,})/$3$2$1/;
    
    $cert_info = {
                        'country' => $subject->{C},
                        'state' => $subject->{ST},
                        'city' => $subject->{L},
                        'orgName' => $subject->{O},
                        'orgUnit' => $subject->{OU},
                        'email' => $subject->{Email},
                        'expires' => $expires
                    };
} # end if ($type)

($ok) = $cce->set($oid, 'SSL', $cert_info);

$cce->bye();

if (!$ok && $type eq 'caCert')
{
    $DEBUG && print STDERR "couldn't update ca cert info in cce!\n";
    exit(13);
}
elsif (not $ok)
{
    $DEBUG && print STDERR "Couldn't update ssl information!\n";
    exit(7);
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

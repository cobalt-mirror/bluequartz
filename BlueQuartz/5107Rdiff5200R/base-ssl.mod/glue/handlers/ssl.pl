#!/usr/bin/perl -w -I/usr/sausalito/perl/ -I/usr/sausalito/handlers/base/ssl
# $Id: ssl.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc.  All rights reserved.
# update the admin server's certificate on changes to System attributes
# such as hostname, domain name, and identity information

use POSIX;
use CCE;
use SSL qw(
            ssl_set_identity ssl_get_cert_info 
            ssl_error ssl_create_directory
            ssl_check_days_valid
            );

# globals
my $cert_dir = '/etc/admserv/certs';

my $cce = new CCE(Domain => 'base-ssl');
my $errors;

$cce->connectfd();

my $system = $cce->event_object();
my ($ok, $ssl) = $cce->get($cce->event_oid(), 'SSL');

if (! -d $cert_dir)
{
    ssl_create_directory(0700, 0, $cert_dir);
}

# make sure we don't hit 2038 rollover
$ssl->{daysValid} = ssl_check_days_valid($ssl->{daysValid}); 

# setup the certificate
my $ret = ssl_set_identity(
                $ssl->{daysValid},
                $ssl->{country},
                $ssl->{state},
                $ssl->{city},
                $ssl->{orgName},
                $ssl->{orgUnit},
                substr(($system->{hostname} . '.' . $system->{domainname}), 0, 64),
                $ssl->{email},
                $cert_dir
                );
                
# check for errors
if ($ret != 1)
{
    $cce->bye('FAIL', ssl_error($ret));
    exit(1);
}

# read the expiration date from the new certificate
my ($sub, $iss, $date) = ssl_get_cert_info($cert_dir);

# munge date because the php strtotime function changed
$date =~ s/(\d{1,2}:\d{2}:\d{2})(\s+)(\d{4,})/$3$2$1/;

($ok) = $cce->set($cce->event_oid(), 'SSL', { 'expires' => $date });

# failing to set expires is non-fatal
if (not $ok)
{
    $cce->warn('[[base-ssl.cantSetExpires]]');
}

# issue a warning for to long a fqdn
if (length($system->{hostname} . '.' . $system->{domainname}) > 64)
{
    $cce->baddata(0, 'fqdn', 'fqdnTooLongOkay', 
            { 
                'fqdn' => ($system->{hostname} . '.' . $system->{domainname})
            });
}

$cce->bye('SUCCESS');
exit(0);

# keeping this around for ideas in case the qube has trouble using the
# old ssl_set_identity and needs the forking stuff.  although I recommend
# fixing this in SSL.pm
# sub set_identity
# {
#     my $cce = shift;
# 
#     my ($ret,$SysObj) = $cce->get($cce->event_oid());
#     my ($reti,$IdentObj) = $cce->get($cce->event_oid(),"Identity");
# 
#     # We're starting from scratch here.. Make backups if we're going
#     # to be overwriting something.
#     for my $file ( qw(request certificate key) ) {
#         if( -f "$CertDir/$file") {
#             rename("$CertDir/$file","$CertDir/$file.bak");
#         }
#     }
# 
#     # Fork off here as generating certificates takes too long.
#     my $pid = fork();
#     if($pid) 
#   {
#         return 1;
#     }
# 
#     # daemonize myself
#     close(STDIN); close(STDOUT); close(STDERR);
#     my $logfile = "/tmp/ssl.log.$$";
#     open(STDOUT, ">$logfile");
#     open(STDERR, ">&STDOUT");
#     open(STDIN, "</dev/null");
#     POSIX::setsid();
# 
#     # generate key:
#     system("$Openssl_cmd genrsa -out $CertDir/key 1024 1>&2");
# 
#     # generate certificate request:
#     my $cmnd =  "|$Openssl_cmd req -new -config /usr/lib/openssl.cnf "
#         . "-key $CertDir/key -days $CertValidFor -out $CertDir/request 1>&2";
# 
#     open(REQ, $cmnd);
# 
#     print REQ $IdentObj->{country} ."\n";
#     print REQ $IdentObj->{state} . "\n";
#     print REQ $IdentObj->{locality} . "\n";
#     print REQ $IdentObj->{organisation} . "\n";
#     print REQ $IdentObj->{organisationUnit} . "\n";
#     print REQ $SysObj->{hostname} .'.'. $SysObj->{domainname} . "\n";
#     print REQ "$ResponsibleUser@".$SysObj->{hostname}.'.'. $SysObj->{domainname}. "\n";
#     close REQ;    
# 
#     # self-sign certificate request to make certificate
#     system("$Openssl_cmd x509 -days $CertValidFor -req -signkey $CertDir/key "
#           ."-in $CertDir/request -out $CertDir/certificate 1>&2");
# 
#     chmod 0660, "$CertDir/key";
#     chmod 0660, "$CertDir/certificate";
#     chmod 0660, "$CertDir/request";
# 
#     exit 1;
# }
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

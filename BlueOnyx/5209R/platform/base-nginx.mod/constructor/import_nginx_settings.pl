#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: import_nginx_settings.pl
#
# This script parses /etc/nginx/nginx.conf and /etc/nginx/conf.d/default.conf and brings CODB up to date on how Nginx is configured.

# Debugging switch:
$DEBUG = "0";

# Uncomment correct type:
$whatami = "constructor";
#$whatami = "handler";

# Location of nginx.conf:
$nginx_conf = "/etc/nginx/nginx.conf";

# Location of ssl_defaults.conf:
$ssl_default_conf = "/etc/nginx/ssl_defaults.conf";

# Location of https_headers.conf:
$headers_conf = '/etc/nginx/headers.d/https_headers.conf';

# Location of default.conf:
$default_conf = "/etc/nginx/conf.d/default.conf";

#
#### No configureable options below!
#

use CCE;
use Data::Dumper;

use Sys::Hostname::FQDN qw(
        asciihostinfo
        gethostinfo
        fqdn
        short
    );

my $cce = new CCE;

if ($whatami eq "handler") {
    $cce->connectfd();
}
else {
    $cce->connectuds();
}

($name,$aliases,$addrtype,$length,@addrs)=gethostinfo();
$myhost = short();
$fqdn = fqdn();

my @oids = $cce->find("System");
if ($#oids == 0) {
    ($ok, $Nginx) = $cce->get($oids[0], 'Nginx');
}

# HSTS Defaults:
$HSTS = '0';
$HSTS_max_age = '31536000';
$HSTS_include_subdomains = '0';
$CONFIG{'HSTS'} = $HSTS;
$CONFIG{'max_age'} = $HSTS_max_age;
$CONFIG{'include_subdomains'} = $HSTS_include_subdomains;

# Config files present?
if ((-f $nginx_conf) && (-f $ssl_default_conf)) {

    # Array of config switches that we want to update in CCE:
    &items_of_interest;

    # Read, parse and hash nginx.conf:
    &nginx_read;
        
    # Verify input and set defaults if needed:
    &verify;

    # Read, parse and hash ssl_defaults.conf:
    &default_read;

    # Verify ssl_defaults.conf:
    &verify_default;

    # Get hostname from default.conf
    &hostname_read;

    # Verify Server-Name is correct:
    &verify_serverName;

    # Read, parse and hash https_headers.conf:
    &headers_read;

    if ($DEBUG gt "o") {
        print Dumper(\%CONFIG);
    }

    # Shove ouput into CCE:
    &feedthemonster;
}
else {
    # Ok, we have a problem: No nginx.conf found.
    # So we just weep silently and exit. 
    $cce->bye('FAIL', "$nginx_conf not found!");
    exit(1);
}

$cce->bye('SUCCESS');
exit(0);

# List of config switches that we're interested in:
sub items_of_interest {
    @whatweneed_nginx = ( 
        'worker_processes', 
        'worker_connections'
    );
    @whatweneed_default = ( 
        'ssl_session_timeout', 
        'ssl_session_cache', 
        'ssl_session_tickets', 
        'resolver',
        'resolver_valid', 
        'resolver_timeout', 
        'ssl_stapling', 
        'ssl_stapling_verify' 
    );
}


# Read and parse nginx.conf:
sub nginx_read {
    open (F, $nginx_conf) || die "Could not open $nginx_conf: $!";

    while ($line = <F>) {
        chomp($line);
        $line =~ s/^\s+//;                                          # Remove leading whitespaces from lines
        next if $line =~ /^\s*$/;                                   # skip blank lines
        next if $line =~ /^\#*$/;                                   # skip comment lines
        next if $line =~ /^}$/;                                     # skip line starting with }
        next if $line =~ /^server {$/;
        next if $line =~ /^events {$/;
        next if $line =~ /^location/;

        if ($line =~ /^([A-Za-z_\.]\w*)/) {     
            $line =~s/#(.*)$//g;                                    # Remove trailing comments in lines
            $line =~s/\"//g;                                        # Remove double quotation marks
            $line =~s/\;$//g;                                       # Remove trailing semicolon
            @row = split (/\s+/, $line);                            # Split row at the remaining single whitespace
            $parsed_key = shift(@row);                              # Remove first entry in @row and use it as key in $CONFIG
            $CONFIG{$parsed_key} = join("&", @row);                 # Join the remaining values with the ampersand as delimiter
        }
    }
    close(F);

    # At this point we have all switches from nginx.conf cleanly in a hash, split in key / value pairs.
    # To read how "Options" is set we query $CONFIG{'Options'} for example. 

    # For debugging only:
    if ($DEBUG > "1") {
        while (my($k,$v) = each %CONFIG) {
            print "$k => $v\n";
        }
    }
}

sub default_read {
    open (F, $ssl_default_conf) || die "Could not open $ssl_default_conf: $!";

    while ($line = <F>) {
        chomp($line);
        $line =~ s/^\s+//;                                          # Remove leading whitespaces from lines
        next if $line =~ /^\s*$/;                                   # skip blank lines
        next if $line =~ /^\#*$/;                                   # skip comment lines
        next if $line =~ /^}$/;                                     # skip line starting with }
        next if $line =~ /^server {$/;
        next if $line =~ /^events {$/;
        next if $line =~ /^location/;

        if ($line =~ /^([A-Za-z_\.]\w*)/) {     
            $line =~s/#(.*)$//g;                                    # Remove trailing comments in lines
            $line =~s/\"//g;                                        # Remove double quotation marks
            $line =~s/\;$//g;                                       # Remove trailing semicolon

            $line =~s/301 http:\/\///g;
            $line =~s/:444\///g;

            @row = split (/\s+/, $line);                            # Split row at the remaining single whitespace
            $parsed_key = shift(@row);                              # Remove first entry in @row and use it as key in $CONFIG
            $CONFIG{$parsed_key} = join("&", @row);                 # Join the remaining values with the ampersand as delimiter
        }
    }
    close(F);

    # At this point we have all switches from default.conf cleanly in a hash, split in key / value pairs.
    # To read how "Options" is set we query $CONFIG{'Options'} for example. 

    # For debugging only:
    if ($DEBUG eq "1") {
        print "***START*** Vals from default_read{} $ssl_default_conf:\n";
        while (my($k,$v) = each %CONFIG) {
            print "$k => $v\n";
        }
        print "***END*** Vals from default_read{} $ssl_default_conf:\n";
    }
}

sub hostname_read {
    open (F, $default_conf) || die "Could not open $default_conf: $!";

    while ($line = <F>) {
        chomp($line);
        $line =~ s/^\s+//;                                          # Remove leading whitespaces from lines
        next if $line =~ /^\s*$/;                                   # skip blank lines
        next if $line =~ /^\#*$/;                                   # skip comment lines
        next if $line =~ /^}$/;                                     # skip line starting with }
        next if $line =~ /^server {$/;
        next if $line =~ /^events {$/;
        next if $line =~ /^location/;

        if ($line =~ /^([A-Za-z_\.]\w*)/) {     
            $line =~s/#(.*)$//g;                                    # Remove trailing comments in lines
            $line =~s/\"//g;                                        # Remove double quotation marks
            $line =~s/\;$//g;                                       # Remove trailing semicolon

            $line =~s/301 http:\/\///g;
            $line =~s/:444\///g;

            @row = split (/\s+/, $line);                            # Split row at the remaining single whitespace
            $parsed_key = shift(@row);                              # Remove first entry in @row and use it as key in $CONFIG
            $CONFIG{$parsed_key} = join("&", @row);                 # Join the remaining values with the ampersand as delimiter
        }
    }
    close(F);

    # At this point we have all switches from default.conf cleanly in a hash, split in key / value pairs.
    # To read how "Options" is set we query $CONFIG{'Options'} for example. 

    # For debugging only:
    if ($DEBUG eq "1") {
        print "***START*** Vals from hostname_read{} $default_conf:\n";
        while (my($k,$v) = each %CONFIG) {
            print "$k => $v\n";
        }
        print "***END*** Vals from hostname_read{} $default_conf:\n";
    }
}

# Read and parse https_headers.conf:
sub headers_read {
    open (F, $headers_conf) || die "Could not open $headers_conf: $!";

    #add_header Strict-Transport-Security "max-age=30; includeSubDomains" always;

    while ($line = <F>) {
        chomp($line);
        $line =~ s/^\s+//;                                          
        next if !$line =~ /^add_header Strict-Transport-Security(.*)$/;

        if ($line =~ /^([A-Za-z_\.]\w*)/) {     
            $line =~s/#(.*)$//g;                                    # Remove trailing comments in lines
            $line =~s/\"//g;                                        # Remove double quotation marks
            $line =~s/\;$//g;                                       # Remove trailing semicolon
            @row = split (/\s+/, $line);                            # Split row at the remaining single whitespace
            if ($line =~ /^add_header Strict-Transport-Security/ ) {
                $HSTS = '1';
                $CONFIG{'HSTS'} = '1';
            }
            if ($line =~ /add_header Strict-Transport-Security max-age=(.*);/ ) {
                $HSTS_max_age = $1;
                $CONFIG{'max_age'} = $HSTS_max_age;
            }
            if ($line =~ /includeSubDomains/ ) {
                $HSTS_include_subdomains = '1';
                $CONFIG{'include_subdomains'} = '1';
            }
            $parsed_key = shift(@row);                              # Remove first entry in @row and use it as key in $CONFIG
            $CONFIG{$parsed_key} = join("&", @row);                 # Join the remaining values with the ampersand as delimiter
        }
    }
    close(F);

    # At this point we have all switches from nginx.conf cleanly in a hash, split in key / value pairs.
    # To read how "Options" is set we query $CONFIG{'Options'} for example. 

    # For debugging only:
    if ($DEBUG > "1") {
        while (my($k,$v) = each %CONFIG) {
            print "$k => $v\n";
        }
    }
}

sub verify {

    # Go through list of config switches we're interested in:
    foreach $entry (@whatweneed_nginx) {
        if ($entry eq "worker_processes") {
            if ($CONFIG{"$entry"} eq "0") {
                $CONFIG{"$entry"} = 'auto';
            }
        }
        if ($entry eq "worker_connections") {
            if ($CONFIG{"$entry"} eq "0") {
                $CONFIG{"$entry"} = '1024';
            }
        }
        if (!$CONFIG{"$entry"}) {
            # Found key without value and none should be empty! Resetting to defaults then:
            if ($entry eq "worker_processes") {
                $CONFIG{"$entry"} = "auto";
            }
            if ($entry eq "worker_connections") {
                $CONFIG{"$entry"} = "1024";
            }
        }
        # For debugging only:
        if ($DEBUG == "1") {
            print $entry . " = " . $CONFIG{"$entry"} . "\n";
        }
    }
}

sub verify_default {

    # Go through list of config switches we're interested in:
    foreach $entry (@whatweneed_default) {
        if ($entry eq "ssl_session_cache") {
            $CONFIG{"$entry"} =~s/^shared:TLS://g;
            if ($CONFIG{"$entry"} eq "") {
                $CONFIG{"$entry"} = '30m';
            }
        }

        if ($entry eq "resolver") {
            $CONFIG{"$entry"} =~s/^(.*)&valid=//g;
            if ($CONFIG{"$entry"} eq "") {
                $CONFIG{"$entry"} = '30m';
            }
            $CONFIG{'resolver_valid'} = $CONFIG{"$entry"};
        }

        if (!$CONFIG{"$entry"}) {
            # Found key without value and none should be empty! Resetting to defaults then:
            if ($entry eq "ssl_session_timeout") {
                $CONFIG{"$entry"} = "1d";
            }
            if ($entry eq "ssl_session_cache") {
                $CONFIG{"$entry"} = "30m";
            }
            if ($entry eq "ssl_session_tickets") {
                $CONFIG{"$entry"} = "off";
            }
            if ($entry eq "resolver_valid") {
                $CONFIG{"$entry"} = "300s";
            }
            if ($entry eq "resolver_timeout") {
                $CONFIG{"$entry"} = "30s";
            }
            if ($entry eq "ssl_stapling") {
                $CONFIG{"$entry"} = "on";
            }
            if ($entry eq "ssl_stapling_verify") {
                $CONFIG{"$entry"} = "on";
            }
        }
        # For debugging only:
        if ($DEBUG == "1") {
            print $entry . " = " . $CONFIG{"$entry"} . "\n";
        }
    }
}

sub verify_serverName {
    if ($CONFIG{"server_name"} ne $fqdn) {
        print "WARN: Server-Name should be $fqdn, but it is " . $CONFIG{"server_name"} . "!\n";
        $CONFIG{"force_update"} = time();
    }
    else {
        $CONFIG{"force_update"} = $Nginx->{'force_update'}
    }
}

sub feedthemonster {

    @oids = $cce->find('System');
    if ($#oids < 0) {
        # we have major problems if the System object doesn't exist.
        print STDERR "No System object in CCE!\n";
        exit 0;
    }
    else {
        # Object already present in CCE. Updating it, NOT forcing a rewrite of nginx.conf.
        ($ok, $sys) = $cce->get($oids[0], "Nginx");
        ($ok) = $cce->set($oids[0], 'Nginx',{
            'worker_processes' => $CONFIG{'worker_processes'},
            'worker_connections' => $CONFIG{'worker_connections'},
            'ssl_session_timeout' => $CONFIG{'ssl_session_timeout'},
            'ssl_session_cache' => $CONFIG{'ssl_session_cache'},
            'ssl_session_tickets' => $CONFIG{'ssl_session_tickets'},
            'resolver_valid' => $CONFIG{'resolver_valid'},
            'resolver_timeout' => $CONFIG{'resolver_timeout'},
            'ssl_stapling' => $CONFIG{'ssl_stapling'},
            'ssl_stapling_verify' => $CONFIG{'ssl_stapling_verify'},
            'HSTS' => $CONFIG{'HSTS'},
            'max_age' => $CONFIG{'max_age'},
            'include_subdomains' => $CONFIG{'include_subdomains'},
            'force_update' => $CONFIG{'force_update'}
        });
    }
}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2018 Team BlueOnyx, BLUEONYX.IT
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#    notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#    notice, this list of conditions and the following disclaimer in 
#    the documentation and/or other materials provided with the 
#    distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#    contributors may be used to endorse or promote products derived 
#    from this software without specific prior written permission.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
# COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
# POSSIBILITY OF SUCH DAMAGE.
# 
# You acknowledge that this software is not designed or intended for 
# use in the design, construction, operation or maintenance of any 
# nuclear facility.
# 
#!/usr/bin/perl
# $Id: dns_generate.pl Mo 01 Apr 2013 01:42:15 CEST mstauber $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# Rewriting the code to generate the DNS db files 
#
# reminder: the Domain class is defined at the end of this file, and
# is not separate.
# C. Hemsing: Addition for custom include named.conf file
#
#use strict;

my $TTL = 86400; # default failed lookup, delegation

my $DEBUG = 0;
$DEBUG && warn `date`." $0\n";

# global variables:
use vars qw( $cce $errors %workfiles );
$cce = undef;
$errors = 0;
%workfiles = ();
my $named_dir = '/var/named/chroot/var/named';
my $named_conf = '/var/named/chroot/etc/named.conf';
my $named_conf_include = '/var/named/chroot/etc/named.conf.include';
my $named_link = '/etc/named.conf';
my $real_dir = '/var/lib/named/etc/named';
my $db_cache = $named_dir.'/root.hint';
my $var_run = '/var/named/chroot/var/run/named';
my $var_dir = '/var/named';
my $rndc_conf = '/var/named/chroot/etc/rndc.conf';
my $personal_id = $named_dir.'/pri.0.0.127.in-addr.arpa';

my $named_uid = (getpwnam('named'))[2];
my $named_gid = (getgrnam('named'))[2];

##############################################################################
# main:
##############################################################################

# connect to the configuration engine
use lib qw( /usr/sausalito/perl );
use Sauce::Util;
use CCE;
$cce = new CCE;
$cce->connectfd();

# environment audit & corrections
#create_dir() unless (-d $real_dir);
#unless(-l $named_dir) {
#  # /etc/named is not a symlink
#  rename($named_dir, "$named_dir.$$");
#  symlink($real_dir, $named_dir);
#}
unless(-l '/etc/named.conf') {
  # /etc/named.conf should be a symlink to the chroot'd named.conf
  rename($named_link, "$named_link.$$");
  symlink($named_conf, $named_link);
} 

#create_var() unless (-d $var_run);
create_cache() unless (-e $db_cache);
# create_rndc_conf() unless (-e $rndc_conf);
create_selfid() unless (-e $personal_id);
chmod 0770, $named_dir;
#system("chown -R $named_uid:$named_gid $var_dir");

# collate list of domains and networks
my $index = collate_domains_and_networks();

# create databases for domains
foreach my $domainname (keys %$index) {
  my $domain = $index->{$domainname};
  $domain->generate_db();
}

# create the master conf file:
generate_named_conf($index);

if (!$errors) {
  # almost done: do the file shuffle
  swap_links_into_place();
}

# finish
if ($errors) {
  $cce->bye("FAIL");
  exit(1);
} else {
  my ($oid) = $cce->find("System");
  $cce->set($oid, "DNS", { "dirty" => "0" });
  $cce->bye("SUCCESS");
  exit(0);
}

##############################################################################
# collate_domains_and_networks
##############################################################################

sub collate_domains_and_networks ()
{
  # my $cce is global
  my $index = {};
  my @oids;

  # look in all the DnsSOA objects: (this must happen first, so that
  # the SOA record is the first object in the object list.)
  @oids = $cce->find("DnsSOA");
  foreach my $oid (@oids) {
    my ($ok, $obj) = $cce->get($oid);
    if (!$ok) { print STDERR "Very Odd: Could not get object $oid\n"; next; }

    if ($obj->{domainname}) { 
      if (defined($index->{$obj->{domainname}})) {
      	print STDERR "Duplicate SOA for domain \"$obj->{domainname}\" in obj $oid\n";
      } else {
      	my $domobj = Domain->new_domain($obj->{domainname});
	$domobj->{soa} = $oid;

      	$index->{$obj->{domainname}} = $domobj;
      }
    }
    
    my $network = pack_network($obj->{ipaddr}, $obj->{netmask});
    if (defined($network)) { 
      if (defined($index->{$network})) {
      	print STDERR "Duplicate SOA for network \"$network\" in obj $oid\n";
      } else {
      	my $domobj = Domain->new_network($obj->{ipaddr}, $obj->{netmask});
	$domobj->{soa} = $oid;
	$index->{$network} = $domobj;
      }
    }
  }

  # look for all DnsSlaveZone objects:  
  @oids = $cce->find("DnsSlaveZone");
  foreach my $oid (@oids) 
  {
    my ($ok, $obj) = $cce->get($oid);
    if (!$ok) { print STDERR "Very Odd: Could not get object $oid\n"; next; }
    
    my @masters = $cce->scalar_to_array($obj->{masters});
    if ($obj->{domain}) { 
      if (defined($index->{$obj->{domain}})) {
      	print STDERR "Duplicate SOA for domain \"$obj->{domain}\" in obj $oid\n";
      } else {
      	my $domobj = Domain->new_domain($obj->{domain},
	  \@masters ); 
	$domobj->{soa} = $oid;
      	$index->{$obj->{domain}} = $domobj;
      }
    }
    
    my $network = pack_network($obj->{ipaddr}, $obj->{netmask});
    if (defined($network)) { 
      if (defined($index->{$network})) {
      	print STDERR "Duplicate SOA for network \"$network\" in obj $oid\n";
      } else {
      	my $domobj = Domain->new_network($obj->{ipaddr}, $obj->{netmask},
	  \@masters );
	$domobj->{soa} = $oid;
	$index->{$network} = $domobj;
      }
    }
  }
  
  # look in all the DnsRecord objects, seive them into the right domains:
  @oids = $cce->find("DnsRecord");
  foreach my $oid (@oids) {
    my ($ok, $obj) = $cce->get($oid);
    if (!$ok) { print STDERR "Very Odd: Could not get object $oid\n"; next; }

    $_ = $obj->{type};
    if (m/^(A)|(CNAME)|(MX)|(TXT)|(NS)|(SN)$/ && $obj->{domainname}) {
      $DEBUG && warn "Found record $oid $_ with domain: $obj->{domainname}\n";

      if (!defined($index->{$obj->{domainname}})) {
      	$index->{$obj->{domainname}} = Domain->new_domain($obj->{domainname});
      }
      $index->{$obj->{domainname}}->add_record($oid);
      next;
    } 
    elsif (m/^(PTR)|(NS)|(SN)$/)
    {
      $DEBUG && warn "Found record $oid $_ with network: $obj->{network}\n";

      my $network = pack_network($obj->{ipaddr}, $obj->{netmask});
      if (!defined($index->{$network})) {
      	$index->{$network} = Domain->new_network($obj->{ipaddr},
	  $obj->{netmask});
      }
      $index->{$network}->add_record($oid);
      next;
    } 
    else
    {
      print STDERR "type $_ not yet implemented, oid=$oid\n";
      next;
    }
  }
  
  return $index;
}

sub pack_network
{
  my ($ip, $nm) = (shift, shift);
  my (@orig) = ($ip, $nm);
  
  if (!$ip || !$nm) { return undef; }
  
  # normalize netmask to a bitcount: (handle bitcount or dotquad)
  if ($nm =~ m/^\s*(\d+)\s*$/) { 
    $nm = $1; 
  }
  elsif ($nm =~ m/^\s*(\d+)\.(\d+)\.(\d+)\.(\d+)\s*$/) { 
    my @bits = split(//,unpack("B32",pack("C4", $1, $2, $3, $4)));
    $nm = 0; while (@bits) { $nm += shift(@bits); };
  } else {
    return undef;
  }

  # normalize ip (mask out unneeded bits):  
  my $tmpstr = '1' x $nm . '0' x (32-$nm);
  my $mask = pack("B32", $tmpstr);
  my $ipbin = pack("C4", split(/\./, $ip));
  $ip = join(".", unpack("C4", $mask & $ipbin));
    
  # create a "n.n.n.n/m" format network string:
  return "$ip/$nm";
}


sub generate_named_conf()
{
  my $index = shift;
  
  my $fname = $named_conf; # use global definition
  my $tmpfname = $fname . '~';

  # get the system object:
  my $obj = undef;
  {
    my $ok;
    my ($oid) = $main::cce->find("System");
    return undef if (!$oid);
  
    ($ok, $obj) = $main::cce->get($oid, "DNS");
    return undef if (!$ok);
  }
  
  # create the new named.conf file:
  my $fh = new FileHandle(">${tmpfname}");
  if (!defined($fh)) {
    print STDERR "Couldn't write ${tmpfname}: $!\n";
    return 0;
  }
  
  # set up vars:
  my $now = scalar(localtime());
  my $forwarders = "// no forwarders defined";
  if ($obj->{forwarders}) {
    $forwarders = "forwarders { "
      . join("; ", $main::cce->scalar_to_array($obj->{forwarders}))
      . "; };";
  }

  # set up zone transfer access
  my $zoneTransferIps = "// zone transfer access denied\n";
  $zoneTransferIps .= "  allow-transfer { none; };";
  if ($obj->{zone_xfer_ipaddr}) {
    $zoneTransferIps = "allow-transfer { "
      . join("; ", $main::cce->scalar_to_array($obj->{zone_xfer_ipaddr}))
      . "; };\n"
      . "  also-notify { "
      . join("; ", $main::cce->scalar_to_array($obj->{zone_xfer_ipaddr}))
      . "; };\n";
  }
  
  # set up recursion access
  my $recursionInet = "// recursion access denied\n";
  # $recursionInet .= " allow-recursion { none; };";
  if ($obj->{recursion_inetaddr}) {
	if ($obj->{caching_all_allowed}) {
	    $caching_all_allowed = "0.0.0.0/0;"
	}
	$recursionInet = "allow-recursion { $caching_all_allowed "
          . join("; ", $main::cce->scalar_to_array($obj->{recursion_inetaddr}))
          . "; };";
  }

  # set up query access
  my $queryInet = "// query access denied\n";
  # $queryInet .= " allow-query { none; };";
    if ($obj->{query_inetaddr}) {
	if ($obj->{query_all_allowed}) {
	    $query_all_allowed = "0.0.0.0/0;"
	}
        $queryInet = "allow-query { $query_all_allowed "
          . join("; ", $main::cce->scalar_to_array($obj->{query_inetaddr}))
          . "; };";
  }

  # set up rate-limit
  my $rateLimit = "// rate-limits disabled\n";
  # $rateLimit .= " rate-limit { responses-per-second 5; window 5 };";
  if ($obj->{rate_limits_enabled}) {
    $rateLimit = "rate-limit { responses-per-second " . $obj->{responses_per_second} . "; window " .  $obj->{window} . ";};";
  }

  # Set up DNS Sec:
  my $dns_sec = "// dns_sec disabled\n"; 
  if ($obj->{enable_dns_sec}) {
    $dns_sec = "\n  dnssec-enable yes;\n  dnssec-validation yes;\n  dnssec-lookaside auto;\n\n  /* Path to ISC DLV key */\n  bindkeys-file \"/etc/named.iscdlv.key\";\n\n  managed-keys-directory \"/var/named/dynamic\";\n";
  }

  # Set up DNS logging:
  my $dns_log = "// logging disabled\n";
  if ($obj->{enable_dns_logging}) {
    $dns_log = "\nlogging {\n        channel default_debug {\n                file \"data/named.run\";\n                severity dynamic;\n        };\n};\n";
  }

  # set up caching
  my $cache = '// recursion allowed';
  my $cache_hint =<<EOF;
zone "." {
  type hint;
  file "root.hint";
};
EOF

  if (!$obj->{caching}) {
    $cache = 'recursion no;';
    $cache_hint = '';
  }

  print $fh <<EOT;
// BIND9 configuration file
// automatically generated $now
//
// Do not edit this file by hand.  Your changes will be lost the
// next time this file is automatically re-generated.

options {
  directory "/var/named";
  // spoof version for a little more security via obscurity
  version "100.100.100";
  $forwarders
  $zoneTransferIps
  $queryInet
  $recursionInet
  $cache
  $rateLimit
  $dns_sec

};

$dns_log

// key rndc_key {
//   algorithm "hmac-md5";
//   secret "sample";
// };
//
// controls {
//   inet 127.0.0.1 allow { localhost; } keys { rndc_key; };
//   inet 127.0.0.1 allow { localhost; } keys { };
// };

include "/etc/named.conf.include";

$cache_hint

zone "0.0.127.in-addr.arpa" { 
  type master; 
  file "pri.0.0.127.in-addr.arpa";
  notify no; 
};

EOT

  # create zone entry for each domain
  foreach my $domkey (keys %$index) {
    my $domain = $index->{$domkey};
    print $fh $domain->generate_zone_conf();
  }
  
  print $fh "// end of file.\n\n";
  $fh->close();
  
  $main::workfiles{$tmpfname} = $fname;
    
  chown($named_uid, $named_gid, $tmpfname);
  chmod(0644, $tmpfname);
  
  if (!(-e $named_conf_include))
  {
    my $fh = new FileHandle(">$named_conf_include");
    if (!defined($fh))
     {
       print STDERR "Couldn't create $named_conf_include: $!\n";
       return 0;
     }
     print $fh "# $named_conf_include\n";
     print $fh "# user customizations can be added here.\n";
     $fh->close();
     chown($named_uid, $named_gid, $named_conf_include);
     chmod(0644, $named_conf_include);
  }

  return 1;
}

#############################################################################
# swap_links_into_place
#
# swaps real files with temporary files in as close to an atomic
# operation as I can get.  Uses the %workfiles hash to determine
# which files to swap with what.  
#   key -- the file that was just generated
#   value -- the file that we should swap places with
#############################################################################
sub swap_links_into_place()
{
  foreach my $key (keys %workfiles) {
    if (!-e $key) {
      print STDERR "Missing file: $key\n";
      next;
    }
    if (Sauce::Util::switch_files($key, $workfiles{$key}) < 0) {
      print STDERR "Couldn't swap: $key $workfiles{$key}\n";
    }
  }
}

sub create_dir
{
      
    system("umask 0022; /bin/mkdir -p $real_dir > /dev/null 2>&1");
    chmod 0755, $real_dir;
    chown $named_uid, $named_gid, $real_dir;
    
    return 1;
}

sub create_var
{
    my $dir;
    foreach $dir ('/var/lib/named', '/var/lib/named/var', '/var/lib/named/var/run') {
      mkdir($dir, 0755);
      chmod(0755, $dir);
      chown($named_uid, $named_gid, $dir);
    }
    return 1;
}

sub create_selfid
{
    open(LCL, "> $personal_id") || return 0;
    print LCL <<EOF;
\$TTL $TTL
0.0.127.in-addr.arpa. IN SOA localhost. admin.localhost. (
        2000081417
        10800
        3600
        604800
        $TTL
        )
0.0.127.in-addr.arpa.   IN      NS      localhost.
; End SOA Header
;
; Do Not edit BIND db files directly.
; Use the administrative web user interface /admin/ -> Control Panel -> DNS Parameters
; Custom additions may be made by creating a file of the same name as this but with a
; .include suffix.  Click Save Changes in the DNS web interface and the inclusion will be made.
; 
1       in      ptr     localhost.
EOF
    close(LCL);
    chmod(0644, $personal_id);
    chown($named_uid, $named_gid, $personal_id);
}

sub create_cache
{
    open(DBC, "> $db_cache") || return 0;
    print DBC <<EOF;
; <<>> DiG 8.2 <<>> \@f.root-servers.net . ns 
; (1 server found)
;; res options: init recurs defnam dnsrch
;; got answer:
;; ->>HEADER<<- opcode: QUERY, status: NOERROR, id: 10
;; flags: qr aa rd; QUERY: 1, ANSWER: 13, AUTHORITY: 0, ADDITIONAL: 13
;; QUERY SECTION:
;;	., type = NS, class = IN

;; ANSWER SECTION:
.			6D IN NS	G.ROOT-SERVERS.NET.
.			6D IN NS	J.ROOT-SERVERS.NET.
.			6D IN NS	K.ROOT-SERVERS.NET.
.			6D IN NS	L.ROOT-SERVERS.NET.
.			6D IN NS	M.ROOT-SERVERS.NET.
.			6D IN NS	A.ROOT-SERVERS.NET.
.			6D IN NS	H.ROOT-SERVERS.NET.
.			6D IN NS	B.ROOT-SERVERS.NET.
.			6D IN NS	C.ROOT-SERVERS.NET.
.			6D IN NS	D.ROOT-SERVERS.NET.
.			6D IN NS	E.ROOT-SERVERS.NET.
.			6D IN NS	I.ROOT-SERVERS.NET.
.			6D IN NS	F.ROOT-SERVERS.NET.

;; ADDITIONAL SECTION:
G.ROOT-SERVERS.NET.	5w6d16h IN A	192.112.36.4
J.ROOT-SERVERS.NET.	5w6d16h IN A	192.58.128.30
K.ROOT-SERVERS.NET.	5w6d16h IN A	193.0.14.129
L.ROOT-SERVERS.NET.	5w6d16h IN A	198.32.64.12
M.ROOT-SERVERS.NET.	5w6d16h IN A	202.12.27.33
A.ROOT-SERVERS.NET.	5w6d16h IN A	198.41.0.4
H.ROOT-SERVERS.NET.	5w6d16h IN A	128.63.2.53
B.ROOT-SERVERS.NET.	5w6d16h IN A	128.9.0.107
C.ROOT-SERVERS.NET.	5w6d16h IN A	192.33.4.12
D.ROOT-SERVERS.NET.	5w6d16h IN A	128.8.10.90
E.ROOT-SERVERS.NET.	5w6d16h IN A	192.203.230.10
I.ROOT-SERVERS.NET.	5w6d16h IN A	192.36.148.17
F.ROOT-SERVERS.NET.	5w6d16h IN A	192.5.5.241

;; Total query time: 10 msec
;; FROM: power.rc.vix.com to SERVER: f.root-servers.net  192.5.5.241
;; WHEN: Thu Jun  3 14:55:57 1999
;; MSG SIZE  sent: 17  rcvd: 436

EOF
    close(DBC);

    chmod 0644, $db_cache;
    chown $named_uid, $named_gid, $db_cache;

    return 1;
}

sub create_rndc_conf
{
    open(DBC, "> $rndc_conf") || return 0;
    print DBC <<EOF;
key rndc_key {
          algorithm "hmac-md5";
          secret "sample";
};
     options {
          default-server localhost;
          default-key    rndc_key;
};
EOF
    close(DBC);

    return 1;
}


############################################################################
############################################################################
############################################################################
############################################################################
############################################################################
############################################################################
# Domain
############################################################################

package Domain;

use vars qw( $DBDIR $SYSOBJ $HOSTNAME $DOMAINNAME );

BEGIN {
  $DBDIR = $named_dir;
  $SYSOBJ = undef;
  $HOSTNAME = undef;
  $DOMAINNAME = undef;
};

########################################
# new_domain
########################################
sub new_domain
{
  my $proto = shift;
  my $class = ref($proto) || $proto;
  my $self = {};
  bless($self, $class);
  return $self->init(@_);
}

########################################
# new_network
########################################
sub new_network
{
  my $proto = shift;
  my $class = ref($proto) || $proto;
  my $self = {};
  bless($self, $class);
  
  my ($ip, $nm) = (shift, shift);
  my $name = $self->network_to_zone($ip,$nm);
  
  return $self->init($name, @_);
}

sub network_to_zone
{
  my $self = shift;
  my $ip = shift;
  my $nmask = shift;
  my $nbits = netmask_to_netbits($nmask);
  my @ip = split(/\./, $ip);

  # add stuff of 0padded ips
  my @fip;
  for(my $i=0; $i<4; $i++){
    $fip[$i] = sprintf("%03d", $ip[$i]);
  }

  my %zone_formats;
  # define default zone format here (should match /etc/cobaltdns.RFC2317 !!!!)
  %zone_formats = (
    'zone-format-24' =>  "%4/%n.%3.%2.%1.in-addr.arpa",    # 24
    'zone-format-16' =>  "%3/%n.%2.%1.in-addr.arpa",       # 16
    'zone-format-8'  =>  "%2/%n.%1.in-addr.arpa",          # 8
    'zone-format-0'  =>  "%1/%n.in-addr.arpa",             # 0
  );
 
  # what zone format should we use?
  my $zone_format = 'RFC2317';          # nice happy default
  my ($lookfor,$returnpat,
         $zone_format_0,$zone_format_8,$zone_format_16,$zone_format_24,
  );
  if ($main::cce) {
    my $sysobj          = $self->load_system_object();
    $zone_format        = $sysobj->{zone_format};
    $zone_format_0      = $sysobj->{zone_format_0};
    $zone_format_8      = $sysobj->{zone_format_8};
    $zone_format_16     = $sysobj->{zone_format_16};
    $zone_format_24     = $sysobj->{zone_format_24};
  }

  #
  # Either the user has defined their own format (USER), or they are using
  # one of the standard ones RFC2317|DION|OCN-JT  (/etc/cobaltdns.*)
  #
  if ($zone_format eq 'USER') {         # are we using a user defined format?
    $zone_formats{'zone-format-8'}  = $zone_format_8;
    $zone_formats{'zone-format-16'} = $zone_format_16;
    $zone_formats{'zone-format-24'} = $zone_format_24;
    $zone_formats{'zone-format-0'}  = $zone_format_0;
  } elsif (open (CDC, "/etc/cobaltdns.$zone_format")) {
    while ($_ = <CDC> ) {
      chomp;
      ($lookfor, $returnpat) = split(/:\s+/);
      if ($lookfor !~ /^#/) {
        $zone_formats{$lookfor} = $returnpat;
      }
    }
  }
 
  my $domain = ip_to_domain($ip, $nmask);
  if ($zone_formats{$domain}){
    $returnpat = $zone_formats{$domain};
  } else {
    if ($nbits > 24) {
      $returnpat = $zone_formats{"zone-format-24"};
    } elsif ($nbits > 16) {
      $returnpat = $zone_formats{"zone-format-16"};
    } elsif ($nbits > 8) {
      $returnpat = $zone_formats{"zone-format-8"};
    } else {
      $returnpat = $zone_formats{"zone-format-0"};
    }
    $returnpat =~ s/%1/$ip[0]/;
    $returnpat =~ s/%2/$ip[1]/;
    $returnpat =~ s/%3/$ip[2]/;
    $returnpat =~ s/%4/$ip[3]/;
    $returnpat =~ s/%n/$nbits/;
    $returnpat =~ s/%01/$fip[0]/;
    $returnpat =~ s/%02/$fip[1]/;
    $returnpat =~ s/%03/$fip[2]/;
    $returnpat =~ s/%04/$fip[3]/;
    $returnpat =~ s/h// if ($nbits =~ /^(8|16|24)$/);
  }
  return $returnpat;
}

########################################
# init
########################################
sub init
{
  my $self = shift;
  my $name = shift;
  $name =~ s#^(\d+)/(24|16|8)\.#$1\.#g;
  $self->{name} = $name;
  $self->{soa} = undef;
  $self->{records} = [];
  $self->{masters} = shift; # defined if is a slave
  return $self;
}

########################################
# add_record
########################################
sub add_record
{
  my ($self, $oid) = @_;
  push (@{$self->{records}}, $oid);
}

########################################
# get_records
########################################
sub get_records
{
  my $self = shift;
  return @{$self->{records}};
}

########################################
# db_file_name
########################################
sub db_file_name
{
  my $self = shift;
  my $dom = $self->{name};
  $dom =~ tr/\//-/;
  return "db.${dom}"; 
}

########################################
# generate_db
########################################
sub generate_db
{
  my $self = shift;
  my ${DBDIR} = $named_dir;
 
  my $fname = $self->db_file_name();
  my $workfilename = $fname . '~';

  if (defined($self->{masters})) {
    # no need to do anything.
    return 1;
  }

  # verify that the include file exists:
  if (!-e "${DBDIR}/${fname}.include") {
    my $fh = new FileHandle(">${DBDIR}/${fname}.include");
    if ($fh) {
      print $fh "; ${DBDIR}/${fname}.include\n";
      print $fh "; user customizations can be added here.\n\n";
      $fh->close();
    }
    chmod 0644, "${DBDIR}/${fname}.include";
    chown $named_uid, $named_gid, "${DBDIR}/${fname}.include";
  }
  
  # generate the db content:
  my $new_data = <<EOT ;
; ${fname}
;
; This file was automatically generated by dns_generate.pl.  Do not
; edit this file directly.  If you need to make additions to this
; file that CCE does not support, add your extra records to the
; ${fname}.include file.

EOT
  
  $new_data .= $self->generate_soa_record();
  
  foreach my $oid (@{$self->{records}}) {
    $new_data .= $self->generate_dns_record($oid);
  }
  
  $new_data .= <<EOT ;

; User customizations go in this include file:
\$INCLUDE ${fname}.include

EOT

  # open filehandle for new work file:
  my $fh = new FileHandle(">${DBDIR}/${workfilename}");
  if (!defined($fh)) {
    print STDERR "Could not write to ${DBDIR}/${workfilename}: $!\n";
    return 0;
  } else {
    # print STDERR "Writing to ${DBDIR}/${workfilename}\n";
  }
  print $fh $new_data;
  $fh->close();

  chmod 0644, "${DBDIR}/${workfilename}";
  chown $named_uid, $named_gid, "${DBDIR}/${workfilename}";
  
  # store this for later:
  $main::workfiles{"${DBDIR}/${workfilename}"} = "${DBDIR}/${fname}";
  
  return 1;
}

########################################
# load_system_object
########################################
sub load_system_object
{
  my $self = shift;
  return $SYSOBJ if (defined($SYSOBJ));
  
  my ($oid) = $main::cce->find("System");
  return undef if (!$oid);
  
  my ($ok, $obj) = $main::cce->get($oid, "");
  return undef if (!$ok);
  $HOSTNAME = $obj->{hostname};
  $DOMAINNAME = $obj->{domainname};
  
  ($ok, $obj) = $main::cce->get($oid, "DNS");
  return undef if (!$ok);
  
  $SYSOBJ = $obj;
  return $SYSOBJ;
}

########################################
# generate_soa_record
########################################
sub generate_soa_record
{
  my $self = shift;

  my $fname = $self->db_file_name();
  my $db_file = "${named_dir}/${fname}";

  my(@Time) = localtime(time);
  $Time[5] += 1900;
  $Time[4]++;
  for (my $i=0;$i<=5;$i++) {
    $Time[$i] = ($Time[$i] < 10) ? "0$Time[$i]" : "$Time[$i]";
  }
  my $today = "$Time[5]$Time[4]$Time[3]";

  my $serial_number = $today . "01";

  if (-e $db_file) {
    open (IN, "< $db_file");
    while (<IN>) {
      if (/\s*([0-9]{8})([0-9]{2}) ; serial number/) {
        my $date = $1;
        my $suffix = $2;
        if ($date eq $today) {
          $suffix++;
          $serial_number = $today . $suffix;
        }
      }
    }
    close(IN);
  }


  # load System objects for defaults:
  my $sys_obj = $self->load_system_object();

  my $oid = $self->{soa};
  my ($ok, $soa_obj) = $main::cce->get($oid, ""); # || {};

  # find values
  my $local_domain = $self->{name};
  my $ns1 = $soa_obj->{primary_dns} || "${HOSTNAME}.${DOMAINNAME}";
  my $email = $soa_obj->{domain_admin} || "admin\@${ns1}";
  $email =~ s/\@/\./; # BIND8 treats first . as the @ in the rp record
  my $refresh = $soa_obj->{refresh} || $sys_obj->{default_refresh};
  my $retry = $soa_obj->{retry} || $sys_obj->{default_retry};
  my $expire = $soa_obj->{expire} || $sys_obj->{default_expire};
  my $ttl = $soa_obj->{ttl} || $sys_obj->{default_ttl};

  # generate record:
  my( %duplicate_ns ); # ..causes named warnings
  $duplicate_ns{$ns1} = 1;
  my( $soa_record ) = <<EOF;
\$TTL $ttl
$local_domain. IN SOA $ns1. $email. (
	$serial_number ; serial number
        $refresh ; refresh
        $retry ; retry
        $expire ; expire
	$ttl ; ttl
        )
EOF
  # It would be nice to auto-gen glue records here, but we need IP addrs for the NSs
  $soa_record .= "$local_domain.	IN	NS	$ns1.\n";
  my @sec_ns = $main::cce->scalar_to_array($soa_obj->{secondary_dns});

  foreach my $ns2 (@sec_ns) {
    next if ($duplicate_ns{$ns2});
    $soa_record .= "$local_domain.	IN	NS	$ns2.\n";
    $duplicate_ns{$ns2} = 1;
  }
  $soa_record .= "\n";

  return $soa_record;
}

########################################
# generate_dns_record
########################################
sub generate_dns_record
{
  my $self = shift;
  my $oid = shift;

  my ($ok, $obj) = $main::cce->get($oid);
  return "; record skipped: could not read object $oid\n" if (!$ok || !$obj);
  
  if ($obj->{type} eq 'A') { return $self->generate_record_A($obj); }
  if ($obj->{type} eq 'PTR') { return $self->generate_record_PTR($obj); }
  if ($obj->{type} eq 'SN') { return $self->generate_record_SN($obj); }
  if ($obj->{type} eq 'CNAME') { return $self->generate_record_CNAME($obj); }
  if ($obj->{type} eq 'MX') { return $self->generate_record_MX($obj); }
  if ($obj->{type} eq 'TXT') { return $self->generate_record_TXT($obj); }

  if ($obj->{type} eq 'NS') { 
    if ($self->{name} =~ m/in-addr.arpa$/) {
      return $self->generate_record_NS_network($obj); 
    } else {
      return $self->generate_record_NS_domain($obj); 
    }
  }
  
  return "; unknown record type \"$obj->{type}\" in object $oid\n";
}

########################################
# generate_record_A
########################################
sub generate_record_A
{
  my $self = shift;
  my $obj = shift;
  
  return formalize_hostname($obj->{hostname}, $obj->{domainname}) .
    "\tin a $obj->{ipaddr}\n";
}

########################################
# Convert IP address and netmask to a domain name
# eg. 192.168.1.70/26 -> 64/26.1.168.192.in-addr.arpa. 
# ..as per RFC2317.  Note we can use arbitrary assignments.
########################################
sub ip_to_domain
{
  my($ip, $nmask) = @_;
  my(@ipa) = split(/\./, $ip);
  my(@maska) = split(/\./, $nmask);
  my($nbits) = netmask_to_netbits($nmask);
  my($res, $i);

  for($i=3; $i>=0; $i--)
  {
    if($maska[$i])
    {
      $res .= '.' if ($res);
      $res .= (($maska[$i]+0) & ($ipa[$i]+0));
      $res .= "/$nbits" if ($maska[$i] != 255);
    }
  }
  return "$res.in-addr.arpa";
}

########################################
# generate_record_SN
########################################
sub generate_record_SN
{
  my $self = shift;
  my $obj = shift;

  my $db_data;
  my @remote_servers = $main::cce->scalar_to_array($obj->{delegate_dns_servers});

  $DEBUG && warn "generate_record_SN invoked, type $obj->{type}, hn $obj->{hostname}, dn $obj->{domainname}, nw $obj->{network}\n";
    
  my ($server, %arpa); 
  foreach $server (@remote_servers)
  {
    # terminate fqdn
    $server .= '.' unless (($server =~ /^[\d\.]+$/) || ($server =~ /\.$/));
      
    if($obj->{domainname}) 
    {
      # subdomain
      $db_data .= "; subdomain delegation for $obj->{hostname} to $server\n";
      $db_data .= formalize_hostname($obj->{hostname}, $obj->{domainname}) .
        "\tin ns $server\n";
    } 
    else 
    {
      # subnet
      $db_data .= "; subnet delegation for $obj->{network_delegate} to $server\n";

      my($net_baseip, $net_slash) = split('/', $obj->{network_delegate});
      my(@ip_frag) = split(/\./, $net_baseip);

      if ($net_slash == 16) # Octet Class B
      {
        $db_data .= "$ip_frag[1]\t$TTL\tIN\tNS\t$server\n";
      }
      elsif ($net_slash == 24) # Octet Class C
      {
        $db_data .= "$ip_frag[2]\t$TTL\tIN\tNS\t$server\n";
      }
      else                     
      {
        # Non-octect bounded
        my $node_base = get_network($net_baseip, $net_slash);
            
	my ($node, $node_low, $diff_mask, $for_node, $for_net);

        if ($net_slash > 23)
        {
          $node_base =~ s/\.\d+$/\.REP/;
          $node_low = (split(/\./,$net_baseip))[3];
          $diff_mask = 32;
        }
        elsif ($net_slash > 15)
        {
          $node_base =~ s/\.\d+\.\d+$/\.REP/;
          $node_low = (split(/\./,$net_baseip))[2];
          $diff_mask = 24;
        }
        elsif ($net_slash > 7)
        {
          $node_base =~ s/^(\d+\.)\d+\..*/$1REP/;
          $node_low = (split(/\./,$net_baseip))[1];
          $diff_mask = 16;
        }
        else
        {
          $node_base = 'REP';
          $node_low = (split(/\./,$net_baseip))[0];
          $diff_mask = 8;
        }

        $for_node = $node_base;
        my $node_hi = $node_low+2**($diff_mask-$net_slash) - 1;
        my $node_range = $node_low.'/'.$net_slash;
        $for_node =~ s/REP/$node_range/;
        $node_range = join('.', reverse( split(/\./, $for_node) ) ).'.in.addr.arpa.';

        $db_data .= "; RFC 2317 Compliant Subnet Delegation  <<$node_low-$node_hi>> /$net_slash\n";
        $db_data .= "$node_low/$net_slash\tIN\tNS\t$server\n";
        for ($node = $node_low + 1;
             $node < $node_low+2**($diff_mask-$net_slash); $node++)
        {
          $for_node = $node_base;
          $for_node =~ s/REP/$node/;
          $for_net = join('.', reverse( split(/\./, $for_node) ) ).'.in.addr.arpa.';
          $db_data .= "$node\tIN\tCNAME\t$node\.$node_range\n" unless 
            ($arpa{"$node\.$node_range"});
          $arpa{"$node\.$node_range"} = 1; # avoid duplicate entries for >1 NS
        }
        $db_data .= ";\n"; # db Readability
      }
    }
  }

  $DEBUG && warn "New data:\n$db_data";
  return $db_data;
}

########################################
# generate_record_PTR
########################################
sub generate_record_PTR
{
  my $self = shift;
  my $obj = shift;

  return ip_to_revname($obj->{ipaddr}, $obj->{netmask})
    . "\tin ptr "
    . formalize_hostname($obj->{hostname}, $obj->{domainname})
    . "\n";
}

########################################
# generate_record_CNAME
########################################
sub generate_record_CNAME
{
  my $self = shift;
  my $obj = shift;
  
  # alias:
  my $from = formalize_hostname($obj->{hostname}, $obj->{domainname});
  my $to = formalize_hostname($obj->{alias_hostname}, $obj->{alias_domainname});
  
  return "$from\tin cname $to\n";
}

########################################
# generate_record_MX
########################################
sub generate_record_MX
{
  my $self = shift;
  my $obj = shift;

  # this really should be more restrictive, but it isn't:  
  my $value = 10;
  if ($obj->{mail_server_priority} =~ m/^\s*(\d+)/) { $value = $1; }
  if ($obj->{mail_server_priority} =~ m/^\s*Very_Low/i) { $value = 50; }
  if ($obj->{mail_server_priority} =~ m/^\s*Low/i) { $value = 40; }
  if ($obj->{mail_server_priority} =~ m/^\s*High/i) { $value = 30; }
  if ($obj->{mail_server_priority} =~ m/^\s*Very_High/i) { $value = 20; }

  return
    formalize_hostname($obj->{hostname}, $obj->{domainname})
    . "\tin mx $value "
    . $obj->{mail_server_name} . '.'
    . "\n";
}  

########################################
# generate_record_TXT
########################################
sub generate_record_TXT
{
  my $self = shift;
  my $obj = shift;

  my $fqdn = formalize_hostname($obj->{hostname}, $obj->{domainname});

  return "$fqdn\tin txt \"$obj->{strings}\"\n";
}

########################################
# generate_record_NS_network
########################################
sub generate_record_NS_network
{
  my $self = shift;
  my $obj = shift;
  
  my $dom = formalize_hostmane($obj->{hostname}, $obj->{domainname}); 
  my $text = "";
  my @dns = $main::cce->scalar_to_array($obj->{delegate_dns_servers});
  foreach my $dns (@dns) {
    $text .= "$dom\tin ns $dns.\n";
  }
  
  return $text;
}

########################################
# generate_record_NS_domain
########################################
sub generate_record_NS_domain
{
  my $self = shift;
  my $obj = shift;
  
  my $dom = ip_to_revname($obj->{ipaddr}, $obj->{netmask}); 
  my $text = "";
  my @dns = $main::cce->scalar_to_array($obj->{delegate_dns_servers});
  foreach my $dns (@dns) {
    $text .= "$dom\tin ns $dns.\n";
  }
  
  return $text;
}  

sub get_zone_name
{
  my $self = shift;
  my ($zone_name) = $self->{name};
  return $zone_name;
}  

########################################
# generate_zone_conf
########################################
sub generate_zone_conf
{
  my $self = shift;
  
  my $zone = $self->get_zone_name();
  my $fname = $self->db_file_name();

  if (defined($self->{masters})) {
    my $masters = join("; ", @{$self->{masters}}) . ";";
    return <<EOT ;  #"
zone \"$zone\" {
  type slave;
  file \"$fname\";
  masters { $masters };
};

EOT
  #"
  } else {
    return <<EOT ; #"
zone \"$zone\" {
  type master;
  file \"$fname\"; 
};

EOT
  #"
  }
}

########################################
# parse_netmask
########################################
sub parse_netmask
{
  my $nbits = shift;
  if ($nbits =~ m/^\s*\d+\s*$/) {
    return unpack("C4",pack("B32", '1' x $nbits . '0' x (32 - $nbits) ));
  };
  if ($nbits =~ m/^\s*(\d+)\.(\d+)\.(\d+)\.(\d+)\s*$/) {
    return ($1,$2,$3,$4);
  }
  warn ("Invalid netmask: $nbits\n");
  return (255,255,255,255);
}

########################################
# netmask_to_netbits
#
# convert generalized netmask format to just a simple bit-count.
########################################
sub netmask_to_netbits
{
  my $nbits = shift;
  if ($nbits =~ m/^\s*(\d+)\s*$/) {
    return $1;
  };
  if ($nbits =~ m/^\s*(\d+)\.(\d+)\.(\d+)\.(\d+)\s*$/) {
    my @bits = split(//, unpack("B32", pack("C4",$1,$2,$3,$4)));
    $nbits = 0;
    while (@bits) { $nbits += shift(@bits); }
    return $nbits;
  }
  warn ("Invalid netmask: $nbits\n");
  return (32);
}

########################################
# formalize_hostname
########################################
sub formalize_hostname
{
  my ($hn, $dn) = (shift, shift);
  if ($hn eq '-') { $hn = ""; }
  if ($dn eq '-') { $dn = ""; }

  if ($hn && $dn) {
    return "${hn}.${dn}.";
  }
  if ($hn) {
    return "${hn}";
  }
  if ($dn) {
    return "${dn}.";
  }
  return "invalid.hostname.";
}  

#########################################################################
# Convert IP address to form appropriate to put in revhost auth file.	#
# For instance, 1.2.3.4 in an 8 bit network would be converted to 4.3.2	#
#########################################################################
sub ip_to_revname
{
    my( $ip, $nbits ) = @_;
    my( @ipa ) = split( /\./, $ip );
    my( @maska ) = parse_netmask($nbits);
    my( $res ) = "";
    my( $i );
    for( $i=3; $i>=0; $i-- )
	{
	if ($maska[$i] != 255 )
	    {
	    $res .= "." if ($res ne "" );
	    $res .= $ipa[$i];
	    }
	}
    return $res;
}

########################################
# get_network
#
# apply the netmask on the IP address to get the network IP address
# argument 0 is the IP address of the network
# argument 1 is the netmask of the network
# IP addresses not necessary have 4 octets
# netmask must have 4 octets
# return the IP address of the network
########################################
sub get_network
{
    my ($ipAddr, $netmask)=@_;

    my @bits_to_mask =
    (
    "0.0.0.0", "128.0.0.0", "192.0.0.0", "224.0.0.0",
    "240.0.0.0", "248.0.0.0", "252.0.0.0", "254.0.0.0",
    "255.0.0.0", "255.128.0.0", "255.192.0.0", "255.224.0.0",
    "255.240.0.0", "255.248.0.0", "255.252.0.0", "255.254.0.0",
    "255.255.0.0", "255.255.128.0", "255.255.192.0", "255.255.224.0",
    "255.255.240.0", "255.255.248.0", "255.255.252.0", "255.255.254.0",
    "255.255.255.0", "255.255.255.128", "255.255.255.192", "255.255.255.224",
    "255.255.255.240", "255.255.255.248", "255.255.255.252", "255.255.255.254",
    "255.255.255.255"
    );
    $netmask = $bits_to_mask[$netmask];

    my @ipAddrNums=split /\./, $ipAddr;
    my @netMaskNums=split /\./, $netmask;

    my $i;
    for( $i=0; $i<4; $i++ ) {
        # bitwise apply the netmask
        $ipAddrNums[ $i ]=( $ipAddrNums[ $i ]+0 ) & ( $netMaskNums[ $i ]+0 );
    }

    return join '.', @ipAddrNums;
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

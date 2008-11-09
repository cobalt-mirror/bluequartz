#!/usr/bin/perl -w
# $Id: dns_generate.pl 3 2003-07-17 15:19:15Z will $
#
# Rewriting the code to generate the DNS db files 
#
# reminder: the Domain class is defined at the end of this file, and
# is not separate.

use strict;

# global variables:
use vars qw( $cce $errors %workfiles );
$cce = undef;
$errors = 0;
%workfiles = ();
my $named_dir = '/etc/named';
my $db_cache = '/etc/named/db.cache';
my $personal_id = '/etc/named/pri.0.0.127.in-addr.arpa';

##############################################################################
# main:
##############################################################################

# hack
open(STDERR, "/dev/null") or die "Couldn't open /dev/null: $!\n";

# connect to the configuration engine
use lib qw( /usr/sausalito/perl );
use Sauce::Util;
use CCE;
$cce = new CCE;
$cce->connectfd();

create_directories() unless (-d $named_dir);
create_cache() unless (-e $db_cache);
create_selfid();		# regenerate $personal_id file everytime 

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
      	my $domobj =Domain->new_domain($obj->{domain},
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
    if (m/^(A)|(CNAME)|(MX)|(NS)$/) {
      if (!defined($index->{$obj->{domainname}})) {
      	$index->{$obj->{domainname}} = Domain->new_domain($obj->{domainname});
      }
      $index->{$obj->{domainname}}->add_record($oid);
      next;
    }
    
    if (m/^(PTR)|(NS)$/) {
      my $network = pack_network($obj->{ipaddr}, $obj->{netmask});
      if (!defined($index->{$network})) {
      	$index->{$network} = Domain->new_network($obj->{ipaddr},
	  $obj->{netmask});
      }
      $index->{$network}->add_record($oid);
      next;
    }
    
    if (m/^(SECDOM)|(SECNET)|(DELEDOM)|(DELENET)$/) {
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
  
  my $fname = "/etc/named.conf";
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
  
  # open: create the new named.conf file:
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
  my $zoneTransferIps = "// no zone transfer access defined";
  if ($obj->{zone_xfer_ipaddr}) {
    $zoneTransferIps = "allow-transfer { "
      . join("; ", $main::cce->scalar_to_array($obj->{zone_xfer_ipaddr}))
      . "; };";
  }
  
  print $fh <<EOT ;
// BIND8 configuration file
// automatically generated $now
//
// Do not edit this file by hand.  Your changes will be lost the
// next time this file is automatically re-generated.

options {
  directory "/etc/named";
  version "";
  $forwarders
  $zoneTransferIps
};

zone "." {
  type hint;
  file "db.cache";
};

zone "0.0.127.in-addr.arpa" { 
  type master; 
  file "pri.0.0.127.in-addr.arpa"; 
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

sub create_directories()
{
  mkdir $named_dir,0644;
  chown 0, 0, '/etc/named';
}

#
# plug in values from System.DNS object
#
sub create_selfid
{
    my ($ok,$sysoid,$dnsobj,$serial_number);
    $sysoid = $cce->find("System") or return undef;
    ($ok,$dnsobj) = $cce->get($sysoid, "DNS");
    return undef if (!$ok);
    chomp($serial_number = time());

    open(LCL, "> $personal_id") || return 0;
    print LCL <<"EOF";
\$TTL 86400
0.0.127.in-addr.arpa. IN SOA localhost. admin.localhost. (
        $serial_number
        $dnsobj->{default_refresh}
        $dnsobj->{default_retry}
        $dnsobj->{default_expire}
        $dnsobj->{default_ttl}
        )
0.0.127.in-addr.arpa.   IN      NS      localhost.
; End SOA Header
;
; Do Not edit BIND db files directly.
; Use the administrative web user interface
;	/admin/ -> Control Panel -> DNS Parameters
; Custom additions may be made by creating a file of the same
; name as this but with a .include suffix.
; Click Save Changes in the DNS web interface and the inclusion will be made.
; 
1       in      ptr     localhost.
EOF
    close(LCL);
}

sub create_cache
{
    open(DBC, "> $db_cache") || return 0;
    print DBC <<EOF;
;       This file holds the information on root name servers needed to
;       initialize cache of Internet domain name servers
;       (e.g. reference this file in the "cache  .  <file>"
;       configuration file of BIND domain name servers).
;
;       This file is made available by InterNIC registration services
;       under anonymous FTP as
;           file                /domain/named.root
;           on server           FTP.RS.INTERNIC.NET
;       -OR- under Gopher at    RS.INTERNIC.NET
;           under menu          InterNIC Registration Services (NSI)
;              submenu          InterNIC Registration Archives
;           file                named.root
;
;       last update:    Nov 05, 2002
;       related version of root zone:   2002110501
;
.                        3600000  IN  NS    A.ROOT-SERVERS.NET.
A.ROOT-SERVERS.NET.      3600000      A     198.41.0.4
.                        3600000      NS    B.ROOT-SERVERS.NET.
B.ROOT-SERVERS.NET.      3600000      A     128.9.0.107
.                        3600000      NS    C.ROOT-SERVERS.NET.
C.ROOT-SERVERS.NET.      3600000      A     192.33.4.12
.                        3600000      NS    D.ROOT-SERVERS.NET.
D.ROOT-SERVERS.NET.      3600000      A     128.8.10.90
.                        3600000      NS    E.ROOT-SERVERS.NET.
E.ROOT-SERVERS.NET.      3600000      A     192.203.230.10
.                        3600000      NS    F.ROOT-SERVERS.NET.
F.ROOT-SERVERS.NET.      3600000      A     192.5.5.241
.                        3600000      NS    G.ROOT-SERVERS.NET.
G.ROOT-SERVERS.NET.      3600000      A     192.112.36.4
.                        3600000      NS    H.ROOT-SERVERS.NET.
H.ROOT-SERVERS.NET.      3600000      A     128.63.2.53
.                        3600000      NS    I.ROOT-SERVERS.NET.
I.ROOT-SERVERS.NET.      3600000      A     192.36.148.17
.                        3600000      NS    J.ROOT-SERVERS.NET.
J.ROOT-SERVERS.NET.      3600000      A     192.58.128.30
.                        3600000      NS    K.ROOT-SERVERS.NET.
K.ROOT-SERVERS.NET.      3600000      A     193.0.14.129
.                        3600000      NS    L.ROOT-SERVERS.NET.
L.ROOT-SERVERS.NET.      3600000      A     198.32.64.12
.                        3600000      NS    M.ROOT-SERVERS.NET.
M.ROOT-SERVERS.NET.      3600000      A     202.12.27.33
; End of File
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
  $DBDIR = "/etc/named";
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
  
  # add stuff for 0padded ip addresses
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
  my $zone_format = 'RFC2317';		# nice happy default
  my ($lookfor,$returnpat,
	 $zone_format_0,$zone_format_8,$zone_format_16,$zone_format_24,
  );
  if ($main::cce) {
    my $sysobj		= $self->load_system_object();
    $zone_format	= $sysobj->{zone_format};
    $zone_format_0	= $sysobj->{zone_format_0};
    $zone_format_8	= $sysobj->{zone_format_8};
    $zone_format_16	= $sysobj->{zone_format_16};
    $zone_format_24	= $sysobj->{zone_format_24};
  }

  #
  # Either the user has defined their own format (USER), or they are using
  # one of the standard ones RFC2317|DION|OCN-JT  (/etc/cobaltdns.*)
  #
  if ($zone_format eq 'USER') {		# are we using a user defined format?
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
    close(CDC);
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
  
  my $fname = $self->db_file_name();
  my $workfilename = $fname . '~';

  if (defined($self->{masters})) {
    # no need to do anything.
    return 1;
  }

  # open: verify that the include file exists:
  if (!-e "${DBDIR}/${fname}.include") {
    my $fh = new FileHandle(">${DBDIR}/${fname}.include");
    if ($fh) {
      print $fh "; ${DBDIR}/${fname}.include\n";
      print $fh "; user customizations can be added here.\n\n";
      $fh->close();
    }
    chmod 0755, "${DBDIR}/${fname}.include";
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

  chomp(my $serial_number = time());

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
  if ($obj->{type} eq 'CNAME') { return $self->generate_record_CNAME($obj); }
  if ($obj->{type} eq 'MX') { return $self->generate_record_MX($obj); }

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
  
  return formalize_hostname($obj->{hostname}, $obj->{domainname})
	. "\tin a $obj->{ipaddr}\n";
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
  my $to = formalize_hostname($obj->{alias_hostname}, $obj->{alias_domainname});
  my $from = formalize_hostname($obj->{hostname}, $obj->{domainname});
  
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

#########################################################################
# Convert IP address and netmask to a domain name.  For instance #
# 192.168.1.70 in 26 bits would become 64/26.1.168.192.in-addr.arpa.    #
# From RFC 2317.                                                        #
#########################################################################
sub ip_to_domain
    {
    my( $ip, $nmask ) = @_;
    my( @ipa ) = split( /\./, $ip );
    my( @maska ) = split( /\./, $nmask );
    my( $res ) = "";
    my( $i );
    my( $nbits ) = netmask_to_netbits($nmask);
    for( $i=3; $i>=0; $i-- )
        {
        if( $maska[$i] != 0 )
            {
            $res .= "." if( $res ne "" );
            $res .= ( ($maska[$i]+0) & ($ipa[$i]+0) );
            $res .= "/$nbits" if( $maska[$i] ne 255 );
            }
        }
    return "$res.in-addr.arpa";
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

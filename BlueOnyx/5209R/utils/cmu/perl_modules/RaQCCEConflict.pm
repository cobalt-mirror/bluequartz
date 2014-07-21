# $Id: RaQCCEConflict.pm 930 2004-04-13 22:28:12Z anders $
# Cobalt Networks, Inc http://www.cobalt.com
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.
# Written by:
#	Jeff Bilicki <jeff.bilicki@sun.com>

package RaQCCEConflict;
use strict;

use RaQConflict;

use vars qw(@ISA @EXPORT @EXPORT_OK);
@ISA	= qw(RaQConflict);	
@EXPORT = qw($EX $IM);
@EXPORT_OK = qw();
	
require Archive;

1;

sub new
{
	my $proto = shift;
	my $class = ref($proto) || $proto;
	
	my $expt = shift || die "You must provide an export tree\n";
	my $impt = shift || die "You must provide an import tree\n";
	my $glbConf = shift || die "You must provide config info\n";

	my $self = RaQConflict->new($expt, $impt, $glbConf);
	bless ($self, $class);
	return $self;
}

sub isVsiteSpace {
	my $self = shift; my $scope = shift;
	my $fqdn = shift; my $space = shift;

	if(defined $self->{$scope}->{vsite}->{$fqdn}->{$space}) {
		return 1;
	} else { return 0; }
}
	
sub getVsiteSpace {
	return($_[0]->{$_[1]}->{vsite}->{$_[2]}->{$_[3]});
}

sub getVsiteSpaceAttr {
	return($_[0]->{$_[1]}->{vsite}->{$_[2]}->{$_[3]}->{$_[4]});
}

sub getVsiteDomains
{
	my $self = shift; my $scope = shift; my $attr = shift;

	my $fqdns = {};

	foreach my $site (keys %{ $self->getVsites($scope) }) {
		my $vTree = $self->getVsite($scope, $site);
		$fqdns->{$site} = 'fqdn';
		next if(!defined($vTree->{$attr}));
		if(ref($vTree->{$attr}->{domain}) eq 'ARRAY') {
			foreach my $d (@{ $vTree->{$attr}->{domain} }) { 
				$fqdns->{$d} = $attr;
			}
		} else { 
			if($vTree->{$attr} eq 'on') {
				# this is the vsite domain name 
				$fqdns->{ $vTree->{domain} } = $attr;
			} elsif($vTree->{$attr} eq 'off') {
				next;
			} else { $fqdns->{ $vTree->{$attr}->{domain} } = $attr }
		}
	}
	return $fqdns;
}

sub getWebDomains { return($_[0]->getVsiteDomains($_[1], 'webAliases')); }
sub getEmailDomains { return($_[0]->getVsiteDomains($_[1], 'mailAliases')); }

sub detectVsiteFqdn
{
	my $self = shift;
	my $site = shift || return 0;
	my $sc = shift || $EX;
	my ($ret, $val);

	my $emailDoms = $self->getEmailDomains($sc);
	my $webDoms = $self->getWebDomains($sc);
	if(defined($webDoms->{$site}) || defined($emailDoms->{$site})) {
		my $reslv = Resolve->new(%{ $self->getConflict('vsiteFqdn') });
		$reslv->text("Virtual site name $site conflicts with current state\n");
		$reslv->key($site);
		return($reslv);
	} else { return 1 }
}

sub detectIpService
{
	my $self = shift;
	my $site = shift;
	my $attr = shift || return 1;
	my $sc = shift || $EX;

	#print "DETECTING IP SERIVCE for $site and $attr with scope $sc\n";
	
	my %ip;
	if($attr eq 'ssl') { $attr = "SSL"; }
	elsif($attr eq 'ftp') { $attr = "AnonFtp"; }
	
	foreach my $fqdn (keys %{ $self->getVsites($sc) }) {
		next unless($self->isVsiteSpace($sc, $fqdn, $attr));
		#warn "Scope: ", $sc, " fqdn: ", $fqdn, " attr: ", $attr, "\n";
		if($self->getVsiteSpaceAttr($sc, $fqdn, $attr, 'enabled')) {
			$ip{ $self->getVsiteAttr($sc, $fqdn, 'ipaddr') } = $fqdn;
		}	
	}

	my $vTree = $self->getVsite($IM, $site);
	if($self->isVsiteSpace($IM, $site, $attr)) {
		if((defined $ip{ $vTree->{ipaddr} }) &&
			($self->getVsiteSpaceAttr($IM, $site, $attr, 'enabled')) &&
			($ip{ $vTree->{ipaddr} } ne $site)
		) {
			my $reslv = Resolve->new(%{ $self->getConflict('ipService') });
			$reslv->key($site);
			$reslv->attr('enabled');
			$reslv->nameSpace($attr);
			if($attr eq 'AnonFtp') { $attr = "anonymous ftp" }
			$reslv->text("$site $attr is already active at ip address $vTree->{ipaddr}\n");
			return($reslv);
		} 
	}
	return 0;
}

sub detectDomainAlias
{
	my $self = shift;
	my $site = shift;
	my $attr = shift; 
	my $doms = shift || return 0;

	my ($reslv, $resList);
	my $vTree = $self->getVsite($IM, $site);
	
	foreach my $d (@{ $vTree->{$attr}->{domain} }) { 
		next unless(defined($doms->{$d}));
		$reslv = Resolve->new(%{ $self->getConflict('domainAlias') });
		$reslv->text("$attr alias $d conflicts in $site\n");
		$reslv->key($site);
		$reslv->attr($attr);
		$reslv->attrValue($d);
		push(@{ $resList }, $reslv);
	}
	return($resList) if(ref $resList eq 'ARRAY');
	return 1;
}

sub detectEmailDomains { 
	my $self = shift;
	my $site = shift;
	my $sc = shift || $EX;
	my $doms = $self->getEmailDomains($sc);
	return($self->detectDomainAlias($site, 'mailAliases', $doms)) 
}

sub detectWebDomains { 
	my $self = shift;
	my $site = shift;
	my $sc = shift || $EX;
	my $doms = $self->getWebDomains($sc);
	return($self->detectDomainAlias($site, 'webAliases', $doms)) 
}

sub detectUserNameNumber
{
	my $self = shift;
	my $user = shift;
	my $scope = shift || $EX;

	if($user =~ /^\d/) {
		my $reslv = Resolve->new(%{ $self->getConflict('userNameNumber') });
		$reslv->text("User names cannot begin with a number: $user\n");
		$reslv->key($user);
		return($reslv);
	} else { return 1 }
}



sub removeUser
{
	my $self = shift;
	my $user = shift || return 0;

	# remap user owned files in site web to admin
	my $fqdn = $self->getUserAttr($IM, $user, 'fqdn');
	if($self->getConfig('confOnly') eq 'f') {
		my $arch = Archive->new(type => 'groups',
			destDir => $self->getConfig('destDir'),
			sessID => $self->{sessID},
			archives => $self->getVsiteAttr($IM, $fqdn, 'archives')
		);	
		$arch->xmlAttrConvert($user, 'admin', 'uid');
	}
	$self->removeListMember($fqdn, $user, 'local_recips');
	
	return 1;
}

sub remapUser
{
	my $self = shift;
	my $old = shift;
	my $new = shift || return 0; 
	my @keys;

	my $uTree = $self->getUser($IM, $old);
	my $fqdn = $uTree->{fqdn};

	if($self->getConfig('confOnly') eq 'f') {
		my $arch = Archive->new(type => 'users',
			destDir => $self->getConfig('destDir'),
			sessID => $self->{sessID},
			archives => $self->getUserAttr($IM, $old, 'archives')
		);	
		$arch->xmlAttrConvert($old, $new, 'uid');
	}

	# remap any mail list memberships
	$self->remapListMember($fqdn, $old, $new, 'local_recips');

	if(exists $self->{$IM}->{user}->{$old}) {
		$self->{$IM}->{user}->{$old}->{name} = $new;
	}
	TreeXml::renameNode($old, $new, $self->{$IM}->{user});
	return 1;
}

sub deactVal
{
	my $self = shift;
	my $reslv = shift || return 0;
	
	my $class = $reslv->class();
	my $name = $reslv->key();
	my $attr = $reslv->attr();
	my $space = $reslv->nameSpace();

	if($space) {
		$self->{$IM}->{$class}->{$name}->{$space}->{$attr} = 0;	
	} else { $self->{$IM}->{$class}->{$name}->{$attr} = 0; }
	return 1;
}


1;
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
# $Id: BX5208R.pm

package BX5208R;

use Configurator;
use vars qw(@ISA);
@ISA = qw(Configurator);

use ShellCCE;
use Data::Dumper;
#use strict;

use vars qw($cce $ok $bad @info);
$cce = new ShellCCE;
$cce->connectuds();
#$cce->auth('admin');

sub new
{
	my $proto = shift;
	my $class = ref($proto) || $proto;
	my $self = Configurator->new(@_);
	bless($self, $class);

	return $self;
}

sub cmod
{
	my $self = shift;
	my $class = shift;
	my $id = shift || 'name';
	my $oid;

	if(!defined $self->{$id}) { die "No name provided\n" }

	my @items = @{ $self->{$id} };
	delete $self->{$id};
	foreach my $item (@items) {
		($oid) = $cce->find($class, { $id => $item });
		if(!$oid) { warn  "Could not find OID for $item skipping\n";next }
		$cce->setShell($oid, $self);
		$cce->setNameSpaces($oid, $self);
	}
}

sub cdel
{
	my $self = shift;
	my $class = shift;
	my $name = shift;
	my $id = shift || 'name';
	my ($oid, $ok, $info);

	if(!defined $name) { print "Cdel: No name provided\n";return }

	($oid) = $cce->find($class, { $id => $name });
	if(!$oid) { 
		print  "Could not find OID for $name skipping\n";
		return
	}
	warn "oid is: ", $oid, "\n";
	($ok, @info) = $cce->destroy($oid);
	if(!$ok) {
		print Dumper(@info);
		return;
	} else { return 1; }
}



sub cadduser 
{
	my $self = shift;
	my $oid;
	my $ret = 1;

	if(!defined $self->{name}) { 
		$self->errorExit("No user name provided\n");
	}
	if(!defined $self->{group} && !defined $self->{fqdn}) {
		$self->errorExit("You must provide --fqdn or --group options\n");
	}
	$self->setVsiteParent;
	
	if(defined $self->{capLevels}) {
		$self->checkPass;
	}
	$self->setUserDefaults;


	# we should get the defaults and use them? or are they auto?
	my @users = @{ $self->{name} };
	delete $self->{name};
	foreach my $user (@users) {
		$self->{name} = $user;
		if(!defined $self->{fullName}) { $self->{fullName} = $user };
		($oid) = $cce->createShell($self, 'User');	
		if(!$oid) {
			print "Could not get oid for user ", $self->{name}, "\n";
			$ret = 0;
		} else { $cce->setNameSpaces($oid, $self) }
	}
	return $ret;
}

sub cmoduser
{
	my $self = shift;
	if(defined $self->{capLevels}) {
		$self->checkPass;
	}
	$self->cmod('User');
}

sub caddvsite 
{
	my $self = shift;
	my $oid;
	my $ret = 1;

	if(!defined $self->{domain} && !defined $self->{hostname}) { 
		$self->errorExit("No vsite hostname or domain name provided\n");
	}
	$self->{fqdn} = $self->{hostname}.'.'.$self->{domain};
	
	$self->setVsiteDefaults;

	# we should get the defaults and use them? or are they auto?
	($oid) = $cce->createShell($self, 'Vsite');	
	if(!$oid) {
		print "Could not get oid for user ", $self->{name}, "\n";
		$ret = 0;
	} else { $cce->setNameSpaces($oid, $self) }
	return $ret;
}


sub cmodvsite
{
	my $self = shift;

	if(!defined $self->{name}) { die "No vsite name(s) provided\n" }
	my @names = $self->vsiteNameConvert;
	delete $self->{name};
	@{ $self->{name} } = @names;
	$self->cmod('Vsite');
}

sub cdelvsite
{
	my $self = shift;
	my (@oids, $oid, $ok, @info);

	if(!defined $self->{name}) { die "No vsite name(s) provided\n" }
	my @names = $self->vsiteNameConvert;
	
	delete $self->{name};
	@{ $self->{name} } = @names;
	foreach my $name (@names) {
		@oids = $cce->find('User', { 'site' => $name });
		foreach $oid (@oids) {
			$cce->set($oid, '', { 'noFileCheck' => 1 });
			($ok, @info) = $cce->destroy($oid);
			if(!$ok) {
				print "Could not destroy user oid: $oid\n";
			} else { print "User oid $oid has been destroyed\n"; }
		}
		($ok, @info) = $self->cdel('Vsite', $name);
		if(!$ok) {
			print "Could not destroy vsite $name\n";
		} else { print "Vsite $name has been destroyed\n"; }
	}
}

sub cdeluser
{
	my $self = shift;
	my $oid;
	
	if(!defined $self->{name}) { die "No user name(s) provided\n" }
	foreach my $user (@{ $self->{name} }) {
		($oid) = $cce->find('User', { name => $user });
		if(!$oid) { 
			print "Cannot find oid for user $user\n"; 
			next;
		}
		$cce->set($oid, '', { 'noFileCheck' => 1 });
		($ok, @info) = $cce->destroy($oid);
		if(!$ok) { 
			print "Could not delete user oid: $oid\n"; 
		} else { print "User $user has been destroyed\n"; }
	}
}

sub clistvsite
{
	my $self = shift;

	if(!defined $self->{sort}) { $self->{sort} = 'fqdn' }

	my $data = $self->getClassData('Vsite');
	foreach my $key (sort keys %{ $data }) {
		print $data->{$key}->{fqdn}, "\t";
		print $data->{$key}->{name}, "\t";
		print $data->{$key}->{ipaddr}, "\t";
		print "\n";
	}

}

sub clistuser
{
	my $self = shift;

	if(!defined $self->{sort}) { $self->{sort} = 'name' }

	my $data = $self->getClassData('User');
	foreach my $key (sort keys %{ $data }) {
		print $data->{$key}->{name}, "\t\t";
		print $data->{$key}->{site}, "\t";
		print $data->{$key}->{fqdn}, "\t";
		print $data->{$key}->{fullName}, "\t";
		print "\n";
	}

}


sub getClassData
{
	my $self = shift;
	my $class = shift || return;
	my $key = shift || $self->{sort};

	my $data = {};	
	my ($ok, $obj, @spaces);
	
	if(!$key) { die "getClassData: A key must be provided\n"; }

	my @oids = $cce->find($class);	
	foreach my $oid (@oids) {
		($ok, $obj) = $cce->get($oid);
		if(!$ok) { 
			print "Could not get oid $oid, skipping...\n"; 
			next;
		}
		if(!defined $obj->{$key}) {
			die "Key $key not defined in class $class\n";
		}
		$data->{ $obj->{$key} } = $obj;

		# this export name space data
		#($ok, @spaces) = $cce->names($oid);
		#if(!$ok) { 
		#	print "Could not get namespaces for oid $oid\n"; 
		#} else {
		#	foreach my $space (@spaces) {
		#		($ok, $obj) = $cce->get($oid, $space);
		#		if(!$ok) {
		#			print "Could not get namespace $space for oid $oid\n";
		#			next;
		#		}
		#		#$data->{$key}->{$space} = $obj;
		#	}
		#}
		
	}	
	return $data;
}

sub setVsiteDefaults
{
	my $self = shift;

	my ($oid, $ok, $nSpace);
	($oid) = $cce->find('System');
	if(!$oid) { die "Cannot find System OID\n" }
	#($ok, $nSpace) = $cce->get($oid, 'UserDefaults');
	($ok, $nSpace) = $cce->get($oid, 'VsiteDefaults');
	if($ok == 0) { die "Cannot get VsiteDefaults\n"; }
	
	# These two values need to be set
	if(!defined $self->{Disk}->{quota}) {
		$self->{Disk}->{quota} = $nSpace->{quota};
	}
	if(!defined $self->{maxusers}) {
		$self->{maxusers} = $nSpace->{maxusers};
	}
	# make sure autodns is off, why because I hate it
	$self->{dns_auto} = 0;

}

sub setUserDefaults
{
	my $self = shift;

	my ($oid, $ok, $nSpace);
	($oid) = $cce->find('System');
	if(!$oid) { die "Cannot find System OID\n" }
	($ok, $nSpace) = $cce->get($oid, 'UserDefaults');
	if($ok == 0) { die "Cannot get UserDefaults\n"; }
	
	# These two values need to be set
	if(!defined $self->{Disk}->{quota}) {
		$self->{Disk}->{quota} = $nSpace->{quota};
	}
}



sub fqdnToGroup
{
	my $self = shift;
	my $fqdn = shift || return;
	
	my ($name) = $cce->findMember('Vsite', { fqdn => $fqdn }, undef, 'name');
	if($name) { return $name }
	else { warn "could not convert $fqdn to group\n";return }
}

sub setVsiteParent
{
	my $self = shift;
	my $site;

	if(defined $self->{group}) {
		($site) = $cce->findx("Vsite", { name => $self->{group} });
		if($site) { $self->{site} = $self->{group} }
		else { 
			$self->errorExit("Cannot find virtual site for group: ".
				$self->{group}."\n");
		}
		delete $self->{group};
	} elsif(defined $self->{fqdn}) {
		$site = $self->fqdnToGroup($self->{fqdn});
		if($site) { $self->{site} = $site }
		else {
			$self->errorExit("Cannont find virtual site for fqdn: ".
				$self->{fqdn}."\n");
		}
		delete $self->{fqdn};
	} 
	return $site;
}

sub checkPass
{
	my $self = shift;
	my $password;
	my $retry = 3;

	if(defined $self->{adminPassword}) {
		if($cce->auth('admin', $self->{adminPassword})) {
			$password = $self->{adminPassword};
			delete $self->{adminPassword};
		} else { $password = 0 }
	} else {
		for(my $i = 0; $i < $retry; $i++) {
			print "Enter admin's password: ";
			#system "/bin/stty -echo";
			chop($password = <STDIN>);
			#system "/bin/stty echo";
			if($cce->auth('admin', $password)) { last; }
			else { $password = 0 }
			print "\nInvalid password\n";
		}
	}
	if(!$password) { 
		warn "Cannot add user....exiting.\n";
		exit 1;
	} else {
		print "\nPassword ok.\n";
		return $password;
	}
}

1;

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#	 notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#	 notice, this list of conditions and the following disclaimer in 
#	 the documentation and/or other materials provided with the 
#	 distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#	 contributors may be used to endorse or promote products derived 
#	 from this software without specific prior written permission.
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
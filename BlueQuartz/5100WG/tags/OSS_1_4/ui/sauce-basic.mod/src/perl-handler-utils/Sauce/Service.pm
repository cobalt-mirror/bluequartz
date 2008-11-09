#!/usr/bin/perl

package Sauce::Service;
use vars qw(@ISA @EXPORT @EXPORT_OK);
require Exporter;
@ISA =    qw(Exporter);
@EXPORT = qw(service_get_init  service_set_init  service_run_init 
	     service_toggle_init
	     service_get_inetd service_set_inetd service_restart_inetd
	     service_get_multi_inetd service_set_multi_inetd
	     service_send_signal 
	    );

use lib '/usr/sausalito/perl';
use Sauce::Util;
1;

sub inetd_conf { return '/etc/inetd.conf' };
sub inetd_perm { return 0644; };

sub service_run_init
# changes the init state. it does this in the background by default
# arguments: file, arguments ('start', 'stop', or 'restart')
{
	my ($service, $arg, $options) = @_;
 	my $pid;

	unless ($options =~ /\bnobg\b/) {
	    if ($pid = fork()) {
		waitpid($pid, 0);
		return;
	    }
	    close(STDIN);
	    close(STDOUT);
	    close(STDERR);
	    exit 0 if fork();
	}
	    
	`/etc/rc.d/init.d/$service $arg`;
	exit 0 unless ($options =~ /\bnobg\b/);
}

sub service_toggle_init
# toggle the service
# arguments: $service, $newstate
{
    my ($service, $new, $options) = @_;
    if ($new) {
      service_set_init($service, 'on');
      service_run_init($service, 'restart', $options);
    } else {
      service_set_init($service, 'off');
      service_run_init($service, 'stop', $options);
    }
}


sub service_get_init
# get the state of the service in the given runlevel
# arguments: state, runlevel (defaults to 3 if not specified)
{
	my ($service, $state) = @_;
	$state ||= 3;
	
	my $status = `/sbin/chkconfig --list $service`;
	return ($status =~ /\b$state:on\b/) ? 1 : 0;
}

sub service_set_init
# set the given service state to on or off
# arguments: service name, state, list of runlevels
{
	my ($service, $state, @runlevels) = @_;
	my $level;
	
	if (@runlevels) {
        	$level = ' --level ';
        	$level .= join('',@runlevels);
		$state = 'off' unless $state eq 'on';
    		`/sbin/chkconfig $level $service $state`;
	} else {
		my $cmd = ($state eq 'on') ? '--add' : '--del';
    		`/sbin/chkconfig $cmd $service`;
	}
}

sub service_get_inetd
# get the state of the service in inetd.conf
# arguments: service
{
	my $service = shift;

	open(INETD, inetd_conf());
	while (<INETD>) {
		next unless /^\s*(\#*)\s*$service\s/;
		close(INETD);
		return $1 =~ /\#/ ? 0 : 'on';
	}
	close(INETD);
}

sub service_get_multi_inetd
# get the state of the service in inetd.conf
# arguments: list of settings
# returns hash of settings/values
{
        my @list = @_;
	my $conf = inetd_conf();
	my $services = ',' . join(',', @list) . ',';
	my ($set, $service, %settings);

	open(INETD, $conf);
	while (<INETD>) {
		next unless /\s*(\#*)\s*(\S+)\s/;
		($set, $service) = ($1, $2);
	        next unless $services =~ /,$service,/;
		$settings{$service} = ($set =~ /\#/ ? 0 : 'on') unless $settings{$service};
	}
	close(INETD);

	return %settings;
}

sub _edit_inetd
{
	my ($input, $output, $service, $enabled) = @_;
	while (<$input>) {
		if (/^[\s\#]*($service\s.*)/) {
			print $output ($enabled eq 'on') ? $1 : "# $1";
			print $output "\n";
			next;
		}
		print $output $_;
	}
	return 0;
}
	
sub _edit_multi_inetd
{
	my ($input, $output, %settings) = @_;
	my $services = ',' . join(',', keys %settings) . ',';
	my ($service, $rest);
	
	while (<$input>) {
		if (/^[\s\#]*(\S+)(\s.*)/) {
		       ($service, $rest) = ($1, $2);
		       if ($services =~ /,$service,/) {
			   print $output ($settings{$service} eq 'on') ? 
			       "$service$rest" : "# $service$rest";  
			   print $output "\n";
			   next;
		       }
	        }
		print $output $_;
	}
	return 0;
}
	
sub service_set_inetd
# get the state of the service in inetd.conf
# arguments: service, state
{
	my ($service, $state) = @_;
	my $ret = Sauce::Util::editfile(inetd_conf(), *_edit_inetd, 
				     $service, $state);
	chmod(inetd_perm(), inetd_conf());
        return $ret;
}

sub service_set_multi_inetd
# get the state of the service in inetd.conf
# arguments: hash of services/settings
{
	my $ret = Sauce::Util::editfile(inetd_conf(), *_edit_multi_inetd, @_);
	chmod(inetd_perm(), inetd_conf());
	return $ret;
}


sub service_send_signal
# send a signal to a process
{
	my ($service, $signal) = @_;
	$signal =~ tr/[a-z]/[A-Z]/;
	`killall -$signal $service`;
}

sub service_restart_inetd
# send sighup to inetd so it rereads /etc/inetd.conf
# this is a backwards compatibility routine. 
{
	service_send_signal('inetd', 'HUP');
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

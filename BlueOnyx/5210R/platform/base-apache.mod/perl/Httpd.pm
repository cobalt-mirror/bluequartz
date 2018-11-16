# Copyright Sun Microsystems, Inc. 2001
# $Id: Httpd.pm,v 1.10 2001/12/12 01:57:15 pbaltz Exp $
# Base::Httpd
# This should be used to provide all the httpd specific paths and provide
# a handler side interface to apache.  The hope is for other modules to be
# able to use this to change configuration info rather than having the other
# modules mess with apache configuration files on their own.

# TODO
# 1. Document all the configuration variables for use by other modules.

package Base::Httpd;

=pod
=head1 NAME

Base::Httpd - provides paths and basic methods to interact with Apache

=head1 SYNOPSIS

 use Base::Httpd;
 use Base::Httpd qw(httpd_add_include httpd_set_allow_override);

 Base::Httpd::httpd_add_include('/file/to/include', 'site1');
 Base::Httpd::httpd_remove_include('/file/to/include', 'site1');
 Base::Httpd::httpd_set_access_rules('Directory', '/dir/to/control', \%info);
 Base::Httpd::httpd_set_allow_override('/directory', 0);
 Base::Httpd::httpd_get_vhost_conf_file('site1');
 Base::Httpd::httpd_add_module('name_module', 'modules/mod_name.so');
 Base::Httpd::httpd_remove_module('name_module', 'modules/mod_name.so');
 Base::Httpd::httpd_set_server_aliases(\@aliases, 'site1');

 $httpd_configuration_dir = $Base::Httpd::conf_dir;

=head1 DESCRIPTION

Base::Httpd is a collection of httpd specific settings and methods that should
be used to avoid the necessity for other modules to edit any of Apache's
configuration files.  When possible, modules should use these methods to make
any necessary changes to Apache's configuration files that are specific to
a particular feature.  Modules should keep their settings in seperate files
in the httpd configuration directory and simple add Include file lines to
Apache configuration files via C<httpd_add_include>.

=head1 EXPORTS

All of the methods provided by Base::Httpd can be imported into the calling
namespace with the standard C<use Module qw(function1 function2 ...);> pragma.
Only methods are available for importing.  All configuration variables must be
used as C<$Base::Httpd::variable> wherever they are used.

=cut

use vars qw(@ISA @EXPORT_OK);

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

require Exporter;
@ISA = qw(Exporter);
@EXPORT_OK = qw(
	httpd_add_include httpd_remove_include 
	httpd_add_module httpd_remove_module
	httpd_set_access_rules httpd_set_allow_override 
	httpd_get_vhost_conf_file
        httpd_set_server_aliases
	);

use lib qw(/usr/sausalito/perl);
use Sauce::Util;

use vars qw($DEBUG);
# debugging flag

# static configuration variables
$Base::Httpd::server_root = '/etc/httpd';
$Base::Httpd::conf_dir = $Base::Httpd::server_root . '/conf';
$Base::Httpd::confd_dir = $Base::Httpd::server_root . '/conf.d';
$Base::Httpd::vhost_dir = $Base::Httpd::conf_dir . '/vhosts';
$Base::Httpd::bin_suexec = '/usr/sbin/suexec';
$Base::Httpd::bin_htpasswd = '/usr/bin/htpasswd';

# this is probably overkill, but these files are the apache standards
$Base::Httpd::httpd_conf_file = $Base::Httpd::conf_dir . '/httpd.conf';
$Base::Httpd::srm_conf_file = $Base::Httpd::conf_dir . '/srm.conf';
$Base::Httpd::access_conf_file = $Base::Httpd::conf_dir . '/access.conf';

=pod

=head1 METHODS

=over 4

=item httpd_add_include($path_to_file_to_include[, $vsite_group_name])

This will add an include to the Apache configuration files using the standard
Include /full/path/to/file directive.  By default, the line will be added to
the main httpd configuration file, httpd.conf.  It returns 0 on failure and 1
on success.

=over 4

=item *

I<$path_to_file_to_include> is the full path to the file that should be 
included.  The file should exist before being passed in, otherwise the method
will fail.

=item *

I<$vsite_group_name> is optional.  If specified, I<$vsite_group_name> should be
the the value of the VirtualHost.name property of a pre-existing VirtualHost
object in the CCE object database.  This will cause the Include line to be added
to the virtual host's configuration file rather than the main configuration
file.

=back

=cut

sub httpd_add_include
{
	my ($file_path, $vhost_name) = @_;

	&debug_msg("In Base::Httpd::httpd_add_include...\n");
	&debug_msg("PATH: $file_path - VHOST: $vhost_name\n");

	# only enforce that it must be the full path (starts with /)
	# and the file/directory must exist
	if ($file_path !~ /^\// || !(-e $file_path))
	{

		&debug_msg("Not full path or non-existent file or directory.\n");
		return 0;
	}

	# figure out which file should be edited
	if ($vhost_name && (-f "$Base::Httpd::vhost_dir/$vhost_name"))
	{
		&debug_msg("Editing vhost file.\n");
		return Sauce::Util::editfile(
				"$Base::Httpd::vhost_dir/$vhost_name",
				*_include_in_vhost,
				$file_path, 1);
	}
	elsif ($vhost_name)
	{
		&debug_msg("Specified vhost does not exist: $Base::Httpd::vhost_dir/$vhost_name\n");
		return 0;
	}
	else
	{
		&debug_msg("Editing main httpd configuration.\n");
		return Sauce::Util::editfile(
				$Base::Httpd::httpd_conf_file,
				*_include_in_main,
				$file_path, 1);
	}
}

=pod

=item httpd_remove_include($path_to_file_to_include[, $vsite_group_name])

This does the exact opposite of httpd_add_include.  The arguments should be
exactly the same those passed to httpd_add_include.  If an Include line was added
as C<httpd_add_include('/some/file', 'site1')>, it would be removed by calling
C<httpd_remove_include('/some/file', 'site1')>.  This method returns 0 on failure
and 1 on success.  It should really only fail if I<$vsite_group_name> is the name
of a non-existent virtual host, otherwise there are probably more serious problems.

=cut

sub httpd_remove_include
{
	my ($file_path, $vhost_name) = @_;

	&debug_msg("In Base::Httpd::httpd_remove_include...\n");
	&debug_msg("FILE: $file_path - VHOST: $vhost_name\n");

	# fail if this is a non-existent vhost, this may or may not be the right
	# thing to do.  Maybe it should just succeed in this case.
	if ($vhost_name && !(-f "$Base::Httpd::vhost_dir/$vhost_name"))
	{
		return 0;
	}

	if ($vhost_name)
	{
		&debug_msg("Editing vhost configuration...\n");
		return Sauce::Util::editfile(
				"$Base::Httpd::vhost_dir/$vhost_name",
				*_include_in_vhost,
				$file_path, 0);
	}
	else
	{
		&debug_msg("Editing main configuration...\n");
		return Sauce::Util::editfile(
				$Base::Httpd::httpd_conf_file,
				*_include_in_main,
				$file_path, 0);
	}
}

=pod

=item httpd_add_module(module_name, DSO_filename)

This will add an Apache DSO module to the "Extra Modules" region of the httpd.conf
Apache configurationan file.  Returns 0 on failure and 1 on success.  

=over 4

=item *

I<$module_name> is the nickname for the Apache module.

For example, "imap_module" is the name for the IMAP DSO "mod_imap.so". 

=item *

I<$DSO_filename> is the actual DSO path and filename relative to the Apache configuration
root, $Base::Httpd::server_root.   

For example, "modules/mod_imap.so" is the DSO filename for "imap_module". 

=back

=cut

sub httpd_add_module
{
	my ($module_name, $DSO) = @_;

	if ($DEBUG)
	{
		warn "In Base::Httpd::httpd_add_module...\n";
		warn "Adding module $module_name with DSO: $DSO\n";
		warn "Testing -r $Base::Httpd::server_root/$module_name\n";
	}

	return 0 unless ($module_name && $DSO);
	return 0 unless (-r "$Base::Httpd::server_root/$DSO");

	&debug_msg("Editing main httpd configuration.\n");

	# _module_control($nick, $dso, $add)
	return Sauce::Util::editfile(
			$Base::Httpd::httpd_conf_file,
			*_module_control,
			$module_name, $DSO, 1);
}

=pod

=item httpd_remove_module($module_name, $DSO_filename)

This does the exact opposite of httpd_add_module.  
The arguments are the same as those for http_add_module.

Returns 0 on failure, 1 on success. 

=cut

sub httpd_remove_module
{
	my ($module_name, $DSO) = @_;

	if ($DEBUG)
	{
		warn "In Base::Httpd::httpd_remove_module...\n";
		warn "Module: $module_name\n";
	}

	return 0 unless ($module_name && $DSO);

	return Sauce::Util::editfile(
			$Base::Httpd::httpd_conf_file,
			*_module_control,
			$module_name, $DSO, 0);
}

=pod

=item httpd_set_access_rules($type, $location, \%info)

This will add an access control section to the access.conf file for the
web server.  It can be used to set any aspect of an access conrol section
from Options to Allowoverride.  It returns 0 on failure and 1 on success.
See the apache documentation for how access rules work and which options
are allowed.

=over 4

=item *

I<$type> is one of Directory, DirectoryMatch, Location, LocationMatch, Files,
or FilesMatch.

=item *

I<$location> is the directory, url, file, or shell glob to which to apply the
access section.

=item *

I<\%info> is a reference to a hash containing the directives to place inside
the access section.  Passing nothing for this argument will result in the
access section specified by the I<$type> and I<$location> arguments being
removed from the access configuration file.  Normally, the hash will contain
directives such as Options as the keys pointing to lists of values that should
be applied to that directive.  Specifying a directive with no value will remove
that directive from the specified section.

For example, calling this function with the following arguments:

$type = 'Directory'
$location = '/home/httpd'
\%info = {
		'Options' => [ 'Indexes', 'Includes' ],
		'AllowOverride' => [ 'AuthConfig' ],
		'FileSections' => { '.ht*' => {
						        'order' => [ 'deny', 'allow' ],
						        'allow' => [ 'all', 'foo.org' ],
						        'deny' => [ 'bar.org' ]
					            },
                             '.txt' => { 'allow' => [ 'all' ] }
                            },
		'order' => [ 'deny', 'all' ],
		'allow' => [ 'all' ],
		'deny' => [ 'bar.org' ]
	}

would result in the following section being added to the access configuration
file:

<Directory '/home/httpd'>
Options Indexes Includes
AllowOverride AuthConfig

order deny, allow
deny from bar.org
allow from all

<Files '.ht*'>
order deny, allow
allow from all foo.org
deny from bar.org
</Files>
<Files '.txt'>
allow from all
</Files>
</Directory>

=cut

sub httpd_set_access_rules
{
    my ($type, $pattern, $directives) = @_;

    if ($type !~ /^(Directory|Location|Files)(Match)*$/)
    {
        return 0;
    }

    return Sauce::Util::editfile($Base::Httpd::access_conf_file, 
                                *_edit_access_section,
                                $type, $pattern, $directives);
}

=pod

=item httpd_set_allow_override($directory, $enabled)

This will toggle the AllowOverride directive for the directory specified by
I<$directory>.  It returns 0 on failure and 1 on success.

=over 4

=item *

I<$directory> is the directory which the AllowOverride option will apply to.  This
is not optional and must specify the full path to an already existing directory.

=item *

I<$enabled> is a boolean value.  If the value passed for this argument, is 1
the directives C<AllowOverride All> and C<Options All> will be applied to the
directory specified in the first argument.  If the second argument is 0, the 
default Apache AllowOverride and Options settings will be applied to the given
directory.

=back

=cut

sub httpd_set_allow_override
{
	my ($directory, $enabled) = @_;

	# make sure $directory is actually an already existing directory
	if ($directory !~ /^\// || !(-d $directory))
	{
		return 0;
	}

	return Sauce::Util::editfile(
			$Base::Httpd::access_conf_file,
			*_edit_access, $directory, $enabled);
}

=pod

=item httpd_get_vhost_conf_file($vhost_group_name)

This method will return the full path of the file containing the VirtualHost
configuration for the VirtualHost specified by I<$vhost_group_name>.  If
I<$vhost_group_name> is '', it will return '' to indicate failure.  It only
tells where the file should be.  The filename returned may or may not exist
already.

=over 4

=item *

I<$vhost_group_name> should be the system group name used as the value of the
VirtualHost.name property of a VirtualHost object.

=back

=cut

sub httpd_get_vhost_conf_file
{
	my $vhost_name = shift;

	&debug_msg("In Base::Httpd::httpd_get_vhost_conf_file ...\n");
	&debug_msg("VHOST: $vhost_name\n");

	if ($vhost_name) {
		&debug_msg("$Base::Httpd::vhost_dir/$vhost_name\n");
		return "$Base::Httpd::vhost_dir/$vhost_name";
	}
	else {
		return '';
	}
}

=pod

=item httpd_set_server_aliases(\@aliases[, $site])

This will set the aliases that the main server or optionally a virtual host
should answer to in addition to their regular fully-qualified domain name.
It returns 1 on success and 0 on error.

=over 4

=item *

I<\@aliases> is a reference to the list of server aliases that should be set.
Passing a reference to an empty list will remove all aliases.

=item *

I<$site> is optional.  If specified, the server aliases will be set for the
virtual host with the name contained in I<$site>.

=back

=cut

sub httpd_set_server_aliases
{
    my ($aliases, $site) = @_;

    my $file_to_edit = $Base::Httpd::httpd_conf_file;
    if ($site ne '')
    {
	$file_to_edit = httpd_get_vhost_conf_file($site);
	
	if (! -f $file_to_edit)
	{
	    return 0;
	}
    }

    return Sauce::Util::editfile($file_to_edit, *_set_server_aliases, $aliases);
}


# only private methods below, these should not be documented or used
# outside of this module
sub _include_in_vhost
{
	my ($in, $out, $file, $add) = @_;

	my $found = 0;
	my $include_line = "Include $file";

	&debug_msg("ADD: $add - INCLUDE: $include_line\n");

	while (<$in>)
	{
		if (/^$include_line$/ && $add)
		{
			&debug_msg("Found line, leaving it alone.\n");
			$found = 1;
			# print it out if it should be added, already there is the same
			# as adding it
			print $out $_;
		}
		elsif (/^<\/VirtualHost>$/)
		{
			&debug_msg("Found end of file.\n");

			if ($add && !$found)
			{
				&debug_msg("Adding $include_line\n");
				print $out $include_line, "\n";
			}

			# output closing virtual host line
			print $out $_;
		}
		elsif (!/^$include_line$/)
		{
			# just pass through everything else
			if ($DEBUG > 1) { &debug_msg("Passing through $_\n"); }
			print $out $_;
		}
	}

	return 1;
}

# this is different in case there are any VirtualHost blocks in httpd.conf
sub _include_in_main
{
	my ($in, $out, $file, $add) = @_;

	my $found = 0;
	my $include_line = "Include $file";

	while (<$in>)
	{
		if (/^$include_line[\n\r]*$/)
		{
            &debug_msg("Found $include_line\n");
			$found = 1;
            if ($add)
            {
                &debug_msg("Leaving found $include_line\n");
			    print $out $_;
            }
            else
            {
                &debug_msg("Removing found $include_line\n");
            }
		}
		else
		{
			print $out $_;
		}
	}

	if ($add && !$found) { print $out $include_line, "\n"; }

	return 1;
}

sub _module_control
{
	my ($in, $out, $nick, $dso, $add) = @_;

	my $found = 0;
	my $conf = "LoadModule $nick $dso";
	my $stage;

	while (<$in>)
	{
		if (/^$conf[\n\r]*$/)
		{
			$found = 1;
			$stage .= $_ if ($add);
			warn "Found $conf in:\n$_" if ($DEBUG);
		}
		else
		{
			$stage .= $_;
		}
	}
	warn "Found $conf? $found\n" if ($DEBUG);

	if ($add && !$found) 
	{
		if($stage =~ /(#\s*Extra\s+Modules)/)
		{
			my $insert = $1;
			$stage =~ s/$insert/$insert\n$conf/;
		} 
		else
		{
			$stage .= $conf."\n"; 
		} 
	}

	print $out $stage;

	return 1;
}

sub _edit_access_section
{
    my ($in, $out, $type, $pattern, $directives) = @_;

    my $header = '# BEGIN ' . $type . ' ' . $pattern . '.  DO NOT EDIT.';
    my $footer = '# END ' . $type . ' ' . $pattern . '.  DO NOT EDIT.';
    my $found = 0;

    my $regex_safe_pattern = $header;
    $regex_safe_pattern =~ s/([\.\?\*\+\-])/\\$1/g;

    $DEBUG && warn($header);
    $DEBUG && warn($footer);
    
    # first find the section to edit
    while (<$in>)
    {
        if (/^$regex_safe_pattern$/)
        {
            $DEBUG && warn("found $_");
            $found = 1;
            last;
        }
        else
        {
            print $out $_;
        }
    }

    # found it now edit the section
    if ($found && $directives)
    {
        print $out $header, "\n";
    }

    while (<$in>)
    {
        if ($directives && /^(Options|AllowOverride)/)
        {
            my $thing = $1;
            if ($directives->{$thing})
            {
                print $out join(' ', $thing, @{$directives->{$thing}}), "\n";
                delete($directives->{$thing});
            }
            elsif (exists($directives->{$thing}) && !$directives->{$thing})
            {
                delete($directives->{$thing});
                next;
            }
        }
        elsif ($directives->{FileSections} && /^<Files\s+'([^']+)'>$/)
        {
            # roll forward past this Files section
            # FIXME: 
            # Files sections need to be fully specified, if you change one
            # (SORRY!)
            my $files;
            while ($files = <$in>)
            {
                if ($files =~ /^<\/Files>$/) { last; }
            }
        }
        elsif ($directives && /^order/)
        {
            if ($directives->{order})
            {
                print $out 'order ', join(',', $directives->{order}), "\n";
            }
            elsif (!exists($directives->{order}))
            {
                print $out $_;
            }

            # order taken care of discard it
            delete($directives->{order});
        }
        elsif ($directives && /^(allow|deny)/)
        {
            my $rule = $1;
            if ($directives->{$rule})
            {
                print $out "$rule from ", 
                                join(' ', @{$directives->{$rule}}), "\n";
            }
            elsif (!exists($directives->{$rule}))
            {
                print $out $_;
            }

            # allow or deny taken care of discard this
            delete($directives->{$rule});
        }
        elsif (/^<\/$type>$/)
        {
            # skip the footer line
            <$in>;
            last;
        }
        elsif ($directives)
        {
            print $out $_;
        }
    } # end of while (<$in>)

    # add the rest
    if (!$found && $directives)
    {
        print $out $header, "\n";
        print $out "<$type '$pattern'>\n";
    }

    # print out any remaining changes, that are new or added
    if ($directives->{Options})
    {
        print $out 'Options ', join(' ', @{$directives->{Options}}), "\n";
    }
    
    if ($directives->{AllowOverride})
    {
        print $out 'AllowOverride ', 
                    join(' ', @{$directives->{AllowOverride}}), "\n";
    }

    if ($directives->{order})
    {
        print $out 'order ', join(',', @{$directives->{order}}), "\n";
    }
    if ($directives->{allow})
    {
        print $out 'allow from ', join(' ', @{$directives->{allow}}), "\n";
    }
    if ($directives->{deny})
    {
        print $out 'deny from ', join(' ', @{$directives->{deny}}), "\n";
    }

    # add Files sections
    for my $key (keys(%{$directives->{FileSections}}))
    {
        my $section = $directives->{FileSections}->{$key};
        print $out "<Files '$key'>\n";
        if ($section->{order})
        {
            print $out 'order ', join(',', @{$section->{order}}), "\n";
        }
        if ($section->{allow})
        {
            print $out 'allow from ', join(' ', @{$section->{allow}}), "\n";
        }
        if ($section->{deny})
        {
            print $out 'deny from ', join(' ', @{$section->{deny}}), "\n";
        }
        print $out "</Files>\n";
    }

    # close the section if it isn't being removed
    if ($directives)
    {
        print $out "</$type>\n";
        print $out $footer, "\n";
    }

    # print anything else
    while(<$in>)
    {
        print $out $_;
    }

    return 1;
}

sub _edit_access
{
	my ($in, $out, $directory, $allow) = @_;

	my $found_web = 0;
    
	while (<$in>)
    {
        if (/^<Directory $directory/ ... /^<\/Directory/)
        {
            $found_web = 1;
            print $out $_ if ($allow);
        }
        else
		{
			print $out $_;
		}
    } 

    if (!$found_web && $allow)
    {
        print $out <<EOF;
<Directory $directory>
AllowOverride All
Options All
</Directory>
EOF
    }

	return 1;
}

# this edit function is kind of brain-dead, it will most likely break
# if things are configured differently
sub _set_server_aliases
{
    my ($in, $out, $aliases) = @_;

    my $line_to_add = join(' ', @{$aliases});

    while (<$in>)
    {
	if (/^ServerName/)
	{
	    print $out $_;
	    if (scalar(@{$aliases}))
	    {   
		print $out "ServerAlias $line_to_add\n";
	    }
	} elsif (/^ServerAlias/) {
	    next;
	} else {
	    print $out $_;
	}
    }

    return 1;
}

sub debug_msg {
    if ($DEBUG) {
        my $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
}

1;

# 
# Copyright (c) 2017-2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2017-2018 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
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
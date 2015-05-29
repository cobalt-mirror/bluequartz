#
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: HomeDir.pm,v 1.6 2001/12/18 01:40:46 pbaltz Exp $
#
# provides a way to provide generic hashing functionality for placing 
# site, group, or user home directories
#

package Base::HomeDir;

=pod
=head1 NAME

Base::HomeDir - functions to get the location of user and group directories

=head1 SYNOPSIS

 use Base::HomeDir;
 use Base::HomeDir qw(homedir_get_group_dir homedir_get_user_dir);

 $path = Base::HomeDir::homedir_get_group_dir('group1');
 $path = Base::HomeDir::homedir_get_group_dir('group2', '/alt/root');
 
 $path = Base::HomeDir::homedir_get_user_dir('user1');
 $path = Base::HomeDir::homedir_get_user_dir('user2', 'site3');
 $path = Base::HomeDir::homedir_get_user_dir('user3, 'site4', '/alt/root');

 $path = Base::HomeDir::homedir_create_group_link('site1', 'foo.foo.com', '/alt/root');
 $path = Base::HomeDir::homedir_create_user_link('user3', 'site2', '/alt/root');

 $path = Base::HomeDir::homedir_get_group_path('users');
 $path = Base::HomeDir::homedir_get_user_path('web');

=head1 DESCRIPTION

Base::HomeDir provides only two methods: one to get the location of a group,
or site, home directory, and one to get the location of a user home directory.
These functions do not create the directories, nor do they check to see if the
directory even exists.  They are primarily for use when creating groups or
users to determine where all the handlers will expect to find the home
directories.  Since the home directory is also an attribute of Vsite and User
objects in CCE, the value in CCE should be considered the accurate value if
they differ.  Handlers, constructors, or anything else should usually never 
build the location of a home directory itself, since the directories are hashed.

=head1 EXPORTS

All of the methods in Base::HomeDir are availble for export into the caller's
namespace using the standard C<use Module qw(function1 function2 ...);> pragma.
Methods are not exported by default.  No configuration variables are available
for import, if needed, should be used as C<$Base::HomeDir::variable>.

=cut

require Exporter;
use vars qw(@ISA @EXPORT_OK);

@ISA = qw(Exporter);
@EXPORT_OK = qw(
        homedir_get_group_dir homedir_get_user_dir
        homedir_create_group_link homedir_create_user_link
        homedir_setup_admin_home homedir_setup_user_home
        );

use lib qw(/usr/sausalito/perl);
use File::Path;
use Sauce::Util;
use CCE;
use I18n;

#
# configuration variables, avoid using these by themselves because
# the actually home directory of a group or site may be hashed and
# not just simple "$HOME_ROOT/$group_dir/group_name" or 
# "$HOME_ROOT/users/user_name"
#
use vars qw($HOME_ROOT $SITE_DIR $USER_DIR);
$is_home_separate = "1";
if (! -f "/etc/fstab") {
    $HOME_ROOT = '/';
}
else {
    $is_home_separate = `cat /etc/fstab|grep /home|wc -l`;
    chomp($is_home_separate);
}
if ($is_home_separate eq "1") {
    $HOME_ROOT = '/home';
}
else {
    $HOME_ROOT = '/';
}
$SITE_DIR = 'sites';
$USER_DIR = 'users';

# private config vars
my $group_dir = '.sites';
my $user_dir = '.users';

=pod

=head1 METHODS

=over 4

=item homedir_get_group_dir($group_name[, $alt_root])

This will get a home directory location for a group.  The location is hashed to
avoid having more than a few hundred entries per directory for performance
reasons.  It returns the empty string, '', if it fails for any reason.

=over 4

=item *

I<$group_name> is the system name of the group such as that found in the
/etc/group file.  For Vsite objects, it is also the value of the name property.
The $group_name will be included in the path returned.

=item *

I<$alt_root> is optional.  If given, it will be used as the "home" directory
under which the group directory should be located.  I<$alt_root> must have a
'/' as the first character or no path will be returned.  If not given, it 
defaults to /home.

=back

=cut

sub homedir_get_group_dir
{
    my $group = shift;
    my $alt_root = shift; # where the sites home dir should be

    # alt root must be relative to / if given
    if ($alt_root && (index($alt_root, '/') != 0)) {
        return '';
    }

    if ($group) {
        my $group_path = '';
        if ($alt_root) {
            $group_path = "$alt_root/$group_dir";
            return _hash_path($group_path, $group);
        } else {
            my $home_dir = _check_cce_group($group);
       
            if ($home_dir eq '') {
                $group_path = "$HOME_ROOT/$group_dir";
                $home_dir = _hash_path($group_path, $group);
            }
            return $home_dir;
        }
    }

    # error if $group is ''
    return '';
}

=pod

=item homedir_get_user_dir($user_name[, $user_group[, $alt_root]])

This will return where a user's home directory should be found in the 
filesystem.  It returns the empty string, '', on failure.  As with 
C<homedir_get_group_dir>, the result is hashed, and C<$user_name> will be part
of the resulting path.  Normally, the value returned by getpwnam should be
used for user's that already exist.

=over 4

=item *

I<$user_name> is the user name such as that used in /etc/passwd.

=item *

I<$user_group> is optional.  If given, the user's directory will be placed in
the users sub-directory of the home directory for the group specified by 
C<$user_group>.  So the path will look like this: /group-home/user-home.

=item *

I<$alt_root> is optional.  C<$alt_root> is used the same as it is with the
C<homedir_get_group_dir> method, and it defaults to "/home" for this method
as well.  C<$alt_root> should begin with "/".

=back

=cut

sub homedir_get_user_dir
{
    my $user = shift;
    my $user_group = shift;
    my $alt_root = shift;  # in case on external storage

    # check for errors
    if (($user eq '') || ($alt_root && (index($alt_root, '/') != 0))) {
        return ''; 
    }

    my $root_dir = '';
    if ($user_group) {
        my $group_root = '';
        if ($alt_root) {
            $group_root = "$alt_root/$group_dir";
            $root_dir = _hash_path($group_root, $user_group);
        } else {
            $root_dir = _check_cce_group($user_group);
            if ($root_dir eq '') {
                $group_root = "$HOME_ROOT/$group_dir";
                $root_dir = _hash_path($group_root, $user_group);
            }
        }
    } else {
        $root_dir = ($alt_root ? $alt_root : $HOME_ROOT);
    }

    return _hash_path("$root_dir/$user_dir", $user);
}

=pod

=item homedir_create_group_link($group_name, $link_name[, $alt_root])

Generate the path for a human-readable link to the site's home directory.  
If successful, return a list with the first element being where the symlink
should be based on the default root or the $alt_root value passed in and the
second element being the target the symlink should point at.  The target may
or may not be absolute, so just pass it directly to the link.
Returns empty list on failure.  The list returned can be used to create a 
symlink to the actual site directory.

=over 4

=item *

I<$group_name> is the name of the system group the group or site is associated
with.

=item *

I<$link_name> is the name that should be used for the symlink file.

=item *

I<$alt_root> is optional.  This specifies that the group or site's home
directory is not located under the default "/home" directory.

=back

The generated symlink locations  will look like the following: 
/home/sites/alt-name, /home/groups/alt-name, /alt/root/sites/alt-name.

=cut

sub homedir_create_group_link
{
    my ($site, $alt_name, $alt_root) = @_;

    # check for valid parameters
    if (!$site || !$alt_name || ($alt_root && index($alt_root, '/') != 0)) {
        return ();
    }

    my $link_target = homedir_get_group_dir($site, $alt_root);
    $link_target =~ s/^(\/.+)(\/\.$SITE_DIR\/.*)$/\.\.$2/;
    if (!$alt_root && $1) {
        $alt_root = $1;
    }

    my $link_path = ($alt_root ? $alt_root : $HOME_ROOT);
    $link_path .= "/$SITE_DIR";
    $link_path .= "/$alt_name";
    
    return ($link_path, $link_target);
}

=pod

=item TODO document homedir_create_user_link

=cut

sub homedir_create_user_link
{
    my ($user, $site, $alt_root) = @_;

    if (!$user || ($alt_root && index($alt_root, '/') != 0)) {
        return ();
    }

    my $link_target = homedir_get_user_dir($user, $site, $alt_root);
    $link_target =~ s/^\/.+(\/\.$USER_DIR\/.*)$/\.\.$1/;

    my $link_path = '';
    
    if ($site) {
        $link_path = homedir_get_group_dir($site, $alt_root);
    } else {
        $link_path = ($alt_root ? $alt_root : $HOME_ROOT);
    }
    
    $link_path .= "/$USER_DIR";
    $link_path .= "/$user";

    return ($link_path, $link_target);
}

sub homedir_setup_admin_home
{
    my $user = shift;

    #
    # for raqs the only thing to do is get rid of the web directory
    # in the user skeleton, this probably shouldn't need to be done
    # but someone needs to sit down and think about the best solution
    # to the differentiation between server administrators and regular
    # users
    #
    my ($uid, $home_dir) = (getpwnam($user->{name}))[2, 7];
    
    system('/bin/rm', '-rf', "$home_dir/web");

    # make sure the directories get the correct permissions
    Sauce::Util::chmodfile(0700, $home_dir);
    Sauce::Util::chownfile($uid, $user->{gid}, $home_dir);
    
    # recursive chown of homedir
    Sauce::Util::modifytree($home_dir);
    system('/bin/chown', '-R', "$uid:$user->{gid}", $home_dir);

    return 1;
}

sub homedir_setup_user_home
{
    my $user = shift;

    my ($uid, $home_dir) = (getpwnam($user->{name}))[2, 7];

    # the old sauce auto-created "Network Trash Folder"
    my $ntf = "${home_dir}/Network Trash Folder";
    Sauce::Util::makedirectory($ntf, 02751);
    Sauce::Util::chownfile($uid, $user->{gid}, $ntf);
    Sauce::Util::chmodfile(02751, $ntf);
    
    # the old sauce auto-created "Private"
    $ntf = "${home_dir}/Private";
    Sauce::Util::makedirectory($ntf, 02700);
    Sauce::Util::chownfile($uid, $user->{gid}, $ntf);
    Sauce::Util::chmodfile(02700, $ntf);
    
    # create a default index.html file for the user's public directory
    $ntf = "${home_dir}/web";
    Sauce::Util::makedirectory($ntf, 02775);
    Sauce::Util::chownfile($uid, $user->{gid}, $ntf);
    Sauce::Util::chmodfile(02775, $ntf);
    
    my $indexFile = "${home_dir}/web/index.html";
    if(!(-e $indexFile)) {
        Sauce::Util::modifyfile("${home_dir}/web/index.html");
        open(INDEX, ">${home_dir}/web/index.html");
        print INDEX '<HTML><BODY>Default user page</BODY></HTML>';
        close(INDEX);
    }

    Sauce::Util::chownfile($uid, $user->{gid}, 
                   "${home_dir}/web/index.html");   
    Sauce::Util::chmodfile(0664, "${home_dir}/web/index.html");
    
    # make sure the directories get the correct permissions
    Sauce::Util::chmodfile(02771, $home_dir);
    Sauce::Util::chownfile($uid, $user->{gid}, $home_dir);
    
    # recursive chown of homedir
    Sauce::Util::modifytree($home_dir);
    system('/bin/chown', '-R', "$uid:$user->{gid}", $home_dir);

    return 1;
}

# only private methods below, not for use outside of the module

# takes a directory name and system group or user name, and returns
# where the directory should lie in the filesystem
sub _hash_path
{
    my ($directory, $sys_name) = @_;

    # only hash sys_name
    my $sum = 1;
    my $temp = $sys_name;
    for (my $j = 0; $j < length($temp); $j++) {
        $ch = chop($temp);
    
        # give vowels less weight since they are usually more common
        if ($ch =~ /^[aeiou]$/) {
            $sum *= ord($ch) / 10;
        } else {
            $sum *= ord($ch);
        }
    }

    # this seems to give good distributions
    my $hash_bucket = $sum % 151;

    return "$directory/$hash_bucket/$sys_name";
}

sub _check_cce_group
{
    my $group = shift;
   
    if (!$group) {
        return ''; 
    }

    # see if this group is in CCE
    my $cce = new CCE;
    $cce->connectuds();
    my $home_dir = '';
    
    my ($site_oid) = $cce->find('Vsite', { 'name' => $group });
    if ($site_oid) {
        my ($ok, $vsite) = $cce->get($site_oid);
        if ($vsite->{basedir} && (index($vsite->{basedir}, '/') == 0)) {
            $home_dir = $vsite->{basedir};
        } elsif ($vsite->{volume} &&
             (index($vsite->{volume}, '/') == 0)) {
            my $group_path = "$vsite->{volume}/$group_dir";
            $home_dir = _hash_path($group_path, $group);
        }
    }

    $cce->bye();
    return $home_dir;
}

1;
# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
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
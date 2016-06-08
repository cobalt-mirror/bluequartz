# $Id: Resolve.pm

package Resolve;
use strict;

sub new
{
    my $proto = shift;
    my $class = ref($proto) || $proto;
    my $self = {};
    bless ($self, $class);

    if(@_) {
        my (%hash) = (@_);
        while(my($name, $value) = each %hash) {
            $self->{$name} = $value;
        }
    }
    # Quit is always an option
    return $self;
}

# build the accessors for the class
sub class { 
    $_[0]->{class} = $_[1] if($_[1]);
    return $_[0]->{class} if(defined($_[0]->{class})); 
} 

sub text {
    $_[0]->{text} = $_[1] if($_[1]);
    return $_[0]->{text} if(defined($_[0]->{text})); 
}

sub validate {
    $_[0]->{validate} = $_[1] if($_[1]);
    return $_[0]->{validate} if(defined($_[0]->{validate})); 
}

sub key {
    $_[0]->{key} = $_[1] if($_[1]);
    return $_[0]->{key} if(defined($_[0]->{key})); 
}

sub attr {
    $_[0]->{attr} = $_[1] if($_[1]);
    return $_[0]->{attr} if(defined($_[0]->{attr})); 
}

sub nameSpace {
    $_[0]->{nameSpace} = $_[1] if($_[1]);
    return $_[0]->{nameSpace} if(defined($_[0]->{nameSpace})); 
}

sub detector {
    $_[0]->{detector} = $_[1] if($_[1]);
    return $_[0]->{detector} if(defined($_[0]->{detector})); 
}

sub attrValue {
    $_[0]->{attrValue} = $_[1] if($_[1]);
    return $_[0]->{attrValue} if(defined($_[0]->{attrValue})); 
}

sub name {
    $_[0]->{name} = $_[1] if($_[1]);
    return $_[0]->{name} if(defined($_[0]->{name})); 
}

sub defaultAction {
    $_[0]->{defaultAction} = $_[1] if($_[1]);
    return $_[0]->{defaultAction} if(defined($_[0]->{defaultAction})); 
}
    

sub actions {
    $_[0]->{actions}->{$_[1]} = $_[2] if($_[2]);
    return keys %{ $_[0]->{actions} } if(exists($_[0]->{actions}));
}

sub result {
    $_[0]->{result} = $_[1] if($_[1]);
    return $_[0]->{result} if(defined($_[0]->{result})); 
}

sub resultVal {
    $_[0]->{resultVal} = $_[1] if($_[1]);
    return $_[0]->{resultVal} if(defined($_[0]->{resultVal})); 
}

sub resolve
{
    my $self = shift;
    
    my $func = $self->getAction();
    if($func eq 'changeClass' || $func eq 'changeVal') {
        my $val = $self->getInput();
        $self->resultVal($val);
    } 
    $self->result($func);
    return 0;
}

sub printConflict
{
    my $self = shift;
    
    warn $self->{text};
    my $line;
    foreach my $act (keys %{ $self->{actions} }) {
        $line .= $act."(".substr($act, 0, 1).") ";
    }
    warn $line, ":\n";
}

sub printAsHtml
{
    my $self = shift;
    $self->printConflict();
}

sub getAction 
{
    my $self = shift;
    my $in;
    while(1) {
        $self->printConflict();
        $in = <STDIN>;
        chomp($in);
        next if($in eq '');
        foreach my $act (keys %{ $self->{actions} }) {
            my $abbr = substr($act, 0, 1);
            if($in =~ /^($abbr|$act)$/i) {
                $self->result($self->{actions}->{$act});
                return($self->{actions}->{$act});
            }   
        }
        warn "Invalid option: $in\n";
    }
}

sub getInput
{
    my $self = shift;
    my ($in, $func);
    while(1) {
        warn "Enter value:\n";
        $in = <STDIN>;
        chomp($in);
        next if($in eq '');
        if(defined($self->{validate})) {
            $func = $self->{validate};
            last if($self->$func($in) == 0);
            warn "Invalid data: $in\n";
        } else { last; }
    }
    $self->resultVal($in);
    return $in;
}

sub asHash
{
    my $self = shift;
    my $rHash = {};

    foreach my $key (keys %{ $self }) {
        warn "$key => ", $self->{$key}, "\n";
        $rHash->{$key} = $self->{$key};
    }
    return $rHash;
}
            
sub isFqdn
{
    my $self = shift;
    my $val = shift;

    return 1 if($val =~ /\..?$/o);
    my @subs = split(/\./, $val);
    return 1 if($#subs < 2);
    return 1 if($val !~ /^[a-zA-Z0-9\-\.]*$/);

    return(0);
}

sub isIp
{
    my $self = shift;
    my $ip = shift;

    # stolen and compacted from Validators.pm in sausalito
    my @numbers = split(/\./, $ip);
    foreach my $num ( @numbers ) {
        # Make sure the block is three numbers.
        if( $num !~ /^\d{1,3}$/o ) { return 1; }
        if( $num > 255 ) { return 1; }
    }
    return 0;
}

sub isUser
{
    my $self = shift;
    my $user = shift;

    return 1 if(length($user) > 12);
    return 1 if($user !~ /^[a-z0-9][a-z0-9\.\-\_][a-z0-9\.\-\_]+$/o);
    return 0;
}

sub isGroup
{
    my $self = shift;
    my $group = shift;

    return 1 if(length($group) > 12);
    return 1 if($group !~ /^[a-z0-9][a-z0-9\.\-\_]*$/o);
    return 0;
}

sub isUserQube
{
    my $self = shift;
    my $user = shift;

    return 1 if(length($user) > 12);
    return 1 if($user !~ /^[a-z][a-z0-9\.\-\_][a-z0-9\.\-\_]+$/o);
    return 0;
}

sub isGroupQube
{
    my $self = shift;
    my $group = shift;

    return 1 if(length($group) > 12);
    return 1 if($group !~ /^[a-z][a-z0-9\.\-\_]*$/o);
    return 0;
}


sub isInt
{
    my $self = shift;
    my $int = shift;
    return 1 if($int eq '0');
    return 1 if($int !~ /^[0-9]+$/);
    return 0;
}

1;
# 
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#     notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#     notice, this list of conditions and the following disclaimer in 
#     the documentation and/or other materials provided with the 
#     distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#     contributors may be used to endorse or promote products derived 
#     from this software without specific prior written permission.
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
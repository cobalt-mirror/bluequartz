#!/usr/bin/perl -w
#Copyright 2000 Cobalt Networks
#Author: Philip Martin
#14 Nov 2000
#
#mail module that will auto-detect the current encoding 
#and language, add the correct headers and convert the
#encoding as needed to conform to standards.

#Currently English and Japanese are supported.
#To add a language please see the _encodeBody
#and _encodeHeader functions.


package I18nMail;

sub false(){0}
sub true(){1}

use vars qw/$VERSION/;
use Jcode;  #Jcode kicks ass.
use POSIX qw(setlocale LC_ALL strftime);

$VERSION="1.0";


$encodingTable = {
	ja => {
		charset => 'ISO-2022-JP',
		schemes => {
			body => [
				\&I18nMail::Subs::toIso2022Jp,
				\&I18nMail::Subs::setBodyLang
				],

			header => {
				"default" => [  #default encoding scheme
					\&I18nMail::Subs::toIso2022Jp,
					\&I18nMail::Subs::MimeJa
					],
				"email" => [ #encoding for headers w/ email addrs
					\&I18nMail::Subs::getEmailName,
					\&I18nMail::Subs::toIso2022Jp,
					\&I18nMail::Subs::MimeJa,
					\&I18nMail::Subs::glueEmailTogether
					]
				}
			}
		},
	en => {
		charset => 'ISO-8859-1',
		schemes => {
			body => [\&I18nMail::Subs::setBodyLang],
			header => {
				"default" => [\&I18nMail::Subs::QuotedPrintable],
				"email" => [
					\&I18nMail::Subs::getEmailName,
					\&I18nMail::Subs::QuotedPrintable,
					\&I18nMail::Subs::glueEmailTogether
					]
				}
			}
		},
        de_DE => {
                charset => 'ISO-8859-1',
                schemes => {
                        body => [\&I18nMail::Subs::setBodyLang],
                        header => {
                                "default" => [\&I18nMail::Subs::QuotedPrintable],
                                "email" => [
                                        \&I18nMail::Subs::getEmailName,
                                        \&I18nMail::Subs::QuotedPrintable,
                                        \&I18nMail::Subs::glueEmailTogether
                                        ]
                                }
                        }
                },
        da_DK => {
                charset => 'ISO-8859-1',
                schemes => {
                        body => [\&I18nMail::Subs::setBodyLang],
                        header => {
                                "default" => [\&I18nMail::Subs::QuotedPrintable],
                                "email" => [
                                        \&I18nMail::Subs::getEmailName,
                                        \&I18nMail::Subs::QuotedPrintable,
                                        \&I18nMail::Subs::glueEmailTogether
                                        ]
                                }
                        }
                },
	zh_CN => {
		charset => 'GB2312',
		schemes => {
			body => [
				\&I18nMail::Subs::setBodyLang
				],

			header => {
				"default" => [  #default encoding scheme
					\&I18nMail::Subs::Base64
					],
				"email" => [ #encoding for headers w/ email addrs
					\&I18nMail::Subs::getEmailName,
					\&I18nMail::Subs::QuotedPrintable,
					\&I18nMail::Subs::glueEmailTogether
					]
				}
			}
		},
	zh_TW => {
		charset => 'Big5',
		schemes => {
			body => [
				\&I18nMail::Subs::setBodyLang
				],

			header => {
				"default" => [  #default encoding scheme
					\&I18nMail::Subs::Base64
					],
				"email" => [ #encoding for headers w/ email addrs
					\&I18nMail::Subs::getEmailName,
					\&I18nMail::Subs::QuotedPrintable,
					\&I18nMail::Subs::glueEmailTogether
					]
				}
			}
		},
	generic => {
		charset => 'ISO-8859-15',
		schemes => {
			body => [\&I18nMail::Subs::setBodyLang],
			header => {
				"default" => [\&I18nMail::Subs::QuotedPrintable],
				"email" => [
					\&I18nMail::Subs::getEmailName,
					\&I18nMail::Subs::QuotedPrintable,
					\&I18nMail::Subs::glueEmailTogether
					]
				}
			}
		},
	unicode => {
		charset => 'ISO-10646',
		schemes => {
			body => [
				\&I18nMail::Subs::toUnicode,
				\&I18nMail::Subs::setBodyLang
				],

			header => {
				"default" => [  #default encoding scheme
					\&I18nMail::Subs::toUnicode,
					\&I18nMail::Subs::Base64
					],
				"email" => [ #encoding for headers w/ email addrs
					\&I18nMail::Subs::getEmailName,
					\&I18nMail::Subs::toUnicode,
					\&I18nMail::Subs::Base64,
					\&I18nMail::Subs::glueEmailTogether
					]
				}
			}
		},
};

$encodingTable->{en_US}=$encodingTable->{en};
$encodingTable->{fr}=$encodingTable->{en};
$encodingTable->{es}=$encodingTable->{en};
$encodingTable->{de}=$encodingTable->{en};
$encodingTable->{de_DE}=$encodingTable->{de_DE};
$encodingTable->{da_DK}=$encodingTable->{da_DK};
$encodingTable->{'zh'}=$encodingTable->{zh_CN};
$encodingTable->{'zh-CN'}=$encodingTable->{zh_CN};
$encodingTable->{'zh-TW'}=$encodingTable->{zh_TW};

#constructor
#takes 1 optional argument, a two letter
#language code.
#without that argument the module will
#auto-detect the encoding, and therefore
#language, of each peice of data.
#(note that I said each piece of data,
#this module will let you have english
#text in the subject and japanese text
#in the body and will encode each one
#differently (which is correct according
#to the RFCs.))

sub new{
	my($class, $lang)=@_;
	my $self={};
	bless($self, $class);
	$self->{_lang}=$lang if defined $lang;
	$self->{_bodyLang}=$lang if defined $lang;
	$self->{_encodingTable}=$encodingTable;
	return $self;
}

sub setLang{
	my($self,$lang)=@_;
	if(exists $self->{_encodingTable}->{$lang}){	
		$self->{_lang}=$lang;
		$self->{_bodyLang}=$lang;
		return true;
	}
	return false;
}

sub addEncoding{
	my($self,$lang,$table)=@_;
	$self->{_encodingTable}->{$lang}=$table;
	return true;
}

sub setBody{
	my ($self,$body,$contentType)=@_;
	$contentType="text/plain" unless defined $contentType;

	$self->{_contentType}=$contentType;
	$body=~s/\r(?!\n)/\n/g; #workaround little Jcode bug
	$body=~s/(?<!\r)\n/\r\n/g;
	$self->{_body}=$self->_encode($body,"body");

	unless(defined $self->{_body}){
		return false;
	}
	return true;
}

sub setSubject{
	my($self,$subject)=@_;
	chomp($subject);
	$self->{_subject}=$self->_encode($subject,"header","default");

	unless(defined $self->{_subject}){
		return false;
	}
	return true;
}

sub addRawTo{
	my $self=shift;

	foreach(@_){
		$self->{_to} .= $_.", " unless $_ eq "";
	}
	unless(defined $self ->{_to}){
		return false;
	}
	return true;
}

sub addTo{
	my $self=shift;
	
	foreach(@_){
		chomp;
		$self->{_to} .= $self->_encode($_,"header","email").", " unless $_ eq "";
	}
	unless(defined $self->{_to}){
		return false;
	}
	return true;
}

sub addCc{
	my $self=shift;
	
	foreach(@_){
		chomp;
		$self->{_cc} .= $self->_encode($_,"header","email").", " unless $_ eq "";
	}
	unless(defined $self->{_cc}){
		return false;
	}
	return true;
}

sub addBcc{
	my $self=shift;
	
	foreach(@_){
		chomp;
		$self->{_bcc} .= $self->_encode($_,"header","email").", " unless $_ eq "";
	}
	unless(defined $self->{_bcc}){
		return false;
	}
	return true;
}

sub setFrom{
	my($self,$from)=@_;
	chomp $from;
	$self->{_from} .= $self->_encode($from,"header","email");

	unless(defined $self->{_from}){
		return false;
	}

	return true;
}

sub toText{
	my($self)=@_;
	my($msg,$charset);
	$charset=$self->{_encodingTable}->{$self->{_bodyLang}}->{charset}; #  :)

	$msg  = "From: $self->{_from}\r\n";
	$msg .= "To: $self->{_to}\r\n";
	$msg .= "Cc: $self->{_cc}\r\n" if defined $self->{_cc};
	$msg .= "Bcc: $self->{_bcc}\r\n" if defined $self->{_bcc};

	#
	# add the date RFC 822 style, always use english locale since this is
	# an smtp header
	#
	my $cur_loc = setlocale(LC_ALL);
	setlocale(LC_ALL, 'en_US');
	$msg .= "Date: ".
		strftime("%a, %d %b %Y %H:%M:%S %z", localtime(time())) .
		"\r\n";
	# restore locale
	setlocale(LC_ALL, $cur_loc);

	$msg .= "Subject: $self->{_subject}\r\n";
	$msg .= "MIME-Version: 1.0\r\n";
	$msg .= "Content-Transfer-Encoding: 8bit\r\n";
	$msg .= "Content-Language: $self->{_bodyLang}\r\n";
	$msg .= "Content-Type: $self->{_contentType}; charset=\"$charset\"\r\n";
	$msg .= "\r\n";
	$msg .= $self->{_body};
	$msg .= "\r\n";

	return $msg;
}

########################################
#PRIVATE FUNCTIONS

sub _detectLang{
	my($self,$str)=@_;

	my %enc2lang=(
		sjis=>'ja',
		jis=>'ja',
		'euc-cn'=>'zh',
		'euc-tw'=>'zh',
		euc=>'ja',
		acsii => 'en' #actually, ascii can mean any one of
			      #a lot of languages, but they are all
			      #processed the same way 'en' is so why
			      #complicate the issue?
	);

	$enc=Jcode::getcode($str);
	if(exists $enc2lang{$enc}){
		return $enc2lang{$enc};
	}else{
		return undef;
	}
}


sub _encode{
	my($self,$str,$type,$subType)=@_;
	my($lang,$code,$charset,$ret);

	$lang=$self->{_lang} || $self->_detectLang($str);
	return undef unless defined $lang;

	# Fallback encoding type to unicode (aka "generic" style) 
	unless (exists $self->{_encodingTable}->{$lang}) {
		warn "Unknown email encoding type for the language $lang, using unicode\n";
		$encodingTable->{$lang}=$encodingTable->{generic};
	}
	warn "Could not find encoding for $lang, fallback to unicode failed\n" 
		unless exists $self->{_encodingTable}->{$lang};

	if(defined $subType){
		$code=$self->{_encodingTable}->{$lang}->{schemes}->{$type}->{$subType};
	}else{
		$code=$self->{_encodingTable}->{$lang}->{schemes}->{$type};
	}
	return undef unless ref $code eq "ARRAY";

	$charset=$self->{_encodingTable}->{$lang}->{charset};
	$ret=$str;

	foreach my $func (@$code){
		$ret=$func->($ret,$charset,$str,$lang,$self);
	}
	return $ret;
}


package I18nMail::Subs;

use Jcode;
use MIME::Base64;
use MIME::QuotedPrint;

sub getEmailName{
	my($email)=@_;
	my($name,$mailaddr);

	$mailaddr=q/[^()<>@,;:\\\\"\\[\\]\\s\\x00-\\x1f\\x7f]+(?:@[a-zA-Z0-9._-]+)?/;

	if($email=~/^$mailaddr$/){
		return $email;
	}elsif($email=~/<.*>/ && $email=~/^([^<]+)<$mailaddr>$/){
		return $1;
	}elsif($email=~/\(.*\)/ && (($name)=($email=~/\((.*)\)/))){
		return $name;
	}else{
		return undef;
	}
}

sub getEmailAddr{
	my($email)=@_;
	my($addr,$mailaddr);

	$mailaddr=q/[^()<>@,;:\\\\"\\[\\]\\s\\x00-\\x1f\\x7f]+(?:@[a-zA-Z0-9._-]+)?/;

	if($email=~/^($mailaddr)$/){
		return $1;
	}elsif($email=~/<($mailaddr)>/){
		return $1;
	}else{
		return undef;
	}
}

sub Base64{
	my($str,$charset)=@_;

	my $enc_str = encode_base64($str, '');
	
	my $ret_val = '';

	# RFC 2047 specifies that an encoded word in a MIME encoded header
	# field including the encoding,
	# encoded text and delimeters cannot be longer than 75 characters
	# however mail readers seem to think the actual encoded text should
	# be 76 characters by itself, tested in outlook express(mac), 
	# netscape messenger(mac/linux), webmail
	my $enc_text_len = 76; # 75 - length("=?$charset?B??=");
	my $offset = 0;
	for (my $offset = 0; $offset < length($enc_str); $offset += $enc_text_len)
	{
		$ret_val .= ($ret_val ? "\r\n " : '') . "=?$charset?B?". substr($enc_str, $offset, $enc_text_len) ."?=";
	}
	return $ret_val;
}

sub MimeJa
{
	my($str,$charset)=@_;

	my $j = Jcode->new($str);
	return $j->mime_encode();
}

sub QuotedPrintable{
	my($str,$charset)=@_;
	my $qp_str=encode_qp($str, "");
	chomp($qp_str);
	return "=?$charset?Q?".$qp_str."?=";
}

sub toIso2022Jp{
	my($str)=@_;
	return Jcode->new($str)->iso_2022_jp;
}

sub toUnicode{
	my($str)=@_;
	return Jcode->new($str)->ucs2;
}

sub setBodyLang{
	my($ret,$lang,$self)=@_[0,3,4];
	$self->{_bodyLang}=$lang;
	return $ret;
}

sub glueEmailTogether{
	my($ret,$str)=@_[0,2];
	return $ret." <".getEmailAddr($str).">";
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

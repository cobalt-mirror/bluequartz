m4_divert(-1)

m4_dnl John D. Blair's standard m4 macros for writing documentation.
m4_dnl $Id: doclib.m4 201 2003-07-18 19:11:07Z will $
m4_dnl
m4_dnl Conventions:
m4_dnl   All HTML tags in macros should be UPPERCASE.  Since I normally write
m4_dnl     all HTML tags as lowercase this lets me tell at-a-glance which
m4_dnl     html tags originated in a macro.


m4_dnl How to debug m4:
m4_dnl 
m4_dnl use the following macros to turn debug tracing on and off:
m4_dnl 
m4_dnl m4_debugmode(e)
m4_dnl m4_traceon
m4_dnl .
m4_dnl .
m4_dnl buggy lines
m4_dnl .
m4_dnl .
m4_dnl m4_traceoff


m4_changecom(`[[[[')
m4_changequote(`[[',`]]')

m4_dnl misc. utility macros
m4_define([[_file_exists]],[[m4_esyscmd(if [ -e '$1' ]; then echo -n yes; else echo -n no; fi)]])
m4_define([[_footer_file]],[[footer.m4]])
m4_define([[_header_file]],[[header.m4]])

m4_define([[_author]],[[[no author defined]]])
m4_define([[_email]],[[[no author email defined]]])

m4_define([[_author_info]], m4_dnl
 [[m4_define([[_author]],$1) m4_dnl
  m4_define([[_email]],$2)]])

m4_define([[_set_revision]], [[m4_define(_this_revision,$1)]]) m4_dnl
m4_define([[_revision]], [[m4_regexp(_this_revision,[[\([0123456789\.]+\)]],[[\1]])]]) m4_dnl
m4_define([[_doclib_revision]], m4_regexp([[$Revision: 201 $]],[[\([0123456789\.]+\)]],[[\1]])) m4_dnl

m4_define([[_stylesheet]],[[<LINK REL="stylesheet" HREF="$1" TYPE="text/css">]])

m4_dnl macros for colors
m4_define([[_red]],#ff0000)
m4_define([[_light_red]],#ffbfbf)
m4_define([[_orange]],#ffa100)
m4_define([[_light_orange]],#ffe1b2)
m4_define([[_yellow]],#ffff00)
m4_define([[_light_yellow]],#ffffb2)
m4_define([[_green]],#007f00)
m4_define([[_light_green]],#bfffbf)
m4_define([[_blue]],#0000ff)
m4_define([[_light_blue]],#b2b2ff)
m4_define([[_cyan]],#00ffee)
m4_define([[_light_cyan]],#bffffa)
m4_define([[_purple]],#ff00ff)
m4_define([[_light_purple]],#ffbfff)
m4_define([[_white]],#ffffff)
m4_define([[_black]],#000000)
m4_define([[_gray10]],#191919)
m4_define([[_gray20]],#333333)
m4_define([[_gray30]],#4c4c4c)
m4_define([[_gray40]],#666666)
m4_define([[_gray50]],#7f7f7f)
m4_define([[_gray60]],#999999)
m4_define([[_gray70]],#b2b2b2)
m4_define([[_gray80]],#cccccc)
m4_define([[_gray90]],#e5e5e5)
m4_define([[_sun_purple]],#594fbf)

m4_define([[_color]],<FONT COLOR="$1">$2</FONT>)

m4_dnl macros for commentary
m4_define([[_hide]],[[]])m4_dnl used to hide text ("comment it out")
m4_define([[_block]],[[<P><TABLE WIDTH="$1" BGCOLOR="$2" BORDER=0 CELLPADDING=5 CELLSPACING=0>
$3
<TR><TD>$4</TD></TR>
</TABLE><P>]])
m4_define([[_noteblock]],_block(80%,_gray80,<TR><TH ALIGN="LEFT">Notes:</TH></TR>,$1))
m4_dnl comments are only displayed if _hidecomments is not defined
m4_define([[_color_comment]],[[m4_ifdef([[_hidecomments]],[[]],[[
<P><TABLE WIDTH=90% BGCOLOR="#FFFFFF" BORDER=1 CELLPADDING=5 CELLSPACING=0>
<TR><TD><FONT COLOR="$1">$2</FONT></TD></TR>
</TABLE><P>]])]])
m4_define([[_red_comment]],[[_color_comment(_red,$1)]])
m4_define([[_orange_comment]],[[_color_comment(_orange,$1)]])
m4_define([[_yellow_comment]],[[_color_comment(_yellow,$1)]])
m4_define([[_green_comment]],[[_color_comment(_green,$1)]])
m4_define([[_blue_comment]],[[_color_comment(_blue,$1)]])
m4_define([[_cyan_comment]],[[_color_comment(_cyan,$1)]])
m4_define([[_purple_comment]],[[_color_comment(_purple,$1)]])
m4_define([[_comment]],[[_color_comment(#000000,$1)]])

m4_dnl E-mail markup:

m4_define([[_mailto]],[[<A HREF="mailto:$1">$2</A>]])
m4_define([[_emailme]],[[_mailto(_email,$1)]])


m4_dnl Logical markup:

m4_define([[_em]],[[<EM>$1</EM>]])
m4_define([[_strong]],[[<STRONG>$1</STRONG>]])
m4_define([[_s]],[[<STRONG>$1</STRONG>]])
m4_define([[_cite]],[[<CITE>$1</CITE>]])
m4_define([[_pre]],[[<BLOCKQUOTE><PRE>$1</PRE></BLOCKQUOTE>]])
m4_define([[_code]],[[<CODE>$1</CODE>]])
m4_define([[_codeblock]],[[<TABLE BORDER=0 CELLPADDING=5 WIDTH=90%><TR><TD BGCOLOR="#f2c474"><PRE>$1</PRE></TD></TR></TABLE>]])


m4_dnl Physical markup:

m4_define([[_bold]],[[<B>$1</B>]])
m4_define([[_b]],[[<B>$1</B>]])
m4_define([[_italics]],[[<I>$1</I>]])
m4_define([[_i]],[[<I>$1</I>]])
m4_define([[_strike]],[[<S>$1</S>]])
m4_define([[_subscript]],[[<SUB>$1</SUB>]])
m4_define([[_sub]],[[<SUB>$1</SUB>]])
m4_define([[_superscript]],[[<SUP>$1</SUP>]])
m4_define([[_sup]],[[<SUP>$1</SUP>]])
m4_define([[_courier]],[[<TT>$1</TT>]])
m4_define([[_tt]],[[<TT>$1</TT>]])
m4_define([[_underline]],[[<U>$1</U>]])
m4_define([[_u]],[[<U>$1</U>]])
m4_define([[_small]],[[<SMALL>$1</SMALL>]])
m4_define([[_big]],[[<BIG>$1</BIG>]])
m4_define([[_center]],[[<CENTER>$1</CENTER>]])
m4_define([[_c]],[[<CENTER>$1</CENTER>]])


m4_dnl Image macros:
m4_dnl NOTE: the fancier features of these macros depend on 
m4_dnl       ImageMagick being on your system

m4_dnl this next macro uses ]]identify]] to create HEIGHT & WIDTH tags

m4_define([[_img_size_tags]],[[m4_esyscmd(if [ -e '$1' ]; then identify -format "HEIGHT=%h WIDTH=%w" $1 | tr -d "\n"; fi)]])

m4_define([[_img]],[[<IMG SRC="$1" _img_size_tags($1) $2>]])
m4_define([[_limg]],[[_img($1,$2 ALIGN="LEFT")]])
m4_define([[_rimg]],[[_img($1,$2 ALIGN="RIGHT")]])
m4_define([[_cimg]],[[<P><CENTER>_img($1,$2)</CENTER><P>]])
m4_define([[_imglink]],_link($1,_img($2,$3)))

m4_dnl automagically generate thumbnail images using convert
m4_define([[_img_thumbnail_path]],[[m4_regexp($1,[[^\(.*\)\.\([^\.]+\)$]],[[\1-thumb.\2]])]])
m4_define([[_img_gen_thumbnail]],[[m4_esyscmd(convert -geometry $2x$2 $1 _img_thumbnail_path($1))_img_thumbnail_path($1)]])
m4_define([[_img_thumbnail]],[[_imglink($1,_img_gen_thumbnail($1,$2),$3)]])


m4_dnl Physical Header Styles

m4_define([[_head1]], [[<H1>$1</H1>]])
m4_define([[_head2]], [[<H2>$1</H2>]])
m4_define([[_head3]], [[<H3>$1</H3>]])
m4_define([[_head4]], [[<H4>$1</H4>]])
m4_define([[_head5]], [[<H5>$1</H5>]])


m4_dnl Links

m4_define([[_link]],[[<A HREF="$1">$2</A>]])
m4_define([[_selflink]],[[_link($1,$1)]])
m4_define([[_locallink]],[[<A HREF="#$1">$2</A>]])
m4_define([[_label]],[[<A NAME="$1"><H2>$1</H2></A>]])
m4_define([[_name]],[[<A NAME="$1">$2</A>]])
m4_define([[_link_to_label]], _locallink($1,$1))
m4_define([[_cvsraq]],_link(http://cvsraq.sfbay.sun.com/cgi-bin/cvs.cgi/$1,$2))
m4_define([[_cvs]],_cvsraq($1,$2))
m4_define([[_glazed]],_link(http://glazed.sfbay.sun.com/cgi-bin/cvs.cgi/$1,$2))

m4_dnl Header and Footer

m4_define([[_header]],
  m4_ifelse(_file_exists(_header_file),[[yes]],m4_dnl
   [[m4_include(_header_file)]],
    [[<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2//EN">
<HTML> 
<HEAD> 
  <TITLE>$1</TITLE>
  <META NAME="Author" CONTENT="[[_author]]">
</HEAD>
<BODY BGCOLOR="#FFFFFE" $2>
<CENTER><A NAME="Contents"><H1>$1</H1></A></CENTER>
<P>
_byline
<P>
]])
_toc
_init_time_est
)

m4_define([[_footer]],
  m4_ifelse(_file_exists(_footer_file),[[yes]],m4_dnl
    [[m4_include(_footer_file)]],
    [[<HR>
_i(Generated using doclib.m4 revision _doclib_revision at _datestamp)]])
</BODY>
</HTML>
_end_toc
)

m4_dnl TABLE OF CONTENTS macros

m4_dnl START TOC
m4_define([[_toc]],[[_head2(Table of Contents:)
<UL>m4_divert(-1)
m4_define([[_h1_num]],0)
m4_define([[_h2_num]],0)
m4_define([[_h3_num]],0)
m4_define([[_h4_num]],0)
m4_define([[_h5_num]],0)
m4_divert(1)]])

m4_dnl HEADER 1
m4_define([[_h1]], [[m4_divert(0)m4_dnl
m4_ifelse(_h2_num,0,,[[</UL>]])m4_dnl
m4_ifelse(_h3_num,0,,[[</UL>]])m4_dnl
m4_ifelse(_h4_num,0,,[[</UL>]])m4_dnl
m4_ifelse(_h5_num,0,,[[</UL>]])m4_dnl
m4_divert(-1)
m4_define([[_h1_num]],m4_incr(_h1_num))
m4_define([[_h2_num]],0)m4_dnl
m4_define([[_h3_num]],0)m4_dnl
m4_define([[_h4_num]],0)m4_dnl
m4_define([[_h5_num]],0)m4_dnl
m4_define([[_toc_label]],[[_h1_num. $1]])m4_dnl
m4_divert(0)<LI><A HREF="#_toc_label">_toc_label</A>
m4_divert(1)<HR><BR><A NAME="_toc_label"><H2>_toc_label</H2></A>]])

m4_dnl HEADER 2
m4_define([[_h2]], [[m4_divert(0)m4_dnl
m4_ifelse(_h3_num,0,,[[</UL>]])m4_dnl
m4_ifelse(_h4_num,0,,[[</UL>]])m4_dnl
m4_ifelse(_h5_num,0,,[[</UL>]])m4_dnl
m4_ifelse(_h2_num,0,[[<UL>]])m4_dnl
m4_divert(-1)
m4_define([[_h2_num]],m4_incr(_h2_num))m4_dnl
m4_define([[_h3_num]],0)m4_dnl
m4_define([[_h4_num]],0)m4_dnl
m4_define([[_h5_num]],0)m4_dnl
m4_define([[_toc_label]],[[_h1_num._h2_num $1]])m4_dnl
m4_divert(0)<LI><A HREF="#_toc_label">_toc_label</A>
m4_divert(1)<A NAME="_toc_label"><H3>_toc_label</H3></A>]])

m4_dnl HEADER 3
m4_define([[_h3]], [[m4_divert(0)m4_dnl
m4_ifelse(_h4_num,0,,[[</UL>]])m4_dnl
m4_ifelse(_h5_num,0,,[[</UL>]])m4_dnl
m4_ifelse(_h3_num,0,[[<UL>]])m4_dnl
m4_divert(-1)
m4_define([[_h3_num]],m4_incr(_h3_num))m4_dnl
m4_define([[_h4_num]],0)m4_dnl
m4_define([[_h5_num]],0)m4_dnl
m4_define([[_toc_label]],[[_h1_num._h2_num._h3_num $1]])
m4_divert(0)<LI><A HREF="#_toc_label">_toc_label</A>
m4_divert(1)<A NAME="_toc_label"><H3>_toc_label</H3></A>]])

m4_dnl HEADER 4
m4_define([[_h4]], [[m4_divert(0)m4_dnl
m4_ifelse(_h5_num,0,,[[</UL>]])m4_dnl
m4_ifelse(_h4_num,0,[[<UL>]])m4_dnl
m4_divert(-1)
m4_define([[_h4_num]],m4_incr(_h4_num))m4_dnl
m4_define([[_h5_num]],0)m4_dnl
m4_define([[_toc_label]],[[_h1_num._h2_num._h3_num._h4_num $1]])
m4_divert(0)<LI><A HREF="#_toc_label">_toc_label</A>
m4_divert(1)<A NAME="_toc_label"><H4>_toc_label</H4></A>]])

m4_dnl HEADER 5
m4_define([[_h5]], [[m4_divert(0)m4_dnl
m4_ifelse(_h5_num,0,[[<UL>]])m4_dnl
m4_divert(-1)
m4_define([[_h5_num]],m4_incr(_h5_num))m4_dnl
m4_define([[_toc_label]],[[_h1_num._h2_num._h3_num._h4_num._h5_num $1]])
m4_divert(0)<LI><A HREF="#_toc_label">_toc_label</A>
m4_divert(1)<A NAME="_toc_label"><H4>_toc_label</H4></A>]])

m4_dnl END TOC
m4_define([[_end_toc]],[[m4_dnl
m4_divert(0)m4_ifelse(_h5_num,0,,[[</UL>]])m4_dnl
m4_divert(0)m4_ifelse(_h4_num,0,,[[</UL>]])m4_dnl
m4_divert(0)m4_ifelse(_h3_num,0,,[[</UL>]])m4_dnl
m4_divert(0)m4_ifelse(_h2_num,0,,[[</UL>]])m4_dnl
m4_divert(0)</UL>]])



m4_dnl TABLES

m4_dnl _start_table(Columns,TABLE parameters)
m4_dnl defaults are BORDER=1 CELLPADDING="1" CELLSPACING="1"
m4_dnl WIDTH="n" pixels or "n%" of screen width
m4_define([[_start_table]],[[<TABLE $1>]])
m4_define([[_table_hdr_item]], [[<TH>$1</TH>m4_ifelse($#,1,,[[_table_hdr_item(m4_shift($@))]])]])
m4_define([[_table_row_item]], [[<TD>$1</TD>m4_ifelse($#,1,,[[_table_row_item(m4_shift($@))]])]])
m4_define([[_table_hdr]],[[<TR>_table_hdr_item($@)</TR>]])
m4_define([[_table_row]],[[<TR>_table_row_item($@)</TR>]])
m4_define([[_tr]],[[<TR>_table_row_item($@)</TR>]])
m4_define([[_end_table]],[[</TABLE>]])



m4_define([[_byline]],[[<CENTER>By _emailme(_author &lt;_email&gt;)<BR><BR>
        This document last updated _datestamp<BR>
        Revision _revision
</CENTER>]])


m4_dnl Time Values

m4_define([[_datestamp]],m4_esyscmd([[date]]))
m4_define([[_year]],m4_esyscmd([[date +%Y]]))


m4_dnl Automatically Tallying Time Estimates

m4_define([[_init_time_est]], m4_dnl
  m4_define([[_day_total]],0) m4_dnl
  m4_define([[_day_subtotal]],0))

m4_define([[_reset_time_est_subtotal]], m4_dnl
  [[m4_define([[_day_subtotal]],0)]])

m4_dnl this is ugly, but it works...
m4_define([[_pretty_days]],m4_dnl
 [[m4_define([[_weeks]],m4_eval($1 / 5)) m4_dnl
  m4_define([[_days]],m4_eval($1 % 5)) m4_dnl
  m4_ifelse(_weeks,0, ,_weeks m4_ifelse(_weeks, 1, week, weeks)) m4_dnl
  m4_ifelse(m4_eval(_days && _weeks),1,[[, ]]) m4_dnl
  m4_ifelse(_days,0, ,_days m4_ifelse(_days, 1, day, days))]])

m4_define([[_time_estimate]],m4_dnl
  [[m4_define([[_day_total]],m4_eval(_day_total + $1)) m4_dnl
  m4_define([[_day_subtotal]],m4_eval(_day_subtotal + $1)) m4_dnl
  _pretty_days($1)]])

m4_define([[_time_est_subtotal]],m4_dnl
  [[_pretty_days(_day_subtotal)
  _reset_time_est_subtotal]])

m4_define([[_time_est_total]],[[_pretty_days(_day_total)]])


m4_divert

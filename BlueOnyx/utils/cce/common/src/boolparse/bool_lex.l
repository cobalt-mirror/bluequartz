/* $Id: bool_lex.l,v 1.3 2001/08/10 22:23:16 mpashniak Exp $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
%{

#include <ctype.h>
#include <bool_parse.h>
#include "bool_parse.tab.h"

extern int yyline;
YY_BUFFER_STATE mybuf;

void
bool_scan(const char *string) {
	mybuf = yy_scan_string(string);
}

void
bool_terminate() {
	yy_delete_buffer(mybuf);
}

%}
WORD	[A-Za-z_][A-Za-z0-9_]*
%%
"("		{
			return LPAREN;
		}
")"		{
			return RPAREN;
		}
[Aa][Nn][Dd]	{
			return AND;
		}
[Oo][Rr]|,	{
			return OR;
		}
[Nn][Oo][Tt]|!  {
			return NOT;
		}
{WORD}\([^()]*\)|{WORD} {
			yylval.str = yytext;
			return RULE;
		}
[ \t\n]*	{	/* ignored */;	}
.		{	return yytext[0];		}

%%

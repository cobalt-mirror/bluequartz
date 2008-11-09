/* $Id: bool_lex.l 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
%{

#include <ctype.h>
#include <bool_parse.h>
#include "bool.tab.h"

extern int boolline;
YY_BUFFER_STATE mybuf;
#undef boolwrap

void
bool_scan(const char *string) {
	mybuf = bool_scan_string(string);
}

void
bool_terminate() {
	bool_delete_buffer(mybuf);
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
			boollval.str = booltext;
			return RULE;
		}
[ \t\n]*	{	/* ignored */;	}
.		{	return booltext[0];		}

%%

/* 
 * The lexer calls this to see if there is more data.  If we define it here, 
 * then we don't have to link libfl
 */
int
boolwrap(void)
{
	return 1;
}

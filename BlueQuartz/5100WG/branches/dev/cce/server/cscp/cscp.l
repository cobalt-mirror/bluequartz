%{
/* $Id: cscp.l 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2002 Sun Microsystems, Inc.  All rights reserved. */
/*
 * Notes:
 *   * The 'args' start-state is for ensuring keywords don't matter once
 *     we've found a keyword.
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include "cce_common.h"
#include "cscp_parse.h"
#include "cce_types.h"
#include "stresc.h"
#include "cscp.tab.h"

#undef cscpwrap

static int cscp_lex_input(char *buf, int max_size);
#define YY_INPUT(buf, result, max_size) \
	do { result = cscp_lex_input(buf, max_size); } while (0)
%}

NL		(\n)
SP		([ \t])
ALPHA_C		([A-Za-z])
NUM_C		([0-9])
HEX_C		({NUM_C}|[A-Fa-f])
PUNCT_C		([~`!@#$%^&*()\-_+=|{}[\];:',.<>/?])
SAFE_C		({ALPHA_C}|{NUM_C}|{PUNCT_C})
ESCABLE_C	({ALPHA_C}|{NUM_C}|{PUNCT_C}|\\|\")
A		([aA])
B		([bB])
C		([cC])
D		([dD])
E		([eE])
F		([fF])
G		([gG])
H		([hH])
I		([iI])
J		([jJ])
K		([kK])
L		([lL])
M		([mM])
N		([nN])
O		([oO])
P		([pP])
Q		([qQ])
R		([rR])
S		([sS])
T		([tT])
U		([uU])
V		([vV])
W		([wW])
X		([xX])
Y		([yY])
Z		([zZ])

%x ARGS
%x ADMIN
%x BYE
%x FIND
%x SCHEMA
%x VALIDATE

%%

{A}{D}{M}{I}{N} {
	BEGIN(ADMIN); 
	return TOK_ADMIN;
}
<ADMIN>{D}{E}{B}{U}{G} {
	BEGIN(ARGS);
	return TOK_ADMIN_DEBUG;
}
<ADMIN>{R}{E}{S}{U}{M}{E} {
	BEGIN(ARGS);
	return TOK_ADMIN_RESUME;
}
<ADMIN>{S}{U}{S}{P}{E}{N}{D} {
	BEGIN(ARGS);
	return TOK_ADMIN_SUSPEND;
}
{A}{U}{T}{H} {
	BEGIN(ARGS);
	return TOK_AUTH;
}
{A}{U}{T}{H}{K}{E}{Y} {
	BEGIN(ARGS);
	return TOK_AUTHKEY;
}
{B}{A}{D}{D}{A}{T}{A} {
	BEGIN(ARGS);
	return TOK_BADDATA;
}
{B}{E}{G}{I}{N} {
	BEGIN(ARGS);
	return TOK_BEGIN;
}
{B}{Y}{E} {
	BEGIN(BYE); 
	return TOK_BYE;
}
<BYE>{S}{U}{C}{C}{E}{S}{S} {
	BEGIN(ARGS);
	return TOK_BYE_SUCCESS;
}
<BYE>{F}{A}{I}{L} {
	BEGIN(ARGS);
	return TOK_BYE_FAIL;
}
<BYE>{D}{E}{F}{E}{R} {
	BEGIN(ARGS);
	return TOK_BYE_DEFER;
}
{C}{L}{A}{S}{S}{E}{S} {
	BEGIN(ARGS);
	return TOK_CLASSES;
}
{C}{O}{M}{M}{I}{T} {
	BEGIN(ARGS);
	return TOK_COMMIT;
}
{C}{R}{E}{A}{T}{E} {
	BEGIN(ARGS);
	return TOK_CREATE;
}
{D}{E}{S}{T}{R}{O}{Y} {
	BEGIN(ARGS);
	return TOK_DESTROY;
}
{E}{N}{D}{K}{E}{Y} {
	BEGIN(ARGS);
	return TOK_ENDKEY;
}
{F}{I}{N}{D} {
	BEGIN(ARGS); 
	return TOK_FIND;
}
{G}{E}{T} {
	BEGIN(ARGS);
	return TOK_GET;
}
{H}{E}{L}{P} {
	BEGIN(ARGS);
	return TOK_HELP;
}
{I}{N}{F}{O} {
	BEGIN(ARGS);
	return TOK_INFO;
}
{N}{A}{M}{E}{S} {
	BEGIN(ARGS);
	return TOK_NAMES;
}
{S}{C}{H}{E}{M}{A} {
	BEGIN(SCHEMA);
	return TOK_SCHEMA;
}
<SCHEMA>{A}{D}{D} {
	BEGIN(ARGS);
	return TOK_SCHEMA_ADD;
}
<SCHEMA>{L}{I}{S}{T} {
	BEGIN(ARGS);
	return TOK_SCHEMA_LIST;
}
<SCHEMA>{R}{E}{M}{O}{V}{E} {
	BEGIN(ARGS);
	return TOK_SCHEMA_REMOVE;
}
{S}{E}{T} {
	BEGIN(ARGS);
	return TOK_SET;
}
{V}{A}{L}{I}{D}{A}{T}{E} {
	BEGIN(VALIDATE);
	return TOK_VALIDATE;
}
<VALIDATE>{P}{R}{O}{P}{E}{R}{T}{Y} {
	BEGIN(ARGS);
	return TOK_VALIDATE_PROPERTY;
}
<VALIDATE>{T}{Y}{P}{E}{D}{E}{F} {
	BEGIN(ARGS);
	return TOK_VALIDATE_TYPEDEF;
}
{W}{A}{R}{N} {
	BEGIN(ARGS);
	return TOK_WARN;
}
{W}{H}{O}{A}{M}{I} {
	BEGIN(ARGS);
	return TOK_WHOAMI;
}

<*>{SP}*={SP}* {
	return TOK_EQ;
}
<*>{SP}*~{SP}* {
	return TOK_REQ;
}
<*>(({NUM_C}+)|(0{X}{HEX_C}+)) {
	cscplval.str = cscp_strdup(cscptext);
	if (!cscplval.str) {
		CCE_OOM();
	}
	return TOK_NUMBER;
}
<*>({ALPHA_C}|_)({ALPHA_C}|{NUM_C}|_)* {
	cscplval.str = cscp_strdup(cscptext);
	if (!cscplval.str) {
		CCE_OOM();
	}
	return TOK_CID;
}
<*>\"({SAFE_C}|{SP}|\\{ESCABLE_C})*\" {
	cscptext[strlen(cscptext)-1] = '\0';
	cscplval.str = unescape_str(cscptext+1);
	if (!cscplval.str) {
		CCE_OOM();
	}
	cscp_note_alloc(cscplval.str);
	return TOK_QSTRING;
}
<*>^{SP}+ { /* eat leading/trailing space */ }
<*>{SP}+$ { /* eat trailing space */ }
<*>{SP}+ {
	/* other whitespace */
	return SP;
}
<*>{NL} {
	BEGIN(INITIAL); 
	return NL;
}
<*><<EOF>> {
	BEGIN(INITIAL); 
	return TOK_EOF;
}
<*>. {
	return cscptext[0];
}

%%

/* 
 * The lexer calls this to see if more data is available.  By specifying it,
 * we eliminate the need for libfl.
 */
int
cscpwrap(void)
{
	return 1;
}

static const char *cmd_inputptr;
static int 
cscp_lex_input(char *buf, int max_size)
{
	int i = 0;
	if (cmd_inputptr) {
		while (*cmd_inputptr && i < max_size) {
			*buf++ = *cmd_inputptr++;
			i++;
		}
	}
	return i;
}

void
cscp_lex_setbuffer(const char *buf)
{
	cmd_inputptr = buf;
	cscprestart(NULL);
	BEGIN(INITIAL);
}

void
cscp_lex_unsetbuffer()
{
	cmd_inputptr = NULL;
}

#ifdef LEX_MAIN
int 
main(void) 
{ 
	while (1) 
		printf("-- GOT %d\n", cscplex()); 
		
	return 0; 
}
#endif

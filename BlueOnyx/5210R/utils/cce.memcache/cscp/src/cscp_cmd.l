%{
/* $Id: cscp_cmd.l 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include <cce_common.h>
#include <cscp.h>
#include <cscp_tokens.h>

int cmd_myinput(char *buf, int max_size);

#define YY_INPUT(buf,result,max_size) \
	{ result = cmd_myinput(buf,max_size); }

%}

%option never-interactive 
%option prefix="cscp_cmd"
WS	([ \t\n\r])
EOW 	(({WS}+)|({WS}*$))
A       ([aA])
B       ([bB])
C       ([cC])
D       ([dD])
E       ([eE])
F       ([fF])
G       ([gG])
H       ([hH])
I       ([iI])
J       ([jJ])
K       ([kK])
L       ([lL])
M       ([mM])
N       ([nN])
O       ([oO])
P       ([pP])
Q       ([qQ])
R       ([rR])
S       ([sS])
T       ([tT])
U       ([uU])
V       ([vV])
W       ([wW])
X       ([xX])
Y       ([yY])
Z       ([zZ])

%x PARAM

%%
<INITIAL>{A}{D}{M}{I}{N}{EOW}       {BEGIN(PARAM);return TOK_ADMIN;}
<INITIAL>{A}{U}{T}{H}{EOW}          {BEGIN(PARAM);return TOK_AUTH;}
<INITIAL>{A}{U}{T}{H}{K}{E}{Y}{EOW} {BEGIN(PARAM);return TOK_AUTHKEY;}
<INITIAL>{B}{A}{D}{D}{A}{T}{A}{EOW} {BEGIN(PARAM);return TOK_BADDATA;}
<INITIAL>{B}{E}{G}{I}{N}{EOW}       {BEGIN(PARAM);return TOK_BEGIN;}
<INITIAL>{B}{Y}{E}{EOW}             {BEGIN(PARAM);return TOK_BYE;}
<INITIAL>{C}{O}{M}{M}{I}{T}{EOW}    {BEGIN(PARAM);return TOK_COMMIT;}
<INITIAL>{C}{L}{A}{S}{S}{E}{S}{EOW} {BEGIN(PARAM);return TOK_CLASSES;}
<INITIAL>{C}{R}{E}{A}{T}{E}{EOW}    {BEGIN(PARAM);return TOK_CREATE;}
<INITIAL>{D}{E}{S}{T}{R}{O}{Y}{EOW} {BEGIN(PARAM);return TOK_DESTROY;}
<INITIAL>{E}{N}{D}{K}{E}{Y}{EOW}    {BEGIN(PARAM);return TOK_ENDKEY;}
<INITIAL>{F}{I}{N}{D}{EOW}          {BEGIN(PARAM);return TOK_FIND;}
<INITIAL>{G}{E}{T}{EOW}             {BEGIN(PARAM);return TOK_GET;}
<INITIAL>{H}{E}{L}{P}{EOW}          {BEGIN(PARAM);return TOK_HELP;}
<INITIAL>{I}{N}{F}{O}{EOW}          {BEGIN(PARAM);return TOK_INFO;}
<INITIAL>{N}{A}{M}{E}{S}{EOW}       {BEGIN(PARAM);return TOK_NAMES;}
<INITIAL>{S}{E}{T}{EOW}             {BEGIN(PARAM);return TOK_SET;}
<INITIAL>{W}{A}{R}{N}{EOW}          {BEGIN(PARAM);return TOK_WARN;}
<INITIAL>{W}{H}{O}{A}{M}{I}{EOW}    {BEGIN(PARAM);return TOK_WHOAMI;}
<INITIAL>{WS}+                      {}
<INITIAL><<EOF>>                    {return TOK_EOF;}
<INITIAL>.                          {BEGIN(PARAM);return TOK_ERROR;}

<PARAM>[ \n\t\r,]+                  {}
<PARAM>\"([^\\\"]|(\\.))*\"         {return TOK_QUOTEDSTR;}
<PARAM>\#[0-9]+\#[A-Za-z0-9+/=]+    {return TOK_BINSTR;}
<PARAM>[A-Za-z0-9+_]+               {return TOK_ALPHANUM;}
<PARAM>={1,2}                       {return TOK_EQUALS;}
<PARAM>~                            {return TOK_REGEQUALS;}
<PARAM>\(                           {return TOK_OPENPAREN;}
<PARAM>\)                           {return TOK_CLOSEPAREN;}
<PARAM>\/                           {return TOK_SLASH;}
<PARAM>\.					{return TOK_PERIOD;}
<PARAM>.                            {return TOK_ERROR;}

%%
/* These are global "dummy variables" used to indicate special tokens via g_pointer */

char *cmd_inputptr;

int cmd_myinput(char *buf, int max_size)
{
	int i = 0;
	while (i < max_size) {
		if (*cmd_inputptr == '\0') break;
		*buf++ = *cmd_inputptr++;
		i++;
	}
	return i;
}

void cscp_cmd_setbuffer( char *buf )
{
	cmd_inputptr = buf;
	cscp_cmdrestart(NULL);
	BEGIN(INITIAL);
}

int yywrap() { return 1; }

void cscp_lexer_free()
{
	cscp_cmd_delete_buffer(YY_CURRENT_BUFFER);
}


/* $Id: cscp_parse.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * Author: jmayer@cobalt.com
 */
#include <cce_common.h>
#include <cscp_internal.h>
#include <cscp.h>
#include <cscp_parse.h>
#include <cce_scalar.h>
#include <string.h>
#include <glib.h>
#include <stdlib.h>

/* defined in lexer */
void cscp_cmd_setbuffer(char *); /* feed lexer a new string */
void cscp_lexer_free(); /* destroy the lexer's buffer */
int cscp_cmdlex(); /* get a token */
extern char *cscp_cmdtext;

cscp_parsed_cmd_t *
cscp_cmd_alloc(void)
{
	cscp_parsed_cmd_t *cmdP;

	/* create a new cscp object */
	cmdP = (cscp_parsed_cmd_t *)malloc(sizeof(cscp_parsed_cmd_t));
	cmdP->cmd = TOK_ERROR;
	cmdP->full_cmd = NULL;
	cmdP->params = NULL ; /* g_slist_alloc(); */
	cmdP->nparams = 0;
	cmdP->parse_err = 0;
	return cmdP;
}

cscp_parsed_cmd_t *
cscp_parse(char *str)
{
	int i;
	int password_detect;
	cscp_parsed_cmd_t *cmdP;
	GString *full_cmd;
	
	/* create a new cscp_cmd object */
	cmdP = cscp_cmd_alloc();
	if (!cmdP) { return 0; }

	cmdP->full_cmd = NULL;

	/* feed lex the string */
	cscp_cmd_setbuffer(str);

	/* parse the command token */
	cmdP->cmd = cscp_cmdlex();

	if (cmdP->cmd == TOK_EOF) {
		return (cmdP);
	}
	if (cmdP->cmd < 0) {
		cmdP->parse_err = 1;
		cmdP->full_cmd = strdup(str);
		return (cmdP);
	}
	
	full_cmd = g_string_new(cscp_cmdtext);
	g_string_append_c(full_cmd, ' ');

	/* parse the parameters */	
	password_detect = 0; /* used to filter out passwords */
	while ((!cmdP->parse_err) && (i = cscp_cmdlex())) {
	      	cce_scalar *sc =NULL;
		if (password_detect == 2) {
		  g_string_append(full_cmd, "xxx");
		} else {
		  g_string_append(full_cmd, cscp_cmdtext);
		}
		g_string_append_c(full_cmd, ' ');
		switch(i) {
			case TOK_QUOTEDSTR:
			      	sc = cce_scalar_new_from_qstr(cscp_cmdtext);
				break;
			case TOK_BINSTR:
			      	sc = cce_scalar_new_from_binstr(cscp_cmdtext);
				break;
			case TOK_ALPHANUM:
			case TOK_REGEQUALS:
			case TOK_EQUALS:
			case TOK_OPENPAREN:
			case TOK_CLOSEPAREN:
			case TOK_SLASH:
			case TOK_PERIOD:
			      	sc = cce_scalar_new_from_str(cscp_cmdtext);
				break;
			case TOK_ERROR:
			default:
				/* it'd be nice if we could end early here */
				cmdP->parse_err = 1;
			      	sc = cce_scalar_new_from_str(cscp_cmdtext);
		}
		
		/* state machine to look for "password =" */
		if (strncasecmp(sc->data, "password", 8) == 0) {
		  password_detect = 1; /* expect '=' */
		} else 
		if ((password_detect==1) && (strcmp(sc->data, "=")==0)) {
		  password_detect = 2; /* expect password */
		} else {
		  password_detect = 0; /* expect anything */
		}
		
		cmdP->params = g_slist_append(cmdP->params, sc);
		cmdP->nparams++;
	}

	cmdP->full_cmd = full_cmd->str;
	g_string_free(full_cmd, 0);
	
	return (cmdP);
}

const char *
cscp_cmd_getfull(cscp_parsed_cmd_t *cmdP)
{
	return cmdP->full_cmd;
}

void
cscp_cmd_free(cscp_parsed_cmd_t *cmdP)
{
	GSList *listP;
	gpointer gP;

	if (!cmdP) return;
	
	/* foreach element in the list, delete */
	listP = cmdP->params;
	while (listP)
	{
		gP = listP->data;
		if (gP) { 
			cce_scalar_destroy((cce_scalar *) gP); 
		}
		listP = g_slist_next(listP);
	}

	/* destruct the list itself, then, eh? */
	if (cmdP->params) {
		g_slist_free(cmdP->params);
	}

	/* free the full_cmd string */
	if (cmdP->full_cmd) {
		free(cmdP->full_cmd);
	}
	
	/* presto! */
	free(cmdP);

	/* and free the lexer */
	cscp_lexer_free();
}
/* Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met: 
 * 
 * -Redistribution of source code must retain the above copyright notice, this
 * list of conditions and the following disclaimer.
 * 
 * -Redistribution in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution. 
 *
 * Neither the name of Sun Microsystems, Inc. or the names of contributors may
 * be used to endorse or promote products derived from this software without 
 * specific prior written permission.

 * This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
 * 
 * You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
 */

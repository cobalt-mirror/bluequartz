/* $Id: cscp_tokens.h 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/* 
 * This file holds the definitions for CSCP token handling
 */

#ifndef _CCE_CSCP_TOKENS_H_ 
#define _CCE_CSCP_TOKENS_H_ 1

#include <cscp.h>

typedef enum {
	TOK_ERROR 		= -1,  

	/* command tokens  - keep grouped together */
	TOK_ADMIN		= CSCP_ADMIN_CMD,
	TOK_AUTH		= CSCP_AUTH_CMD,
	TOK_AUTHKEY		= CSCP_AUTHKEY_CMD,
	TOK_BADDATA		= CSCP_BADDATA_CMD,
	TOK_BEGIN		= CSCP_BEGIN_CMD,
	TOK_BYE			= CSCP_BYE_CMD,
	TOK_CLASSES		= CSCP_CLASSES_CMD,
	TOK_COMMIT		= CSCP_COMMIT_CMD,
	TOK_CREATE		= CSCP_CREATE_CMD,
	TOK_DESTROY		= CSCP_DESTROY_CMD,
	TOK_ENDKEY		= CSCP_ENDKEY_CMD,
	TOK_FIND		= CSCP_FIND_CMD,
	TOK_GET			= CSCP_GET_CMD,
	TOK_HELP		= CSCP_HELP_CMD,
	TOK_INFO		= CSCP_INFO_CMD,
	TOK_NAMES		= CSCP_NAMES_CMD,
	TOK_SET			= CSCP_SET_CMD,
	TOK_WARN		= CSCP_WARN_CMD,
	TOK_WHOAMI		= CSCP_WHOAMI_CMD,

	/* extra tokens - not commands */
	TOK_QUOTEDSTR		= 65535,
	TOK_BINSTR,
	TOK_ALPHANUM,
	TOK_REGEQUALS,
	TOK_EQUALS,
	TOK_OPENPAREN,
	TOK_CLOSEPAREN,
	TOK_SLASH,
	TOK_PERIOD,
	TOK_EOF,
	TOK__LAST
} token_t;

#endif /* cce/cscp_tokens.h */
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

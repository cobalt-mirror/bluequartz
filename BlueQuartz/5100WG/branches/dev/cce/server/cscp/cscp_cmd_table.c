/* $Id: cscp_cmd_table.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * CSCP command entries
 */

#include "cce_common.h"
#include "cscp.h"
#include <stdio.h>
#include "cscp_cmd_table.h"

/* the cmd table - must stay in order */
/* cf: cscp_cmd.l, cscp_parse.h, cscp.h */
//FIXME: update all this
struct cscp_cmd_ent cscp_cmd_table[] = {
	{
		CSCP_ADMIN_CMD,
		"ADMIN", "suspend | resume",
		"suspend or resume write operations",
		1, 2,
		CTF(CLIENT),
		STF(CMD)
	},
	{	
		CSCP_AUTH_CMD,
		"AUTH", "<username> <passwd>",
		"Authenticate as a user",
		2, 2, 
		CTF_ALL, 
		STF_ALL
	},
	{	
		CSCP_AUTHKEY_CMD,
		"AUTHKEY", "<username> <sessionid>",
		"Attempt to resume a session",
		2, 2, 
		CTF_ALL, 
		STF_ALL
	},
	{ 	
		CSCP_BADDATA_CMD,
		"BADDATA", "<oid> <property> <value>", 
		"Flag a key or value as invalid", 
		3, 3, 
		CTF(HANDLER), 
		STF_CMDTXN
	},
	{	
		CSCP_BEGIN_CMD,
		"BEGIN", NULL,
		"Begin a transaction",
		0, 0,
		CTF_ALL,
		STF(CMD)
	},
	{	
		CSCP_BYE_CMD, 
		"BYE", "[SUCCESS | FAIL | DEFER]",
		"Disconnect immediately, indicating exit status",
		0, -1, 
		CTF_ALL, 
		STF_ALL
	},
	{ 	
		CSCP_CLASSES_CMD,
		"CLASSES", NULL, 
		"List all classes", 
		0, 0, 
		CTF_ALL, 
		STF_ALL
	},
	{ 	
		CSCP_COMMIT_CMD,
		"COMMIT", NULL, 
		"Commit any defered actions", 
		0, 0, 
		CTF(CLIENT) | CTF(HANDLER), 
		STF(TXN)
	},
	{ 	
		CSCP_CREATE_CMD,
		"CREATE", "<class> [<key>=<value> ...]", 
		"Create a new instance of the specified class", 
		1, -1, 
		CTF(CLIENT) | CTF(HANDLER), 
		STF_CMDTXN
	},
	{ 	
		CSCP_DESTROY_CMD,
		"DESTROY", "<oid>", 
		"Destroy the specified object", 
		1, 1, 
		CTF(CLIENT) | CTF(HANDLER), 
		STF_CMDTXN
	},
	{	
		CSCP_ENDKEY_CMD,
		"ENDKEY", NULL,
		"Expire the current sessionid now",
		0, 0, 
		CTF_ALL, 
		STF_ALL
	},
	{ 	
		CSCP_FIND_CMD,
		"FIND", "<class> [<key>=<value> ...]", 
		"Find instances of the specified class, matching given criteria", 
		1, -1, 
		CTF_ALL, 
		STF_ALL
	},
	{ 	
		CSCP_GET_CMD,
		"GET", "<oid>[.<namespace>]", 
		"Get a list of key=value pairs for the specified object", 
		1, 3, 
		CTF_ALL, 
		STF_ALL
	},
	{	
		CSCP_HELP_CMD,
		"HELP", NULL,
		"Show help about all commands currently available",
		0, 0, 
		CTF_ALL, 
		STF_ALL
	},
	{	
		CSCP_INFO_CMD,
		"INFO", "<message>",
		"Emit an informational message",
		1, 1, 
		CTF(HANDLER), 
		STF_CMDTXN
	},
	{ 	
		CSCP_NAMES_CMD,
		"NAMES", "<oid> | <class>", 
		"List available namespaces for an object or class", 
		1, 1, 
		CTF_ALL, 
		STF_ALL
	},
	{ 	
		CSCP_SET_CMD,
		"SET", "<oid>[.<namespace>] [<key>=<value> ...]", 
		"Set all listed keys to listed values in the specified object", 
		1, -1, 
		CTF(CLIENT) | CTF(HANDLER), 
		STF_CMDTXN
	},
	{	
		CSCP_WARN_CMD,
		"WARN", "<message>",
		"Emit a warning message",
		1, 1, 
		CTF(HANDLER), 
		STF_CMDTXN
	},
	{	
		CSCP_WHOAMI_CMD,
		"WHOAMI", NULL,
		"Get the object id of the currently logged in user",
		0, 0, 
		CTF_ALL, 
		STF_ALL
	},
	{ 0, NULL, NULL, NULL, 0, 0, 0L, 0L }
};
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

/* 
 * This file holds the external definitions for CSCP and CSCP related data
 * $Id: cscp.h 3 2003-07-17 15:19:15Z will $
 */
#ifndef __CSCP_H__
#define __CSCP_H__ 1

#include <codb.h>

/* the various contexts in which a connection can be */
typedef enum {
	CTXT_CLIENT = 0,
	CTXT_HANDLER,
	CTXT_RO,
	CTXT_MAX,
} cscp_ctxt_t;
char *ctxt_name(int ctxt);
#define CTF(x)		(1 << CTXT_ ## x)
#define CTF_ALL		0xffff
#define CTF_NONE		0

/* the various states in which the protocol engine can be */
typedef enum {
	STATE_ID = 0,
	STATE_CMD,
	/* state_closed is a flag state */
	STATE_CLOSED,
	STATE_MAX
} cscp_state_t;
char *state_name(int state);
#define STF(x)		(1 << STATE_ ## x)
#define STF_ALL		0xffff
#define STF_NONE		0


/* every CSCP command gets one of these */
typedef enum {
	CSCP_AUTH_CMD = 0,
	CSCP_AUTHKEY_CMD,
	CSCP_BADDATA_CMD,
	CSCP_BYE_CMD,
	CSCP_CLASSES_CMD,
	CSCP_COMMIT_CMD,
	CSCP_CREATE_CMD,
	CSCP_DESTROY_CMD,
	CSCP_ENDKEY_CMD,
	CSCP_FIND_CMD,
	CSCP_GET_CMD,
	CSCP_HELP_CMD,
	CSCP_INFO_CMD,
	CSCP_NAMES_CMD,
	CSCP_SET_CMD,
	CSCP_WARN_CMD,
	CSCP_WHOAMI_CMD,
	CSCP_CMD_MAX
} cscp_cmd_int_t;


/* the MSB of return codes */
#define CSCP_RET_INFO			1
#define CSCP_RET_SUCCESS		2
#define CSCP_RET_WARN			3
#define CSCP_RET_ERR			4


#endif /* cscp.h */
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

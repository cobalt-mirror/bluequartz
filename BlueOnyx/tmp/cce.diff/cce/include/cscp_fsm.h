/* $Id: cscp_fsm.h 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * This file defines the CSCP protocol state machine
 */

#ifndef _CCE_CSCP_FSM_H_
#define _CCE_CSCP_FSM_H_ 1

#include <cscp.h>
#include <codb.h>
#include <cce_ed.h>
#include <sessionmgr.h>
#include <glib.h>

/* a cscp connection */
typedef struct {
	cscp_ctxt_t context; 	/* connection context */
	cscp_state_t state; 	/* current state */
	int client; 		/* fd to client */
	char *idstr;		/* ID string */
	oid_t auth_oid;		/* the currently authenticated oid */
	codb_handle *odbh; 	/* handle to the odb */
	cce_ed *ed;		/* event dispatcher */
	cce_session *session;	/* session to which we belong */
	union {
	    int wrlock;		/* semaphore for writing (client) */
	    ed_handler_event *event; /* handler event that triggered us */
	} ctxt_data;
	char *clibuf; 		/* to hold read, but unparsed cmds */
	GString *resp_buffer;   /* response buffer */
} cscp_conn;

int write_str(cscp_conn *cscp, const char *str);
int write_str_nl(cscp_conn *cscp, const char *str);
int write_err(cscp_conn *cscp, const char *str);

typedef enum {
	FSM_RET_FAIL = -1,
	FSM_RET_SUCCESS = 0,
	FSM_RET_DEFER = 1
} fsm_ret;

/* the main entry to FSM */
fsm_ret cscp_fsm(cscp_ctxt_t ctxt, int fd, char *idstr, codb_handle *h, 
	cce_ed *ed, ...);
	
void cscp_shutdown(void);
void cscp_onfire(void);

#endif
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

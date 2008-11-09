/* $Id: cscp_fsm.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * Notes:
 *  * We will change to STATE_CLOSED if we get a 0 length read
 */

#include "cce_common.h"
#include "cscp_all.h"
#include "cscp.h"
#include "codb.h"
#include "cce_ed.h"
#include "sessionmgr.h"
#include "classconf.h"
#include "csem.h"
#include "stresc.h"
#include "g_hashwrap.h"

#include <stdlib.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <string.h>
#include <signal.h>
#include <stdarg.h>
#include <time.h>
#include <ctype.h>

/* the declaration of a state function */
#define DECL_STATE(fn)		static fsm_ret state_ ## fn(cscp_conn *cscp)
/* validate that a token is a valid cmd for a given state */
//FIXME: dump this?
#define is_valid_cmd_tok(tok, cscp)	\
		((cscp_cmd_table[tok].contexts & (1<<(cscp)->context)) \
		 && (cscp_cmd_table[tok].states & (1<<(cscp)->state)))

/* easier hash manipulations */
//FIXME: fix or dump these
#define HASH_NEW()		g_hashwrap_new(g_str_hash, g_str_equal, NULL, NULL)
#define HASH_INSERT(h, k, v)	g_hashwrap_insert(h, k, v)
#define HASH_DESTROY(h)		g_hashwrap_destroy(h)

#define MAX_SUSP_REASON		511

/*
 * Function prototypes 
 */

/* state function prototypes */
DECL_STATE(id);
DECL_STATE(cmd);
DECL_STATE(txn);
DECL_STATE(client_cmd);
DECL_STATE(client_txn);
DECL_STATE(handler_cmd);
DECL_STATE(handler_txn);
DECL_STATE(ro);

/* local function prototypes */
static int fsm_loop(cscp_conn *cscp);
cscp_conn *alloc_conn(cscp_ctxt_t ctxt, int fd, char *idstr, codb_handle *h, 
	cce_ed *ed);
static void dealloc_conn(cscp_conn *cscp);
static int cscp_parse_conn(cscp_conn *conn, struct cscp_parsed_cmd *parsed_cmd);
static char *oid_to_str(oid_t oid);
static int merge_attribs(GHashWrap *dest, GHashWrap *src);
static void setup_cscp_signals(struct sigaction *oldpipe);
static void unset_cscp_signals(struct sigaction *oldpipe);
static oid_t get_useroid(codb_handle *odbh, char *name);
static void set_auth_oid(cscp_conn *cscp, oid_t authoid);
static int dump_get_req(cscp_conn *cscp, get_t *getreq);
static void dump_baddata(cscp_conn *cscp);
static void dump_baddata_core(cscp_conn *cscp, GHashWrap *hash, oid_t oid);
static void write_perm_errs(cscp_conn *cscp, GHashWrap *errs);
static void do_cscp_shutdown(cscp_conn *cscp);
static void close_conn(cscp_conn *cscp);
static const char *is_suspended();
static void suspend(const char *reason);

/* functions where most of the work gets done */
static int do_help(cscp_conn *cscp);
static void do_admin(cscp_conn *cscp, struct cscp_parsed_cmd *cmd);
static int fail_auth(cscp_conn *cscp, char *user);
static void auth_failed_sleep(void);
static int do_auth(cscp_conn *cscp, struct cscp_parsed_cmd *cmd);
static int do_dispatch(cscp_conn *cscp, const char *);
static int do_commit(cscp_conn *cscp, const char *);
static int do_create(cscp_conn *cscp, struct cscp_parsed_cmd *cmd);
static int do_destroy(cscp_conn *cscp, struct cscp_parsed_cmd *cmd);
static int do_find(cscp_conn *cscp, struct cscp_parsed_cmd *cmd);
static int do_get(cscp_conn *cscp, struct cscp_parsed_cmd *cmd);
static int do_names(cscp_conn *cscp, struct cscp_parsed_cmd *cmd);
static int do_set(cscp_conn *cscp, struct cscp_parsed_cmd *cmd);
static int do_baddata(cscp_conn *cscp, struct cscp_parsed_cmd *cmd);
static int do_info(cscp_conn *cscp, struct cscp_parsed_cmd *cmd);
static int do_warn(cscp_conn *cscp, struct cscp_parsed_cmd *cmd);
static int do_bye(cscp_conn *cscp, struct cscp_parsed_cmd *cmd, int succeed);
static int do_whoami(cscp_conn *cscp);
static int do_authkey(cscp_conn *cscp, struct cscp_parsed_cmd *cmd);
static int do_endkey(cscp_conn *cscp);
static int do_classes(cscp_conn *cscp);

int txnstopflag = 0;		/* don't running txn library support */
int txnfailflag = 0;		/* fail all transactions */

/*
 * FIXME: this is here for compatibility with CODB.  Once CODB has been
 * converted to use props_t and property_t, we NEED to dump this
 */
static GHashWrap *
props_to_compat(props_t *props)
{
	GHashWrap *hw;
	int i;
	
	hw = HASH_NEW();

	for (i = 0; props && i < props_count(props); i++) {
		property_t *k;
		char *v;

		props_index(props, i, &k, &v);
		HASH_INSERT(hw, k->property, v);
	}
	return hw;
}
	
static void cscplog(cscp_conn *cscp, const char *s, ...)
{
	char buf[1024];
	va_list args;

	va_start(args, s);
	vsnprintf(buf, sizeof(buf), s, args);
	va_end(args);
	CCE_SYSLOG("client %d:%s: %s", codb_handle_getoid(cscp->odbh),
		cscp->idstr, buf);
}

/* state function table - must jive with cscp_state_t enum */
static int (*states[])(cscp_conn *cscp) = {
	state_id,
	state_cmd,
	state_txn,
	state_ro,
};

/*
 * NOTE: this code MUST be re-entrant.  Example:
 *    client connects, cced (fork()ed) enters cscp_fsm(CTXT_CLIENT, ...)
 *    client issues cmd that starts handler h1
 *    cced enters cscp_fsm(CTXT_HANDLER, ...), handles cmds
 *    h1 issues cmd that starts handler h2
 *    cced enters cscp_fsm(CTXT_HANDLER, ...), handles cmds
 *
 *    We must examine global/static variables very carefully.
 */

/* global flag to signal CSCP - re-entrant safe, because we never reset it */
static enum {NO, SHUTDOWN, ONFIRE} need_shutdown = NO;

/* this is called from each top-level state */
static int
read_cmd(cscp_conn *cscp, struct cscp_parsed_cmd *cmd)
{
	int r;

	r = cscp_parse_conn(cscp, cmd);
	if (r < 0) {
		/* connection must have died, or we received a SIGTERM */
		cscp->state = STATE_CLOSED;
	} else if (r > 0) {
		/* parse error */
		if (cmd->cmd == CSCP_CMD_NONE) {
			//DPRINTF(DBG_CSCP, 
			  //  "CSCP: bad command (%s)\n",cmd->full_cmd);
			write_str_nl(cscp, badcmd_msg);
		} else {
			//DPRINTF(DBG_CSCP, 
			 //   "CSCP: bad params (%s)\n",cmd->full_cmd);
			write_str_nl(cscp, badparams_msg);
		}
	}

	return r;
}

/*
 * setup_cscp_signals
 *
 * set up any signals that we need for the duration of the FSM
 */
static void
setup_cscp_signals(struct sigaction *oldpipe)
{
	struct sigaction act;

	act.sa_handler = SIG_IGN;
	sigemptyset(&act.sa_mask);
	act.sa_flags = 0;
	act.sa_restorer = NULL;
	sigaction(SIGPIPE, &act, oldpipe);
}

/*
 * unset_cscp_signals
 *
 * put signals back how they were when we entered the FSM
 */
static void
unset_cscp_signals(struct sigaction *oldpipe)
{
	sigaction(SIGPIPE, oldpipe, NULL);
}

/*
 * cscp_fsm
 *
 * the 'main' routine for a program implementing CSCP
 * CLIENT mode needs the varargs to be a semaphore (int)
 * HANDLER mode needs the varargs to be an ed_handler_event *
 */
fsm_ret
cscp_fsm(cscp_ctxt_t ctxt, int fd, char *id, codb_handle *h, cce_ed *ed, ...)
{
	cscp_conn *cscp;
	int r = 0;
	struct sigaction oldpipe;
	va_list args;

	DPRINTF(DBG_CSCP, "cscp_fsm: starting context = %s\n", ctxt_name(ctxt));

	if (ctxt == CTXT_HANDLER
		|| ctxt == CTXT_ROLLBACK
		|| ctxt == CTXT_RULE)
	{
		/* handlers are run with admin rights, for now */
		h = codb_handle_branch(h);
		codb_handle_addflags(h, CODBF_ADMIN);
	}

	cscp = alloc_conn(ctxt, fd, id, h, ed);
	if (!cscp) {
		DPRINTF(DBG_CSCP, "cscp_fsm: alloc_conn() failed\n");
		return -99;
	}

	/* get ready for the last (variable) args */
	va_start(args, ed);

	/* set up the context specific data */
	if (ctxt == CTXT_CLIENT) {
		cscp->ctxt_data.wrlock = va_arg(args, int);
	} else if (ctxt == CTXT_HANDLER
		|| ctxt == CTXT_ROLLBACK
		|| ctxt == CTXT_RULE)
	{
		cscp->ctxt_data.event = va_arg(args, ed_handler_event *);
	}

	/* signals */
	setup_cscp_signals(&oldpipe);

	/* do the main deed */
	r = fsm_loop(cscp);
	
	/* cleanup */
	dealloc_conn(cscp);
	unset_cscp_signals(&oldpipe);
	if (ctxt == CTXT_HANDLER
		|| ctxt == CTXT_ROLLBACK
		|| ctxt == CTXT_RULE)
	{
		codb_handle_unbranch(h);
	}

	/* clean up varargs */
	va_end(args);

	DPRINTF(DBG_CSCP, "cscp_fsm: returning %d\n", r);

	return r;
}

/*
 * fsm_loop
 *
 * the main FSM loop - called by both entry points
 */
static int
fsm_loop(cscp_conn *cscp)
{
	int r = 0;
	cscp_state_t oldstate;

	/* loop until exit */
	while (cscp->state != STATE_CLOSED) {
		oldstate = cscp->state;
		
		/* transition into the next state */
		r = states[cscp->state](cscp);
		DPRINTF(DBG_EXCESSIVE, "fsm_loop: %s returned %d\n",
			state_name(oldstate), r);
	}	

	return r;
}

/*
 * alloc_conn
 *
 * make a new cscp_conn object
 */
cscp_conn *
alloc_conn(cscp_ctxt_t ctxt, int fd, char *idstr, codb_handle *h, cce_ed *ed)
{
	cscp_conn *cscp;
	
	cscp = malloc(sizeof(cscp_conn));
	if (!cscp) {
		return NULL;
	}
	
	cscp->context = ctxt;
	cscp->state = STATE_ID;
	cscp->client = fd;
	if (!idstr)
		idstr = "";
	cscp->idstr = strdup(idstr);
	cscp->odbh = h;
	cscp->ed = ed;
	cscp->session = NULL;
	cscp->clibuf = NULL;

	cscp->resp_buffer = g_string_new("<< ");

	return cscp;
}

/*
 * dealloc_conn
 *
 * clean up a cscp_conn object
 */
static void
dealloc_conn(cscp_conn *cscp)
{
	/* clean up some dynamic memory that may be left around */
	if (cscp->clibuf) {
		free(cscp->clibuf);
	}
	if (cscp->idstr) {
		free(cscp->idstr);
	}
	if (cscp->session) {
		cce_session_destroy(cscp->session);
	}
	
	g_string_free(cscp->resp_buffer, 1);
	
	free(cscp);
}

/*
 * These are the state functions, as declared in the table above
 */

/*
 * state_id
 *
 * Display the protocol header to the client, based on context
 */
DECL_STATE(id)
{
	GSList *p;
	const char *reason;

	/* say hello */
	write_str_nl(cscp, cscp_id);

	/* handlers have a special header */
	if (cscp->context == CTXT_HANDLER
		|| cscp->context == CTXT_ROLLBACK
		|| cscp->context == CTXT_RULE)
	{
		codb_event *e;
		p = cscp->ctxt_data.event->events;
		
		/* print each event */
		while (p) {
			e = (codb_event *)p->data;
		
			write_str(cscp, handler_msg);
			write_str(cscp, oid_to_str(codb_event_get_oid(e)));
			write_str(cscp, ".");
			if (codb_event_is_create(e)) {
				write_str_nl(cscp, "_CREATE");
			} else if (codb_event_is_destroy(e)) {
				write_str_nl(cscp, "_DESTROY");
			} else {
				write_str_nl(cscp, codb_event_get_string(e));
			}
			p = g_slist_next(p);
		}
	}

	if (cscp->context == CTXT_ROLLBACK) {
		write_str_nl(cscp, rollback_msg);
	}

	reason = is_suspended();
	if (reason) {
		char *escreason;

		escreason = escape_str(reason);
		write_str(cscp, suspended_msg);
		write_str(cscp, "\"");
		write_str(cscp, escreason);
		write_str_nl(cscp, "\"");
		free(escreason);
	}

	/* tell them we're ready */
	write_str_nl(cscp, ready_msg);

	/* next state is... */
	if (cscp->context == CTXT_ROLLBACK || cscp->context == CTXT_RULE)
	{
		cscp->state = STATE_RO;
	} else {
		cscp->state = STATE_CMD;
	}

	return FSM_RET_SUCCESS;
}

/*
 * state_cmd
 *
 * Handle any CSCP commands issued - this is significantly different for
 * clients and handlers, so we branch early to discreet functions
 */
DECL_STATE(cmd)
{
	if (cscp->context == CTXT_CLIENT) {
		return state_client_cmd(cscp);
	} else if (cscp->context == CTXT_HANDLER) {
		return state_handler_cmd(cscp);
	} else {
		DPRINTF(DBG_CSCP, "unhandled context %s in state %s\n",
			ctxt_name(cscp->context), state_name(cscp->state));
	}

	return FSM_RET_FAIL;
}

/*
 * state_txn
 *
 * Handle any CSCP commands issued in transaction state - this is
 * significantly different for clients and handlers, so we branch early to
 * discreet functions
 */
DECL_STATE(txn)
{
	if (cscp->context == CTXT_CLIENT) {
		return state_client_txn(cscp);
	} else if (cscp->context == CTXT_HANDLER) {
		return state_handler_txn(cscp);
	} else {
		DPRINTF(DBG_CSCP, "unhandled context %s in state %s\n",
			ctxt_name(cscp->context), state_name(cscp->state));
	}

	return FSM_RET_FAIL;
}

/* 
 * state_client_cmd
 * 
 * handle frontend connections
 * This is the only state that cares if we receive a SIGTERM, If we do, 
 * we do what we can to shutdown gracefully.
 */
DECL_STATE(client_cmd)
{
	fsm_ret state_ret = FSM_RET_FAIL;
	struct cscp_parsed_cmd cmd;
	struct timeval start;
	
	DPRINTF(DBG_CSCP, "%s:%s\n", 
		ctxt_name(cscp->context), state_name(cscp->state));

	/* loop for this state */
	while (cscp->state == STATE_CMD) {
		/* this is a safe point - check for changes in global state */
		if (need_shutdown) {
			do_cscp_shutdown(cscp);
			continue;
		}

		/* read and parse a command (blocks, SIGTERM can interrupt) */
		if (read_cmd(cscp, &cmd) != 0) {
			//FIXME: cleanup STATE_CLOSED side-effects!
			//also fix the comment @top of this file!
			if (need_shutdown) {
				/* this may be interrupted by SIGTERM */
				do_cscp_shutdown(cscp);
			}
			continue;
		}

		/* handle it */
		//FIXME: DPROFILE_START(PROF_CSCP, &start, "CSCP %.30s", cmd->full_cmd);
		switch (cmd.cmd) {
			case CSCP_CMD_ADMIN:
			case CSCP_CMD_ADMIN_SUSPEND:
			case CSCP_CMD_ADMIN_RESUME:
				do_admin(cscp, &cmd);
				break;
			case CSCP_CMD_AUTH:
				do_auth(cscp, &cmd);
				break;
			case CSCP_CMD_AUTHKEY:
				do_authkey(cscp, &cmd);
				break;
			case CSCP_CMD_BEGIN:
				cscp->state = STATE_TXN;
				write_str_nl(cscp, success_msg);
				cscplog(cscp, "BEGIN");
				break;
			case CSCP_CMD_BYE:
				state_ret = do_bye(cscp, &cmd, 1);
				break;
			case CSCP_CMD_ENDKEY:
				do_endkey(cscp);
				break;
			case CSCP_CMD_HELP:
				do_help(cscp);
				break;
			case CSCP_CMD_WHOAMI:
				do_whoami(cscp);
				break;
			case CSCP_CMD_FIND:
				do_find(cscp, &cmd);
				break;
			case CSCP_CMD_GET:
				do_get(cscp, &cmd);
				break;
			case CSCP_CMD_NAMES:
				do_names(cscp, &cmd);
				break;
			case CSCP_CMD_CLASSES:
				do_classes(cscp);
				break;
			case CSCP_CMD_CREATE:
				if (do_create(cscp, &cmd) == CODB_RET_SUCCESS)
				{
					do_commit(cscp, "CREATE");
				} else {
					codb_flush(cscp->odbh);
				}
				break;
			case CSCP_CMD_DESTROY:
				if (do_destroy(cscp, &cmd) == CODB_RET_SUCCESS)
				{
					do_commit(cscp, "DESTROY");
				} else {
					codb_flush(cscp->odbh);
				}
				break;
			case CSCP_CMD_SET:
				if (do_set(cscp, &cmd) == CODB_RET_SUCCESS)
				{
					do_commit(cscp, "SET");
				} else {
					codb_flush(cscp->odbh);
				}
				break;
			default:
				CCE_SYSLOG("%s:%s: uncaught invalid cmd (%d)", 
					ctxt_name(cscp->context), 
					state_name(cscp->state), cmd.cmd);
				write_str_nl(cscp, badcmd_msg);
		}
		/* cleanup */
		DPROFILE(PROF_CSCP, start, "command is done");
		cscp_parsed_cmd_cleanup(&cmd);
	}	

	return state_ret;
}

/* 
 * state_client_txn
 * 
 * handle frontend connections in the transaction state
 * This is the only (other) state that cares if we receive a SIGTERM, If we
 * do, we do what we can to shutdown gracefully.
 */
DECL_STATE(client_txn)
{
	fsm_ret state_ret = FSM_RET_SUCCESS;
	struct cscp_parsed_cmd cmd;
	struct timeval start;
	
	DPRINTF(DBG_CSCP, "%s:%s\n", 
		ctxt_name(cscp->context), state_name(cscp->state));

	/* loop for this state */
	while (cscp->state == STATE_TXN) {
		/* this is a safe point - check for changes in global state */
		if (need_shutdown) {
			// FIXME: mpashniak: need to do something special here
			exit(255);
			do_cscp_shutdown(cscp);
			continue;
		}

		/* read and parse a command (blocks, SIGTERM can interrupt) */
		if (read_cmd(cscp, &cmd) != 0) {
			if (need_shutdown) {
				// FIXME: mpashniak: need to do something
				exit(255);
				/* this may be interrupted by SIGTERM */
				do_cscp_shutdown(cscp);
			}
			continue;
		}

		/* handle it */
		//FIXME: DPROFILE_START(PROF_CSCP, &start, "CSCP %.30s", cmd->full_cmd);
		switch (cmd.cmd) {
			case CSCP_CMD_BYE:
				state_ret = do_bye(cscp, &cmd, 1);
				break;
			case CSCP_CMD_HELP:
				do_help(cscp);
				break;
			case CSCP_CMD_AUTH:
				do_auth(cscp, &cmd);
				break;
			case CSCP_CMD_AUTHKEY:
				do_authkey(cscp, &cmd);
				break;
			case CSCP_CMD_ENDKEY:
				do_endkey(cscp);
				break;
			case CSCP_CMD_WHOAMI:
				do_whoami(cscp);
				break;
			case CSCP_CMD_FIND:
				do_find(cscp, &cmd);
				break;
			case CSCP_CMD_GET:
				do_get(cscp, &cmd);
				break;
			case CSCP_CMD_NAMES:
				do_names(cscp, &cmd);
				break;
			case CSCP_CMD_CLASSES:
				do_classes(cscp);
				break;
			case CSCP_CMD_CREATE:
				if (do_create(cscp, &cmd) == CODB_RET_SUCCESS)
					write_str_nl(cscp, success_msg);
				break;
			case CSCP_CMD_DESTROY:
				if (do_destroy(cscp, &cmd) == CODB_RET_SUCCESS)
					write_str_nl(cscp, success_msg);
				break;
			case CSCP_CMD_SET:
				if (do_set(cscp, &cmd) == CODB_RET_SUCCESS)
					write_str_nl(cscp, success_msg);
				break;
			case CSCP_CMD_COMMIT:
				do_commit(cscp, "COMMIT");
				cscp->state=STATE_CMD;
				break;
			default:
				CCE_SYSLOG("%s:%s: uncaught invalid cmd (%d)", 
					ctxt_name(cscp->context), 
					state_name(cscp->state), cmd.cmd);
				write_str_nl(cscp, badcmd_msg);
		}
		/* cleanup */
		DPROFILE(PROF_CSCP, start, "command is done");
		cscp_parsed_cmd_cleanup(&cmd);
	}	

	return state_ret;
}

/* 
 * state_handler
 * 
 * handle handler connections
 */
DECL_STATE(handler_cmd)
{
	fsm_ret state_ret = FSM_RET_FAIL;
	struct cscp_parsed_cmd cmd;
	
	DPRINTF(DBG_CSCP, "%s:%s\n", 
		ctxt_name(cscp->context), state_name(cscp->state));

	/* loop for this state */
	while (cscp->state == STATE_CMD) {
		/* read and parse a command */
		if (read_cmd(cscp, &cmd) != 0) {
			continue;
		}

		/* handle it */
		switch (cmd.cmd) {
			case CSCP_CMD_BYE:
				state_ret = do_bye(cscp, &cmd, 0);
				break;
			case CSCP_CMD_HELP:
				do_help(cscp);
				break;
			case CSCP_CMD_AUTH:
				do_auth(cscp, &cmd);
				break;
			case CSCP_CMD_AUTHKEY:
				do_authkey(cscp, &cmd);
				break;
			case CSCP_CMD_BEGIN:
				cscp->state = STATE_TXN;
				write_str_nl(cscp, success_msg);
				cscplog(cscp, "BEGIN");
				break;
			case CSCP_CMD_ENDKEY:
				do_endkey(cscp);
				break;
			case CSCP_CMD_WHOAMI:
				do_whoami(cscp);
				break;
			case CSCP_CMD_FIND:
				do_find(cscp, &cmd);
				break;
			case CSCP_CMD_GET:
				do_get(cscp, &cmd);
				break;
			case CSCP_CMD_NAMES:
				do_names(cscp, &cmd);
				break;
			case CSCP_CMD_CLASSES:
				do_classes(cscp);
				break;
			case CSCP_CMD_BADDATA:
				do_baddata(cscp, &cmd);
				break;
			case CSCP_CMD_INFO:
				do_info(cscp, &cmd);
				break;
			case CSCP_CMD_WARN:
				do_warn(cscp, &cmd);
				break;
			case CSCP_CMD_CREATE:
				if (do_create(cscp, &cmd) == CODB_RET_SUCCESS) {
					do_dispatch(cscp, "CREATE");
				} else {
					codb_flush(cscp->odbh);
				}
				break;
			case CSCP_CMD_DESTROY:
				if (do_destroy(cscp, &cmd) == CODB_RET_SUCCESS) {
					do_dispatch(cscp, "DESTROY");
				} else {
					codb_flush(cscp->odbh);
				}
				break;
			case CSCP_CMD_SET:
				if (do_set(cscp, &cmd) == CODB_RET_SUCCESS)
				{
					do_dispatch(cscp, "SET");
				} else {
					codb_flush(cscp->odbh);
				}
				break;
			default:
				CCE_SYSLOG("%s:%s: uncaught invalid cmd (%d)", 
					ctxt_name(cscp->context), 
					state_name(cscp->state), cmd.cmd);
				write_str_nl(cscp, badcmd_msg);
		}
		/* cleanup */
		cscp_parsed_cmd_cleanup(&cmd);
	}	

	return state_ret;
}

/* 
 * state_handler_txn
 */
DECL_STATE(handler_txn)
{
	fsm_ret state_ret = FSM_RET_FAIL;
	struct cscp_parsed_cmd cmd;
	
	DPRINTF(DBG_CSCP, "%s:%s\n", 
		ctxt_name(cscp->context), state_name(cscp->state));

	/* loop for this state */
	while (cscp->state == STATE_TXN) {
		/* read and parse a command */
		if (read_cmd(cscp, &cmd) != 0) {
			continue;
		}

		/* handle it */
		switch (cmd.cmd) {
			case CSCP_CMD_BYE:
				state_ret = do_bye(cscp, &cmd, 0);
				break;
			case CSCP_CMD_HELP:
				do_help(cscp);
				break;
			case CSCP_CMD_AUTH:
				do_auth(cscp, &cmd);
				break;
			case CSCP_CMD_AUTHKEY:
				do_authkey(cscp, &cmd);
				break;
			case CSCP_CMD_ENDKEY:
				do_endkey(cscp);
				break;
			case CSCP_CMD_WHOAMI:
				do_whoami(cscp);
				break;
			case CSCP_CMD_FIND:
				do_find(cscp, &cmd);
				break;
			case CSCP_CMD_GET:
				do_get(cscp, &cmd);
				break;
			case CSCP_CMD_NAMES:
				do_names(cscp, &cmd);
				break;
			case CSCP_CMD_CLASSES:
				do_classes(cscp);
				break;
			case CSCP_CMD_BADDATA:
				do_baddata(cscp, &cmd);
				break;
			case CSCP_CMD_INFO:
				do_info(cscp, &cmd);
				break;
			case CSCP_CMD_WARN:
				do_warn(cscp, &cmd);
				break;
			case CSCP_CMD_CREATE:
				if (do_create(cscp, &cmd) == CODB_RET_SUCCESS)
					write_str_nl(cscp, success_msg);
				break;
			case CSCP_CMD_DESTROY:
				if (do_destroy(cscp, &cmd) == CODB_RET_SUCCESS)
					write_str_nl(cscp, success_msg);
				break;
			case CSCP_CMD_SET:
				if (do_set(cscp, &cmd) == CODB_RET_SUCCESS)
					write_str_nl(cscp, success_msg);
				break;
			case CSCP_CMD_COMMIT:
				do_dispatch(cscp, "COMMIT");
				state_ret = FSM_RET_SUCCESS;
				cscp->state=STATE_CMD;
				break;
			default:
				CCE_SYSLOG("%s:%s: uncaught invalid cmd (%d)", 
					ctxt_name(cscp->context), 
					state_name(cscp->state), cmd.cmd);
				write_str_nl(cscp, badcmd_msg);
		}
		/* cleanup */
		cscp_parsed_cmd_cleanup(&cmd);
	}	

	return state_ret;
}

/* this is for rollback and rules */
DECL_STATE(ro)
{
	fsm_ret state_ret = FSM_RET_FAIL;
	struct cscp_parsed_cmd cmd;
	struct timeval start;
	
	DPRINTF(DBG_CSCP, "%s:%s\n", 
		ctxt_name(cscp->context), state_name(cscp->state));

	/* loop for this state */
	while (cscp->state == STATE_RO) {
		/* this is a safe point - check for changes in global state */
		if (need_shutdown) {
			do_cscp_shutdown(cscp);
			continue;
		}

		/* read and parse a command (blocks, SIGTERM can interrupt) */
		if (read_cmd(cscp, &cmd) != 0) {
			if (need_shutdown) {
				/* this may be interrupted by SIGTERM */
				do_cscp_shutdown(cscp);
			}
			continue;
		}

		/* handle it */
		//FIXME: DPROFILE_START(PROF_CSCP, &start, "CSCP %.30s", cmd->full_cmd);
		switch (cmd.cmd) {
			case CSCP_CMD_AUTH:
				do_auth(cscp, &cmd);
				break;
			case CSCP_CMD_AUTHKEY:
				do_authkey(cscp, &cmd);
				break;
			case CSCP_CMD_BYE:
				state_ret = do_bye(cscp, &cmd, 0);
				break;
			case CSCP_CMD_ENDKEY:
				do_endkey(cscp);
				break;
			case CSCP_CMD_HELP:
				do_help(cscp);
				break;
			case CSCP_CMD_WHOAMI:
				do_whoami(cscp);
				break;
			case CSCP_CMD_FIND:
				do_find(cscp, &cmd);
				break;
			case CSCP_CMD_GET:
				do_get(cscp, &cmd);
				break;
			case CSCP_CMD_NAMES:
				do_names(cscp, &cmd);
				break;
			case CSCP_CMD_CLASSES:
				do_classes(cscp);
				break;
			default:
				CCE_SYSLOG("%s:%s: uncaught invalid cmd (%d)", 
					ctxt_name(cscp->context), 
					state_name(cscp->state), cmd.cmd);
				write_str_nl(cscp, badcmd_msg);
		}
		/* cleanup */
		DPROFILE(PROF_CSCP, start, "command is done");
		cscp_parsed_cmd_cleanup(&cmd);
	}	

	return state_ret;
}

/* change this to turn on debugging for parse_conn() */
#if 0
#define PARSEDBG(f, a...) DPRINTF(DBG_CSCP, f, ##a)
#else
#define PARSEDBG(f, a...)
#endif
/*
 * cscp_parse_conn
 *
 * read a cscp_cmd from an open fd, trying to be clever about reading
 *
 * Return: 0 on success, >0 on error, <0 on EOF
 */
static int
cscp_parse_conn(cscp_conn *conn, struct cscp_parsed_cmd *parsed_cmd)
{
	int r;
	int done = 0;
	char *p;
	char *cmdstr;
	char buf[512];
	struct sigaction termsig;
	struct sigaction oldtermsig;
	
	/* loop so we can swallow blank lines */
	
	while (!done) {
		cmdstr = NULL;

		while (!done) {
			PARSEDBG("loop...\n");
			/* see if we have any left over from the last read */
			if (conn->clibuf) {
				cmdstr = conn->clibuf;
				PARSEDBG("clibuf = \"%s\"\n", 
				    conn->clibuf);
				/* ooh, maybe even a full cmd! */
				p = strchr(cmdstr, '\n');
				if (p) {
					p++;
					if (*p) {
						/* if there is more, save 
						 * the extra */
						conn->clibuf = strdup(p);
						/* and break the string */
						*p = '\0';
					} else {
						/* got nl, and nothing more */
						conn->clibuf = NULL;
					}
					done = 1;
				} else {
					conn->clibuf = NULL;
				}
				PARSEDBG("cmdstr after clibuf = \"%s\"\n", 
				    cmdstr);
			}
	
			if (done) {
				PARSEDBG("done.\n");
				break;
			}

			/*
			 * here we set up SIGTERM to NOT restart syscalls.
			 * this lets us detect if a SIGTERM (our exit signal)
			 * comes in, and shutdown gracefully.
			 *
			 * It is a bit hackish, but then, so is the fact that 
			 * signal handlers do not get any parameters.
			 *
			 * We restore it in all cases
			 */
			sigaction(SIGTERM, NULL, &termsig);
			termsig.sa_flags |= SA_RESETHAND;
			termsig.sa_flags &= (~SA_RESTART);
			sigaction(SIGTERM, &termsig, &oldtermsig);

			/* cmdstr may have data - but maybe not a full cmd */
			while (1) {
				errno = 0;
				r = read(conn->client, buf, sizeof(buf)-1);
				if (r > 0) {
					break;
				}

				/* otherwise r <= 0 */
				if (errno != EINTR || need_shutdown) {
					/* TERM signals set need_shutdown */
					if (cmdstr) free(cmdstr);
					sigaction(SIGTERM, &oldtermsig, NULL);
					DPRINTF(DBG_CSCP, "read = %d\n", r);
					return -1;
				}
			}
			buf[r] = '\0';
			PARSEDBG("read: %s", buf);
			sigaction(SIGTERM, &oldtermsig, NULL);

			/* did we get EOL? */
			p = strchr(buf, '\n');
			if (p) {
				p++;
				if (*p) {
					/* if there is more, save the extra */
					conn->clibuf = strdup(p);
					if (!conn->clibuf) {
						CCE_OOM();
					}
					/* and break the string */
					*p = '\0';
				}
				done = 1;
			}
			if (cmdstr) {
				char *new;
				/* append buf to cmdstr */
				new = malloc(strlen(cmdstr) + strlen(buf) + 1);
				if (!new) {
					CCE_OOM();
				} else {
					sprintf(new, "%s%s", cmdstr, buf);
					free(cmdstr);
					cmdstr = new;
				}
			} else {
				cmdstr = strdup(buf);
				if (!cmdstr) {
					CCE_OOM();
				}
			}
		}
		PARSEDBG("FINAL cmdstr = \"%s\"\n", cmdstr);

		r = cscp_parse_line(cmdstr, parsed_cmd);

		/* pass to parsed_cmd */
		parsed_cmd->raw_input = cmdstr;

		if (r == 0 && parsed_cmd->cmd == CSCP_CMD_NONE) {
			/* swallow blank lines */
			done = 0;
		}
	}

	//DPRINTF(DBG_CSCP, ">> %s\n", cmd->full_cmd);

	return r;
}

/*
 * These are pretty straightforward - do the work for each command
 */
static int
do_bye(cscp_conn *cscp, struct cscp_parsed_cmd *cmd, int succeed)
{
	int r = FSM_RET_FAIL;
	bye_t status;

	status = cmd->args.bye.status;
	if (status == BYE_NONE) {
		status = succeed ? BYE_SUCCESS : BYE_FAIL;
	}
	
	switch (status) {
	case BYE_SUCCESS:
		cce_session_refresh(cscp->session); 
		r = FSM_RET_SUCCESS;
		break;
	case BYE_FAIL:
		r = FSM_RET_FAIL;
		break;
	case BYE_DEFER:
		r = FSM_RET_DEFER;
		break;
	default:
		break;
	}

	write_str_nl(cscp, bye_msg); 
	close_conn(cscp);

	//DPRINTF(DBG_CSCP_XTRA, "BYE %s from client %s\n", param, cscp->idstr);

	return r;
}

/* 
 * do_help
 *
 * show usage for all commands in cscp_cmd_table
 * undocumented...
 */
static int
do_help(cscp_conn *cscp)
{
	char help_msg[256];
	int i = 0;	

	/* print each valid cmd entry for this state */
	while (cscp_cmd_table[i].cmd) {
		struct cscp_cmd_ent *e = &cscp_cmd_table[i];

		if (is_valid_cmd_tok(i, cscp)) {
			snprintf(help_msg, sizeof(help_msg)-1, "%s%s%s : %s", 
				e->cmd, e->params ? " " : "", 
				e->params ? e->params : "", e->descr);
			write_str_nl(cscp, help_msg);
		}
		i++;
	}
	
	return 0;
}

static void
suspend(const char *reason)
{
	int fd;
	int len;

	len = strlen(reason);
	if (len > MAX_SUSP_REASON)
		len = MAX_SUSP_REASON;

	fd = open(CCELOCKFILE "~", O_CREAT|O_EXCL|O_RDWR, S_IRUSR|S_IWUSR);
	if (fd < 0)
		return;

	write(fd, reason, len);
	close(fd);
	rename(CCELOCKFILE "~", CCELOCKFILE);
}

static const char *
is_suspended()
{
	static char buf[MAX_SUSP_REASON+1];
	int fd;
	int len;

	fd = open(CCELOCKFILE, O_RDONLY);
	if (fd < 0)
		return NULL;

	len = read(fd, buf, MAX_SUSP_REASON);
	buf[len] = '\0';
	close(fd);

	return buf;
}

static void
do_admin(cscp_conn *cscp, struct cscp_parsed_cmd *cmd)
{
	char *command;
	int ret = FSM_RET_FAIL;

	if (!codb_is_sysadmin(cscp->odbh)) {
		cscplog(cscp, "ADMIN command failed: permission denied");
		write_str_nl(cscp, fail_msg);
		return;
	}

	if (cmd->cmd == CSCP_CMD_ADMIN_SUSPEND) {
		cscplog(cscp, "ADMIN SUSPEND \"%s\"",
		    escape_str(cmd->args.admin_suspend.reason));

		if (!is_suspended()) {
			suspend(cmd->args.admin_suspend.reason);
			if (is_suspended())
				ret = FSM_RET_SUCCESS;
		}
	} else if (cmd->cmd == CSCP_CMD_ADMIN_RESUME) {
		cscplog(cscp, "ADMIN RESUME");

		if (is_suspended()) {
			unlink(CCELOCKFILE);
			if (!is_suspended())
				ret = FSM_RET_SUCCESS;
		}
	}

	if (ret == FSM_RET_SUCCESS) {
		cscplog(cscp, "ADMIN command succeeded");
		write_str_nl(cscp, success_msg);
	} else {
		cscplog(cscp, "ADMIN command failed");
		write_str_nl(cscp, fail_msg);
	}
}

/*
 * set_auth_oid
 * 
 * set the currently authed oid, and drop any special flags
 */
static void
set_auth_oid(cscp_conn *cscp, oid_t authoid)
{
	codb_handle_setoid(cscp->odbh, authoid);

	/* drop any special permission flags */
	codb_handle_rmflags(cscp->odbh, CODBF_ADMIN);
}

/*
 * fail_auth
 *
 * authentication failed, sleep a bit, do some work, then let the user know
 */
static int
fail_auth(cscp_conn *cscp, char *user)
{
	/* nope */
	write_str_nl(cscp, fail_msg);

	/* log it */
	cscplog(cscp, "AUTH to user \"%s\" failed", user);

	auth_failed_sleep();

	return -1;
}

static void
auth_failed_sleep(void)
{
	/* wait for a bit - 1/2 to 3 secs */
	srand(time(NULL));
	usleep((random()%2500000) + 500000);

	/* do something useful, at least */
	cce_session_cleanup();
}


static int 
do_auth(cscp_conn *cscp, struct cscp_parsed_cmd *cmd)
{
	oid_t useroid;

	/* test for magic "" user */
	if (!strcmp(cmd->args.auth.username, "")) {
		/* as if we had just connected (no rights) */
		set_auth_oid(cscp, 0);
	} else {
		/* get the user oid */
		useroid = get_useroid(cscp->odbh, cmd->args.auth.username);
		if (!useroid) {
			return fail_auth(cscp, cmd->args.auth.username);
		}

		/* make sure the user is enabled */
		{
			GHashWrap *attribs;
			char *enabled;
			int authflaghack;
	
			attribs = HASH_NEW();
			/* hack to not run security rules on auth */
			authflaghack = codb_handle_getflags(cscp->odbh);
			codb_handle_addflags(cscp->odbh, CODBF_ADMIN);
			codb_get(cscp->odbh, useroid, "", attribs);
			codb_handle_setflags(cscp->odbh, authflaghack);
	
			enabled = g_hashwrap_lookup(attribs, "enabled");
			if (!enabled 
			 || !strcmp(enabled, "") || !strcmp(enabled, "0")) {
				codb_attr_hash_destroy(attribs);
				return fail_auth(cscp, cmd->args.auth.username);
			}

			codb_attr_hash_destroy(attribs);
		}

		/* check if the user is validated through PAM */
		if (cscp_auth(cmd->args.auth.username, cmd->args.auth.password) != 0) {
			return fail_auth(cscp, cmd->args.auth.username);
		}

		cscplog(cscp, "AUTH to \"%s\" (%ld) succeeded", 
		    cmd->args.auth.username, useroid);

		/* success */
		set_auth_oid(cscp, useroid);
	}

	/* send a new sessionid */
	cscp->session = cce_session_new(cmd->args.auth.username);
	cce_session_cleanup();
	if (!cscp->session) {
		write_str(cscp, nomem_msg);
		write_str_nl(cscp, "SESSIONID");
		CCE_SYSLOG("can't get new session");
	} else {
		write_str(cscp, sessionid_msg);
		write_str_nl(cscp, cce_session_getid(cscp->session));
	}

	write_str_nl(cscp, success_msg);

	return 0;
}

static int
do_dispatch(cscp_conn *cscp, const char *op)
{
	int r;

	/* do pre-dispatch call */
	cce_ed_txnbranch(cscp->ed);

	r = cce_ed_dispatch(cscp->ed, cscp->odbh);

	/* post-dispatch unbranch/rollback */
	if (r) {
		cce_ed_txnrollback(cscp->ed);
	}
	cce_ed_txnunbranch(cscp->ed);

	/* show all bad data returns */
	dump_baddata(cscp);
	cce_ed_flush_baddata(cscp->ed);

	/* check result of ed */
	if (r) {
		codb_flush(cscp->odbh);
		write_str_nl(cscp, fail_msg);
		cscplog(cscp, "%s failed", op);
	} else {
		codb_commit(cscp->odbh);
		write_str_nl(cscp, success_msg);
		cscplog(cscp, "%s succeeded", op);
	}

	return r;
}

/* this relys on only being called from the client context */
static int
do_commit(cscp_conn *cscp, const char *op)
{
	int r;
	GSList *messages;
	const char *reason;

	/* get the write lock */
	csem_down(cscp->ctxt_data.wrlock);

	/* check the suspended semaphore */
	reason = is_suspended(cscp);
	if (reason)
	{
		char *escreason;

		escreason = escape_str(reason);
		write_str(cscp, suspended_msg);
		write_str(cscp, "\"");
		write_str(cscp, escreason);
		write_str_nl(cscp, "\"");
		cscplog(cscp, "suspended: \"%s\"", escreason);
		free(escreason);
		r = FSM_RET_FAIL;
	}
	else
	{
		/* pre-dispatch branch */
		cce_ed_txnstart(cscp->ed);

		/* dispatch */
		r = cce_ed_dispatch(cscp->ed, cscp->odbh);

		/* fail every transaction? */
		if (txnfailflag)
			r = FSM_RET_FAIL;

		/* post-dispatch unbranch/rollback */
		if (r) {
			cce_ed_txnrollback(cscp->ed);
		}
		cce_ed_txnend(cscp->ed);

		/* show all bad data returns */
		dump_baddata(cscp);

		/* show all messages */
		messages = cce_ed_access_infos(cscp->ed);
		while (messages) {
			char *escdata = escape_str((char *)messages->data);
			write_str(cscp, info_msg);
			write_str(cscp, "\"");
			write_str(cscp, escdata);
			write_str_nl(cscp, "\"");
			messages = g_slist_next(messages);
		}
		messages = cce_ed_access_warns(cscp->ed);
		while (messages) {
			char *escdata = escape_str((char *)messages->data);
			write_str(cscp, warn_msg);
			write_str(cscp, "\"");
			write_str(cscp, escdata);
			write_str_nl(cscp, "\"");
			messages = g_slist_next(messages);
		}

		/* flush all messages */
		cce_ed_flush(cscp->ed);
	}

	/* check result of ed */
	if (r) {
		codb_flush(cscp->odbh);
		write_str_nl(cscp, fail_msg);
		cscplog(cscp, "%s failed", op);
	} else {
		codb_commit(cscp->odbh);
		write_str_nl(cscp, success_msg);
		cscplog(cscp, "%s succeeded", op);
	}

	/* release the write lock */
	csem_up(cscp->ctxt_data.wrlock);

	return r;
}

static int
do_create(cscp_conn *cscp, struct cscp_parsed_cmd *cmd)
{
	GHashWrap *attribs;
	GHashWrap *errs = NULL;
	oid_t newoid;
	int r;

	//cscplog(cscp, "%s", cscp_cmd_getfull(cmd));
	
	//FIXME: TAKE OUT THIS COMPAT CRAP
	attribs = props_to_compat(cmd->args.create.props);
	errs = HASH_NEW();
		
	/* do the CREATE */
	r = codb_create(cscp->odbh, cmd->args.create.classname, 
	    attribs, errs, &newoid);
	if (r != CODB_RET_SUCCESS) {
		switch (r) {
			case CODB_RET_UNKCLASS:
				write_str(cscp, unknownclass_msg);
				write_str_nl(cscp, cmd->args.create.classname);
				break;
			case CODB_RET_BADDATA: {
				dump_baddata_core(cscp, errs, 0); 
				break;
			}
			case CODB_RET_PERMDENIED:
				write_perm_errs(cscp, errs);
				break;
			default:
				write_err(cscp, "UNKNOWN ERROR DURING CREATE");
		}
		write_str_nl(cscp, fail_msg);
		cscplog(cscp, "CREATE %s failed (%d)", 
		    cmd->args.create.classname, r);
	} else {
		char num_buf[32];
		snprintf(num_buf, sizeof(num_buf), "%d", newoid);
		write_str(cscp, object_msg);
		write_str_nl(cscp, num_buf);
	}
	
	/* cleanup */
	if (errs) {
		HASH_DESTROY(attribs);
		codb_attr_hash_destroy(errs);
	}

	return r;
}

static int
do_destroy(cscp_conn *cscp, struct cscp_parsed_cmd *cmd)
{
	int r;
	char *param;
	oid_t oid;
	GHashWrap *errs = NULL;

	cscplog(cscp, "%s", cmd->raw_input);
	
	/* convert to an oid_t */
	if (!cmd->args.destroy.oid) {
		/* can't destroy 0 */
		write_str_nl(cscp, badparams_msg);
		return -1;
	}

	errs = HASH_NEW();

	/* do the DESTROY */
	r = codb_destroy(cscp->odbh, cmd->args.destroy.oid, errs);
	if (r) {
		switch (r) {
			case CODB_RET_UNKOBJ:
				write_str(cscp, unknownobj_msg);
				write_str_nl(cscp, oid_to_str(oid));
				break;
			case CODB_RET_PERMDENIED:
				write_str(cscp, permdenied_msg);
				write_str_nl(cscp, "DESTROY");
				break;
			default:
				write_err(cscp, "UNKNOWN ERROR DURING DESTROY");
		}
		write_str_nl(cscp, fail_msg);
		cscplog(cscp, "DESTROY %lu failed (%d)", oid, r);
	}

	codb_attr_hash_destroy(errs);

	return r;
}

static gboolean
destroy_criteria(gpointer key, gpointer value, gpointer data)
{
	if (key) {
		free((char*)key);
	}
	if (value) {
		free((char*)value);
	}
	return TRUE;
}

static void
destroy_criteria_table(GHashWrap *table)
{
	g_hashwrap_foreach_remove(table, destroy_criteria, NULL);
	g_hashwrap_destroy(table);
}

static int
do_find(cscp_conn *cscp, struct cscp_parsed_cmd *cmd)
{
	GSList *oidlist = NULL;
	GHashWrap *crit;
	GHashWrap *recrit;
	int r;

	//FIXME: TAKE THIS COMPAT CRAP OUT
	crit = props_to_compat(cmd->args.find.exact_props);
	recrit = props_to_compat(cmd->args.find.regex_props);

	/* do the find */
	r = codb_find(cscp->odbh, cmd->args.find.classname, 
	    crit, recrit,
	    cmd->args.find.sorttype, cmd->args.find.sortprop, &oidlist);

	/* if r is an error - send the correct string down */
	if (r) {
		switch (r) {
			case CODB_RET_UNKCLASS:
				write_str(cscp, unknownclass_msg);
				write_str_nl(cscp, cmd->args.find.classname);
				write_str_nl(cscp, fail_msg);
				break;
			case CODB_RET_BADARG:
				write_str_nl(cscp, badparams_msg);
				break;
			default:
				write_err(cscp, "UNKNOWN ERROR DURING FIND");
				write_str_nl(cscp, fail_msg);
				break;
		}
	} else {
		GSList *p;
		
		/* for each datum in oidlist, send a data line to client */
		p = oidlist;
		while (p) {
			char num_buf[32];
			oid_t oid;

			oid = *(int *)p->data;
			snprintf(num_buf, sizeof(num_buf), "%d", oid);

			write_str(cscp, object_msg);
			write_str_nl(cscp, num_buf);
			p = g_slist_next(p);
		}

		/* terminate */
		write_str_nl(cscp, success_msg);
	}

	/* cleanup */
	codb_free_list(oidlist);
	HASH_DESTROY(crit);
	HASH_DESTROY(recrit);

	return r;
}

static int
do_get(cscp_conn *cscp, struct cscp_parsed_cmd *cmd)
{
	int i;
	int r = 0;
			
	/* all the requests have to succeed */
	for (i = 0; i < cmd->args.get.nrequests; i++) {
		get_t *g = &(cmd->args.get.requests[i]);
		char *class = NULL;

		/* is the oid valid? */
		if (!codb_objexists(cscp->odbh, g->oid)) {
			write_str(cscp, unknownobj_msg);
			write_str_nl(cscp, oid_to_str(g->oid));
			r++;
			continue;
		}
		class = codb_get_classname(cscp->odbh, g->oid);
		/* is the namespace valid? */
		if (!codb_classconf_getclass(classconf, class, 
		    g->property.namespace)) {
			if (g->property.namespace) {
				write_str(cscp, unknownns_msg);
				write_str_nl(cscp, g->property.namespace);
			} else {
				write_str(cscp, unknownclass_msg);
				write_str_nl(cscp, class);
			}
			r++;
			if (class) { 
				free(class);
			}
			continue;
		}
		/* is the property valid? */
		if (g->property.property) {
		    	if (!codb_class_get_property(classconf, class, 
			    &g->property)) {
				//FIXME: add an unknownprop message
				r++;
				if (class) { 
					free(class);
				}
				continue;
			}
		}
	}
	if (r) {
		write_str_nl(cscp, fail_msg);
		return -1;
	}

	/* process the requests */
	for (i = 0; i < cmd->args.get.nrequests; i++) {
		get_t *g = &(cmd->args.get.requests[i]);
		int r;

		/* dump the object, if it exists */
		r = dump_get_req(cscp, g);
	
		/* if r is an error - send the correct string down */
		if (r) {
			switch (r) {
			case CODB_RET_UNKOBJ:
				write_str(cscp, unknownobj_msg);
				write_str_nl(cscp, oid_to_str(g->oid));
				break;
			case CODB_RET_UNKNSPACE:
				write_str(cscp, unknownns_msg);
				write_str_nl(cscp, g->property.namespace);
				break;
			//FIXME: new error cases
			case CODB_RET_UNKCLASS: {
				char *class;
				
				class = codb_get_classname(cscp->odbh, g->oid);
				write_str(cscp, unknownclass_msg);
				write_str_nl(cscp, class);
				free(class);
				break;
			}
			default:
				write_err(cscp, "UNKNOWN ERROR DURING GET");
			}
			//FIXME: DPRINTF(DBG_CSCP_XTRA, 
				//"client %s: GET %d%s%s failed (%d)\n",
				//cscp->idstr, oid, ns?".":"", ns?ns:"", r);
		}
	}
		
	/* if we get here we've succeeded */
	write_str_nl(cscp, success_msg);
	//FIXME: DPRINTF(DBG_CSCP_XTRA, "GET %d%s%s succeeded from client %s\n", 
		//oid, ns?".":"", ns?ns:"", cscp->idstr);
		
	return 0;
}

/*
 * dump_get_req
 *
 * send an object down the wire
 */
static int
dump_get_req(cscp_conn *cscp, get_t *getreq)
{		
	int adminflaghack;
	GHashWrap *attribs;
	GHashWrap *attribs_new;
	codb_handle *old_odbh;
	int created_flag = 0;
	int r = 0;

	/* prepare to catch the attributes */
	attribs = HASH_NEW();
	
	old_odbh = codb_handle_rootref(cscp->odbh);

	/* get the old info (before this txn) */
	adminflaghack = codb_handle_getflags(old_odbh);
	codb_handle_setflags(old_odbh, codb_handle_getflags(cscp->odbh));
	r = codb_get_old(old_odbh, getreq->oid, getreq->property.namespace, 
	    attribs);
	codb_handle_setflags(old_odbh, adminflaghack);
	
	/* check return code... */
	if ((r == CODB_RET_UNKOBJ) && codb_objexists(cscp->odbh, getreq->oid)) {
		/* mmm mmm fresh baked object */
		write_str_nl(cscp, created_msg);
		created_flag = 1;
	} else if (r) {
		/* r is a real error */
		codb_attr_hash_destroy(attribs);
		return r;
	} else {
		int hashi, hashn;

		/* for each data in attribs, send a data line to client */
		hashn = g_hashwrap_size(attribs);
		for (hashi = 0; hashi < hashn; hashi++) {
		     	char *tmp;
			gpointer key, val;

			g_hashwrap_index(attribs, hashi, &key, &val);

			write_str(cscp, data_msg);
			write_str(cscp, (char *)key);
			write_str(cscp, " = \"");
			tmp = escape_str((char *)val);
			write_str(cscp, tmp);
			write_str_nl(cscp, "\"");
			free(tmp);
		}
	}
	
	/* cleanup */
	codb_attr_hash_destroy(attribs);
	
	/* now it is time for changed properties */
	attribs = HASH_NEW();

	adminflaghack = codb_handle_getflags(old_odbh);
	codb_handle_setflags(old_odbh, codb_handle_getflags(cscp->odbh));

	if (created_flag) {
		/* if the object was just created, all attributes are new */
		r = codb_get(old_odbh, getreq->oid, 
		    getreq->property.namespace, attribs);
	} else {
		/* get the changed info from before this level of txn */
		r = codb_get_changed(old_odbh, getreq->oid, 
		    getreq->property.namespace, attribs);
	}

	codb_handle_setflags(old_odbh, adminflaghack);
	
	if (r) {
		if (r == CODB_RET_UNKOBJ) {
			/* object has been destroyed */
			write_str_nl(cscp, destroyed_msg);
			r = 0;
		}
		codb_attr_hash_destroy(attribs);
		return r;
	}

	attribs_new = HASH_NEW();

	/* get the changed properties in this txn level */
	r = codb_get_changed(cscp->odbh, getreq->oid, 
	    getreq->property.namespace, attribs_new);

	if (r) {
		if (r == CODB_RET_UNKOBJ) {
			/* object has been destroyed */
			write_str_nl(cscp, destroyed_msg);
			r = 0;
		}
		codb_attr_hash_destroy(attribs);
		codb_attr_hash_destroy(attribs_new);
		return r;
	}

	/* merge attribs and attribs_new */
	merge_attribs(attribs, attribs_new);
	codb_attr_hash_destroy(attribs_new);
	
	/* print all new attribs */
	{
		int hashi, hashn;

		/* for each data in attribs, send a data line to client */
		hashn = g_hashwrap_size(attribs);
		for (hashi = 0; hashi < hashn; hashi++) {
		     	char *tmp;
			gpointer key, val;

			g_hashwrap_index(attribs, hashi, &key, &val);

			write_str(cscp, cdata_msg);
			write_str(cscp, (char *)key);
			write_str(cscp, " = \"");
			tmp = escape_str((char *)val);
			write_str(cscp, tmp);
			write_str_nl(cscp, "\"");
			free(tmp);
		}
	}
	
	/* cleanup */
	codb_attr_hash_destroy(attribs);

	return 0;
}

static int
do_names(cscp_conn *cscp, struct cscp_parsed_cmd *cmd)
{
	int r;
	char *class;
	int freeclass = 0;
	GSList *names;

	/* user can pass an oid or a class name */
	if (cmd->args.names.classname) {
		class = cmd->args.names.classname;
	} else {
		/* have to get the freaking classname */
		class = codb_get_classname(cscp->odbh, cmd->args.names.oid);
		if (!class) {
			write_str(cscp, unknownobj_msg);
			write_str_nl(cscp, oid_to_str(cmd->args.names.oid));
			/* fail */
			write_str_nl(cscp, fail_msg);
			DPRINTF(DBG_CSCP_XTRA, "client %s: NAMES %d failed\n",
				cscp->idstr, cmd->args.names.oid);
			return CODB_RET_UNKOBJ;
		}
		freeclass = 1;
	}

	/* do NAMES */
	r = codb_names(cscp->odbh, class, &names);
	
	/* if r is an error - send the correct string down */
	if (r) {
		switch (r) {
			case CODB_RET_UNKCLASS:
				write_str(cscp, unknownclass_msg);
				write_str_nl(cscp, class);
				break;
			default:
				write_err(cscp, "UNKNOWN ERROR DURING NAMES");
		}
		write_str_nl(cscp, fail_msg);
		DPRINTF(DBG_CSCP_XTRA, "client %s: NAMES %s failed (%d)\n",
			cscp->idstr, class, r);
	} else {
		GSList *p;
		
		/* for each datum in names, send a data line to client */
		p = names;
		while (p) {
			write_str(cscp, namespace_msg);
			write_str_nl(cscp, (char *)p->data);
			p = g_slist_next(p);
		}

		/* terminate */
		write_str_nl(cscp, success_msg);
		DPRINTF(DBG_CSCP_XTRA, "NAMES %s succeeded from client %s\n", 
			class, cscp->idstr);
	}
	
	/* cleanup */
	if (freeclass) {
		free(class);
	}
	codb_free_list(names);

	return r;
}

static int
do_set(cscp_conn *cscp, struct cscp_parsed_cmd *cmd)
{
	GHashWrap *data_errs = NULL;
	GHashWrap *perm_errs = NULL;
	GHashWrap *attribs;
	int r;
	GSList *list_ent;
	
	cscplog(cscp, "%s", cmd->raw_input);
	
	data_errs = HASH_NEW();
	perm_errs = HASH_NEW();
	//FIXME: TAKE THIS COMPAT CRAP OUT!
	attribs = props_to_compat(cmd->args.set.props);
		
	/* do the SET */
	r = codb_set(cscp->odbh, cmd->args.set.oid, cmd->args.set.namespace, 
	    attribs, data_errs, perm_errs);
	if (r != CODB_RET_SUCCESS) {
		switch (r) {
			case CODB_RET_UNKOBJ:
				write_str(cscp, unknownobj_msg);
				write_str_nl(cscp, oid_to_str(cmd->args.set.oid));
				break;
			case CODB_RET_UNKNSPACE:
				write_str(cscp, unknownns_msg);
				write_str_nl(cscp, cmd->args.set.namespace);
				break;
			case CODB_RET_UNKCLASS: {
				char *class;
				
				class = codb_get_classname(cscp->odbh, 
				    cmd->args.set.oid);
				write_str(cscp, unknownclass_msg);
				write_str_nl(cscp, class);
				free(class);
				break;
			}
			case CODB_RET_BADDATA:
				dump_baddata_core(cscp, data_errs, 
				    cmd->args.set.oid); 
				break;
			case CODB_RET_PERMDENIED:
				write_perm_errs(cscp, perm_errs);
				break;
			default:
				write_err(cscp, "UNKNOWN ERROR DURING SET");
		}
		write_str_nl(cscp, fail_msg);
		cscplog(cscp, "SET %lu%s%s failed (%d)", cmd->args.set.oid,
			cmd->args.set.namespace?".":"", 
			cmd->args.set.namespace?cmd->args.set.namespace:"", r);
	}
	
	/* cleanup */
	codb_attr_hash_destroy(data_errs);
	codb_attr_hash_destroy(perm_errs);
	HASH_DESTROY(attribs);

	return r;
}

static void
dump_baddata(cscp_conn *cscp)
{
	GHashWrap *allmess;
	int hashi, hashn;

	allmess = cce_ed_access_baddata(cscp->ed);

	hashn = g_hashwrap_size(allmess);
	for (hashi = 0; hashi < hashn; hashi++) {
		GHashWrap *oneoidmess;
		oid_t *oid;
		gpointer key, val;

		g_hashwrap_index(allmess, hashi, &key, &val);

		oneoidmess = val;
		oid = key;
		dump_baddata_core(cscp, oneoidmess, *oid);
	}
}

static void
dump_baddata_core(cscp_conn *cscp, GHashWrap *hash, oid_t oid)
{
	if (hash) {
		int hashi, hashn;

		hashn = g_hashwrap_size(hash);
		for (hashi = 0; hashi < hashn; hashi++) {
		     	char *tmp;
			gpointer key, val;

			g_hashwrap_index(hash, hashi, &key, &val);
			
			write_str(cscp, baddata_msg);
			write_str(cscp, oid_to_str(oid));
			write_str(cscp, " ");
			write_str(cscp, (char *)key);
			write_str(cscp, " ");
			write_str(cscp, "\"");
			tmp = escape_str((char *)val);
			write_str(cscp, tmp);
			write_str_nl(cscp, "\"");
			free(tmp);
		}
	}
}

static int
do_baddata(cscp_conn *cscp, struct cscp_parsed_cmd *cmd)
{
	cce_ed_add_baddata(cscp->ed, cmd->args.baddata.oid, cmd->args.baddata.key, 
	    cmd->args.baddata.value);

	write_str_nl(cscp, success_msg);
	DPRINTF(DBG_CSCP_XTRA, "BADDATA succeeded from client %s\n", 
	    cscp->idstr);
	
	return 0;
}

static int
do_info(cscp_conn *cscp, struct cscp_parsed_cmd *cmd)
{
	/* tell ed */
	cce_ed_add_info(cscp->ed, cmd->args.info.message);

	write_str_nl(cscp, success_msg);
	DPRINTF(DBG_CSCP_XTRA, "INFO succeeded from client %s\n", cscp->idstr);
	
	return 0;
}

static int
do_warn(cscp_conn *cscp, struct cscp_parsed_cmd *cmd)
{
	/* tell ed */
	cce_ed_add_warn(cscp->ed, cmd->args.warn.message);

	write_str_nl(cscp, success_msg);
	DPRINTF(DBG_CSCP_XTRA, "WARN succeeded from client %s\n", cscp->idstr);
	
	return 0;
}

static int 
do_whoami(cscp_conn *cscp)
{
	char num_buf[16];
	oid_t authoid;

	authoid = codb_handle_getoid(cscp->odbh);

	if (!authoid) {
		snprintf(num_buf, sizeof(num_buf), "-1");
	} else {
		snprintf(num_buf, sizeof(num_buf), "%d", authoid);
	}

	write_str(cscp, object_msg);
	write_str_nl(cscp, num_buf);
	write_str_nl(cscp, success_msg);
	DPRINTF(DBG_CSCP_XTRA, "WHOAMI succeeded from client %s\n", cscp->idstr);

	return 0;
}

static int 
do_authkey(cscp_conn *cscp, struct cscp_parsed_cmd *cmd)
{
	oid_t uoid = 0;
	
	/* see if the authkey is valid */
	cscp->session = cce_session_resume(cmd->args.authkey.username, 
	    cmd->args.authkey.sessionkey);
	if (cscp->session) {
		/* restart the timeout on this session */
		cce_session_refresh(cscp->session);
		/* look up the user oid */
		uoid = get_useroid(cscp->odbh, cmd->args.authkey.username);
	}
		
	if (!cscp->session || !uoid) {
		write_str_nl(cscp, fail_msg);

		cscplog(cscp, "AUTHKEY to user \"%s\" failed", 
		    cmd->args.authkey.username);

		auth_failed_sleep();

		return -1;
	}

	DPRINTF(DBG_SESSION, "AUTHKEY to user \"%s\" succeeded", 
	    cmd->args.authkey.username);

	/* set the current authoid to this user */
	set_auth_oid(cscp, uoid);

	/* tell the client what happened */
	write_str_nl(cscp, success_msg);

	return 0;
}

static int
do_endkey(cscp_conn *cscp)
{
	cce_session_expire(cscp->session);
	cce_session_destroy(cscp->session);
	cscp->session = NULL;
	write_str_nl(cscp, success_msg);

	return 0;
}

static int
do_classes(cscp_conn *cscp)
{
	GSList *classes;
	int r;

	/* do NAMES */
	r = codb_classlist(cscp->odbh, &classes);
	
	/* if r is an error - send the correct string down */
	if (r) {
		write_err(cscp, "UNKNOWN ERROR DURING CLASSES");
		write_str_nl(cscp, fail_msg);
		DPRINTF(DBG_CSCP_XTRA, "client %s: CLASSES failed (%d)\n",
			cscp->idstr, r);
	} else {
		GSList *p;
		
		/* for each datum in classes, send a data line to client */
		p = classes;
		while (p) {
			write_str(cscp, class_msg);
			write_str_nl(cscp, (char *)p->data);
			p = g_slist_next(p);
		}

		/* terminate */
		write_str_nl(cscp, success_msg);
		DPRINTF(DBG_CSCP_XTRA, "CLASSES succeeded from client %s\n",
			cscp->idstr);
	}
	
	codb_free_list(classes);

	return 0;
}

/*
 * cscp_shutdown, cscp_onfire
 * set flags for the CSCP state machine to exit when it is safe
 */
void
cscp_shutdown()
{
	need_shutdown = SHUTDOWN;
}

void
cscp_onfire()
{
	need_shutdown = ONFIRE;
}

/*
 * do_cscp_shutdown
 *
 * the above routines set the flags, this gets called when the state
 * machines notice the flags (at certain safe-points)
 */
static void
do_cscp_shutdown(cscp_conn *cscp)
{
	if (need_shutdown == SHUTDOWN) {
		cce_session_refresh(cscp->session); 
		write(cscp->client, shutdown_msg, strlen(shutdown_msg));
	} else {
		write(cscp->client, onfire_msg, strlen(onfire_msg));
	}
	close_conn(cscp);
}

/*
 * close_conn
 *
 * provide a common place/way to close the connection on a client
 */
static void
close_conn(cscp_conn *cscp)
{
	DPRINTF(DBG_CSCP_XTRA, "Closing cscp connection explicitly.\n");
	cscp->state = STATE_CLOSED;
}

/*
 * oid_to_str
 *
 * the reverse of atol
 * (note: uses static memory)
 */
static char *
oid_to_str(oid_t oid)
{
	static char buf[16];

	snprintf(buf, sizeof(buf), "%d", oid);

	return buf;
}

/*
 * merge_attribs
 *
 * take two GHashWrap and copy all the data from src into dest, 
 * freeing data in the first if it gets overwritten.
 *
 * (note: does not affect sre hash)
 */
static int
merge_attribs(GHashWrap *dest, GHashWrap *src)
{
	int hashi, hashn;

	/* for each data in src, copy to dest */
	hashn = g_hashwrap_size(src);
	for (hashi = 0; hashi < hashn; hashi++) {
		gpointer key, val;
		gpointer oldkey;
		gpointer oldval;
		
		g_hashwrap_index(src, hashi, &key, &val);

	     	/* if it is already in dest, free it */
		if (g_hashwrap_lookup_extended(dest, key, &oldkey, &oldval)) {
			g_hashwrap_remove(dest, oldkey);
			free(oldkey);
			free(oldval);
		}
		/* insert it into dest */
		g_hashwrap_insert(dest, strdup(key), strdup(val));
	}

	return 0;
}

/*
 * get_useroid
 *
 * given a username, find their oid
 */
static oid_t
get_useroid(codb_handle *odbh, char *name)
{
	GHashWrap *criteria;
	GSList *oidlist = NULL;
	oid_t uoid = 0;

	/* build criteria: name = <user> */
	criteria = HASH_NEW();
	if (!criteria) {
		DPRINTF(DBG_CSCP, "get_useroid: HASH_NEW() failed\n");
		return 0;
	}
	HASH_INSERT(criteria, "name", name);
	
	/* FIND the user */
	if (codb_find(odbh, "User", criteria, NULL, NULL, NULL, &oidlist)) {
		DPRINTF(DBG_CSCP, "get_useroid: codb_find() failed\n");
		HASH_DESTROY(criteria);
		return 0;
	}
	HASH_DESTROY(criteria);

	if (g_slist_length(oidlist)) {
		/* get the oid out of the list */
		uoid = *(int *)(oidlist->data);
		codb_free_list(oidlist);
	}

	return uoid;
}

/* 
 * write_perm_errs
 *
 * helper to dump a hash of permission errors 
 */
static void
write_perm_errs(cscp_conn *cscp, GHashWrap *errs)
{
	int hashi, hashn;

	hashn = g_hashwrap_size(errs);
	for (hashi = 0; hashi < hashn; hashi++) {
		gpointer key;
		g_hashwrap_index(errs, hashi, &key, NULL);
		write_str(cscp, permdenied_msg);
		write_str_nl(cscp, (char *)key);
	}
}

/* translate a context id to a name - do not free! */
const char *
ctxt_name(int ctxt) 
{
	static const char *ctxt_map[] = {
		"CTXT_CLIENT",
		"CTXT_HANDLER",
		"CTXT_ROLLBACK",
		"CTXT_RULE",
		"invalid context"
	};

	if (ctxt >= 0 && ctxt < CTXT_MAX) {
		return ctxt_map[ctxt];
	}

	return ctxt_map[CTXT_MAX];
}

/* translate a state id to a name - do not free! */
const char *
state_name(int state) 
{
	static const char *state_map[] = {
		"STATE_ID",
		"STATE_CMD",
		"STATE_TXN",
		"STATE_RO",
		"STATE_CLOSED",
		"invalid state"
	};

	if (state >= 0 && state < STATE_MAX) {
		return state_map[state];
	}

	return state_map[STATE_MAX];
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

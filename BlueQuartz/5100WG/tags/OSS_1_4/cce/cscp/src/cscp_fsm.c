/*
 * $Id: cscp_fsm.c 3 2003-07-17 15:19:15Z will $
 *
 * Notes:
 *  * We will change to STATE_CLOSED if we get a 0 length read
 */

#include <cscp_all.h>
#include <cscp_internal.h>
#include <codb.h>
#include <cce_ed.h>
#include <sessionmgr.h>
#include <csem.h>
#include <stresc.h>

#include <stdio.h>
#include <stdlib.h>
#define _XOPEN_SOURCE
#include <unistd.h>
#include <errno.h>
#include <string.h>
#include <signal.h>
#include <stdarg.h>
#include <time.h>
#include <ctype.h>

/* the declaration of a state function */
#define DECL_STATE(fn)		static fsm_ret state_ ## fn(cscp_conn *cscp)
/* validate that a token is a valid cmd for a given state */
#define is_valid_cmd_tok(tok, cscp)	\
		((cscp_cmd_table[tok].contexts & (1<<(cscp)->context)) \
		 && (cscp_cmd_table[tok].states & (1<<(cscp)->state)))
/* validate a cmd */
#define is_valid_cmd(cmd, cscp)	\
		((cmd->cmd >= 0) && (cmd->cmd < CSCP_CMD_MAX) \
		 && (is_valid_cmd_tok(cmd->cmd, cscp)))
/* validate number of params */
#define is_valid_params(cmd)		\
		((cscp_cmd_table[cmd->cmd].minparams <= cmd->nparams)	\
		 && ((cscp_cmd_table[cmd->cmd].maxparams < 0) \
		     || (cscp_cmd_table[cmd->cmd].maxparams >= cmd->nparams)))

/* easier hash manipulations */
#define HASH_NEW()		g_hash_table_new(g_str_hash, g_str_equal)
#define HASH_INSERT(h, k, v)	g_hash_table_insert(h, k, v)
#define HASH_DESTROY(h)		g_hash_table_destroy(h)

#define USLEEP_BEFORE_CLOSE	20000

/*
 * Function prototypes 
 */

/* state function prototypes */
DECL_STATE(id);
DECL_STATE(cmd);
DECL_STATE(client_cmd);
DECL_STATE(handler_cmd);

/* local function prototypes */
static int fsm_loop(cscp_conn *cscp);
cscp_conn *alloc_conn(cscp_ctxt_t ctxt, int fd, char *idstr, codb_handle *h, 
	cce_ed *ed);
static void dealloc_conn(cscp_conn *cscp);
static cscp_parsed_cmd_t *cscp_parse_conn(cscp_conn *conn);
static GHashTable *hash_params(GSList *params);
static char *get_param(cscp_parsed_cmd_t *cmd, int index);
static int is_valid_oid(char *str);
static char *oid_to_str(oid_t oid);
static int merge_attribs(GHashTable *dest, GHashTable *src);
static void setup_cscp_signals(struct sigaction *oldpipe);
static void unset_cscp_signals(struct sigaction *oldpipe);
static oid_t get_useroid(codb_handle *odbh, char *name);
static void set_auth_oid(cscp_conn *cscp, oid_t authoid);
static void set_auth_oid_noflags(cscp_conn *cscp, oid_t authoid);
static int dump_object(cscp_conn *cscp, oid_t oid, char *ns);
static int dump_messages(cscp_conn *cscp, cce_ed *ed);
static int dump_baddata(cscp_conn *cscp, cce_ed *ed, oid_t oid);
static int dump_baddata_core(cscp_conn *cscp, GHashTable *hash, oid_t oid);
static void write_perm_errs(cscp_conn *cscp, GHashTable *errs);
static void do_cscp_shutdown(cscp_conn *cscp);
static void close_conn(cscp_conn *cscp);

/* functions where most of the work gets done */
static int do_help(cscp_conn *cscp);
static int fail_auth(cscp_conn *cscp, char *user);
static void auth_failed_sleep(void);
static int do_auth(cscp_conn *cscp, cscp_parsed_cmd_t *cmd);
static int do_commit(cscp_conn *cscp);
static int do_create(cscp_conn *cscp, cscp_parsed_cmd_t *cmd);
static int do_destroy(cscp_conn *cscp, cscp_parsed_cmd_t *cmd);
static int do_find(cscp_conn *cscp, cscp_parsed_cmd_t *cmd);
static int do_get(cscp_conn *cscp, cscp_parsed_cmd_t *cmd);
static int do_names(cscp_conn *cscp, cscp_parsed_cmd_t *cmd);
static int do_set(cscp_conn *cscp, cscp_parsed_cmd_t *cmd);
static int do_baddata(cscp_conn *cscp, cscp_parsed_cmd_t *cmd);
static int do_info(cscp_conn *cscp, cscp_parsed_cmd_t *cmd);
static int do_warn(cscp_conn *cscp, cscp_parsed_cmd_t *cmd);
static int do_bye(cscp_conn *cscp, cscp_parsed_cmd_t *cmd, int succeed);
static int do_whoami(cscp_conn *cscp);
static int do_authkey(cscp_conn *cscp, cscp_parsed_cmd_t *cmd);
static int do_endkey(cscp_conn *cscp);
static int do_classes(cscp_conn *cscp);

/* state function table - must jive with cscp_state_t enum */
static int (*states[])(cscp_conn *cscp) = {
	state_id,
	state_cmd,
};

/* FIXME: this code MUST be re-entrant.  Example:
 *    client connects, cced (fork()ed) enters cscp_fsm(CTXT_CLIENT, ...)
 *    client issues cmd that starts handler h1
 *    cced enters cscp_fsm(CTXT_HANDLER, ...), handles cmds
 *    h1 issues cmd that starts handler h2
 *    cced enters cscp_fsm(CTXT_HANDLER, ...), handles cmds
 *    we can't use globals that change...
 */

/* global flags to signal CSCP */
static enum {NO, SHUTDOWN, ONFIRE} need_shutdown = 0;

/* this is called from each top-level state */
static cscp_parsed_cmd_t *
read_cmd(cscp_conn *cscp)
{
	cscp_parsed_cmd_t *cmd;

	cmd = cscp_parse_conn(cscp);
	if (!cmd) {
		/* connection must have died */
		cscp->state = STATE_CLOSED;
	}

	return cmd;
}

/* 
 * this is called from each top-level state 
 * return 0 for fail, 1 for success 
 */
static int
params_valid(cscp_parsed_cmd_t *cmd, cscp_conn *cscp)
{
	if (!is_valid_cmd(cmd, cscp)) {
		DPRINTF(DBG_CSCP, "CSCP: bad command (%s)", cmd->full_cmd);
		write_str_nl(cscp, badcmd_msg);
		cscp_cmd_free(cmd);
		return 0;
	} else if (!is_valid_params(cmd)) {
		DPRINTF(DBG_CSCP, "CSCP: bad params (%s)", cmd->full_cmd);
		write_str_nl(cscp, badparams_msg);
		cscp_cmd_free(cmd);
		return 0;
	} else if (cmd->parse_err) {
		DPRINTF(DBG_CSCP, "CSCP: parse error (%s)", cmd->full_cmd);
		write_err(cscp, "COMMAND PARSE ERROR");
		write_str_nl(cscp, badcmd_msg);
		cscp_cmd_free(cmd);
		return 0;
	}

	return 1;
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

	if (ctxt == CTXT_HANDLER) {
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
	} else if (ctxt == CTXT_HANDLER) {
		cscp->ctxt_data.event = va_arg(args, ed_handler_event *);
	}

	/* signals */
	setup_cscp_signals(&oldpipe);

	/* do the main deed */
	r = fsm_loop(cscp);
	
	/* cleanup */
	dealloc_conn(cscp);
	unset_cscp_signals(&oldpipe);
	if (ctxt == CTXT_HANDLER) {
		codb_handle_destroy(h);
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

	set_auth_oid_noflags(cscp, codb_handle_getoid(h)); /* flag for user anon */

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
	char *cscp_id = "100 CSCP/" CSCP_VERSION;
	char *ready_msg = "200 READY";
	char *handler_msg = "101 EVENT ";
	GSList *p;

	/* say hello */
	write_str_nl(cscp, cscp_id);

	/* handlers have a special header */
	if (cscp->context == CTXT_HANDLER) {
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

	/* tell them we're ready */
	write_str_nl(cscp, ready_msg);

	/* next state is... */
	cscp->state = STATE_CMD;

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
 * state_client_cmd
 * 
 * handle frontend connections
 * This is the only state that cares if we receive a SIGTERM, If we do, 
 * we do what we can to shutdown gracefully.
 */
DECL_STATE(client_cmd)
{
	fsm_ret r = FSM_RET_SUCCESS;
	cscp_parsed_cmd_t *cmd;
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
		cmd = read_cmd(cscp);
		if (!cmd) { 
			if (need_shutdown) {
				/* this may be interrupted by SIGTERM */
				do_cscp_shutdown(cscp);
			}
			continue;
		}

		/* make sure it is a valid cmd */
		if (!params_valid(cmd, cscp)) {
			continue;
		}

		/* handle it */
		DPROFILE_START(PROF_CSCP, &start, "CSCP %.30s", cmd->full_cmd);
		switch (cmd->cmd) {
			/* FIXME: do per-object locks, check them on reads */
			case CSCP_BYE_CMD:
				r = do_bye(cscp, cmd, 1);
				break;
			case CSCP_HELP_CMD:
				do_help(cscp);
				break;
			case CSCP_AUTH_CMD:
				do_auth(cscp, cmd);
				break;
			case CSCP_AUTHKEY_CMD:
				do_authkey(cscp, cmd);
				break;
			case CSCP_ENDKEY_CMD:
				do_endkey(cscp);
				break;
			case CSCP_WHOAMI_CMD:
				do_whoami(cscp);
				break;
			case CSCP_FIND_CMD:
				do_find(cscp, cmd);
				break;
			case CSCP_GET_CMD:
				do_get(cscp, cmd);
				break;
			case CSCP_NAMES_CMD:
				do_names(cscp, cmd);
				break;
			case CSCP_CLASSES_CMD:
				do_classes(cscp);
				break;
			case CSCP_COMMIT_CMD:
				/* FIXME: this is not valid in cmd state */
				csem_down(cscp->ctxt_data.wrlock);
				do_commit(cscp);
				csem_up(cscp->ctxt_data.wrlock);
				break;
			case CSCP_CREATE_CMD:
				csem_down(cscp->ctxt_data.wrlock);
				do_create(cscp, cmd);
				csem_up(cscp->ctxt_data.wrlock);
				break;
			case CSCP_DESTROY_CMD:
				csem_down(cscp->ctxt_data.wrlock);
				do_destroy(cscp, cmd);
				csem_up(cscp->ctxt_data.wrlock);
				break;
			case CSCP_SET_CMD:
				csem_down(cscp->ctxt_data.wrlock);
				do_set(cscp, cmd);
				csem_up(cscp->ctxt_data.wrlock);
				break;
			default:
				CCE_SYSLOG("%s:%s: uncaught invalid cmd (%d)\n", 
					ctxt_name(cscp->context), 
					state_name(cscp->state), cmd->cmd);
				write_str_nl(cscp, badcmd_msg);
		}
		/* cleanup */
		DPROFILE(PROF_CSCP, start, "command is done");
		cscp_cmd_free(cmd);
	}	

	return r;
}

/* 
 * state_handler
 * 
 * handle handler connections
 */
DECL_STATE(handler_cmd)
{
	fsm_ret r = FSM_RET_SUCCESS;
	cscp_parsed_cmd_t *cmd;
	
	DPRINTF(DBG_CSCP, "%s:%s\n", 
		ctxt_name(cscp->context), state_name(cscp->state));

	/* loop for this state */
	while (cscp->state == STATE_CMD) {
		/* read and parse a command */
		cmd = read_cmd(cscp);
		if (!cmd) {
			continue;
		}

		/* make sure it is a valid cmd */
		if (!params_valid(cmd, cscp)) {
			continue;
		}

 
		/* handle it */
		switch (cmd->cmd) {
			case CSCP_BYE_CMD:
				r = do_bye(cscp, cmd, 0);
				break;
			case CSCP_HELP_CMD:
				do_help(cscp);
				break;
			case CSCP_AUTH_CMD:
				do_auth(cscp, cmd);
				break;
			case CSCP_AUTHKEY_CMD:
				do_authkey(cscp, cmd);
				break;
			case CSCP_ENDKEY_CMD:
				do_endkey(cscp);
				break;
			case CSCP_WHOAMI_CMD:
				do_whoami(cscp);
				break;
			case CSCP_FIND_CMD:
				do_find(cscp, cmd);
				break;
			case CSCP_GET_CMD:
				do_get(cscp, cmd);
				break;
			case CSCP_NAMES_CMD:
				do_names(cscp, cmd);
				break;
			case CSCP_CLASSES_CMD:
				do_classes(cscp);
				break;
			case CSCP_BADDATA_CMD:
				do_baddata(cscp, cmd);
				break;
			case CSCP_INFO_CMD:
				do_info(cscp, cmd);
				break;
			case CSCP_WARN_CMD:
				do_warn(cscp, cmd);
				break;
			case CSCP_COMMIT_CMD:
				/* FIXME: this is not valid in cmd state */
				do_commit(cscp);
				break;
			case CSCP_CREATE_CMD:
				do_create(cscp, cmd);
				break;
			case CSCP_DESTROY_CMD:
				do_destroy(cscp, cmd);
				break;
			case CSCP_SET_CMD:
				do_set(cscp, cmd);
				break;
			default:
				CCE_SYSLOG("%s:%s: uncaught invalid cmd (%d)\n", 
					ctxt_name(cscp->context), 
					state_name(cscp->state), cmd->cmd);
				write_str_nl(cscp, badcmd_msg);
		}
		/* cleanup */
		cscp_cmd_free(cmd);
	}	

	return r;
}

/*
 * cscp_parse_conn
 *
 * read a cscp_cmd from an open fd, trying to be clever about reading
 */
static cscp_parsed_cmd_t *
cscp_parse_conn(cscp_conn *conn)
{
	/* fast read */
	int r;
	int done = 0;
	char *p;
	char *cmdstr;
	char buf[512];
	cscp_parsed_cmd_t *newcmd;
	struct sigaction termsig;
	struct sigaction oldtermsig;
	
	/* loop so we can swallow blank lines */
	
	while (!done) {
		cmdstr = NULL;

		while (!done) {
			//DPRINTF(DBG_CSCP, "loop...\n");
			/* see if we have any left over from the last read */
			if (conn->clibuf) {
				cmdstr = conn->clibuf;
				//DPRINTF(DBG_CSCP, "clibuf = \"%s\"\n", conn->clibuf);
				/* ooh, maybe even a full cmd! */
				p = strchr(cmdstr, '\n');
				if (p) {
					p++;
					if (*p) {
						/* if there is more, save the extra */
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
				//DPRINTF(DBG_CSCP, "cmdstr after clibuf = \"%s\"\n", cmdstr);
			}
	
			if (done) {
				//DPRINTF(DBG_CSCP, "done.\n");
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
					sigaction(SIGTERM, &oldtermsig, NULL);
					DPRINTF (DBG_CSCP, "read returned %d\n",
						r);
					return NULL;
				}
			}
			buf[r] = '\0';
			// DPRINTF(DBG_CSCP, "read: %s", buf);
			sigaction(SIGTERM, &oldtermsig, NULL);

			/* did we get EOL? */
			p = strchr(buf, '\n');
			if (p) {
				p++;
				if (*p) {
					/* if there is more, save the extra */
					conn->clibuf = strdup(p);
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
					CCE_SYSLOG("cscp_parse_conn: malloc() %s", strerror(errno));
				} else {
					sprintf(new, "%s%s", cmdstr, buf);
					free(cmdstr);
					cmdstr = new;
				}
			} else {
				cmdstr = strdup(buf);
			}
		}
		// DPRINTF(DBG_CSCP, "FINAL cmdstr = \"%s\"\n", cmdstr);

		newcmd = cscp_parse(cmdstr);

		/* cleanup */
		free(cmdstr);

		/* swallow blank lines */
		if (newcmd->cmd == TOK_EOF) {
			cscp_cmd_free(newcmd);
			done = 0;
		}
	}

	DPRINTF(DBG_CSCP, ">> %s\n", newcmd->full_cmd);

	return newcmd;
}

/*
 * These are pretty straightforward - do the work for each command
 */
static int
do_bye(cscp_conn *cscp, cscp_parsed_cmd_t *cmd, int succeed)
{
	char *bye_msg = "202 GOODBYE\n";
	int r;
	char *param;
	
	if (cmd->nparams >= 1) {
		param = get_param(cmd, 0);
	} else if (succeed) {
		param = "SUCCESS";
	} else {
		param = "FAIL";
	}

	if (!strcasecmp(param, "SUCCESS")) {
		cce_session_refresh(cscp->session); 
		r = FSM_RET_SUCCESS;
	} else if (!strcasecmp(param, "FAIL")) {
		r = FSM_RET_FAIL;
	} else if (!strcasecmp(param, "DEFER")) {
		r = FSM_RET_DEFER;
	} else {
		DPRINTF(DBG_CSCP, "Invalid parameter for BYE: %s", param);
		write_str_nl(cscp, badparams_msg);
		return FSM_RET_FAIL;
	}

	write_str_nl(cscp, bye_msg); 
	close_conn(cscp);

	DPRINTF(DBG_CSCP_XTRA, "BYE %s from client %s\n", param, cscp->idstr);

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

/*
 * set_auth_oid
 * 
 * set the currently authed oid, and drop any special flags
 */
static void
set_auth_oid(cscp_conn *cscp, oid_t authoid)
{
	set_auth_oid_noflags(cscp, authoid);

	/* drop any special permission flags */
	codb_handle_rmflags(cscp->odbh, CODBF_ADMIN);
}

/*
 * set_auth_oid_noflags
 * 
 * set the currently authed oid, ignoring flags
 */
static void
set_auth_oid_noflags(cscp_conn *cscp, oid_t authoid)
{
	cscp->auth_oid = authoid;
	codb_handle_setoid(cscp->odbh, authoid);
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
	CCE_SYSLOG("AUTH failed for user %s from client %s", user,
		cscp->idstr);

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
do_auth(cscp_conn *cscp, cscp_parsed_cmd_t *cmd)
{
	char *user;
	char *pwd;
	oid_t useroid;

	/* get the user */
	user = get_param(cmd, 0);

	/* test for magic "" user */
	if (!strcmp(user, "")) {
		/* as if we had just connected (no rights) */
		set_auth_oid(cscp, 0);
	} else {
		/* get the password */	  
		pwd = get_param(cmd, 1);

		/* get the user oid */
		useroid = get_useroid(cscp->odbh, user);
		if (!useroid) {
			return fail_auth(cscp, user);
		}

		/* make sure the user is enabled */
		{
			GHashTable *attribs;
			char *enabled;
	
			attribs = HASH_NEW();
			codb_get(cscp->odbh, useroid, "", attribs);
	
			enabled = g_hash_table_lookup(attribs, "enabled");
			if (!enabled 
			 || !strcmp(enabled, "") || !strcmp(enabled, "0")) {
				codb_attr_hash_destroy(attribs);
				return fail_auth(cscp, user);
			}

			codb_attr_hash_destroy(attribs);
		}

		/* check if the user is validated through PAM */
		if (cscp_auth(user, pwd) != 0) {
			return fail_auth(cscp, user);
		}

		/* success */
		set_auth_oid(cscp, useroid);
	}

	/* send a new sessionid */
	cscp->session = cce_session_new(user);
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
	CCE_SYSLOG("AUTH succeeded for user %s from client %s", 
		user, cscp->idstr);

	return 0;
}

static int
do_commit(cscp_conn *cscp)
{
	int r;
	
	/* FIXME: this function never does anything useful, and
	   is probably broken w.r.t. branching ed for every subtxn. */
	
	/* dispatch */
	r = cce_ed_dispatch(cscp->ed, cscp->odbh);
	{
		GSList *messages;

		/* FIXME: show all bad data returns */
		/* show all messages */
		messages = cce_ed_access_messages(cscp->ed);
		while (messages) {
			write_str_nl(cscp, (char *)messages->data);
			messages = g_slist_next(messages);
		}
	}
		
	/* check result of ed */
	if (r) {
		codb_flush(cscp->odbh);
		write_str_nl(cscp, fail_msg);
		CCE_SYSLOG("COMMIT dispatch failed from client %s",
			cscp->idstr);
	} else {
		codb_commit(cscp->odbh);
		write_str_nl(cscp, success_msg);
		CCE_SYSLOG("COMMIT succeeded from client %s", 
			cscp->idstr);
	}

	return r;
}

static int
do_cmd_dispatch(cscp_conn *cscp, oid_t oid)
{
  int r;
  cce_ed *ed;
  ed = cce_ed_branch(cscp->ed);
  r = cce_ed_dispatch(ed, cscp->odbh);
  dump_baddata(cscp, ed, oid);
  dump_messages(cscp, ed);
  cce_ed_destroy(ed);
  return r;
}

static int
do_create(cscp_conn *cscp, cscp_parsed_cmd_t *cmd)
{
	char *class;
	GHashTable *attribs = NULL;
	GHashTable *errs = NULL;
	oid_t newoid;
	int r;

	cscp_cmd_log(cmd);
	
	/* class name is first param */
	class = get_param(cmd, 0);

	/* did the user provide any data? */
	if (cmd->nparams > 1) {
		attribs = hash_params((GSList *)cmd->params->next);
		if (!attribs) {
			write_str_nl(cscp, badparams_msg);
			return -1;
		}
	} else {
		attribs = HASH_NEW();
	}

	errs = HASH_NEW();
		
	/* do the CREATE */
	r = codb_create(cscp->odbh, class, attribs, errs, &newoid);
	if (r != CODB_RET_SUCCESS) {
		switch (r) {
			case CODB_RET_UNKCLASS:
				write_str(cscp, unknownclass_msg);
				write_str_nl(cscp, class);
				break;
			case CODB_RET_BADDATA: {
				dump_baddata_core(cscp, errs, 0); 
				break;
			}
			case CODB_RET_PERMDENIED:
				write_str(cscp, permdenied_msg);
				write_str_nl(cscp, "CREATE");
				break;
			default:
				write_err(cscp, "UNKNOWN ERROR DURING CREATE");
		}
		write_str_nl(cscp, fail_msg);
		CCE_SYSLOG("CREATE %s failed from client %s (%d)", 
			class, cscp->idstr, r);
	} else {
		/* dispatch */
		r = do_cmd_dispatch(cscp, newoid);
		
		/* check the results of ed */
		if (r) {
			codb_flush(cscp->odbh);
			write_str_nl(cscp, fail_msg);
			CCE_SYSLOG("CREATE %s dispatch failed from client %s",
				class, cscp->idstr);
		} else {
			char num_buf[32];

			codb_commit(cscp->odbh);
			snprintf(num_buf, sizeof(num_buf), "%lu", newoid);
			write_str(cscp, object_msg);
			write_str_nl(cscp, num_buf);
			write_str_nl(cscp, success_msg);
			CCE_SYSLOG("CREATE %s succeeded from client %s", 
				class, cscp->idstr);
		}
	}
	
	/* cleanup */
	if (attribs) {
		codb_attr_hash_destroy(attribs);
		codb_attr_hash_destroy(errs);
	}

	return r;
}

static int
do_destroy(cscp_conn *cscp, cscp_parsed_cmd_t *cmd)
{
	int r;
	char *param;
	oid_t oid;

	cscp_cmd_log(cmd);
	
	param = get_param(cmd, 0);

	/* check that the oid is actually an oid */
	if (!is_valid_oid(param)) {
		write_str_nl(cscp, badparams_msg);
		return -1;
	}

	/* convert to an oid_t */
	oid = atol(param);
	if (!oid) {
		/* can't destroy 0 */
		write_str_nl(cscp, badparams_msg);
		return -1;
	}

	/* do the DESTROY */
	r = codb_destroy(cscp->odbh, oid);
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
		CCE_SYSLOG("DESTROY %lu failed from client %s (%d)", 
			oid, cscp->idstr, r);
	} else {
		/* dispatch */
		r = do_cmd_dispatch(cscp, oid);
		
		/* check the results of ed */
		if (r) {
			codb_flush(cscp->odbh);
			write_str_nl(cscp, fail_msg);
			CCE_SYSLOG("DESTROY %lu dispatch failed from client %s", 
				oid, cscp->idstr);
		} else {
			codb_commit(cscp->odbh);
			write_str_nl(cscp, success_msg);
			CCE_SYSLOG( "DESTROY %lu succeeded from client %s", 
				oid, cscp->idstr);
		}
	}

	return r;
}

static int
do_find(cscp_conn *cscp, cscp_parsed_cmd_t *cmd)
{
	char *class;
	int sorttype;
	char *sortkey;
	GHashTable *attribs = NULL;
	int r;
	GSList *oidlist = NULL;
	GSList *params;
	
	params = (GSList*)cmd->params;
	sorttype = 0;
	sortkey = "name"; // default sort key

      	class = cce_scalar_string(params->data);
	params = params->next;
	DPRINTF(DBG_CODB, "class = %s\n",class);
	
	if (params 
	  && (strcasecmp("sortnum", cce_scalar_string(params->data))==0)) 
	{
	  params = params->next;
	  sorttype = 1;
	  if (params) {
	    sortkey = cce_scalar_string(params->data);
	    params = params->next;
	  }
	}
	else if (params 
	  && (strcasecmp("sort", cce_scalar_string(params->data))==0)) 
	{
	  params = params->next;
	  sorttype = 0;
	  if (params) {
	    sortkey = cce_scalar_string(params->data);
	    params = params->next;
	  }
	}
	
	/* did the user provide any data? */
	if (params) {
	    attribs = hash_params((GSList*)params); // GList->GSList? ugh!
	    if (!attribs) {
		write_str_nl(cscp, badparams_msg);
		return -1;
	    }
	}
		
	/* do the FIND */
	r = codb_find(cscp->odbh, class, attribs, &oidlist, 
	  sortkey, sorttype);

	/* if r is an error - send the correct string down */
	if (r) {
		switch (r) {
			case CODB_RET_UNKCLASS:
				write_str(cscp, unknownclass_msg);
				write_str_nl(cscp, class);
				break;
			default:
				write_err(cscp, "UNKNOWN ERROR DURING FIND");
		}
		write_str_nl(cscp, fail_msg);
	} else {
		GSList *p;
		
		/* for each datum in oidlist, send a data line to client */
		p = oidlist;
		while (p) {
			char num_buf[32];
			oid_t oid;

			oid = *(int *)p->data;
			snprintf(num_buf, sizeof(num_buf), "%lu", oid);

			write_str(cscp, object_msg);
			write_str_nl(cscp, num_buf);
			p = g_slist_next(p);
		}

		/* terminate */
		write_str_nl(cscp, success_msg);
	}
	
	/* cleanup */
	codb_free_list(oidlist);

	return r;
}

static int
do_get(cscp_conn *cscp, cscp_parsed_cmd_t *cmd)
{
	int r;
	oid_t oid;
	char *ns = NULL;
	char *param;

	/* get the oid */
	param = get_param(cmd, 0);
	
	/* make sure it is an oid */
	if (!is_valid_oid(param)) {
		write_str_nl(cscp, badparams_msg);
		return -1;
	}

	/* convert to oid_t */
	oid = atol(param);
	if (!oid) {
		write_str_nl(cscp, badparams_msg);
		return -1;
	}

	/* see if they asked for a namespace */
	if (cmd->nparams > 1) {
		/* if we have >1, it must be 3  "." "namespace" */
		if (cmd->nparams != 3) {
			write_str_nl(cscp, badparams_msg);
			return -1;
		}
		/* should be "." */
		param = get_param(cmd, 1);
		if (strcmp(param, ".")) {
			write_str_nl(cscp, badparams_msg);
			return -1;
		}
		/* should be the namespace */
		ns = get_param(cmd, 2);
	}
			
	/* dump the object, if it exists */
	r = dump_object(cscp, oid, ns);
	
	/* if r is an error - send the correct string down */
	if (r) {
		switch (r) {
			case CODB_RET_UNKOBJ:
				write_str(cscp, unknownobj_msg);
				write_str_nl(cscp, oid_to_str(oid));
				break;
			case CODB_RET_UNKNSPACE:
				write_str(cscp, unknownns_msg);
				write_str_nl(cscp, ns);
				break;
			case CODB_RET_UNKCLASS: {
				char *class;
				
				class = codb_get_classname(cscp->odbh, oid);
				write_str(cscp, unknownclass_msg);
				write_str_nl(cscp, class);
				free(class);
				break;
			}
			default:
				write_err(cscp, "UNKNOWN ERROR DURING GET");
		}
		write_str_nl(cscp, fail_msg);
		DPRINTF(DBG_CSCP_XTRA, 
			"GET %lu%s%s failed from client %s (%d)\n", 
			oid, ns?".":"", ns?ns:"", cscp->idstr, r);
	} else {
		/* terminate */
		write_str_nl(cscp, success_msg);
		DPRINTF(DBG_CSCP_XTRA, "GET %lu%s%s succeeded from client %s\n", 
			oid, ns?".":"", ns?ns:"", cscp->idstr);
	}
	
	return r;
}

/*
 * dump_object
 *
 * send an object down the wire
 */
static int
dump_object(cscp_conn *cscp, oid_t oid, char *ns)
{		
	int adminflaghack;
	GHashTable *attribs;
	GHashTable *attribs_new;
	codb_handle *old_odbh;
	int created_flag = 0;
	int r = 0;

	/* prepare to catch the attributes */
	attribs = HASH_NEW();
	
	old_odbh = codb_handle_rootref(cscp->odbh);

	/* get the old info (before this txn) */
	adminflaghack = codb_handle_getflags(old_odbh);
	codb_handle_setflags(old_odbh, codb_handle_getflags(cscp->odbh));
	r = codb_get_old(old_odbh, oid, ns, attribs);
	codb_handle_setflags(old_odbh, adminflaghack);
	
	/* check return code... */
	if ((r == CODB_RET_UNKOBJ) && codb_objexists(cscp->odbh, oid)) {
		/* mmm mmm fresh baked object */
		write_str_nl(cscp, created_msg);
		created_flag = 1;
	} else if (r) {
		/* r is a real error */
		codb_attr_hash_destroy(attribs);
		return r;
	} else {
		GHashIter *it;
		gpointer key;
		gpointer val;

		/* for each data in attribs, send a data line to client */
		it = g_hash_iter_new(attribs);
		for (key = g_hash_iter_first(it, &key, &val); key;
		     key = g_hash_iter_next(it, &key, &val)) {
		     	char *tmp;

			write_str(cscp, data_msg);
			write_str(cscp, (char *)key);
			write_str(cscp, " = \"");
			tmp = stresc((char *)val);
			write_str(cscp, tmp);
			write_str_nl(cscp, "\"");
			free(tmp);
		}

		g_hash_iter_destroy(it);
	}
	
	/* cleanup */
	codb_attr_hash_destroy(attribs);
	
	/* now it is time for changed properties */
	attribs = HASH_NEW();

	adminflaghack = codb_handle_getflags(old_odbh);
	codb_handle_setflags(old_odbh, codb_handle_getflags(cscp->odbh));

	if (created_flag) {
		/* if the object was just created, all attributes are new */
		r = codb_get(old_odbh, oid, ns, attribs);
	} else {
		/* get the changed info from before this level of txn */
		r = codb_get_changed(old_odbh, oid, ns, attribs);
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
	r = codb_get_changed(cscp->odbh, oid, ns, attribs_new);

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
		GHashIter *it;
		gpointer key;
		gpointer val;

		/* for each data in attribs, send a data line to client */
		it = g_hash_iter_new(attribs);
		for (key = g_hash_iter_first(it, &key, &val); key;
		     key = g_hash_iter_next(it, &key, &val)) {
		     	char *tmp;

			write_str(cscp, cdata_msg);
			write_str(cscp, (char *)key);
			write_str(cscp, " = \"");
			tmp = stresc((char *)val);
			write_str(cscp, tmp);
			write_str_nl(cscp, "\"");
			free(tmp);
		}

		g_hash_iter_destroy(it);
	}
	
	/* cleanup */
	codb_attr_hash_destroy(attribs);

	return 0;
}

static int
do_names(cscp_conn *cscp, cscp_parsed_cmd_t *cmd)
{
	int r;
	oid_t oid = 0;
	char *class;
	GSList *names;
	char *param;

	param = get_param(cmd, 0);

	/* user can pass an oid or a class name */
	if (is_valid_oid(param)) {
		/* have to get the freaking classname */
		oid = atol(param);
		class = codb_get_classname(cscp->odbh, oid);
		if (!class) {
			write_str(cscp, unknownobj_msg);
			write_str_nl(cscp, oid_to_str(oid));
			/* fail */
			write_str_nl(cscp, fail_msg);
			DPRINTF(DBG_CSCP_XTRA, "NAMES %lu failed from client %s\n",
				oid, cscp->idstr);
			return CODB_RET_UNKOBJ;
		}
	} else {
		class = strdup(param);
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
		DPRINTF(DBG_CSCP_XTRA, "NAMES %s failed from client %s (%d)\n", 
			class, cscp->idstr, r);
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
	free(class);
	codb_free_list(names);

	return r;
}

static int
do_set(cscp_conn *cscp, cscp_parsed_cmd_t *cmd)
{
	oid_t oid;
	char *param;
	GHashTable *attribs = NULL;
	GHashTable *data_errs = NULL;
	GHashTable *perm_errs = NULL;
	char *ns = NULL;
	int r;
	GSList *list_ent;
	
	cscp_cmd_log(cmd);
	
	/* class name is first param */
	param = get_param(cmd, 0);

	/* make sure it is an oid */
	if (!is_valid_oid(param)) {
		write_str_nl(cscp, badparams_msg);
		return -1;
	}

	/* convert to oid_t */
	oid = atol(param);
	if (!oid) {
		write_str_nl(cscp, badparams_msg);
		return -1;
	}

	/* figure out the requested namespace, if any */
	list_ent = (GSList *)cmd->params->next;
	if (cmd->nparams > 1) {
		param = get_param(cmd, 1);

		if (!strcmp(param, ".")) {
			/* next should be the namespace */
			ns = get_param(cmd, 2);
			/* move list_ent past the end of namespace */
			list_ent = (GSList *)cmd->params->next->next->next;
		}
	}

	/* did the user provide any data? */
	if (list_ent) {
		attribs = hash_params(list_ent);
		if (!attribs) {
			write_str_nl(cscp, badparams_msg);
			return -1;
		}
	} else {
		attribs = HASH_NEW();
	}

	data_errs = HASH_NEW();
	perm_errs = HASH_NEW();
		
	/* do the SET */
	r = codb_set(cscp->odbh, oid, ns, attribs, data_errs, perm_errs);
	if (r != CODB_RET_SUCCESS) {
		switch (r) {
			case CODB_RET_UNKOBJ:
				write_str(cscp, unknownobj_msg);
				write_str_nl(cscp, oid_to_str(oid));
				break;
			case CODB_RET_UNKNSPACE:
				write_str(cscp, unknownns_msg);
				write_str_nl(cscp, ns);
				break;
			case CODB_RET_UNKCLASS: {
				char *class;
				
				class = codb_get_classname(cscp->odbh, oid);
				write_str(cscp, unknownclass_msg);
				write_str_nl(cscp, class);
				free(class);
				break;
			}
			case CODB_RET_BADDATA:
				dump_baddata_core(cscp, data_errs, oid); 
				break;
			case CODB_RET_PERMDENIED:
				write_perm_errs(cscp, perm_errs);
				break;
			default:
				write_err(cscp, "UNKNOWN ERROR DURING SET");
		}
		write_str_nl(cscp, fail_msg);
		CCE_SYSLOG("SET %lu%s%s failed from client %s (%d)", 
			oid, ns?".":"", ns?ns:"", cscp->idstr, r);
	} else {
		/* dispatch */
		r = do_cmd_dispatch(cscp, oid);

		/* check the results of ed */
		if (r) {
			codb_flush(cscp->odbh);
			write_str_nl(cscp, fail_msg);
			CCE_SYSLOG("SET %lu%s%s dispatch failed from client %s: %d", 
			    oid, ns?".":"", ns?ns:"", cscp->idstr, (int)r);
		} else {
			codb_commit(cscp->odbh);
			write_str_nl(cscp, success_msg);
			CCE_SYSLOG("SET %lu%s%s succeeded from client %s",
			    oid, ns?".":"", ns?ns:"", cscp->idstr);
		}
	}
	
	/* cleanup */
	if (attribs) {
		codb_attr_hash_destroy(attribs);
		codb_attr_hash_destroy(data_errs);
		codb_attr_hash_destroy(perm_errs);
	}

	return r;
}

static int
dump_baddata(cscp_conn *cscp, cce_ed *ed, oid_t oid)
{
	GHashTable *hash;

	/* show all bad data returns */
	hash = cce_ed_access_baddata(ed, oid);

	dump_baddata_core(cscp, hash, oid);

	return 0;
}

static int
dump_baddata_core(cscp_conn *cscp, GHashTable *hash, oid_t oid)
{
	if (hash) {
		GHashIter *it;
		gpointer key;
		gpointer val;

		it = g_hash_iter_new(hash);
		g_hash_iter_first(it, &key, &val);
		while (key) {
		     	char *tmp;

			write_str(cscp, baddata_msg);
			write_str(cscp, oid_to_str(oid));
			write_str(cscp, " ");
			write_str(cscp, (char *)key);
			write_str(cscp, " ");
			write_str(cscp, "\"");
			tmp = stresc((char *)val);
			write_str(cscp, tmp);
			write_str_nl(cscp, "\"");
			free(tmp);
			g_hash_iter_next(it, &key, &val);
		}
		g_hash_iter_destroy(it);
	}

	return 0;
}

static int
dump_messages(cscp_conn *cscp, cce_ed *ed)
{
	GSList *messages;

	/* show all messages */
	messages = cce_ed_access_messages(ed);
	while (messages) {
		write_str_nl(cscp, (char *)messages->data);
		messages = g_slist_next(messages);
	}

	return 0;
}
		
static int
do_baddata(cscp_conn *cscp, cscp_parsed_cmd_t *cmd)
{
	char *param;
	oid_t oid;
	char *key;
	char *val;

	param = get_param(cmd, 0);

	/* check that the oid is actually an oid */
	if (!is_valid_oid(param)) {
		write_str_nl(cscp, badparams_msg);
		return -1;
	}

	/* convert to an oid_t */
	oid = atol(param);

	/* get key, val */
	key = get_param(cmd, 1);
	val = get_param(cmd, 2);

	cce_ed_add_baddata(cscp->ed, oid, key, val);

	write_str_nl(cscp, success_msg);
	DPRINTF(DBG_CSCP_XTRA, "BADDATA succeeded from client %s\n", cscp->idstr);
	
	return 0;
}

static int
do_info(cscp_conn *cscp, cscp_parsed_cmd_t *cmd)
{
	GString *msg;
	char *param;
	
	param = get_param(cmd, 0);

	/* build the message */
	msg = g_string_new(info_msg);
	g_string_append(msg, param);

	/* tell ed */
	cce_ed_add_message(cscp->ed, msg->str);

	/* cleanup */
	g_string_free(msg, 1);

	write_str_nl(cscp, success_msg);
	DPRINTF(DBG_CSCP_XTRA, "INFO succeeded from client %s\n", cscp->idstr);
	
	return 0;
}

static int
do_warn(cscp_conn *cscp, cscp_parsed_cmd_t *cmd)
{
	GString *msg;
	char *param;
	
	param = get_param(cmd, 0);

	/* build the message */
	msg = g_string_new(warn_msg);
	g_string_append(msg, param);

	/* tell ed */
	cce_ed_add_message(cscp->ed, msg->str);
	/* cleanup */
	g_string_free(msg, 1);

	write_str_nl(cscp, success_msg);
	DPRINTF(DBG_CSCP_XTRA, "WARN succeeded from client %s\n", cscp->idstr);
	
	return 0;
}

static int 
do_whoami(cscp_conn *cscp)
{
	char num_buf[16];

	if (!cscp->auth_oid) {
		snprintf(num_buf, sizeof(num_buf), "-1");
	} else {
		snprintf(num_buf, sizeof(num_buf), "%lu", cscp->auth_oid);
	}

	write_str(cscp, object_msg);
	write_str_nl(cscp, num_buf);
	write_str_nl(cscp, success_msg);
	DPRINTF(DBG_CSCP_XTRA, "WHOAMI succeeded from client %s\n", cscp->idstr);

	return 0;
}

static int 
do_authkey(cscp_conn *cscp, cscp_parsed_cmd_t *cmd)
{
	char *user;
	char *sessid;
	oid_t uoid = 0;
	
	user = get_param(cmd, 0);
	sessid = get_param(cmd, 1);

	/* see if the authkey is valid */
	cscp->session = cce_session_resume(user, sessid);
	if (cscp->session) {
		/* restart the timeout on this session */
		cce_session_refresh(cscp->session);
		/* look up the user oid */
		uoid = get_useroid(cscp->odbh, user);
	}
		
	if (!cscp->session || !uoid) {
		write_str_nl(cscp, fail_msg);

		/* log it */
		CCE_SYSLOG("AUTHKEY failed for user %s from client %s", 
			user, cscp->idstr);

		auth_failed_sleep();

		return -1;
	}

	/* set the current authoid to this user */
	set_auth_oid(cscp, uoid);

	/* tell the client what happened */
	write_str_nl(cscp, success_msg);
	CCE_SYSLOG("AUTHKEY succeeded for user %s from client %s", 
		user, cscp->idstr);

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
		DPRINTF(DBG_CSCP_XTRA, "CLASSES failed from client %s (%d)\n", 
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
	usleep(USLEEP_BEFORE_CLOSE);
	DPRINTF(DBG_CSCP_XTRA, "Closing cscp connection explicitly.\n");
	cscp->state = STATE_CLOSED;
}

/* 
 * hash_params
 *
 * build a hash of the multiple parameters 
 */
static GHashTable *
hash_params(GSList *params)
{
	GHashTable *hash;

	/* make the hash */
	hash = HASH_NEW();
	if (!hash) {
		DPRINTF(DBG_CSCP, "concat_params: g_hash_table_new() failed\n");
		return NULL;
	}

	while (params) {
		cce_scalar *sc;
		char *key;
		char *val;
		
		/* key */
		sc = (cce_scalar *)params->data;
		key = (char *)sc->data;
		
		/* = */
		params = g_slist_next(params);
		if (!params) {
			DPRINTF(DBG_CSCP, "hash_params: error 1 getting key = val\n");
			g_hash_table_destroy(hash);
			hash = NULL;
			break;
		}

		/* make sure it IS a '=' */
		sc = (cce_scalar *)params->data;
		val = (char *)sc->data;
		if (strcmp(val, "=")) {
			DPRINTF(DBG_CSCP, "hash_params: error 2 getting key = val\n");
			g_hash_table_destroy(hash);
			hash = NULL;
			break;
		}
		
		/* move forward */
		params = g_slist_next(params);
		if (!params) {
			DPRINTF(DBG_CSCP, "hash_params: error 3 getting key = val\n");
			g_hash_table_destroy(hash);
			hash = NULL;
			break;
		}
		
		/* val */
		sc = (cce_scalar *)params->data;
		val = (char *)sc->data;

		/* enhash it */
		g_hash_table_insert(hash, strdup(key), strdup(val));

		/* loop */
		params = g_slist_next(params);
	}

	return hash;
}

/*
 * is_valid_oid
 *
 * validate a string as an oid
 */
static int
is_valid_oid(char *str)
{
	char *p = str;
	
	/* must be all digits */
	while (*p) {
		if (!isdigit(*p)) {
			return 0;
		}
		p++;
	}

	return 1;
}

/*
 * get_param
 *
 * get an indexed parameter from a list
 */
static char *
get_param(cscp_parsed_cmd_t *cmd, int index)
{
	GSList *list;
	cce_scalar *param;

	list = (GSList *)cmd->params;
	param = (cce_scalar *)g_slist_nth_data(list, index);
	return (char *)param->data;
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

	snprintf(buf, sizeof(buf), "%lu", oid);

	return buf;
}

/*
 * merge_attribs
 *
 * take two GHashTables and copy all the data from src into dest, 
 * freeing data in the first if it gets overwritten.
 *
 * (note: does not affect sre hash)
 */
static int
merge_attribs(GHashTable *dest, GHashTable *src)
{
	GHashIter *it;
	gpointer key;
	gpointer val;
	gpointer oldkey;
	gpointer oldval;

	/* for each data in src, copy to dest */
	it = g_hash_iter_new(src);
	for (key = g_hash_iter_first(it, &key, &val); key;
	     key = g_hash_iter_next(it, &key, &val)) {
	     	/* if it is already in dest, free it */
		if (g_hash_table_lookup_extended(dest, key, &oldkey, &oldval)) {
			free(oldkey);
			free(oldval);
		}
		/* insert it into dest */
		g_hash_table_insert(dest, strdup(key), strdup(val));
	}

	g_hash_iter_destroy(it);

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
	GHashTable *criteria;
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
	if (codb_find_n(odbh, "User", criteria, 1, &oidlist, NULL, 0)) {
		DPRINTF(DBG_CSCP, "get_useroid: codb_find_n() failed\n");
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
write_perm_errs(cscp_conn *cscp, GHashTable *errs)
{
	GHashIter *it;
	gpointer key;
	gpointer val;

	it = g_hash_iter_new(errs);
	for (key = g_hash_iter_first(it, &key, &val); key;
	     key = g_hash_iter_next(it, &key, &val)) {
		write_str(cscp, permdenied_msg);
		write_str_nl(cscp, (char *)key);
	}

	g_hash_iter_destroy(it);
}

/* translate a context id to a name - do not free! */
char *
ctxt_name(int ctxt) 
{
	static char *ctxt_map[] = {
		"CTXT_CLIENT",
		"CTXT_HANDLER",
		"CTXT_RO",
		"invalid context"
	};

	if (ctxt >= 0 && ctxt < CTXT_MAX) {
		return ctxt_map[ctxt];
	}

	return ctxt_map[CTXT_MAX];
}

/* translate a state id to a name - do not free! */ char *
state_name(int state) 
{
	static char *state_map[] = {
		"STATE_ID",
		"STATE_CMD",
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

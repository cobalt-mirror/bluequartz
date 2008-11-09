%{
/* $Id: cscp.y 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2002 Sun Microsystems, Inc.  All rights reserved. */

/*
 * Notes about the implementation herein:
 * - Whitespace (except lead/trail) must be explicitly listed (SP)
 * - This version of the parser does not try to be the full interpreter.
 *   The support logic is all external to the parser.  For now (CCE 1.0), it
 *   is safer to do it this way.  More responsibility can be assigned later
 *   to this code, if it is so decided.  cscp_fsm.c has too many details to
 *   convert whole hog just yet.
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <glib.h>
#include "cce_common.h"
#include "cscp_parse.h"
#include "cce_types.h"
#include "props.h"
/* do not include cscp.tab.h here */

static struct cscp_parsed_cmd *cur_cmd;
static props_t *cur_props;
static props_t *cur_reprops;

extern int cscplex(void);
static void cscperror(char *s);
static void add_to_props(props_t **props, property_t *key, char *value);
static void add_to_get_reqs(oid_t oid, char *namespace, char *property);
static void cleanup_allocs(void);

%}

%union {
	char *str;
	oid_t oid;
	property_t prop;
}

%type <str> cscp_token c_identifier
%type <str> class namespace typedef schema bare_property
%type <prop> property
%type <oid> object_id

%token SP NL TOK_EOF
%token TOK_NUMBER TOK_CID TOK_STRING TOK_QSTRING
%token TOK_EQ TOK_REQ
%token TOK_ADMIN TOK_ADMIN_DEBUG TOK_ADMIN_RESUME TOK_ADMIN_SUSPEND
%token TOK_AUTHKEY
%token TOK_AUTH
%token TOK_BADDATA
%token TOK_BEGIN
%token TOK_BYE TOK_BYE_SUCCESS TOK_BYE_FAIL TOK_BYE_DEFER
%token TOK_CLASSES
%token TOK_COMMIT
%token TOK_CREATE
%token TOK_DESTROY
%token TOK_ENDKEY
%token TOK_FIND
%token TOK_GET
%token TOK_HELP
%token TOK_INFO
%token TOK_NAMES
%token TOK_SCHEMA TOK_SCHEMA_ADD TOK_SCHEMA_LIST TOK_SCHEMA_REMOVE
%token TOK_SET
%token TOK_VALIDATE TOK_VALIDATE_PROPERTY TOK_VALIDATE_TYPEDEF
%token TOK_WARN
%token TOK_WHOAMI

%%

client_msg: cscp_command NL {
		return 0;
	}
	| TOK_EOF {
		return -1;
	}
	;

object_id:
	TOK_NUMBER {
		$$ = atoi(cscplval.str);
		cscp_free(cscplval.str);
	}
	;

property:
	bare_property {
		$$.namespace = NULL;
		$$.property = $1;
	}
	| namespace '.' bare_property {
		$$.namespace = $1;
		$$.property = $3;
	}
	;

bare_property:
	TOK_CID		{ $$ = cscplval.str; }
	;

class:
	TOK_CID		{ $$ = cscplval.str; }
	;

namespace:
	TOK_CID		{ $$ = cscplval.str; }
	;

typedef:
	TOK_CID		{ $$ = cscplval.str; }
	;

schema:
	TOK_CID		{ $$ = cscplval.str; }
	;

c_identifier:
	TOK_CID		{ $$ = cscplval.str; }
	;

cscp_token:
	TOK_CID		{ $$ = cscplval.str; }
	| TOK_NUMBER	{ $$ = cscplval.str; }
	| TOK_QSTRING	{ $$ = cscplval.str; }
	;

cscp_command:
	cmd_admin
	| cmd_auth
	| cmd_authkey
	| cmd_baddata
	| cmd_begin
	| cmd_bye
	| cmd_classes
	| cmd_commit
	| cmd_create
	| cmd_destroy
	| cmd_endkey
	| cmd_find
	| cmd_get
	| cmd_help
	| cmd_info
	| cmd_names
	| cmd_schema
	| cmd_set
	| cmd_validate
	| cmd_warn
	| cmd_whoami
	| /* blank */
	;

admin:
	TOK_ADMIN {
		cur_cmd->cmd = CSCP_CMD_ADMIN;
	}
	;

cmd_admin:
	admin SP TOK_ADMIN_SUSPEND SP cscp_token {
		cur_cmd->cmd = CSCP_CMD_ADMIN_SUSPEND;
		cur_cmd->args.admin_suspend.reason = $5;
	}
	| admin SP TOK_ADMIN_RESUME {
		cur_cmd->cmd = CSCP_CMD_ADMIN_RESUME;
	}
	;

auth:
	TOK_AUTH {
		cur_cmd->cmd = CSCP_CMD_AUTH;
		cur_cmd->args.auth.username = NULL;
		cur_cmd->args.auth.password = NULL;
	}
	;

cmd_auth:
	auth SP cscp_token SP cscp_token {
		cur_cmd->args.auth.username = $3;
		cur_cmd->args.auth.password = $5;
	}
	;

authkey:
	TOK_AUTHKEY {
		cur_cmd->cmd = CSCP_CMD_AUTHKEY;
		cur_cmd->args.authkey.username = NULL;
		cur_cmd->args.authkey.sessionkey = NULL;
	}
	;

cmd_authkey:
	authkey SP cscp_token SP cscp_token {
		cur_cmd->args.authkey.username = $3;
		cur_cmd->args.authkey.sessionkey = $5;
	}
	;

begin:
	TOK_BEGIN {
		cur_cmd->cmd = CSCP_CMD_BEGIN;
	}
	;

cmd_begin:
	begin
	;

bye:
	TOK_BYE	{
		cur_cmd->cmd = CSCP_CMD_BYE;
		cur_cmd->args.bye.status = BYE_NONE;
	}
	;

cmd_bye:
	bye
	| bye SP TOK_BYE_SUCCESS {
		cur_cmd->args.bye.status = BYE_SUCCESS;
	}
	| bye SP TOK_BYE_FAIL {
		cur_cmd->args.bye.status = BYE_FAIL;
	}
	| bye SP TOK_BYE_DEFER {
		cur_cmd->args.bye.status = BYE_DEFER;
	}
	;
	
classes:
	TOK_CLASSES {
		cur_cmd->cmd = CSCP_CMD_CLASSES;
	}
	;

cmd_classes:
	classes
	;

commit:
	TOK_COMMIT {
		cur_cmd->cmd = CSCP_CMD_COMMIT;
	}
	;

cmd_commit:
	commit
	;

/*
 * Create Class foo=bar ns.foo=bar
 */
create:
	TOK_CREATE {
		cur_cmd->cmd = CSCP_CMD_CREATE;
		cur_cmd->args.create.classname = NULL;
		cur_cmd->args.create.props = NULL;
	}
	;

cmd_create:
	create SP class assignment_list {
		cur_cmd->args.create.classname = $3;
		cur_cmd->args.create.props = cur_props;
	}
	;

assignment_list:
	assignment_list SP assignment
	| /* blank */
	;

assignment:
	property TOK_EQ cscp_token {
		add_to_props(&cur_props, &$1, $3);
		/* props_* functions manage their own memory */
		cscp_free($1.namespace);
		cscp_free($1.property);
		cscp_free($3);
	}
	;
	
destroy:
	TOK_DESTROY {
		cur_cmd->cmd = CSCP_CMD_DESTROY;
		cur_cmd->args.destroy.oid = -1;
	}
	;

cmd_destroy:
	destroy SP object_id {
		cur_cmd->args.destroy.oid = $3;
	}
	;

endkey:
	TOK_ENDKEY {
		cur_cmd->cmd = CSCP_CMD_ENDKEY;
	}
	;

cmd_endkey:
	endkey
	;

/*
 * FIND class sorttype = "foo" sortprop = "bar"  k1 = v1 k2 ~ "v2"
 */
find:
	TOK_FIND {
		cur_cmd->cmd = CSCP_CMD_FIND;
		cur_cmd->args.find.classname = NULL;
		cur_cmd->args.find.sortprop = NULL;
		cur_cmd->args.find.sorttype = NULL;
		cur_cmd->args.find.exact_props = NULL;
		cur_cmd->args.find.regex_props = NULL;
	}
	;

cmd_find:
	find SP class find_arg_list {
		cur_cmd->args.find.classname = $3;
		if ((cur_cmd->args.find.sorttype &&
		    !cur_cmd->args.find.sortprop) ||
		    (!cur_cmd->args.find.sorttype &&
		    cur_cmd->args.find.sortprop)) {
			YYERROR;
		}
		cur_cmd->args.find.exact_props = cur_props;
		cur_cmd->args.find.regex_props = cur_reprops;
	}
	;

find_arg_list:
	find_arg_list SP find_arg
	| /* blank */
	;

find_arg:
	find_ctl
	| criterion
	;

find_ctl:
	c_identifier SP cscp_token {
		/* figure out if it is a command we know about */
		if (!strcasecmp($1, "sortprop") &&
		    !cur_cmd->args.find.sortprop) {
			cur_cmd->args.find.sortprop = $3;
			cscp_free($1);
		} else if (!strcasecmp($1, "sorttype") &&
		    !cur_cmd->args.find.sorttype) {
			cur_cmd->args.find.sorttype = $3;
			cscp_free($1);
		} else {
			YYERROR;
		}
	}
	;

criterion:
	property TOK_EQ cscp_token {
		add_to_props(&cur_props, &$1, $3);
		/* props_* functions manage their own memory */
		cscp_free($1.namespace);
		cscp_free($1.property);
		cscp_free($3);
	}
	| property TOK_REQ cscp_token {
		add_to_props(&cur_reprops, &$1, $3);
		/* props_* functions manage their own memory */
		cscp_free($1.namespace);
		cscp_free($1.property);
		cscp_free($3);
	}
	;

help:
	TOK_HELP {
		cur_cmd->cmd = CSCP_CMD_HELP;
	}
	;

cmd_help:
	help
	;

/*
 * GET 10
 * GET 10.*
 * GET 10.foo
 * GET 10.Ns.*
 * GET 10.Ns.Foo
 */
get:
	TOK_GET {
		cur_cmd->cmd = CSCP_CMD_GET;
		cur_cmd->args.get.nrequests = 0;
		cur_cmd->args.get.requests = NULL;
	}
	;

cmd_get:
	get SP get_arg_list
	;
	
get_arg_list:
	get_arg_list SP get_arg
	| get_arg
	;

get_arg:
	object_id '.' property {
		add_to_get_reqs($1, $3.namespace, $3.property);
		/* don't free ns,prop here, get_reqs is holding them */
	}
	| object_id '.' '*' {
		add_to_get_reqs($1, NULL, NULL);
	}
	| object_id {
		/* this is for compatibility - "GET 10" vs "GET 10.*" */
		add_to_get_reqs($1, NULL, NULL);
	}
	| object_id '.' namespace '.' '*' {
		add_to_get_reqs($1, $3, NULL);
		/* don't free ns here, get_reqs is holding it */
	}
	;
	
names:
	TOK_NAMES {
		cur_cmd->cmd = CSCP_CMD_NAMES;
		cur_cmd->args.names.oid = -1;
		cur_cmd->args.names.classname = NULL;
	}
	;

cmd_names:
	names SP class {
		cur_cmd->args.names.classname = $3;
	}
	| names SP object_id {
		cur_cmd->args.names.oid = $3;
	}
	;

schema:
	TOK_SCHEMA {
		cur_cmd->cmd = CSCP_CMD_SCHEMA;
	}

cmd_schema:
	schema SP TOK_SCHEMA_ADD SP cscp_token {
		cur_cmd->cmd = CSCP_CMD_SCHEMA_ADD;
		cur_cmd->args.schema_add.schema = $5;
	}
	| schema SP TOK_SCHEMA_LIST {
		cur_cmd->cmd = CSCP_CMD_SCHEMA_LIST;
	}
	| schema SP TOK_SCHEMA_REMOVE SP schema {
		cur_cmd->cmd = CSCP_CMD_SCHEMA_REMOVE;
		cur_cmd->args.schema_remove.schema = $5;
	}

/*
 * SET 10 foo=bar ns.foo=bar
 * SET 10.ns foo=bar
 */
set:
	TOK_SET {
		cur_cmd->cmd = CSCP_CMD_SET;
		cur_cmd->args.set.oid = -1;
		cur_cmd->args.set.namespace = NULL;
		cur_cmd->args.set.props = NULL;
	}
	;

cmd_set:
	set SP object_id assignment_list {
		cur_cmd->args.set.oid = $3;
		cur_cmd->args.set.props = cur_props;
	}
	| set SP object_id '.' namespace assignment_list {
		cur_cmd->args.set.oid = $3;
		cur_cmd->args.set.namespace = $5;
		cur_cmd->args.set.props = cur_props;
	}
	;

whoami:
	TOK_WHOAMI {
		cur_cmd->cmd = CSCP_CMD_WHOAMI;
	}
	;

cmd_whoami:
	whoami
	;

baddata:
	TOK_BADDATA {
		cur_cmd->cmd = CSCP_CMD_BADDATA;
		cur_cmd->args.baddata.oid = -1;
		cur_cmd->args.baddata.key = NULL;
		cur_cmd->args.baddata.value = NULL;
	}
	;

cmd_baddata:
	baddata SP object_id SP cscp_token SP cscp_token {
		cur_cmd->args.baddata.oid = $3;
		cur_cmd->args.baddata.key = $5;
		cur_cmd->args.baddata.value = $7;
	}
	;

info:
	TOK_INFO {
		cur_cmd->cmd = CSCP_CMD_INFO;
		cur_cmd->args.info.message = NULL;
	}
	;

cmd_info:
	info SP cscp_token {
		cur_cmd->args.info.message = $3;
	}
	;

validate:
	TOK_VALIDATE {
		cur_cmd->cmd = CSCP_CMD_VALIDATE;
	}
	;

cmd_validate:
	validate SP TOK_VALIDATE_PROPERTY SP class '.' property SP cscp_token {
		cur_cmd->cmd = CSCP_CMD_VALIDATE_PROPERTY;
		cur_cmd->args.validate_property.class = $5;
		cur_cmd->args.validate_property.property.namespace =
			$7.namespace;
		cur_cmd->args.validate_property.property.property = 
			$7.property;
		cur_cmd->args.validate_property.data = $9;
	}
	| validate SP TOK_VALIDATE_TYPEDEF SP typedef SP cscp_token {
		cur_cmd->cmd = CSCP_CMD_VALIDATE_TYPEDEF;
		cur_cmd->args.validate_typedef.type = $5;
		cur_cmd->args.validate_typedef.data = $7;
	}
	;

warn:
	TOK_WARN {
		cur_cmd->cmd = CSCP_CMD_WARN;
		cur_cmd->args.warn.message = NULL;
	}
	;

cmd_warn:
	warn SP cscp_token {
		cur_cmd->args.info.message = $3;
	}
	;

%%

static void
cscperror(char *s)
{
	int tok;

	/* finish the line */
	tok = cscpchar;
	while (tok != NL && tok != TOK_EOF) {
		tok = cscplex();
	}

	/* clean up */
	if (cur_props) {
		props_destroy(cur_props);
	}
	if (cur_reprops) {
		props_destroy(cur_reprops);
	}
	cleanup_allocs();
}

/* this will duplicate key and value - caller can free them */
static void
add_to_props(props_t **props, property_t *key, char *value)
{
	if (!*props) {
		*props = props_new();
		if (!*props) {
			CCE_OOM();
		}
	}
	props_set(*props, key, value);
}

static void
add_to_get_reqs(oid_t oid, char *namespace, char *property)
{
	get_t *tmp;

	tmp = realloc(cur_cmd->args.get.requests,
		sizeof(*tmp) * (cur_cmd->args.get.nrequests + 1));
	if (!tmp) {
		CCE_OOM();
	}

	tmp[cur_cmd->args.get.nrequests].oid = oid;
	tmp[cur_cmd->args.get.nrequests].property.namespace =
	    namespace;
	tmp[cur_cmd->args.get.nrequests].property.property =
	    property;

	cur_cmd->args.get.requests = tmp;
	cur_cmd->args.get.nrequests++;
}

/*
 * These are used to track outstanding allocations because yacc sucks at it.
 * These are not a "lazy-man's GC", they are here so we can clean up if an
 * error occurs, and we have allocated memory (strings).
 */
static GPtrArray *cscp_allocs;

void *
cscp_malloc(size_t size)
{
	void *newp;
	
	newp = malloc(size);
	if (newp) {
		g_ptr_array_add(cscp_allocs, newp);
	}
	return newp;
}
	
void
cscp_free(void *p)
{
	if (p) {
		g_ptr_array_remove_fast(cscp_allocs, p);
		free(p);
	}
}

char *
cscp_strdup(const char *p)
{
	char *newp;

	newp = strdup(p);
	if (newp) {
		g_ptr_array_add(cscp_allocs, newp);
	}
	return newp;
}
		
void
cscp_note_alloc(void *p)
{
	if (p) {
		g_ptr_array_add(cscp_allocs, p);
	}
}

/* call this on error */
static void
cleanup_allocs(void)
{
	int i;

	for (i = 0; i < cscp_allocs->len; i++) {
		free(g_ptr_array_index(cscp_allocs, i));
	}
	for (i = 0; i < cscp_allocs->len; i++) {
		g_ptr_array_remove_index_fast(cscp_allocs, i);
	}
}

/*
 * This is the main exported interface to this parser
 */
int
cscp_parse_line(const char *cmdstr, struct cscp_parsed_cmd *cmd)
{
	int r;

	if (!cmdstr) {
		return -1;
	}

	/* initialize surrounding state */
	cur_cmd = cmd;
	cur_props = cur_reprops = NULL;
	cur_cmd->cmd = CSCP_CMD_NONE;

	/* let lex know from whence to read */
	cscp_lex_setbuffer(cmdstr);
	
	/* we need to have a list of allocations, in case of error */
	cscp_allocs = g_ptr_array_new();

	r = cscpparse();

	/* we can now assume anything allocated is safely stored */
	g_ptr_array_free(cscp_allocs, 1);

	/* tell the lexer that we're done */
	cscp_lex_unsetbuffer();

	return r;
}

/*
 * Exported function to safely free a cscp_parsed_cmd structure
 */
#define SAFE_FREE(p)	do { if (p) free(p); } while (0)
void
cscp_parsed_cmd_cleanup(struct cscp_parsed_cmd *cmd)
{
	switch (cmd->cmd) {
	case CSCP_CMD_NONE:
	case CSCP_CMD_ADMIN:
	case CSCP_CMD_ADMIN_RESUME:
	case CSCP_CMD_BEGIN:
	case CSCP_CMD_BYE:
	case CSCP_CMD_CLASSES:
	case CSCP_CMD_COMMIT:
	case CSCP_CMD_DESTROY:
	case CSCP_CMD_ENDKEY:
	case CSCP_CMD_HELP:
	case CSCP_CMD_SCHEMA:
	case CSCP_CMD_SCHEMA_LIST:
	case CSCP_CMD_VALIDATE:
	case CSCP_CMD_WHOAMI:
		/* no params to free */
		break;

	case CSCP_CMD_ADMIN_SUSPEND:
		SAFE_FREE(cmd->args.admin_suspend.reason);
		break;
	case CSCP_CMD_AUTH:
		SAFE_FREE(cmd->args.auth.username);
		SAFE_FREE(cmd->args.auth.password);
		break;
	case CSCP_CMD_AUTHKEY:
		SAFE_FREE(cmd->args.authkey.username);
		SAFE_FREE(cmd->args.authkey.sessionkey);
		break;
	case CSCP_CMD_BADDATA:
		SAFE_FREE(cmd->args.baddata.key);
		SAFE_FREE(cmd->args.baddata.value);
		break;
	case CSCP_CMD_CREATE:
		SAFE_FREE(cmd->args.create.classname);
		if (cmd->args.create.props) {
			props_destroy(cmd->args.create.props);
		}
		break;
	case CSCP_CMD_FIND:
		SAFE_FREE(cmd->args.find.classname);
		if (cmd->args.find.exact_props) {
			props_destroy(cmd->args.find.exact_props);
		}
		if (cmd->args.find.regex_props) {
			props_destroy(cmd->args.find.regex_props);
		}
		SAFE_FREE(cmd->args.find.sorttype);
		SAFE_FREE(cmd->args.find.sortprop);
		break;
	case CSCP_CMD_GET: {
		int i;
		for (i = 0; i < cmd->args.get.nrequests; i++) {
			SAFE_FREE(cmd->args.get.requests[i].property.
			    namespace);
			SAFE_FREE(cmd->args.get.requests[i].property.
			    property);
		}
		if (cmd->args.get.requests)
			free(cmd->args.get.requests);
		break;
	}
	case CSCP_CMD_INFO:
		SAFE_FREE(cmd->args.info.message);
		break;
	case CSCP_CMD_NAMES:
		SAFE_FREE(cmd->args.names.classname);
		break;
	case CSCP_CMD_SCHEMA_ADD:
		SAFE_FREE(cmd->args.schema_add.schema);
		break;
	case CSCP_CMD_SCHEMA_REMOVE:
		SAFE_FREE(cmd->args.schema_remove.schema);
		break;
	case CSCP_CMD_SET:
		SAFE_FREE(cmd->args.set.namespace);
		if (cmd->args.set.props) {
			props_destroy(cmd->args.set.props);
		}
		break;
	case CSCP_CMD_VALIDATE_PROPERTY:
		SAFE_FREE(cmd->args.validate_property.class);
		SAFE_FREE(cmd->args.validate_property.property.namespace);
		SAFE_FREE(cmd->args.validate_property.property.property);
		SAFE_FREE(cmd->args.validate_property.data);
		break;
	case CSCP_CMD_VALIDATE_TYPEDEF:
		SAFE_FREE(cmd->args.validate_typedef.type);
		SAFE_FREE(cmd->args.validate_typedef.data);
		break;
	case CSCP_CMD_WARN:
		SAFE_FREE(cmd->args.warn.message);
		break;
	}
}

#ifdef YACC_MAIN
int main(void)
{
	struct cscp_parsed_cmd cmd;
	int r;
	char buf[256];

	while (1) {
		r = -1;
		fgets(buf, sizeof(buf)-1, stdin);
		if (!feof(stdin)) {
			r = cscp_parse_line(buf, &cmd);
		}
		if (r < 0) {
			printf("exiting\n");
			exit(0);
		} else if (r > 0) {
			printf("parse error\n");
		} else {
			printf("got cmd #%d\n", cmd.cmd);
		}
		/* if buf were dynamic, we'd free it here */
	}
	return 0;
}
#endif

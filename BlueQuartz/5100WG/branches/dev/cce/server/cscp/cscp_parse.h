#ifndef CSCP_PARSE_H__
#define CSCP_PARSE_H__

#include "cce_types.h"
#include "props.h"

typedef enum {
	BYE_NONE = 0,
	BYE_SUCCESS,
	BYE_FAIL,
	BYE_DEFER,
} bye_t;

typedef struct {
	oid_t oid;
	property_t property;
} get_t;

typedef enum { 
	CSCP_CMD_NONE = 0,
	CSCP_CMD_ADMIN,
	CSCP_CMD_ADMIN_RESUME,
	CSCP_CMD_ADMIN_SUSPEND,
	CSCP_CMD_AUTH,
	CSCP_CMD_AUTHKEY,
	CSCP_CMD_BADDATA,
	CSCP_CMD_BEGIN,
	CSCP_CMD_BYE,
	CSCP_CMD_CLASSES,
	CSCP_CMD_COMMIT,
	CSCP_CMD_CREATE,
	CSCP_CMD_DESTROY,
	CSCP_CMD_ENDKEY,
	CSCP_CMD_FIND,
	CSCP_CMD_GET,
	CSCP_CMD_HELP,
	CSCP_CMD_INFO,
	CSCP_CMD_NAMES,
	CSCP_CMD_SCHEMA,
	CSCP_CMD_SCHEMA_ADD,
	CSCP_CMD_SCHEMA_LIST,
	CSCP_CMD_SCHEMA_REMOVE,
	CSCP_CMD_SET,
	CSCP_CMD_VALIDATE,
	CSCP_CMD_VALIDATE_PROPERTY,
	CSCP_CMD_VALIDATE_TYPEDEF,
	CSCP_CMD_WARN,
	CSCP_CMD_WHOAMI,
} cscp_cmd_t;

/* a struct to hold a parsed command */
struct cscp_parsed_cmd {
	char *raw_input;
	cscp_cmd_t cmd;
	union {
		struct {
			char *reason;
		} admin_suspend;
		struct {
			char *username;
			char *password;
		} auth;
		struct {
			char *username;
			char *sessionkey;
		} authkey;
		struct {
			oid_t oid;
			char *key;
			char *value;
		} baddata;
		struct {
			bye_t status;
		} bye;
		struct {
			char *classname;
			props_t *props;
		} create;
		struct {
			oid_t oid;
		} destroy;
		struct {
			char *classname;
			props_t *exact_props;
			props_t *regex_props;
			char *sorttype; 
			char *sortprop;
		} find;
		struct {
			int nrequests;
			get_t *requests;
		} get;
		struct {
			char *message;
		} info;
		struct {
			char *classname;
			oid_t oid;
		} names;
		struct {
			char *schema;
		} schema_add;
		struct {
			char *schema;
		} schema_remove;
		struct {
			oid_t oid;
			char *namespace;
			props_t *props;
		} set;
		struct {
			char *class;
			property_t property;
			char *data;
		} validate_property;
		struct {
			char *type;
			char *data;
		} validate_typedef;
		struct {
			char *message;
		} warn;
	} args;
};

int cscp_parse_line(const char *buf, struct cscp_parsed_cmd *cmd);
void cscp_parsed_cmd_cleanup(struct cscp_parsed_cmd *cmd);
void cscp_print_line(struct cscp_parsed_cmd *cmd, char *buf, int bufsize);

/*
 * All the below functions are PARSER INTERNAL - DO NOT CALL THEM!
 */

/* help the parser to sanely cleanup on errors */
void *cscp_malloc(size_t size);
void cscp_free(void *p);
char *cscp_strdup(const char *p);
void cscp_note_alloc(void *p);

/* set the lexer to parse from a string */
void cscp_lex_setbuffer(const char *buf);
void cscp_lex_unsetbuffer();

#endif /* CSCP_PARSE_H__ */
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

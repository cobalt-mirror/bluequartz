/* $Id: c6.h 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * Copyright Cobalt Network 2000-2001
 * Author: Harris Vaegan-Lloyd
 */
#ifndef _CCE_C6_H_
#define _CCE_C6_H_ 1

#include <unistd.h>
#include <sys/types.h>

#include <glib.h>

/* Struct -> Typedefs */
typedef struct cscp_conn_struct cscp_conn_t;
typedef struct cscp_line_struct cscp_line_t;
typedef struct cscp_cmnd_struct cscp_cmnd_t;
typedef struct cscp_resp_struct cscp_resp_t;
typedef ulong cscp_oid_t;

/*
 * Connection Functions
 */

/* Create a new cscp_conn_t assume we always connect to the same place
 * inits state to AUTH and sets version string */
cscp_conn_t *cscp_conn_new(void);

/* Tear down the connection and destroy the object */
void cscp_conn_destroy( cscp_conn_t *conn );

/* Actually connect to a socket */
int cscp_conn_connect(cscp_conn_t *conn, char *path);

/* Actually connect to stdin/stdout */
int cscp_conn_connect_stdin( cscp_conn_t *conn );

/* Run a command and fetch the response */
int cscp_conn_do(cscp_conn_t *conn, cscp_cmnd_t *cmnd);

/* As above but just send the command and forget the response */
int cscp_conn_do_nowait(cscp_conn_t *conn, cscp_cmnd_t *cmnd);

/* Search for new data on the wire in CHECK mode
 * returns just the new lines of data, as well as adding them to it's internal
 * cache of the last response */
int cscp_conn_poll(cscp_conn_t *conn);

/* Checks to see if the current response is finished and if the connection
 * is ready for more commands */
int cscp_conn_is_finished( cscp_conn_t *conn );

/* Clears out the last response from the connection */
void cscp_conn_clear ( cscp_conn_t *conn );

/* Returns the last of the server's responses. */
cscp_resp_t *cscp_conn_last_resp( cscp_conn_t *conn );

/*
 * Command Functions
 */

/* Creates a new cmnd from it's command name */
cscp_cmnd_t *cscp_cmnd_new(void);
void cscp_cmnd_destroy( cscp_cmnd_t *cmnd );

/* Set and get the command */
void cscp_cmnd_setcmnd(cscp_cmnd_t *cmnd, int cmd);
int  cscp_cmnd_getcmnd(cscp_cmnd_t *cmnd);

/* Add various types of args to our command structure */
void cscp_cmnd_addstr( cscp_cmnd_t *cmnd, char *arg);
void cscp_cmnd_addliteral( cscp_cmnd_t *cmnd, char *arg);
void cscp_cmnd_addoid( cscp_cmnd_t *cmnd, cscp_oid_t oid, char *namespace);

/* Get the nth argument, indexes start at 0 */
char *cscp_cmnd_getparam(cscp_cmnd_t *cmnd, int n);

int cscp_cmnd_getnumparams(cscp_cmnd_t *cmnd);

/* Turn a cmnd object into a string suitable for sending down the wire */
char *cscp_cmnd_serialise ( cscp_cmnd_t *line );

/*
 * Line Functions
 */

/* Create a new line */
cscp_line_t *cscp_line_new(void);
/* Create a new line from a string of data */
cscp_line_t *cscp_line_from_string( char *data );
void cscp_line_destroy( cscp_line_t *line );
int cscp_line_getcode( cscp_line_t *line );
char *cscp_line_getparam( cscp_line_t *line, int pos);
char *copy_message( cscp_line_t *line);

/* Get the INFO/WARN/SUCCESS/FAIL code.. */
int cscp_line_code_status( cscp_line_t *line );
/* Get the final digit for type */
int cscp_line_code_type( cscp_line_t *line );

/*
 * Response Functions
 */

/* Create an empty response object */

cscp_resp_t *cscp_resp_new(void);
void cscp_resp_destroy( cscp_resp_t *resp );

/* Add a cscp_line_t to the response object, add the line to the appropriate
 * list as well as the all list. Also sets success or failure upon receinv
 * the final command
 *
 * Returns true unless an error occurs (e.g. Adding multiple lines that
 * convey success or failure
 */

int cscp_resp_add_line( cscp_resp_t *resp, cscp_line_t *line);
int cscp_resp_is_success(cscp_resp_t *resp);

/* Returns the next line to process, or the first line if it has not been
 * called before */

cscp_line_t *cscp_resp_nextline(cscp_resp_t* resp);

/* Rewinds the current pointer for nextline back to the start */

void cscp_resp_rewind(cscp_resp_t *resp);

/* Returns the final success/failure line */

cscp_line_t *cscp_resp_lastline(cscp_resp_t *resp);

/* Checks to see if the response is finished */

int cscp_resp_is_finished( cscp_resp_t *resp );

cscp_oid_t cscp_oid_from_string( char *string );
char *cscp_oid_to_string(cscp_oid_t oid);

/* Length of the numeric code to start a line */
#define C6_CODE_LENGTH 3

#ifdef DEBUG
/* All of these are just used for state resporting */
char *cscp_line_serialise ( cscp_line_t *line );
char *cscp_resp_serialise ( cscp_resp_t *line );

char *cscp_line_print ( cscp_line_t *line );
char *cscp_cmnd_print ( cscp_cmnd_t *line );
char *cscp_resp_print ( cscp_resp_t *line );
#endif /* DEBUG */

#endif /* _C6_H */
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

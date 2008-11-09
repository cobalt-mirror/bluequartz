/* $Id: cce_ed.h 3 2003-07-17 15:19:15Z will $
 * 
 * ed, the talking event dispatcher.  "Dispatching events is what I 
 * like to do," says ed.
 */

#ifndef _CCE_ED_H_
#define _CCE_ED_H_

#include <cce_conf.h>
#include <codb.h>
#include <codb_events.h>
#include <glib.h>

/* how many levels of handlers invoking handlers can we go? */
#define ED_MAX_DEPTH	50

typedef struct cce_ed_struct cce_ed; /* hi, i'm ed! */

/* a handler -> event (1-n) relationship */
typedef struct {
	cce_conf_handler *handler;
	GSList *events;
} ed_handler_event;
ed_handler_event *handler_event_new(cce_conf_handler *h);
void handler_event_destroy(ed_handler_event *e);

/* construct me */
cce_ed *cce_ed_new(cce_conf *conf); 
cce_ed *cce_ed_branch(cce_ed *ed);
/* destroy me */
void cce_ed_destroy(cce_ed *ed);   
/* dispatch handlers for events */
int cce_ed_dispatch(cce_ed *ed, codb_handle *odb);

/* flush message buffers */
void cce_ed_flush(cce_ed *ed);

GSList *cce_ed_get_bad_oidlist(cce_ed *ed);
GHashTable *cce_ed_access_baddata(cce_ed *ed, oid_t oid);
GSList *cce_ed_access_messages(cce_ed *ed);

/* issue bad keys */
void cce_ed_add_baddata(cce_ed *ed, oid_t oid, char *key, char *why);

/* issue warning/error messages */
void cce_ed_add_message(cce_ed *, char *msg);

/* concats all messages (bad data, etc.) */
void cce_ed_concat_messages(cce_ed *, cce_ed *);

#endif /* cce/cce_ed.h */
/* eof */
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

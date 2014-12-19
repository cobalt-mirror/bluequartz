/* $Id: cce_ed_internal.h 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#ifndef __CCE_ED_INTERNAL_H__
#define __CCE_ED_INTERNAL_H__

#include <cce_ed.h>
#include <cscp.h>

#include <string.h>
#include <stdlib.h>

/* "magic" property names that actually imply special events: */
#define EVENTNAME_CREATE 		"_CREATE"
#define EVENTNAME_DESTROY		"_DESTROY"

/* here's whats inside ed */
struct cce_ed_struct {
	cce_conf *conf; /* a reference to the configuration object */
	codb_handle *odb;
	GSList *msgs;
	GHashTable *prop_msgs;
	GSList *old_rollback_handlers;
};

/* useful for creating hashes of *oid_t's */
guint oid_hash(gconstpointer key);
gint oid_equal(gconstpointer a, gconstpointer b);

/* handler interfaces */
int handler_exec(codb_handle *odb, cce_ed *ed, ed_handler_event *he,
	cscp_ctxt_t context);
int handler_perl(codb_handle *odb, cce_ed *ed, ed_handler_event *he,
	cscp_ctxt_t context);
int handler_test(codb_handle *odb, cce_ed *ed, ed_handler_event *he,
	cscp_ctxt_t context);

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

/* $Id: cce_ed_internal.h 3 2003-07-17 15:19:15Z will $
 */

#ifndef __CCE_ED_INTERNAL_H__
#define __CCE_ED_INTERNAL_H__

/* define DEBUG_ED to enable ED debugging messages and assertions */
/* FIXME: MPASHNIAK: either take this out, or make it global. sheesh */
#ifdef DEBUG_ED
  #define CCE_ENABLE_DEBUG

  #include <stdio.h>
  #undef DASSERT
  #define DASSERT(a) \
  	do { \
		if (!(a)) CCE_SYSLOG("ASSERTION FAILED\n  %70.70s\n", #a ); \
	} while (0)
#else
	#undef CCE_ENABLE_DEBUG
	#undef DASSERT
	#define DASSERT(a)
#endif  
#include <cce_debug.h>

#include <cce_common.h>
#include <cce_ed.h>

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
};

/* useful for creating hashes of *oid_t's */
guint oid_hash(gconstpointer key);
gint oid_equal(gconstpointer a, gconstpointer b);

/* handler interfaces */
int handler_exec(cce_ed *ed, oid_t oid, ed_handler_event *handler);
int handler_perl(cce_ed *ed, oid_t oid, ed_handler_event *handler);
int handler_test(cce_ed *ed, oid_t oid, ed_handler_event *handler);

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

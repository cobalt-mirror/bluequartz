/* $Id: cce_conf.h 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * Defines an object where all configuration information for CCE
 * is held.  This object knows how to read it's configuration information
 * from files on disk.
 */

#ifndef CCE_CONF_H_
#define CCE_CONF_H_ 1

#include <glib.h>
#include "cce_conf_types.h"

typedef struct cce_conf_struct cce_conf;

/** Constructor function of cce_conf objects.
 *  @param conf_root path of topmost directory within which to search for
 *    configuration files.
 *  @return a pointer to a new cce_conf object.
 */
cce_conf *cce_conf_get_configuration(const char *conf_root, const char *handler_dir);

/** Destructor fn of cce_conf objects 
 *  @param conf cce_conf object to destroy 
 */
void cce_conf_destroy( cce_conf *conf );

/** Queries the cce_conf object to get the list of handlers for a given
 *  event.
 *  @param conf The cce_conf object
 *  @param class Class of event to look up.
 *  @param property Property name of event to look up.
 *  @return Returns a GSList of cce_conf_handler objects.
 */
GSList *cce_conf_get_handlers(cce_conf *conf, const char *class, 
	const char *namespace, const char *property);

void cce_conf_dump_state(cce_conf *conf);

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

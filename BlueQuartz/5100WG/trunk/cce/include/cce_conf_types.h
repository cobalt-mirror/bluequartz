/* $Id: cce_conf_types.h 3 2003-07-17 15:19:15Z will $
 */
#ifndef _CCE_CONF_TYPES_H_
#define _CCE_CONF_TYPES_H_ 1

#include <glib.h>

/* handlers */
typedef struct cce_conf_handler_struct cce_conf_handler;

/* denote various stages at which a handler can run */
typedef enum {
	H_STAGE_NONE = 0,
	H_STAGE_VALIDATE,
	H_STAGE_CONFIGURE,
	H_STAGE_EXECUTE,
	H_STAGE_TEST,
	H_STAGE_CLEANUP,
	H_STAGE_MAX,
} handler_stage_t;

cce_conf_handler *cce_conf_handler_new(char *type, char *data, char *stage);
cce_conf_handler *cce_conf_handler_dup(cce_conf_handler *);
void cce_conf_handler_destroy(cce_conf_handler*);
char *cce_conf_handler_type(cce_conf_handler *);
char *cce_conf_handler_data(cce_conf_handler *);
char *cce_conf_handler_stage(cce_conf_handler *);
int cce_conf_handler_nstage(cce_conf_handler *);
char *cce_conf_handler_serialize(cce_conf_handler *);

#endif /* cce/cce_conf_types.h */
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

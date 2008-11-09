#ifndef CCEWRAP_CONF_H
#define CCEWRAP_CONF_H

#include "../include/cce.h"

typedef struct ccewrapconf_t * ccewrapconf;

#include "ccewrap_program.h"

ccewrapconf ccewrapconf_new(cce_handle_t *cce, char *authuser);
void ccewrapconf_free(ccewrapconf);
int ccewrapconf_parse_dir(ccewrapconf conf, char *dname);
void ccewrapconf_addprogram(ccewrapconf conf, ccewrapconf_program program);
GList *ccewrapconf_getprograms(ccewrapconf conf);
int ccewrapconf_checkprogram(ccewrapconf conf, char *progname, GList **validUsers);
cce_handle_t *ccewrapconf_getcce(ccewrapconf conf);
char *ccewrapconf_getauthuser (ccewrapconf conf);
char *ccewrapconf_getusercapabilities(ccewrapconf conf);
int ccewrapconf_issystemadministrator(ccewrapconf conf);

/* Other functions */
int isSystemAdministrator(cce_handle_t *cce, char *user);

#endif /* CCEWRAP_CONF_H */
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

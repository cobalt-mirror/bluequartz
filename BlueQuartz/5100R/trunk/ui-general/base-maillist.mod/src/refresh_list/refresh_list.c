/* $Id: refresh_list.c 3 2003-07-17 15:19:15Z will $
 *
 * A simple C program designed to be run suid such that MailList member
 * information can be updated on demand (ie. whenever majordomo runs).
 *
 * Yet another silly hack from the good people at Cobalt Networks.
 */

#define _GNU_SOURCE 
#include <stdio.h>
#include <cce.h>
#include <glib.h>
#include <string.h>
#include <unistd.h>
#include <time.h>

int
main(int argv, char *argc[])
{
  cce_handle_t *cce;
  GSList *oidlist;
  cce_props_t* propertyHash;
  char listname[200];
  
  if (argc[1] == NULL) {
    fprintf(stderr, "Usage: %s [listname]\n\n", argc[0]);
    exit(1);
  }
  
  propertyHash = cce_props_new();
  strncpy (listname, argc[1], 199); // avoid stack smash risk.
  cce_props_set(propertyHash, "name", listname);
  
  // connect to cce:
  cce = cce_handle_new(); 
  if (!cce_connect_cmnd(cce, NULL)) {
    fprintf (stderr, "Error: Could not connect to CCE.\n");
    exit(1);
  }
  
  oidlist = cce_find_cmnd(cce, "MailList", propertyHash);
  if (!oidlist) {
    fprintf (stderr, "Error: Could not find specified mailing list.\n");
    exit(1);
  }
  
  cce_props_destroy(propertyHash);
  propertyHash = cce_props_new();
  snprintf ( listname, 199, "%ld", (long)time(NULL) );  
  cce_props_set(propertyHash, "update", listname);
  
  cce_set_cmnd(cce, (cscp_oid_t)(oidlist->data), "", propertyHash);
  
  cce_props_destroy(propertyHash);
  cce_handle_destroy(cce);
  
  fprintf(stderr, "Done.\n");
  return 0;
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

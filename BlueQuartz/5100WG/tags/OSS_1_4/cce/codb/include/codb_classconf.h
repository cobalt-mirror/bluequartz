/* $Id: codb_classconf.h 3 2003-07-17 15:19:15Z will $
 *
 * Used to configure the valid datatypes and classes for the codb
 * object system.  Things like handlers are registered via the
 * separate "cce_conf" object.
 *
 * a quick warning: the "name" and "namespace" properties are used
 * to index into hashes inside the codb_classconf object.  For example,
 * it would be a bad idea to change the name of a class after it's
 * been inserted into a codb_classconf object.
 *
 * similarly, once a property has been added to a class, or a class has
 * been added to classconf, the encapsulating object now "owns" that
 * object reference (ie. it stores the reference, not a copy).  So be
 * sure not to destruct something that you've "given" to another object.
 * Instead, the encapsulating object becomes responsible for destroying
 * any object that is "given" to it.  makes sense?  jm.
 */

#ifndef __CODB_CLASSCONF_H__
#define __CODB_CLASSCONF_H__

#include <glib.h>
#include <codb.h>

/* class declarations */
typedef struct codb_typedef_struct codb_typedef;
typedef struct codb_property_struct codb_property;
typedef struct codb_class_struct codb_class;
typedef struct codb_classconf_struct codb_classconf;

/* type definitions */
codb_typedef *codb_typedef_new(char *type, char *name, char *data, 
	char *errmsg);
void codb_typedef_destroy(codb_typedef *);
char *codb_typedef_get_name(codb_typedef *);
char *codb_typedef_get_data(codb_typedef *);
char *codb_typedef_get_errmsg(codb_typedef *td);
codb_ret codb_typedef_validate(codb_typedef *, char *str);
codb_ret codb_typedef_validate_array(codb_typedef *, char *str);

/* property definitions for codb classes */
codb_property *codb_property_new(
	char *name,
	char *type,
	char *readacl,
	char *writeacl,
	char *def_val,
	char *optional,
	char *array);
int codb_property_assign(
	codb_property *,
	char *name,
	char *type,
	char *readacl,
	char *writeacl,
	char *def_val,
	char *optional,
	char *array);
char *codb_property_get_name(codb_property *);
char *codb_property_get_type(codb_property *);
char *codb_property_get_readacl(codb_property *);
char *codb_property_get_writeacl(codb_property *);
char *codb_property_get_def_val(codb_property *);
int codb_property_get_optional(codb_property *);
int codb_property_get_array(codb_property *);
codb_typedef *codb_property_get_typedef(codb_property *);
void codb_property_bindtype(codb_property *, codb_typedef *);
void codb_property_destroy(codb_property *);

/* class definition */
codb_class *codb_class_new(
	char *name,
	char *namespace, /* null or "" if defining the main nspace */
	char *version,
	char *createacl,
	char *destroyacl
);
int codb_class_assign(
	codb_class *,
	char *name,
	char *namespace,
	char *version
);
char *codb_class_get_name (codb_class *class);
char *codb_class_get_namespace (codb_class *class);
char *codb_class_get_version(codb_class *class);
char *codb_class_get_createacl( codb_class *class);
char *codb_class_get_destroyacl( codb_class *class);
int codb_class_addproperty(codb_class *, codb_property *);
GHashTable *codb_class_getproperties(codb_class *);
void codb_class_destroy(codb_class *); /* destroys all props in class, too. */
codb_ret codb_class_validate(codb_class *, GHashTable *attribs, GHashTable *errs);

/* codb_classconf
 *
 * contains a store of all class and type definitions.
 *
 * Initialization procedure:
 *   1. construct a new classconf object
 *   2. add all types and classes to classconf
 *   3. call bindtypes to validate
 */
codb_classconf *codb_classconf_new(void);
void codb_classconf_refresh(codb_classconf *cc);

codb_class *codb_classconf_getclass(codb_classconf *cc, char *name, 
	char *namespace);
GSList *codb_classconf_getnamespaces(codb_classconf *cc, char *class);
GSList *codb_classconf_getclasses(codb_classconf *cc);
int codb_classconf_setclass(codb_classconf *cc, codb_class *);
int codb_classconf_remclass(codb_classconf *cc, char *name, char *namespace);

codb_typedef *codb_classconf_gettype(codb_classconf *, char *name);
int codb_classconf_settype(codb_classconf *cc, codb_typedef *);
int codb_classconf_remtype(codb_classconf *cc, char *name);

/* bindtypes: verifies that every class property has a valid type, and
 * updates the internal pointers appropriately.  Returns # of errors
 * encountered. */
int codb_classconf_bindtypes(codb_classconf *cc);

codb_ret codb_classconf_validate(codb_classconf *cc,
	char *classname, char *spacename, 
  GHashTable *attribs, GHashTable *errs);

/* initialize all the classconf stuff */
codb_classconf *codb_classconf_init(void);
void codb_classconf_destroy(codb_classconf *);

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

/* $Id: classconf.h 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
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

#ifndef CLASSCONF_H__
#define CLASSCONF_H__

#include <glib.h>
#include "g_hashwrap.h"
#include "props.h"
#include "codb.h"
#include "codb_events.h"

/* class declarations */
typedef struct codb_rule_struct codb_rule;
typedef struct codb_matchtype_struct codb_matchtype;
typedef struct codb_typedef_struct codb_typedef;
typedef struct codb_property_struct codb_property;
typedef struct codb_index_struct codb_index;
typedef struct codb_class_struct codb_class;
typedef struct codb_classconf_struct codb_classconf;

extern codb_classconf *classconf;

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
const char *codb_property_get_name(codb_property *);
const char *codb_property_get_type(codb_property *);
const char *codb_property_get_readacl(codb_property *);
const char *codb_property_get_writeacl(codb_property *);
const char *codb_property_get_def_val(codb_property *);
int codb_property_get_optional(codb_property *);
int codb_property_get_array(codb_property *);
codb_typedef *codb_property_get_typedef(codb_property *);
void codb_property_bindtype(codb_property *, codb_typedef *);
void codb_property_destroy(codb_property *);
int codb_property_addindex(codb_property *, codb_index *);
GSList *codb_property_getindexes(codb_property *);

/* Index function definitions */
codb_index *codb_index_new(char *name, const char *classname,
	const char *property, char *sortname);
char *codb_index_get_name(codb_index*);
char *codb_index_get_property(codb_index*);
void codb_index_destroy(codb_index *);
int codb_class_addindex(codb_class *, codb_index *);
codb_ret codb_classconf_get_indexes(codb_classconf *, const char *,
	const char *, const char *, GSList **);

/* Sorttype function definitions */
codb_matchtype *codb_matchtype_new(char *name, char *type, char *data);
void codb_matchtype_destroy(codb_matchtype *);
const char *codb_matchtype_getname(codb_matchtype *);
sortfunc *codb_matchtype_getcompfunc(codb_matchtype *);
codb_matchtype *codb_classconf_get_matchtype(codb_classconf *, const char *);

/* Rule function definitions */
codb_rule *codb_rule_new(char *name, char *type, char *data);
void codb_rule_destroy(codb_rule *);
const char *codb_rule_getname(codb_rule *);
int codb_rule_check(codb_rule *, codb_handle *, codb_event *, GSList *);

/* class definition */
codb_class *codb_class_new(
	char *name,
	char *namespace, /* null or "" if defining the main nspace */
	char *version,
	char *createacl,
	char *destroyacl
);
const char *codb_class_get_name (codb_class *class);
const char *codb_class_get_namespace (codb_class *class);
const char *codb_class_get_version(codb_class *class);
const char *codb_class_get_createacl(codb_class *);
const char *codb_class_get_destroyacl(codb_class *);
int codb_class_addproperty(codb_class *, codb_property *);
GHashWrap *codb_class_getproperties(codb_class *);
void codb_class_destroy(codb_class *); /* destroys all props in class, too. */
codb_ret codb_class_validate(codb_class *, GHashWrap *attribs,
	GHashWrap *errs);
codb_property *codb_class_get_property(codb_classconf *cc, 
    const char *classname, property_t *prop);

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
void codb_classconf_destroy(codb_classconf *cc);
void codb_classconf_refresh(codb_classconf *cc);

codb_class *codb_classconf_getclass(codb_classconf *cc, const char *name, 
	const char *namespace);
GSList *codb_classconf_getnamespaces(codb_classconf *cc, const char *class);
GSList *codb_classconf_getclasses(codb_classconf *cc);
int codb_classconf_setclass(codb_classconf *cc, codb_class *);
int codb_classconf_remclass(codb_classconf *cc, char *name, char *namespace);

codb_typedef *codb_classconf_gettype(codb_classconf *, char *name);
int codb_classconf_settype(codb_classconf *cc, codb_typedef *);
int codb_classconf_remtype(codb_classconf *cc, char *name);

codb_rule *codb_classconf_getrule(codb_classconf *, const char *name);
int codb_classconf_setrule(codb_classconf *cc, codb_rule *);

codb_matchtype *codb_classconf_getmatchtype(codb_classconf *, const char *name);
int codb_classconf_setmatchtype(codb_classconf *cc, codb_matchtype *);

/* bindtypes: verifies that every class property has a valid type, and
 * updates the internal pointers appropriately.  Returns # of errors
 * encountered. */
int codb_classconf_bindtypes(codb_classconf *cc);

codb_ret codb_classconf_validate(codb_classconf *cc,
	const char *classname, const char *spacename,
	GHashWrap *attribs, GHashWrap *errs);

/* initialize all the classconf stuff */
codb_classconf *codb_classconf_init(const char *);

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

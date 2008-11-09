/* $Id: props.h 229 2003-07-18 20:22:20Z will $ */
/* Copyright (c) 2001-2002 Sun Microsystems Inc.  All rights reserved. */

/*
 * This is a simple way of using and representing CCE properties throughout
 * CCE.  'property_t' is a transparent struct that describes a tuple of 
 * (namespace, property).  'props_t' is an opaque hash of 
 * {'property_t *' => 'char *'}.  Some helper functions are defined to allow
 * you to get/set/unset values, with the keys as strings.  All the memory
 * management of props_* functions is internal - you don't need to worry 
 * about them holding on to pointers you pass in, the data will be
 * duplicated.  The property_[to/from]_str functions return memory which the
 * caller must free.
 */

#ifndef PROPS_H__
#define PROPS_H__

struct props_t;
typedef struct props_t props_t;
typedef struct {
	char *namespace;
	char *property;
} property_t;

/*
 * make a new props_t
 * 
 * returns a pointer on success
 * returns NULL on failure
 */
props_t *props_new(void);

/*
 * clean up a props_t
 *
 * always succeeds
 */
void props_destroy(props_t *props);

/*
 * lookup a property
 *
 * returns a pointer on success (do not free())
 * returns NULL on failure
 */
char *props_get(props_t *props, const property_t *key);
char *props_get_str(props_t *props, const char *key);

/*
 * set a property
 *
 * returns 0 on success
 * returns -EINVAL if props or key were NULL
 */
int props_set(props_t *props, property_t *key, char *value);
int props_set_str(props_t *props, const char *key, char *value);

/*
 * unset/remove a property
 *
 * returns 0 on success
 * returns -EINVAL if props or key were NULL
 */
int props_unset(props_t *props, property_t *key);
int props_unset_str(props_t *props, char *key);

/*
 * re-init a props_t
 *
 * returns 0 on success
 * returns -EINVAL if props was NULL
 * returns -ENOMEM on memory allocation failure
 */
int props_renew(props_t *props);

/*
 * get the key/value at a specific index
 *
 * returns 0 on success
 * returns -EINVAL if props was NULL or idx was out-of-bounds
 */
int props_index(props_t *props, int idx, property_t **key, char **value);

/*
 * duplicate a props_t
 *
 * returns a pointer on success
 * returns NULL on failure
 */
props_t *props_clone(props_t *props);

/*
 * merge two props_t into one new one
 *
 * returns a pointer on success
 * returns NULL on failure
 */
props_t *props_merge(props_t *base, props_t *mask);

/*
 * count the number of properties
 */
int props_count(props_t *props);


/*
 * Some helpers for property_t data.  property_[to,from]_str() return memory
 * which must be free()d by the caller.
 */
property_t *property_from_str(const char *str);
char *property_to_str(const property_t *prop);
void property_destroy(property_t *prop);

#endif /* PROPS_H__ */
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

/* $Id: test1.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * test code.  ugly.  but that's ok.
 */

#include <cce_common.h>
#include <odb_impl.h>

static int errors = 0;
static int tests = 0;

static char * __codb_ret_str[256];

void
codb_ret_str_init ()
{
	int i;
	for (i = 0; i < 256; i++) { __codb_ret_str[i] = "???"; }
	__codb_ret_str[0] = "CODB_RET_SUCCESS";
	__codb_ret_str[1] = "CODB_RET_BAD_HANDLE";
	__codb_ret_str[2] = "CODB_RET_ON_FIRE";
	__codb_ret_str[3] = "CODB_RET_UNKOBJ";
	__codb_ret_str[4] = "CODB_RET_UNKPROP";
	__codb_ret_str[5] = "CODB_RET_UNKCLASS";
	__codb_ret_str[6] = "CODB_RET_BADVALUE";
	__codb_ret_str[7] = "CODB_RET_READONLY";
	__codb_ret_str[8] = "CODB_RET_UNKLIST";
	__codb_ret_str[9] = "CODB_RET_UNKREF";
	__codb_ret_str[10] = "CODB_RET_ALREADY";
	__codb_ret_str[254] = "CODB_RET_NOMEM";
	__codb_ret_str[255] = "CODB_RET_OTHER";
};

const char *
codb_ret_str(codb_ret ret)
{
	return __codb_ret_str[0xFF & (-1 * ret)];
}

void
dump_codb_ret(codb_ret ret)
{
	printf ("  returned %d: %s\n", 
		ret, 
		codb_ret_str(ret)
	);
}

int
dump_classes(odb_impl_handle *imp)
{
	char **classlist, **pp;
	int i = 0;

	return 0;

	printf ("\tDumping classes:\n");
	
	impl_classlist(imp, &classlist);
	
	pp = classlist;
	while (*pp != NULL) {
		i++;
		printf ("\t  %d:\t%s\n", i, *pp);
		pp++;
	}
	
	impl_classlist_free(classlist);
	printf ("\t%d classes.\n", i);
	return i;
}

#define TRY(ss, exp) \
	{ tests++; printf ("test%03d: " #ss "\t -> ", tests); \
		ret = ss; \
		if (ret != exp) { dump_codb_ret(ret); errors++; } \
		else { printf ("%d OK\n", ret); } \
	}

#define TRYSTR(ss, exp, str) \
	{ tests++; printf ("test%03d: " #ss "\t -> ", tests); \
		ret = ss; \
		if (ret != exp) { dump_codb_ret(ret); errors++; } \
		else { printf ("%d OK\n", ret); } \
		if (strcmp(*cpp, str) != 0) { printf ("ERROR: "); errors++;} \
			printf ("\t\t*cpp = %s\n", *cpp); \
	}

int main()
{
	odb_typedef_ent td1, td2, td3, *tdp;
	char *cp;
	char **cpp;
	codb_ret ret;
	odb_impl_handle * imp;
	cce_scalar *sc1, *sc2, *sc3 ;
	odb_oid oid1, oid2, oid3;

	cp = NULL;
	cpp = &cp;
	system("/bin/rm -rf codb");
	
	codb_ret_str_init();
	
	imp = impl_handle_new();

	printf("\n************************** class store/retr ************\n");
	
	TRY(impl_create_class(imp, "CLASS1", "Why is this here?"), 0);
	TRY(impl_create_class(imp, "CLASS2", "CLASS1"), 0);
	dump_classes(imp);

	TRY(impl_destroy_class(imp, "CLASS1"), 0)
	dump_classes(imp);

	TRY(impl_class_exists(imp, "CLASS1"), 0);
	TRY(impl_class_exists(imp, "CLASS2"), 1);

	printf("\n*********************** prop type store/retr ************\n");
	
	TRY(impl_write_prop_type(imp, "CLASS2", "propa", "scalar"), 0);
	TRY(impl_write_prop_type(imp, "classX", "propb", "int"), CODB_RET_UNKCLASS);
	TRY(impl_test_prop_type_defined(imp,"CLASS2","propb"), 0);
	TRY(impl_test_prop_type_defined(imp,"CLASS2","propa"), 1);
	TRYSTR(impl_read_prop_type(imp, "CLASS2", "propa", cpp), 0, "scalar");
	free(*cpp);
	TRY(impl_read_prop_type(imp, "CLASS2", "propb", cpp), CODB_RET_UNKPROP);
	TRY(impl_read_prop_type(imp, "CLASS3", "propa", cpp), CODB_RET_UNKCLASS);
	
	printf("\n******************** prop default store/retr ************\n");

	sc1 = cce_scalar_new_from_str("defaultc");
	sc2 = cce_scalar_new_undef();
	sc3 = cce_scalar_new_undef();
	
	TRY(impl_write_classprop(imp, "CLASS2", "propc", sc1), 0);
	TRY(impl_read_classprop(imp, "CLASS2", "propc", sc2), 0);
	if (strcmp(sc2->data, sc1->data) != 0) {
		printf ("Wrong data: %s\n", (char*)sc2->data);
		errors++;
	}
	TRY(impl_read_classprop(imp, "CLASS2", "propd", sc3), CODB_RET_SUCCESS);
	if (cce_scalar_isdefined(sc3)) {
		printf ("CLASS2.propd was defined, shouldn't have been.\n");
		errors++;
	}
	
	TRY(impl_read_classprop(imp, "CLASS2", "_CLASS", sc3), 0);
	if (!cce_scalar_isdefined(sc3) || 
		(strcmp((char*)sc3->data, "CLASS1") != 0))
	{
		printf ("CLASS2._CLASS returned %s\n", (char*)sc3->data);
		errors++;
	}
	
	/* test isdefined */
	TRY(impl_classprop_isdefined(imp, "CLASS2", "propc"), 1);
	TRY(impl_classprop_isdefined(imp, "CLASS2", "propd"), 0);
	TRY(impl_classprop_isdefined(imp, "CLASS2", "_CLASS"), 1);
	
	/* create propd */
	TRY(impl_write_classprop(imp, "CLASS2", "propd", sc1), 0);
	/* test undefinine a property */
	cce_scalar_undefine(sc3);
	TRY(impl_write_classprop(imp, "CLASS2", "propc", sc3), 0);
	
	/* test isdefined */
	TRY(impl_classprop_isdefined(imp, "CLASS2", "propc"), 0);
	TRY(impl_classprop_isdefined(imp, "CLASS2", "propd"), 1);

	printf("\n******************** instance store/retr ************\n");
	oid1.oid = 1;
	oid2.oid = 2;
	oid3.oid = 3;
	TRY(impl_create_obj(imp, &oid1, "CLASS2"), 0);
	TRY(impl_create_obj(imp, &oid1, "CLASS2"), CODB_RET_ALREADY);
	TRY(impl_create_obj(imp, &oid2, "CLASSZ"), CODB_RET_UNKCLASS);
	TRY(impl_create_obj(imp, &oid2, "CLASS2"), 0);
	TRY(impl_obj_exists(imp, &oid2), 1);
	TRY(impl_obj_exists(imp, &oid3), 0);
	TRY(impl_create_obj(imp, &oid3, "CLASS2"), 0);
	TRY(impl_obj_exists(imp, &oid3), 1);
	TRY(impl_destroy_obj(imp, &oid2), 0);
	TRY(impl_obj_exists(imp, &oid2), 0);
	TRY(impl_destroy_obj(imp, &oid2), CODB_RET_ALREADY);
	TRY(impl_obj_exists(imp, &oid2), 0);

	printf("\n*************** instance data store/retr ************\n");
	TRY(impl_read_objprop(imp, &oid3, "bogus", sc1), 0);
	if (cce_scalar_isdefined(sc1)) {
		printf("bogus should have been undefined, got %s\n", (char*)sc1->data);
		errors++;
	}
	TRY(impl_read_objprop(imp, &oid3, "propd", sc1), 0);
	if (cce_scalar_isdefined(sc1)) {
		printf("propd should have been undefined, got %s\n", (char*)sc1->data);
		errors++;
	}
	cce_scalar_delete(sc1);
	sc1 = cce_scalar_new_from_str("scalar one");
	cce_scalar_delete(sc2);
	sc2 = cce_scalar_new_from_str("scalar two");
	/* write some properties */
	TRY(impl_write_objprop(imp, &oid1, "propa", sc1), 0);
	TRY(impl_write_objprop(imp, &oid2, "propa", sc1), CODB_RET_UNKOBJ);
	TRY(impl_write_objprop(imp, &oid3, "propa", sc1), 0);
	TRY(impl_write_objprop(imp, &oid3, "propc", sc2), 0);
	/* check defined */
	TRY(impl_objprop_isdefined(imp, &oid3, "propc"), 1);
	TRY(impl_objprop_isdefined(imp, &oid3, "propz"), 0);
	TRY(impl_objprop_isdefined(imp, &oid2, "propc"), 0);
	/* read back 1.propa */
	TRY(impl_read_objprop(imp, &oid1, "propa", sc3), 0);
	if (!cce_scalar_isdefined(sc3) 
		|| (strcmp((char*)sc3->data, (char*)sc1->data) !=0 ))
	{
		printf("%s(%d): Didn't read back same value I wrote\n", 
			__FILE__ , __LINE__ );
		errors++;
	}
	/* read back 3.propa */
	cce_scalar_undefine(sc3);
	TRY(impl_read_objprop(imp, &oid3, "propa", sc3), 0);
	if (!cce_scalar_isdefined(sc3) 
		|| (strcmp((char*)sc3->data, (char*)sc1->data) !=0 ))
	{
		printf("%s(%d): Didn't read back same value I wrote\n", 
			__FILE__ , __LINE__ );
		errors++;
	}
	/* read back 3.propc */
	cce_scalar_undefine(sc3);
	TRY(impl_read_objprop(imp, &oid3, "propc", sc3), 0);
	if (!cce_scalar_isdefined(sc3) 
		|| (strcmp((char*)sc3->data, (char*)sc2->data) !=0 ))
	{
		printf("%s(%d): Didn't read back same value I wrote\n", 
			__FILE__ , __LINE__ );
		errors++;
	}

/* typedef stuff isn't needed just now, forget about it. */
#if 0
	printf("\n******************** typedef  store/retr ************\n");
	td1.type_name = "typeA";
	td1.type_str = "AAaaaaaa";
	TRY(impl_test_typedef_defined(imp, "typeA"), 0);
	TRY(impl_rm_typedef(imp, &td1), 0);
	TRY(impl_test_typedef_defined(imp, "typeA"), 0);
	TRY(impl_add_typedef(imp, &td1), 0);
	TRY(impl_test_typedef_defined(imp, "typeA"), 1);
	TRY(impl_test_typedef_defined(imp, "typeB"), 0);
	TRY(impl_rm_typedef(imp, &td1), 0);
	TRY(impl_test_typedef_defined(imp, "typeA"), 0);
#endif

	cce_scalar_delete(sc3);
	cce_scalar_delete(sc2);
	cce_scalar_delete(sc1);

	impl_handle_destroy(imp);

	printf ("\n%d error(s) in %d tests.\n", errors, tests);
	return errors;
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

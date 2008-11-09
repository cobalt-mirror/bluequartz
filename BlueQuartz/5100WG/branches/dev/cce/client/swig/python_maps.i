/* Tell swig how to handle returning GList[ char * ] */
%typemap(python, out) GSListStrs * {
	GSList *list;
	list = $source;
	$target = PyList_New(0);
	while( list ) {
		PyList_Append($target, PyString_FromString( (char *) list->data ));
		list = g_slist_next(list);
	}
}

/* Tell swig how to handle returning GList[ cscp_oid_t ] */
%typemap(python, out) GSListOids * {
	GSList *list;
	list = $source;
	$target = PyList_New(0);
	while( list ) {
		PyList_Append($target, PyInt_FromLong( (unsigned long) list->data ));
		list = g_slist_next(list);
	}
}

/* Tell swig how to handle returning GList *errors */

/* Tell swig how to handle a cce_props_t arg  */
%typemap(python,out) cce_props_t * {
	char *key;
	char *val;

	$target = PyDict_New();

	if( $source ) {
		cce_props_reinit($source);
		
		while( key = cce_props_nextkey($source) ) {
			PyDict_SetItemString( $target, key, PyString_FromString(val) );
		}
	}
}

%typemap(python,in) cce_props_t * {
	int i;
	int num_keys;
	int num_vals;

	PyObject *key_list;
	PyObject *val_list;

	$target = cce_props_new();

	if( ! PyDict_Check( $source ) ) {
		PyErr_SetString(PyExc_TypeError,"Argument must be a dictionary");
	} else {
		key_list = PyDict_Keys( $source );
		val_list = PyDict_Items( $source );

		num_keys = PyList_Size(key_list);
		num_vals = PyList_Size(val_list);

		if( num_keys != num_vals ) {
			PyErr_SetString(PyExc_TypeError,"More values that key!?");
		}

		for ( i = 0;
			  i < num_keys;
			  i++ ) {
			PyObject *key;
			PyObject *value;

			char *key_str;
			char *val_str;
	
			key = PyList_GetItem( key_list, i );
			value = PyList_GetItem( val_list, i);
		
			if( ! PyString_Check(key) ) {
				PyErr_SetString(PyExc_TypeError,"Dictionary key must be a string");
				continue;
			}
			if( ! PyString_Check(value) ) {
				PyErr_SetString(PyExc_TypeError,"Dictionary value must be a string or integer");
				continue;
			}
			/* Set a Break by raising a signal */
			key_str = PyString_AsString(key);
			val_str = PyString_AsString(value);
			cce_props_set($target,key_str,val_str);
			raise(SIGSEGV);
		}
	}
}

/* Clean up the cce_props now that we no longer need it */
%typemap(python,ret) cce_props_t * {
	cce_props_destroy($source);
}

/* Tell swig how to handle returning a cce_props_t */

/* Tell swig which functions return what type of GSList */


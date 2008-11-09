/* A note on return values.
 * Perl is weird, you do not return a value, you fill a global stack with
 * values and return how many values you put on the stack.
 */

/* Tell swig how to handle returning GList[ char * ] */
%typemap(perl5, out) GSListStrs * {
	GSList *list = $source;

	/* Create a new array */
	while( list ) {
		ST(argvi) = sv_newmortal();
		sv_setpv( ST(argvi), (char *) list->data);
		list = g_slist_next(list);
		argvi++;
	}
}

/* Tell swig how to handle returning GList[ cscp_oid_t ] */
%typemap(perl5, out) GSListOids * {
	GSList *list = $source;

	while( list ) {
		ST(argvi) = sv_newmortal();
		sv_setiv( ST(argvi), (unsigned long) list->data );
		list = g_slist_next(list);
		argvi++;
	}
}

/* How to handle returning cce_props_t * */

%typemap(perl5,out) cce_props_t * {
	char *key;
	char *val;
	U32  hash_val;
	HV	 *vals;
	HV   *old_vals;

	vals = newHV();
	old_vals = newHV();

	if( $source ) {
		cce_props_reinit($source);
		
		while( key = cce_props_nextkey($source) ) {
			/* First zero is to tell perl to work out the length of the
			 * key itself, second is to tell it to work out the hash of the
			 * value itself
			 */
			hv_store(vals, key, strlen(key), newSVpv( cce_props_get( $source, key ), 0 ), hash_val);
			if ( ( val = cce_props_get_old($source, key) ) ) {
				hv_store(old_vals, key, strlen(key),
					newSVpv( val, 0 ), 0);
			}
		}
	}

	hv_store(vals,"OLD", strlen("OLD"), newRV_noinc( (SV *) old_vals ), 0);

	ST(argvi++) = newRV_noinc( (SV *) vals );
}

/* Tell swig how to handle a function reutrning cce_props_t
 *
 * Passing in is made easy as we only have to set the hash and don't have
 * to worry about any of the refrence values.
 */

%typemap(perl5,in) cce_props_t * {
	char *key;
	char *val;
	SV   *sv_val;
	HV   *vals;
	I32  retlen;

	$target = cce_props_new();

	if( ! SvROK($source) ) {
		croak("cce_props_t type arguments must be a refrence.");
	} else if ( SvTYPE(SvRV($source)) != SVt_PVHV ) {
		croak("cce_props_t type arguments must be a refrence to a hash.");
	} else {
		vals = (HV *) SvRV( $source );
		while( ( sv_val = hv_iternextsv(vals, &key, &retlen) ) ) {
			if( SvROK( sv_val ) ) {
				croak("cce_props_t must only have scalars in the values of the hash");
			}
			val = SvPV( sv_val, na );
			cce_props_set($target, key, val);
		}
	}
}

/* Tell swig how to handle a GSList of cce_error_t
 *
 * As swig makes a perl class for cce_error_t we jsut bless the refrences
 * into that class
 */
%typemap( perl5, out ) GSListErrors * {
	GSList *errors = $source;

	while( errors ) {
		ST(argvi) = sv_newmortal();
		sv_setref_pv(ST(argvi),"cce_error_t", errors->data);
		argvi++;
		errors = g_slist_next(errors);
	}
}
		

/* Clean up the cce_props now that we no longer need it */
%typemap(perl5,ret) cce_props_t * {
	cce_props_destroy($source);
}

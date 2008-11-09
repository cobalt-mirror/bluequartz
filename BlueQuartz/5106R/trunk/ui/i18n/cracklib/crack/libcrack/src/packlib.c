/*
 * This program is copyright Alec Muffett 1993. The author disclaims all 
 * responsibility or liability with respect to it's usage or its effect 
 * upon hardware or computer systems, and maintains copyright as set out 
 * in the "LICENCE" document which accompanies distributions of Crack v4.0 
 * and upwards.
 */

/*
 * Updated by Sascha Kettler <skettler@php.net> to not leak any
 * unwanted symbols and add extra functionality.
 * 
 * Also beautified code and optimized for the PHP extension.
 */
 
#include "cracklib.h"

static int16
cracklib_swap_int16(int16 input)
{
	union {
		int16 int_value;
		unsigned char byte_value[2];
	} in, out;
	
	in.int_value = input;
	out.byte_value[0] = in.byte_value[1];
	out.byte_value[1] = in.byte_value[0];
	
	return out.int_value;
}

static int32
cracklib_swap_int32(int32 input)
{
	union {
		int32 int_value;
		unsigned char byte_value[4];
	} in, out;
	
	in.int_value = input;
	out.byte_value[0] = in.byte_value[3];
	out.byte_value[1] = in.byte_value[2];
	out.byte_value[2] = in.byte_value[1];
	out.byte_value[3] = in.byte_value[0];
	
	return out.int_value;
}

CRACKLIB_PWDICT *
cracklib_pw_open(char *prefix, char *mode)
{
	int32 i;
	CRACKLIB_PWDICT *pdesc;
	char *name;
    int name_len;
	FILE *dfp;
	FILE *ifp;
	FILE *wfp;
	
	pdesc = (CRACKLIB_PWDICT *) MALLOC(sizeof(CRACKLIB_PWDICT));
	if (!pdesc) {
		/* unable to allocate memory */
		return (CRACKLIB_PWDICT *) 0;
	}
	
	memset(pdesc, '\0', sizeof(pdesc));
	pdesc->prevblock = 0xffffffffU;
	
    /* Allocate memory dynamically to thwart buffer overflows */
    name_len = strlen(prefix) + 10;
    name = (char *) MALLOC(name_len);
    if (!name) {
		/* unable to allocate memory */
        FREE(pdesc);
		return (CRACKLIB_PWDICT *) 0;
    }
	
    memset(name, '\0', name_len);
    strcpy(name, prefix);
    strcat(name, ".pwd");
	if (!(pdesc->dfp = fopen(name, mode))) {
		perror(name);
        FREE(name);
		FREE(pdesc);
		
		return (CRACKLIB_PWDICT *) 0;
	}
	
    memset(name, '\0', name_len);
    strcpy(name, prefix);
    strcat(name, ".pwi");
	if (!(pdesc->ifp = fopen(name, mode))) {
		fclose(pdesc->dfp);
		perror(name);
        FREE(name);
		FREE(pdesc);
		
		return (CRACKLIB_PWDICT *) 0;
	}
	
    memset(name, '\0', name_len);
    strcpy(name, prefix);
    strcat(name, ".hwm");
	pdesc->wfp = fopen(name, mode);
	if (pdesc->wfp) {
		pdesc->flags |= PFOR_USEHWMS;
	}
    FREE(name);
	
	ifp = pdesc->ifp;
	dfp = pdesc->dfp;
	wfp = pdesc->wfp;
	
	if (mode[0] == 'w') {
		pdesc->flags |= PFOR_WRITE;
		pdesc->header.pih_magic = PIH_MAGIC;
		pdesc->header.pih_blocklen = NUMWORDS;
		pdesc->header.pih_numwords = 0;
		
		fwrite((char *) &pdesc->header, sizeof(pdesc->header), 1, ifp);
	}
	else {
		pdesc->flags &= ~PFOR_WRITE;
		
		if (!fread((char *) &pdesc->header, sizeof(pdesc->header), 1, ifp)) {
			fprintf(stderr, "%s: error reading header\n", prefix);
			
			fclose(ifp);
			fclose(dfp);
			FREE(pdesc);
			
			return (CRACKLIB_PWDICT *) 0;
		}
		
		if (pdesc->header.pih_magic == PIH_MAGIC) {
			pdesc->needs_swap = 0;
		}
		else if (pdesc->header.pih_magic == cracklib_swap_int32(PIH_MAGIC)) {
			pdesc->needs_swap = 1;
			
			pdesc->header.pih_magic    = cracklib_swap_int32(pdesc->header.pih_magic);
			pdesc->header.pih_numwords = cracklib_swap_int32(pdesc->header.pih_numwords);
			pdesc->header.pih_blocklen = cracklib_swap_int16(pdesc->header.pih_blocklen);
			pdesc->header.pih_pad      = cracklib_swap_int16(pdesc->header.pih_pad);
		}
		else {
			fprintf(stderr, "%s: magic mismatch\n", prefix);
			
			fclose(ifp);
			fclose(dfp);
			FREE(pdesc);
			
			return (CRACKLIB_PWDICT *) 0;
		}
		
		if (pdesc->header.pih_blocklen != NUMWORDS) {
			fprintf(stderr, "%s: size mismatch\n", prefix);
		
			fclose(ifp);
			fclose(dfp);
			FREE(pdesc);
			
			return (CRACKLIB_PWDICT *) 0;
		}
		
		if (pdesc->flags & PFOR_USEHWMS) {
			if (fread(pdesc->hwms, 1, sizeof(pdesc->hwms), wfp) != sizeof(pdesc->hwms)) {
				pdesc->flags &= ~PFOR_USEHWMS;
			}
			else if (pdesc->needs_swap) {
				for (i=0; i<256; i++) {
					pdesc->hwms[i] = cracklib_swap_int32(pdesc->hwms[i]);
				}
			}
		}
	}
	
	return pdesc;
}

int
cracklib_pw_close(CRACKLIB_PWDICT *pwp)
{
	if (pwp->header.pih_magic != PIH_MAGIC) {
		fprintf(stderr, "PWClose: close magic mismatch\n");
		return -1;
	}
	
	if (pwp->flags & PFOR_WRITE) {
		pwp->flags |= PFOR_FLUSH;
		cracklib_put_pw(pwp, (char *) 0);	/* flush last index if necess */
		
		if (fseek(pwp->ifp, 0L, SEEK_SET)) {
			fprintf(stderr, "index magic fseek failed\n");
			return -1;
		}
		
		if (!fwrite((char *) &pwp->header, sizeof(pwp->header), 1, pwp->ifp)) {
			fprintf(stderr, "index magic fwrite failed\n");
			return -1;
		}
		
		if (pwp->flags & PFOR_USEHWMS) {
			int i;
			for (i=1; i<=0xff; i++) {
				if (!pwp->hwms[i]) {
					pwp->hwms[i] = pwp->hwms[i-1];
				}
			}
			fwrite(pwp->hwms, 1, sizeof(pwp->hwms), pwp->wfp);
		}
	}
	
	fclose(pwp->ifp);
	fclose(pwp->dfp);
	if (pwp->flags & PFOR_USEHWMS) {
		fclose(pwp->wfp);
	}
	
	FREE(pwp);
	
	return 0;
}

int
cracklib_put_pw(CRACKLIB_PWDICT *pwp, char *string)
{
	if (!(pwp->flags & PFOR_WRITE)) {
		return -1;
	}
	
	if (string) {
		strncpy(pwp->data[pwp->count], string, MAXWORDLEN);
		pwp->data[pwp->count][MAXWORDLEN - 1] = '\0';
		
		pwp->hwms[string[0] & 0xff]= pwp->header.pih_numwords;
		
		++(pwp->count);
		++(pwp->header.pih_numwords);
	}
	else if (!(pwp->flags & PFOR_FLUSH)) {
		return -1;
	}
	
	if ((pwp->flags & PFOR_FLUSH) || !(pwp->count % NUMWORDS)) {
		int i;
		int32 datum;
		char *ostr;
		
		datum = (int32) ftell(pwp->dfp);
		
		fwrite((char *) &datum, sizeof(datum), 1, pwp->ifp);
		
		fputs(pwp->data[0], pwp->dfp);
		putc(0, pwp->dfp);
		
		ostr = pwp->data[0];
		
		for (i = 1; i < NUMWORDS; i++) {
			int j;
			char *nstr;
			nstr = pwp->data[i];
			
			if (nstr[0]) {
				for (j = 0; ostr[j] && nstr[j] && (ostr[j] == nstr[j]); j++) {
				}
				
				putc(j & 0xff, pwp->dfp);
				fputs(nstr + j, pwp->dfp);
			}
			putc(0, pwp->dfp);
		
			ostr = nstr;
		}
		
		memset(pwp->data, '\0', sizeof(pwp->data));
		pwp->count = 0;
	}
	
	return 0;
}

char *
cracklib_get_pw(CRACKLIB_PWDICT *pwp, int32 number)
{
	int32 datum;
	int i;
	char *ostr;
	char *nstr;
	char *bptr;
	char buffer[NUMWORDS * MAXWORDLEN];
	int32 thisblock;
	
	thisblock = number / NUMWORDS;
	
	if (pwp->prevblock == thisblock) {
		return (pwp->prevdata[number % NUMWORDS]);
	}
	
	if (fseek(pwp->ifp, sizeof(struct cracklib_pi_header) + (thisblock * sizeof(int32)), SEEK_SET)) {
		perror("(index fseek failed)");
		return (char *) 0;
	}
	
	if (!fread((char *) &datum, sizeof(datum), 1, pwp->ifp)) {
		perror("(index fread failed)");
		return (char *) 0;
	}
	
	if (fseek(pwp->dfp, datum, SEEK_SET)) {
		perror("(data fseek failed)");
		return (char *) 0;
	}
	
	if (!fread(buffer, 1, sizeof(buffer), pwp->dfp)) {
		perror("(data fread failed)");
		return (char *) 0;
	}
	
	pwp->prevblock = thisblock;
	
	bptr = buffer;
	
	ostr = pwp->prevdata[0];
	while (0 != (*(ostr++) = *(bptr++))) {
	}
	
	ostr = pwp->prevdata[0];
	for (i=1; i<NUMWORDS; i++) {
		nstr = pwp->prevdata[i];
		strcpy(nstr, ostr);
		
		ostr = nstr + *(bptr++);
		while (0 != (*(ostr++) = *(bptr++))) {
		}
		
		ostr = nstr;
	}
	
	return pwp->prevdata[number % NUMWORDS];
}

int32
cracklib_find_pw(CRACKLIB_PWDICT *pwp, char *string)
{
	int32 lwm;
	int32 hwm;
	int32 middle;
	char *current;
	int idx;
	
	if (pwp->flags & PFOR_USEHWMS) {
		idx = string[0] & 0xff;
		lwm = idx ? pwp->hwms[idx - 1] : 0;
		hwm = pwp->hwms[idx];
	}
	else {
		lwm = 0;
		hwm = PW_WORDS(pwp) - 1;
	}
	
	for (;;) {
		int cmp;
		
		middle = lwm + ((hwm - lwm + 1) / 2);
		
		if (middle == hwm) {
			break;
		}
		
		current = cracklib_get_pw(pwp, middle);
		if (!current) {
			/* internal error, don't crash because of this null pointer */
			return PW_WORDS(pwp);
		}
		
		cmp = strcmp(string, current);
		
		if (cmp < 0) {
			hwm = middle;
		}
		else if (cmp > 0) {
			lwm = middle;
		}
		else {
			return middle;
		}
	}
	
	return PW_WORDS(pwp);
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 fdm=marker
 * vim<600: sw=4 ts=4
 */

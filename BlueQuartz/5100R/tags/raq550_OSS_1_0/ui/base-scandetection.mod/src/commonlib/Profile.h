#ifndef _PROFILE_H_
#define _PROFILE_H_

#include "serial.h"

typedef struct Profile * Profile_ptr;

/*
 *  public:
 *
 *  This is the public interface.
 */

Profile_ptr  getProfile             ();
int          profileIsAppliance     (Profile_ptr obj);
int          profileReadObject      (Profile_ptr obj, SerialObject *);
int          profileRestoreObject   (Profile_ptr obj, int fd);
int          profileStoreObject     (Profile_ptr obj, int fd);
int          profileWriteObject     (Profile_ptr obj, SerialObject *);

/*  Get routines  */

const char *      profileGetCompanyDomainName     (Profile_ptr obj);
const char *      profileGetCompanyName           (Profile_ptr obj);
const char *      profileGetCompanyNameLcd1       (Profile_ptr obj);
const char *      profileGetCompanyNameLcd2       (Profile_ptr obj);
const char *      profileGetHostDefault           (Profile_ptr obj);
const char *      profileGetProductName           (Profile_ptr obj);
const char *      profileGetProductNameLcd1       (Profile_ptr obj);
const char *      profileGetProductNameLcd2       (Profile_ptr obj);
const char *      profileGetProductNameShort      (Profile_ptr obj);
const char *      profileGetProductLicenseName    (Profile_ptr obj);
const char *      profileGetProductVersion        (Profile_ptr obj);
const char *      profileGetSupportEmail          (Profile_ptr obj);
const char *      profileGetSupportPhone          (Profile_ptr obj);
const char *      profileGetWebsite               (Profile_ptr obj);

/*  Set routines  */

void        profileSetCompanyDomainName     (Profile_ptr obj, const char *);
void        profileSetCompanyName           (Profile_ptr obj, const char *);
void        profileSetCompanyNameLcd1       (Profile_ptr obj, const char *);
void        profileSetCompanyNameLcd2       (Profile_ptr obj, const char *);
void        profileSetHostDefault           (Profile_ptr obj, const char *);
void        profileSetProductName           (Profile_ptr obj, const char *);
void        profileSetProductNameLcd1       (Profile_ptr obj, const char *);
void        profileSetProductNameLcd2       (Profile_ptr obj, const char *);
void        profileSetProductNameShort      (Profile_ptr obj, const char *);
void        profileSetProductLicenseName    (Profile_ptr obj, const char *);
void        profileSetProductVersion        (Profile_ptr obj, const char *);
void        profileSetSupportEmail          (Profile_ptr obj, const char *);
void        profileSetSupportPhone          (Profile_ptr obj, const char *);
void        profileSetWebsite               (Profile_ptr obj, const char *);

#endif /* #ifndef _PROFILE_H_ */
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

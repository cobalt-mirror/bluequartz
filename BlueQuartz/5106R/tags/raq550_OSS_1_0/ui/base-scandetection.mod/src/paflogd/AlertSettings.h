/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/paflogd/AlertSettings.h,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  06-Nov-2000
    Originating Author :  Brian Adkins

      Last Modified by :  $Author: jthrowe $ 
    Date Last Modified :  $Date: 2001/09/18 16:59:37 $

   **********************************************************************

   Copyright (c) 2000 Progressive Systems Inc.
   All rights reserved.

   This code is confidential property of Progressive Systems Inc.  The
   algorithms, methods and software used herein may not be duplicated or
   disclosed to any party without the express written consent from
   Progressive Systems Inc.

   Progressive Systems Inc. makes no representations concerning either
   the merchantability of this software or the suitability of this
   software for any particular purpose.

   These notices must be retained in any copies of any part of this
   documentation and/or software.

   ********************************************************************** */

#ifndef _ALERTSETTINGS_H_
#define _ALERTSETTINGS_H_

#include <string>

#include "Externalizable.h"
#include "ObjectInput.h"
#include "ObjectOutput.h"

using namespace std;

class AlertSettings : public Externalizable
{
 private:
    static const int serialVersionId = 1;

    string   emailAddress;
    bool     frontPanelAccess;
    bool     lowDiskSpace;
    int      lowDiskSpaceValue;
    bool     restrictedSiteAccess;
    bool     scanAttack;
    string   smtpServer;
    bool     systemReboot;

    void init ();
    void init (const AlertSettings&);

 public:
    AlertSettings ();
    AlertSettings (const AlertSettings&);
    AlertSettings& operator=(const AlertSettings&);
    ~AlertSettings () {}

    string getEmailAddress ()         const { return emailAddress;          }
    bool   getFrontPanelAccess ()     const { return frontPanelAccess;      }
    bool   getLowDiskSpace ()         const { return lowDiskSpace;          }
    int    getLowDiskSpaceValue ()    const { return lowDiskSpaceValue;     }
    bool   getRestrictedSiteAccess () const { return restrictedSiteAccess;  }
    bool   getScanAttack ()           const { return scanAttack;            }
    string getSmtpServer ()           const { return smtpServer;            }
    bool   getSystemReboot ()         const { return systemReboot;          }

    void setEmailAddress      (const string& str) { emailAddress = str;    }
    void setLowDiskSpace      (bool b)            { lowDiskSpace = b;      }
    void setLowDiskSpaceValue (int i)             { lowDiskSpaceValue = i; }
    void setRestrictedSiteAccess (bool b) { restrictedSiteAccess = b; }
    void setScanAttack        (bool b)            { scanAttack = b;        }
    void setSmtpServer        (const string& str) { smtpServer = str;      }


    static Externalizable * createExternalizable () 
	{ return new AlertSettings(); }

    virtual void readExternal (ObjectInput&);
    virtual void writeExternal (ObjectOutput&) const;
};

#endif /* #ifndef _ALERTSETTINGS_H_ */
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

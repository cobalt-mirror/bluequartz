/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/paflogd/AlertSettings.cpp,v $
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

#include "AlertSettings.h"
#include "Exception.h"

AlertSettings::AlertSettings ()
{
    init ();
}

AlertSettings::AlertSettings (const AlertSettings& obj)
{
    init (obj);
}

AlertSettings& AlertSettings::operator=(const AlertSettings& obj)
{
    if (this != &obj)
    {
	init (obj);
    }

    return *this;
}

void AlertSettings::init ()
{
    frontPanelAccess     = false;
    lowDiskSpace         = false;
    lowDiskSpaceValue    = 0;
    restrictedSiteAccess = false;
    scanAttack           = false;
    systemReboot         = false;
}

void AlertSettings::init (const AlertSettings& obj)
{
    emailAddress         = obj.emailAddress;
    frontPanelAccess     = obj.frontPanelAccess;
    lowDiskSpace         = obj.lowDiskSpace;
    lowDiskSpaceValue    = obj.lowDiskSpaceValue;
    restrictedSiteAccess = obj.restrictedSiteAccess;
    scanAttack           = obj.scanAttack;
    smtpServer           = obj.smtpServer;
    systemReboot         = obj.systemReboot;
}

void AlertSettings::readExternal (ObjectInput& obj)
{
    int version = obj.readInt();

    if (version != serialVersionId)
    {
	throw IOException ("AlertSettings::readExternal(): invalid version");
    }

    emailAddress            = obj.readString ();
    frontPanelAccess        = obj.readBoolean ();
    lowDiskSpace            = obj.readBoolean ();
    lowDiskSpaceValue       = obj.readInt ();
    restrictedSiteAccess    = obj.readBoolean ();
    scanAttack              = obj.readBoolean ();
    smtpServer              = obj.readString ();
    systemReboot            = obj.readBoolean ();
}

void AlertSettings::writeExternal (ObjectOutput& obj) const
{
    obj.writeInt    (serialVersionId);

    obj.writeString   (emailAddress);
    obj.writeBoolean  (frontPanelAccess);
    obj.writeBoolean  (lowDiskSpace);
    obj.writeInt      (lowDiskSpaceValue);
    obj.writeBoolean  (restrictedSiteAccess);
    obj.writeBoolean  (scanAttack);
    obj.writeString   (smtpServer);
    obj.writeBoolean  (systemReboot);
}
	

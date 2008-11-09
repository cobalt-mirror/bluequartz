export PATH=$PATH:/usr/java/jdk1.3/bin
export JAVA_HOME=/usr/java/jdk1.3

# Try to raise the max number of processes
ulimit -u 2048 &> /dev/null

#
# FIXME: make sure the class path includes all preinstalled classes
#	 The corresponding rpms should really be fixed.
#
CLASSPATH=$CLASSPATH:/usr/java/jes/bundles/device.jar
CLASSPATH=$CLASSPATH:/usr/java/jes/bundles/driverlocator.jar
CLASSPATH=$CLASSPATH:/usr/java/jes/bundles/homeportal.jar
CLASSPATH=$CLASSPATH:/usr/java/jes/bundles/http.jar
CLASSPATH=$CLASSPATH:/usr/java/jes/bundles/httpauth.jar
CLASSPATH=$CLASSPATH:/usr/java/jes/bundles/httpusers.jar
CLASSPATH=$CLASSPATH:/usr/java/jes/bundles/jesmp.jar
CLASSPATH=$CLASSPATH:/usr/java/jes/bundles/log.jar
CLASSPATH=$CLASSPATH:/usr/java/jes/bundles/servlet.jar
CLASSPATH=$CLASSPATH:/usr/java/jes/bundles/ssl.jar
CLASSPATH=$CLASSPATH:/usr/java/jes/bundles/tcatjspcruntime.jar
CLASSPATH=$CLASSPATH:/usr/java/jes/bundles/timer.jar
CLASSPATH=$CLASSPATH:/usr/java/jes/forteplugin/java_embedded_server.jar
CLASSPATH=$CLASSPATH:/usr/java/jes/lib/framework.jar
CLASSPATH=$CLASSPATH:/usr/jxta/cms/cms.jar
CLASSPATH=$CLASSPATH:/usr/jxta/platform/jxta.jar
CLASSPATH=$CLASSPATH:/usr/jxta/security/jxtasecurity.jar
CLASSPATH=$CLASSPATH:/usr/share/pgsql/jdbc7.0-1.2.jar
CLASSPATH=$CLASSPATH:/usr/share/pgsql/jdbc7.0-1.1.jar
CLASSPATH=$CLASSPATH:/usr/java/jakarta_regexp1.2/bin/jakarta-regexp-1.2.jar
CLASSPATH=$CLASSPATH:/usr/java/jmx1.0/lib/jmxgrinder.jar
CLASSPATH=$CLASSPATH:/usr/java/jmx1.0/lib/jmxri.jar
CLASSPATH=$CLASSPATH:/usr/java/jmx1.0/lib/jmxtools.jar
CLASSPATH=$CLASSPATH:/usr/java/jndi1.2.1/lib/jndi.jar
CLASSPATH=$CLASSPATH:/usr/interclient/interclient-core.jar
CLASSPATH=$CLASSPATH:/usr/interclient/interclient-res.jar
CLASSPATH=$CLASSPATH:/usr/interclient/interclient-utils.jar
CLASSPATH=$CLASSPATH:/usr/interclient/interclient.jar
export CLASSPATH
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without 
# modification, are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, 
# this list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation  
# and/or other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.

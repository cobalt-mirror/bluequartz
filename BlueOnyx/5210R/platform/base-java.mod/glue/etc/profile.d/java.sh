export JAVA_HOME=/etc/alternatives
export PATH=$PATH:$JAVA_HOME

# Try to raise the max number of processes
ulimit -u 2048 &> /dev/null

#
# FIXME: make sure the class path includes all preinstalled classes
#	 The corresponding rpms should really be fixed.
#

CLASSPATH=$CLASSPATH:/var/lib/tomcat/common/endorsed
CLASSPATH=$CLASSPATH:/var/lib/tomcat/common/lib
CLASSPATH=$CLASSPATH:/usr/share/java/javamail
CLASSPATH=$CLASSPATH:/usr/share/java
CLASSPATH=$CLASSPATH:/usr/share/java/mx4j
export CLASSPATH


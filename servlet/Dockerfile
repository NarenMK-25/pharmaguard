# Use the official Tomcat image with Java 21
FROM tomcat:9.0-jdk21

# Delete the default Tomcat apps to keep it clean
RUN rm -rf /usr/local/tomcat/webapps/*

# Copy your compiled WAR file into the Tomcat webapps directory as ROOT
# (Make sure your pharmaguard.war is actually inside a 'target' folder, or adjust this path)
COPY target/pharmaguard.war /usr/local/tomcat/webapps/ROOT.war

EXPOSE 8080
package com.pharmaguard;

import java.sql.Connection;
import javax.naming.InitialContext;
import javax.sql.DataSource;

/** Gets pooled JDBC connections from Tomcat (jdbc/PharmaDB, defined in META-INF/context.xml). */
public final class Db {
    private static DataSource ds;
    private Db() {}

    public static synchronized Connection get() throws Exception {
        if (ds == null) {
            ds = (DataSource) new InitialContext().lookup("java:comp/env/jdbc/PharmaDB");
        }
        return ds.getConnection();
    }
}

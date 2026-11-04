package com.pharmaguard;

import java.io.IOException;
import java.sql.ResultSet;
import java.sql.ResultSetMetaData;
import java.sql.SQLException;
import javax.servlet.http.HttpServletResponse;

/** Tiny JSON helpers so the project needs no extra libraries. */
public final class Json {
    private Json() {}

    public static String esc(String s) {
        if (s == null) return "";
        StringBuilder b = new StringBuilder();
        for (char ch : s.toCharArray()) {
            if (ch == '"') b.append("\\\"");
            else if (ch == '\\') b.append("\\\\");
            else if (ch < 0x20) b.append(' ');
            else b.append(ch);
        }
        return b.toString();
    }

    /** Converts every remaining row of a ResultSet to a JSON array of objects. */
    public static String rows(ResultSet rs) throws SQLException {
        ResultSetMetaData md = rs.getMetaData();
        int n = md.getColumnCount();
        StringBuilder b = new StringBuilder("[");
        boolean first = true;
        while (rs.next()) {
            if (!first) b.append(',');
            first = false;
            b.append('{');
            for (int i = 1; i <= n; i++) {
                if (i > 1) b.append(',');
                b.append('"').append(md.getColumnLabel(i)).append("\":");
                Object v = rs.getObject(i);
                if (v == null) b.append("null");
                else if (v instanceof Number) b.append(v);
                else b.append('"').append(esc(v.toString())).append('"');
            }
            b.append('}');
        }
        return b.append(']').toString();
    }

    public static void send(HttpServletResponse r, int status, String body) throws IOException {
        r.setStatus(status);
        r.setContentType("application/json");
        r.setCharacterEncoding("UTF-8");
        r.getWriter().write(body);
    }

    public static void error(HttpServletResponse r, int status, String msg) throws IOException {
        send(r, status, "{\"error\":\"" + esc(msg) + "\"}");
    }
}

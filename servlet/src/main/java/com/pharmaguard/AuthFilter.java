package com.pharmaguard;

import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.util.Base64;
import javax.crypto.Mac;
import javax.crypto.spec.SecretKeySpec;
import javax.servlet.*;
import javax.servlet.http.*;

/**
 * Guards /api/*. PHP signs a cookie "pg_token" = base64url("uid|role|expiry").HMAC-SHA256.
 * This filter verifies the signature + expiry, then exposes uid/role to the servlets.
 * It also adds the CORS headers needed because the pages (Apache :80) call Tomcat (:8080).
 */
public class AuthFilter implements Filter {
    private String secret;
    private String origin;

    @Override
    public void init(FilterConfig c) {
        secret = c.getInitParameter("secret");
        origin = c.getInitParameter("allowedOrigin");
    }

    @Override
    public void doFilter(ServletRequest rq, ServletResponse rs, FilterChain chain)
            throws IOException, ServletException {
        HttpServletRequest req = (HttpServletRequest) rq;
        HttpServletResponse res = (HttpServletResponse) rs;

        res.setHeader("Access-Control-Allow-Origin", origin);
        res.setHeader("Access-Control-Allow-Credentials", "true");
        res.setHeader("Access-Control-Allow-Headers", "Content-Type");
        res.setHeader("Access-Control-Allow-Methods", "GET,POST,OPTIONS");
        res.setHeader("Vary", "Origin");
        if ("OPTIONS".equals(req.getMethod())) { res.setStatus(204); return; }

        String[] user = verify(req);
        if (user == null) { Json.error(res, 401, "Not signed in, or session expired."); return; }
        req.setAttribute("uid", Integer.parseInt(user[0]));
        req.setAttribute("role", user[1]);
        chain.doFilter(rq, rs);
    }

    private String[] verify(HttpServletRequest req) {
        if (req.getCookies() == null) return null;
        for (Cookie c : req.getCookies()) {
            if (!"pg_token".equals(c.getName())) continue;
            try {
                String[] p = c.getValue().split("\\.");
                if (p.length != 2) return null;
                Mac mac = Mac.getInstance("HmacSHA256");
                mac.init(new SecretKeySpec(secret.getBytes(StandardCharsets.UTF_8), "HmacSHA256"));
                StringBuilder hex = new StringBuilder();
                for (byte b : mac.doFinal(p[0].getBytes(StandardCharsets.UTF_8))) hex.append(String.format("%02x", b));
                if (!MessageDigest.isEqual(hex.toString().getBytes(StandardCharsets.UTF_8),
                                           p[1].getBytes(StandardCharsets.UTF_8))) return null;
                String[] f = new String(Base64.getUrlDecoder().decode(p[0]), StandardCharsets.UTF_8).split("\\|");
                if (f.length != 3 || Long.parseLong(f[2]) < System.currentTimeMillis() / 1000) return null;
                return f;
            } catch (Exception e) {
                return null;
            }
        }
        return null;
    }
}

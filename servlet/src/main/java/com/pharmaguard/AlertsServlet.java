package com.pharmaguard;

import java.io.IOException;
import java.sql.*;
import javax.servlet.annotation.WebServlet;
import javax.servlet.http.*;

/**
 * GET /api/alerts -> { expiring: [...], expired: [...], lowStock: [...] }
 * Reorder rule: target = max(2 x reorder_level, units dispensed in last 30 days);
 *               suggested order = target - usable (unexpired) stock.
 */
@WebServlet("/api/alerts")
public class AlertsServlet extends HttpServlet {
    private static final String EXPIRING =
        "SELECT m.name AS medicine, b.batch_no, b.expiry_date, b.quantity, "
      + "DATEDIFF(b.expiry_date, CURDATE()) AS days_left "
      + "FROM batches b JOIN medicines m ON m.medicine_id = b.medicine_id "
      + "WHERE b.quantity > 0 AND b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) "
      + "ORDER BY b.expiry_date";

    private static final String EXPIRED =
        "SELECT m.name AS medicine, b.batch_no, b.expiry_date, b.quantity, "
      + "DATEDIFF(CURDATE(), b.expiry_date) AS days_ago "
      + "FROM batches b JOIN medicines m ON m.medicine_id = b.medicine_id "
      + "WHERE b.quantity > 0 AND b.expiry_date < CURDATE() ORDER BY b.expiry_date";

    private static final String LOW =
        "SELECT t.*, GREATEST(t.reorder_level * 2, t.used30) - t.stock AS suggested FROM ("
      + " SELECT m.medicine_id, m.name AS medicine, m.reorder_level, s.name AS supplier,"
      + "  CAST(COALESCE((SELECT SUM(b.quantity) FROM batches b WHERE b.medicine_id = m.medicine_id AND b.expiry_date >= CURDATE()),0) AS SIGNED) AS stock,"
      + "  CAST(COALESCE((SELECT SUM(sm.qty) FROM stock_movements sm WHERE sm.medicine_id = m.medicine_id AND sm.move_type = 'DISPENSE' AND sm.created_at >= NOW() - INTERVAL 30 DAY),0) AS SIGNED) AS used30"
      + " FROM medicines m LEFT JOIN suppliers s ON s.supplier_id = m.supplier_id"
      + ") t WHERE t.stock <= t.reorder_level ORDER BY t.stock / t.reorder_level";

    @Override
    protected void doGet(HttpServletRequest req, HttpServletResponse resp) throws IOException {
        try (Connection c = Db.get(); Statement st = c.createStatement()) {
            String expiring, expired, low;
            try (ResultSet rs = st.executeQuery(EXPIRING)) { expiring = Json.rows(rs); }
            try (ResultSet rs = st.executeQuery(EXPIRED)) { expired = Json.rows(rs); }
            try (ResultSet rs = st.executeQuery(LOW)) { low = Json.rows(rs); }
            resp.setHeader("Cache-Control", "no-store");
            Json.send(resp, 200, "{\"expiring\":" + expiring + ",\"expired\":" + expired + ",\"lowStock\":" + low + "}");
        } catch (Exception e) {
            Json.error(resp, 500, "Could not load alerts: " + e.getMessage());
        }
    }
}

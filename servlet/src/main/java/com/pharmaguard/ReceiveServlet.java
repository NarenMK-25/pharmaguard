package com.pharmaguard;

import java.io.IOException;
import java.math.BigDecimal;
import java.sql.*;
import java.time.LocalDate;
import javax.servlet.annotation.WebServlet;
import javax.servlet.http.*;

/** POST /api/receive (ADMIN only): adds a new batch, or tops up an existing batch number. */
@WebServlet("/api/receive")
public class ReceiveServlet extends HttpServlet {
    @Override
    protected void doPost(HttpServletRequest req, HttpServletResponse resp) throws IOException {
        if (!"ADMIN".equals(req.getAttribute("role"))) {
            Json.error(resp, 403, "Only an admin can receive stock.");
            return;
        }
        int medicineId, qty;
        String batchNo;
        LocalDate expiry;
        BigDecimal cost;
        try {
            medicineId = Integer.parseInt(req.getParameter("medicine_id"));
            qty = Integer.parseInt(req.getParameter("quantity"));
            batchNo = req.getParameter("batch_no").trim();
            expiry = LocalDate.parse(req.getParameter("expiry_date"));
            cost = new BigDecimal(req.getParameter("cost_price"));
            if (qty <= 0 || batchNo.isEmpty() || batchNo.length() > 30 || cost.signum() < 0) throw new IllegalArgumentException();
        } catch (RuntimeException e) {
            Json.error(resp, 400, "Check the form: all fields are required and quantity must be above 0.");
            return;
        }
        if (!expiry.isAfter(LocalDate.now())) {
            Json.error(resp, 400, "Expiry date must be in the future.");
            return;
        }
        int uid = (Integer) req.getAttribute("uid");

        try (Connection c = Db.get()) {
            c.setAutoCommit(false);
            try {
                try (PreparedStatement ps = c.prepareStatement(
                        "INSERT INTO batches (medicine_id, batch_no, expiry_date, quantity, cost_price) VALUES (?,?,?,?,?) "
                      + "ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)")) {
                    ps.setInt(1, medicineId); ps.setString(2, batchNo); ps.setDate(3, Date.valueOf(expiry));
                    ps.setInt(4, qty); ps.setBigDecimal(5, cost);
                    ps.executeUpdate();
                }
                int batchId;
                try (PreparedStatement ps = c.prepareStatement("SELECT batch_id FROM batches WHERE medicine_id = ? AND batch_no = ?")) {
                    ps.setInt(1, medicineId); ps.setString(2, batchNo);
                    try (ResultSet rs = ps.executeQuery()) { rs.next(); batchId = rs.getInt(1); }
                }
                try (PreparedStatement ps = c.prepareStatement(
                        "INSERT INTO stock_movements (batch_id, medicine_id, move_type, qty, user_id) VALUES (?,?,'RECEIVE',?,?)")) {
                    ps.setInt(1, batchId); ps.setInt(2, medicineId); ps.setInt(3, qty); ps.setInt(4, uid);
                    ps.executeUpdate();
                }
                c.commit();
                Json.send(resp, 200, "{\"success\":true,\"message\":\"Received " + qty + " units into batch "
                        + Json.esc(batchNo) + ".\"}");
            } catch (Exception e) {
                c.rollback();
                throw e;
            }
        } catch (Exception e) {
            Json.error(resp, 500, "Could not save the batch (is the medicine valid?): " + e.getMessage());
        }
    }
}

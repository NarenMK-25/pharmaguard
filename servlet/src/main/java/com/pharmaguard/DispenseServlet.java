package com.pharmaguard;

import java.io.IOException;
import java.sql.*;
import java.util.ArrayList;
import java.util.List;
import javax.servlet.annotation.WebServlet;
import javax.servlet.http.*;

/**
 * POST /api/dispense  (medicine_id, quantity)
 * Dispenses using FEFO (First-Expired-First-Out) and never touches expired batches.
 * Rows are locked with SELECT ... FOR UPDATE so two pharmacists cannot oversell the same stock.
 */
@WebServlet("/api/dispense")
public class DispenseServlet extends HttpServlet {
    @Override
    protected void doPost(HttpServletRequest req, HttpServletResponse resp) throws IOException {
        int medicineId, qty;
        try {
            medicineId = Integer.parseInt(req.getParameter("medicine_id"));
            qty = Integer.parseInt(req.getParameter("quantity"));
            if (qty <= 0) throw new NumberFormatException();
        } catch (NumberFormatException e) {
            Json.error(resp, 400, "Choose a medicine and enter a quantity greater than 0.");
            return;
        }
        int uid = (Integer) req.getAttribute("uid");

        try (Connection c = Db.get()) {
            c.setAutoCommit(false);
            try {
                List<int[]> plan = new ArrayList<>(); // {batch_id, take}
                int need = qty, available = 0;
                String pick = "SELECT batch_id, quantity FROM batches "
                        + "WHERE medicine_id = ? AND quantity > 0 AND expiry_date >= CURDATE() "
                        + "ORDER BY expiry_date, batch_id FOR UPDATE";
                try (PreparedStatement ps = c.prepareStatement(pick)) {
                    ps.setInt(1, medicineId);
                    try (ResultSet rs = ps.executeQuery()) {
                        while (rs.next()) {
                            int have = rs.getInt("quantity");
                            available += have;
                            if (need > 0) {
                                int take = Math.min(have, need);
                                plan.add(new int[]{rs.getInt("batch_id"), take});
                                need -= take;
                            }
                        }
                    }
                }
                if (need > 0) {
                    c.rollback();
                    Json.error(resp, 409, "Not enough unexpired stock. Available: " + available + ".");
                    return;
                }
                try (PreparedStatement upd = c.prepareStatement("UPDATE batches SET quantity = quantity - ? WHERE batch_id = ?");
                     PreparedStatement log = c.prepareStatement(
                         "INSERT INTO stock_movements (batch_id, medicine_id, move_type, qty, user_id) VALUES (?,?,'DISPENSE',?,?)")) {
                    for (int[] p : plan) {
                        upd.setInt(1, p[1]); upd.setInt(2, p[0]); upd.executeUpdate();
                        log.setInt(1, p[0]); log.setInt(2, medicineId); log.setInt(3, p[1]); log.setInt(4, uid);
                        log.executeUpdate();
                    }
                }
                c.commit();
                Json.send(resp, 200, "{\"success\":true,\"message\":\"Dispensed " + qty + " units from "
                        + plan.size() + " batch(es), earliest expiry first.\"}");
            } catch (Exception e) {
                c.rollback();
                throw e;
            }
        } catch (Exception e) {
            Json.error(resp, 500, "Could not complete the transaction: " + e.getMessage());
        }
    }
}

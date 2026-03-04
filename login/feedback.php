<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// /admin/feedback.php
session_name('manual_login');
session_start();

require_once "../config/connection.php";


// Only allow logged-in admin
if (!isset($_SESSION['AdminLoginId'])) {
    http_response_code(403);
    exit("Forbidden");
}

// handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $d = $con->prepare("DELETE FROM feedback WHERE id = ?");
    $d->bind_param('i', $id);
    $d->execute();
    $d->close();
    header('Location: feedback.php'); exit;
}

$res = $con->query("SELECT id, anon_id, message, created_at FROM feedback ORDER BY created_at DESC LIMIT 500");
?>
<!doctype html><html><head><meta charset="utf-8"><title>Feedback</title></head><body style="background:#000;color:#fff;font-family:sans-serif;">
<h2>Feedback (admin)</h2>
<table border="0" cellpadding="8" style="width:100%;border-collapse:collapse;">
<tr style="border-bottom:1px solid #333;"><th>ID</th><th>Message</th><th>Token</th><th>When</th><th>Action</th></tr>
<?php while ($row = $res->fetch_assoc()): ?>
<tr style="border-bottom:1px solid #222;">
  <td><?= (int)$row['id'] ?></td>
  <td style="max-width:80%;white-space:pre-wrap;"><?= htmlspecialchars($row['message']) ?></td>
  <td style="opacity:.6;font-size:.85rem;"><?= htmlspecialchars(substr($row['anon_id'],0,12)) ?>…</td>
  <td><?= htmlspecialchars($row['created_at']) ?></td>
  <td>
    <form method="POST" style="display:inline">
      <input type="hidden" name="delete_id" value="<?= (int)$row['id'] ?>">
      <button type="submit" style="background:#e50814;color:#fff;border:none;padding:6px 8px;border-radius:6px;">Delete</button>
    </form>
  </td>
</tr>
<?php endwhile; ?>
</table>
    </body></html>

<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/db.php';

// Metrics
$active_rentals  = $conn->query("SELECT COUNT(*) c FROM reservations WHERE status IN ('confirmed','active')")->fetch_assoc()['c'];
$available_bikes  = $conn->query("SELECT COUNT(*) c FROM motorcycles WHERE status='available'")->fetch_assoc()['c'];
$today_revenue    = $conn->query("SELECT COALESCE(SUM(amount),0) c FROM payments WHERE DATE(paid_at)=CURDATE() AND status='paid'")->fetch_assoc()['c'];
$pending_returns  = $conn->query("SELECT COUNT(*) c FROM reservations WHERE status='active' AND end_date<=CURDATE()")->fetch_assoc()['c'];
$fleet_status     = $conn->query("SELECT status, COUNT(*) c FROM motorcycles GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$recent_res       = $conn->query("SELECT r.*, c.first_name, c.last_name, m.model, m.plate_number FROM reservations r JOIN customers c ON r.customer_id=c.customer_id JOIN motorcycles m ON r.motorcycle_id=m.motorcycle_id ORDER BY r.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$status_map = ['available'=>0,'rented'=>0,'maintenance'=>0,'damage_review'=>0];
foreach ($fleet_status as $fs) $status_map[$fs['status']] = $fs['c'];
$total_bikes = array_sum($status_map);

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-header">
  <div>
    <div class="page-title">Dashboard</div>
    <div class="page-sub">Overview for today, <?= date('F j, Y') ?></div>
  </div>
</div>

<div class="metrics-grid">
  <div class="metric-card">
    <div class="metric-label">Active rentals</div>
    <div class="metric-value"><?= $active_rentals ?></div>
    <div class="metric-sub">Confirmed + ongoing</div>
  </div>
  <div class="metric-card">
    <div class="metric-label">Available bikes</div>
    <div class="metric-value"><?= $available_bikes ?></div>
    <div class="metric-sub">Ready to rent</div>
  </div>
  <div class="metric-card">
    <div class="metric-label">Revenue today</div>
    <div class="metric-value">₱<?= number_format($today_revenue) ?></div>
    <div class="metric-sub">Payments received</div>
  </div>
  <div class="metric-card">
    <div class="metric-label">Pending returns</div>
    <div class="metric-value <?= $pending_returns>0?'metric-down':'' ?>"><?= $pending_returns ?></div>
    <div class="metric-sub">Due today or overdue</div>
  </div>
</div>

<div class="grid2">
  <div class="card">
    <div class="card-title">Fleet status (<?= $total_bikes ?> units)</div>
    <div style="display:flex;gap:8px;flex-direction:column">
      <?php
      $fleet_labels = ['available'=>['Available','badge-success'],'rented'=>['Rented','badge-info'],'maintenance'=>['Maintenance','badge-warning'],'damage_review'=>['Damage review','badge-danger']];
      foreach ($fleet_labels as $key=>[$label,$badge]):
        $count = $status_map[$key];
        $pct = $total_bikes ? round($count/$total_bikes*100) : 0;
      ?>
      <div style="display:flex;align-items:center;gap:10px">
        <span style="width:110px;font-size:12px;color:var(--text2)"><?= $label ?></span>
        <div style="flex:1;background:var(--bg3);border-radius:4px;height:8px">
          <div style="width:<?= $pct ?>%;background:<?= ['available'=>'var(--green)','rented'=>'var(--blue)','maintenance'=>'var(--amber)','damage_review'=>'var(--red)'][$key] ?>;height:8px;border-radius:4px"></div>
        </div>
        <span style="font-size:12px;font-weight:600;width:24px;text-align:right"><?= $count ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-title">Recent bookings</div>
    <div class="activity-list">
    <?php foreach($recent_res as $r): ?>
      <div class="activity-item">
        <div class="activity-dot <?= ['confirmed'=>'dot-info','active'=>'dot-success','returned'=>'dot-success','cancelled'=>'dot-danger'][$r['status']] ?? 'dot-info' ?>"></div>
        <div>
          <div class="activity-text"><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?> — <?= htmlspecialchars($r['model']) ?> (<?= $r['plate_number'] ?>)</div>
          <div class="activity-time"><?= date('M j',strtotime($r['start_date'])) ?> – <?= date('M j',strtotime($r['end_date'])) ?> &nbsp;·&nbsp; ₱<?= number_format($r['total_amount']) ?> &nbsp;·&nbsp; <span class="badge badge-<?= ['confirmed'=>'info','active'=>'success','returned'=>'neutral','cancelled'=>'danger'][$r['status']]?? 'neutral' ?>"><?= ucfirst($r['status']) ?></span></div>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

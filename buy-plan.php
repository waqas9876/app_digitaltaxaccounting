<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buy Plan — Digital Tax Accounting</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
  <style>
    .plan-card {
      background: white;
      border-radius: 16px;
      border: 2px solid var(--gray-200);
      padding: 28px 24px;
      transition: all .3s;
      position: relative;
      overflow: hidden;
    }
    .plan-card:hover { border-color: var(--orange); transform: translateY(-4px); box-shadow: 0 16px 40px rgba(255,116,33,.15); }
    .plan-card.popular { border-color: var(--orange); box-shadow: 0 8px 32px rgba(255,116,33,.2); }
    .popular-badge {
      position: absolute;
      top: 16px; right: -24px;
      background: var(--orange);
      color: white;
      font-size: 11px;
      font-weight: 700;
      padding: 4px 32px;
      transform: rotate(45deg);
      letter-spacing: 1px;
    }
    .plan-icon {
      width: 52px; height: 52px;
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 16px;
    }
    .plan-icon svg { width: 24px; height: 24px; }
    .plan-name { font-size: 20px; font-weight: 800; color: var(--blue); margin-bottom: 4px; }
    .plan-desc { font-size: 13px; color: var(--gray-500); margin-bottom: 20px; }
    .plan-price { margin-bottom: 20px; }
    .plan-price .amount { font-size: 36px; font-weight: 900; color: var(--blue); line-height: 1; }
    .plan-price .amount span { font-size: 20px; vertical-align: top; margin-top: 6px; display: inline-block; }
    .plan-price .period { font-size: 13px; color: var(--gray-500); margin-top: 4px; }
    .plan-features { list-style: none; margin-bottom: 24px; }
    .plan-features li {
      display: flex; align-items: flex-start; gap: 10px;
      font-size: 13px; color: var(--gray-600);
      padding: 5px 0;
    }
    .plan-features li svg { width: 16px; height: 16px; color: var(--success); flex-shrink: 0; margin-top: 1px; }
    .plan-cta { width: 100%; padding: 13px; border-radius: 10px; font-size: 15px; font-weight: 700; }
  </style>
</head>
<body>
<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireClientLogin();
$client = getClient();

$planStmt = db()->prepare("SELECT * FROM plans WHERE is_active=1 ORDER BY price_monthly ASC");
$planStmt->execute();
$plans = $planStmt->fetchAll();
?>

<div class="app-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="main-content">
    <header class="top-header">
      <button class="header-menu-btn" id="menuBtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <div class="header-title">
        <h1>Upgrade Your Plan</h1>
        <p>Current plan: <strong style="color:var(--orange)"><?= ucfirst($client['plan']) ?></strong></p>
      </div>
    </header>

    <div class="page-body">
      <!-- Toggle Monthly/Yearly -->
      <div style="text-align:center;margin-bottom:32px">
        <div style="display:inline-flex;background:var(--gray-100);border-radius:12px;padding:4px;gap:4px">
          <button id="btnMonthly" class="btn btn-secondary btn-sm" style="min-width:110px">Monthly</button>
          <button id="btnYearly"  class="btn btn-ghost btn-sm"     style="min-width:110px">Yearly <span style="color:var(--success);font-size:11px;font-weight:700">-17%</span></button>
        </div>
        <p style="font-size:13px;color:var(--gray-500);margin-top:8px">Save up to 17% with annual billing</p>
      </div>

      <!-- Plans Grid -->
      <div class="grid grid-4 mb-24" style="gap:20px">
        <?php
        $planIcons = [
          'free'         => ['bg'=>'#F1F3F8','color'=>'#6B7D99'],
          'basic'        => ['bg'=>'#ECFDF5','color'=>'#10B981'],
          'professional' => ['bg'=>'#FFF4EE','color'=>'#FF7421'],
          'premium'      => ['bg'=>'#FFFBEB','color'=>'#F59E0B'],
        ];
        $planIconSVGs = [
          'free' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
          'basic' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
          'professional' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
          'premium' => '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>',
        ];
        foreach ($plans as $p):
          $isCurrentPlan = $client['plan'] === $p['slug'];
          $isPopular      = $p['slug'] === 'professional';
          $ic = $planIcons[$p['slug']] ?? $planIcons['free'];
          $features = json_decode($p['features'] ?? '[]', true);
        ?>
          <div class="plan-card <?= $isPopular ? 'popular' : '' ?>">
            <?php if ($isPopular): ?><div class="popular-badge">POPULAR</div><?php endif; ?>
            <div class="plan-icon" style="background:<?= $ic['bg'] ?>;color:<?= $ic['color'] ?>">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <?= $planIconSVGs[$p['slug']] ?? '' ?>
              </svg>
            </div>
            <div class="plan-name"><?= sanitize($p['name']) ?></div>
            <div class="plan-desc"><?= sanitize($p['description']) ?></div>
            <div class="plan-price">
              <div class="amount" data-monthly="<?= $p['price_monthly'] ?>" data-yearly="<?= $p['price_yearly'] ?>">
                <?php if ((float)$p['price_monthly'] === 0.0): ?>
                  <span style="font-size:36px">Free</span>
                <?php else: ?>
                  <span>£</span><?= number_format($p['price_monthly'],0) ?>
                <?php endif; ?>
              </div>
              <?php if ((float)$p['price_monthly'] > 0): ?><div class="period">/month billed monthly</div><?php endif; ?>
            </div>
            <ul class="plan-features">
              <?php foreach ($features as $feature): ?>
                <li>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                  <?= sanitize($feature) ?>
                </li>
              <?php endforeach; ?>
            </ul>
            <?php if ($isCurrentPlan): ?>
              <button class="btn btn-ghost plan-cta" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
                Current Plan
              </button>
            <?php elseif ((float)$p['price_monthly'] === 0.0): ?>
              <button class="btn btn-ghost plan-cta" disabled>Free Plan</button>
            <?php else: ?>
              <button class="btn <?= $isPopular ? 'btn-primary' : 'btn-secondary' ?> plan-cta"
                      onclick="selectPlan('<?= $p['slug'] ?>', '<?= sanitize($p['name']) ?>', <?= $p['price_monthly'] ?>)">
                Get <?= sanitize($p['name']) ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
              </button>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- FAQ -->
      <div class="card mb-24">
        <div class="card-header"><span class="card-title">Frequently Asked Questions</span></div>
        <div class="card-body">
          <div class="grid grid-2" style="gap:16px">
            <?php
            $faqs = [
              ['Can I change plans anytime?', 'Yes! You can upgrade or downgrade your plan at any time. Changes take effect immediately.'],
              ['Is my data safe?', 'Absolutely. All data is encrypted and stored securely. We never share your financial information.'],
              ['What payment methods do you accept?', 'We accept all major credit cards, PayPal, and ACH bank transfers.'],
              ['Do you offer refunds?', 'We offer a 30-day money-back guarantee for all paid plans. No questions asked.'],
            ];
            foreach ($faqs as [$q, $a]):
            ?>
              <div style="padding:16px;background:var(--gray-50);border-radius:10px">
                <div style="font-size:14px;font-weight:700;color:var(--blue);margin-bottom:6px"><?= $q ?></div>
                <div style="font-size:13px;color:var(--gray-600);line-height:1.6"><?= $a ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Checkout Modal -->
<div class="modal-backdrop" id="checkoutModal">
  <div class="modal" style="max-width:420px">
    <div class="modal-header">
      <span class="modal-title" id="checkoutTitle">Upgrade Plan</span>
      <button class="modal-close" data-close-modal="checkoutModal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body">
      <div id="checkoutInfo" style="text-align:center;padding:10px 0 20px;margin-bottom:20px;border-bottom:1px solid var(--gray-100)">
        <div style="font-size:28px;font-weight:900;color:var(--blue)" id="checkoutPrice"></div>
        <div style="font-size:13px;color:var(--gray-500)">per month</div>
      </div>
      <div class="form-group">
        <label class="form-label">Card Number</label>
        <input type="text" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19" id="cardNumber" oninput="formatCard(this)">
      </div>
      <div class="grid grid-2">
        <div class="form-group">
          <label class="form-label">Expiry</label>
          <input type="text" class="form-control" placeholder="MM / YY" maxlength="7">
        </div>
        <div class="form-group">
          <label class="form-label">CVV</label>
          <input type="text" class="form-control" placeholder="123" maxlength="4">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Cardholder Name</label>
        <input type="text" class="form-control" placeholder="John Smith">
      </div>
      <div style="background:var(--success-bg);border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:8px">
        <svg viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2" width="18" height="18"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <span style="font-size:12px;color:#065f46">Your payment is secured with 256-bit SSL encryption</span>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal="checkoutModal">Cancel</button>
      <button class="btn btn-primary" onclick="toast('Payment processing is not configured. Please contact support.','info');closeModal('checkoutModal')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Complete Purchase
      </button>
    </div>
  </div>
</div>

<script src="/assets/js/main.js"></script>
<script>
let isYearly = false;

document.getElementById('btnMonthly').addEventListener('click',()=>{
  isYearly=false;
  document.getElementById('btnMonthly').className='btn btn-secondary btn-sm';
  document.getElementById('btnYearly').className='btn btn-ghost btn-sm';
  updatePrices();
});

document.getElementById('btnYearly').addEventListener('click',()=>{
  isYearly=true;
  document.getElementById('btnYearly').className='btn btn-secondary btn-sm';
  document.getElementById('btnMonthly').className='btn btn-ghost btn-sm';
  updatePrices();
});

function updatePrices() {
  document.querySelectorAll('.plan-price .amount[data-monthly]').forEach(el => {
    const m = parseFloat(el.dataset.monthly);
    const y = parseFloat(el.dataset.yearly);
    if (m === 0) return;
    const price = isYearly ? (y/12) : m;
    el.innerHTML = '<span>£</span>' + price.toFixed(0);
    el.nextElementSibling.textContent = isYearly ? '/month billed annually' : '/month billed monthly';
  });
}

function selectPlan(slug, name, price) {
  document.getElementById('checkoutTitle').textContent = 'Upgrade to ' + name;
  document.getElementById('checkoutPrice').textContent = '£' + (isYearly ? (price*12*.83).toFixed(2) : price.toFixed(2));
  openModal('checkoutModal');
}

function formatCard(el) {
  let v = el.value.replace(/\D/g,'').substring(0,16);
  el.value = v.replace(/(.{4})/g,'$1 ').trim();
}
</script>
</body>
</html>

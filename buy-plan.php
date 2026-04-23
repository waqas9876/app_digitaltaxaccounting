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
    .plan-tab {
      padding: 9px 24px;
      border-radius: 10px;
      border: none;
      background: transparent;
      font-size: 14px;
      font-weight: 600;
      color: var(--gray-500);
      cursor: pointer;
      transition: all .2s;
    }
    .plan-tab:hover { color: var(--blue); }
    .plan-tab.active { background: white; color: var(--blue); box-shadow: 0 2px 8px rgba(22,41,90,.12); }
    .plan-tab-panel { display: none; }
    .plan-tab-panel.active { display: block; }
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

      <!-- Tab Switcher -->
      <div style="text-align:center;margin-bottom:32px">
        <div style="display:inline-flex;background:var(--gray-100);border-radius:14px;padding:5px;gap:4px">
          <button class="plan-tab active" data-tab="mtd">MTD</button>
          <button class="plan-tab" data-tab="nonmtd">NON MTD</button>
          <button class="plan-tab" data-tab="companies">For Companies</button>
        </div>
        <p id="tabDesc" style="font-size:13px;color:var(--gray-500);margin-top:10px">Making Tax Digital — for sole traders &amp; landlords mandated under HMRC MTD scheme</p>
      </div>

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
      $mtdProFeatures    = ['Free registration to HMRC','Use of WebApp all data digitally saved','Quarterly submissions to HMRC','Final submission to HMRC','Self Assessment return submission to HMRC'];
      $mtdProPlusFeatures = array_merge($mtdProFeatures, ['Further enhanced accountancy services support loans, mortgage and tax enquiry work']);
      ?>

      <!-- Tab Panel: MTD -->
      <div class="plan-tab-panel active" data-panel="mtd">
        <div style="display:flex;justify-content:center;gap:20px;flex-wrap:wrap;margin-bottom:24px">

          <!-- PRO -->
          <div class="plan-card" style="width:500px;flex-shrink:0">
            <div class="plan-icon" style="background:#EFF6FF;color:#3B82F6">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="plan-name">PRO</div>
            <div class="plan-desc">Best for part-time or low-volume drivers who need simple HMRC compliance.</div>
            <div class="plan-price">
              <div class="amount"><span>£</span>235</div>
              <div class="period">/ year</div>
            </div>
            <ul class="plan-features">
              <?php foreach ($mtdProFeatures as $f): ?>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><?= $f ?></li>
              <?php endforeach; ?>
            </ul>
            <button class="btn btn-secondary plan-cta" onclick="selectPlan('pro','PRO',235)">
              Get PRO
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
          </div>

          <!-- PRO+ (popular) -->
          <div class="plan-card popular" style="width:500px;flex-shrink:0">
            <div class="popular-badge">POPULAR</div>
            <div class="plan-icon" style="background:#FFF4EE;color:#FF7421">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            </div>
            <div class="plan-name">PRO+</div>
            <div class="plan-desc">Best for full-time taxi and ride-share drivers.</div>
            <div class="plan-price">
              <div class="amount"><span>£</span>300</div>
              <div class="period">/ year</div>
            </div>
            <ul class="plan-features">
              <?php foreach ($mtdProPlusFeatures as $f): ?>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><?= $f ?></li>
              <?php endforeach; ?>
            </ul>
            <button class="btn btn-primary plan-cta" onclick="selectPlan('pro-plus','PRO+',300)">
              Get PRO+
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
          </div>

        </div>
      </div>

      <!-- Tab Panel: NON MTD -->
      <div class="plan-tab-panel" data-panel="nonmtd">
        <div style="display:flex;justify-content:center;gap:20px;flex-wrap:wrap;margin-bottom:24px">

          <!-- NON MTD PRO -->
          <div class="plan-card" style="width:500px;flex-shrink:0">
            <div class="plan-icon" style="background:#EFF6FF;color:#3B82F6">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="plan-name">PRO</div>
            <div class="plan-desc">Best for part-time or low-volume drivers who need simple HMRC compliance.</div>
            <div class="plan-price">
              <div class="amount"><span>£</span>135</div>
              <div class="period">/ year</div>
            </div>
            <ul class="plan-features">
              <?php foreach (['Taximanager web APP to record your income and expenses','Accounts and self assessment return prepared and submitted to HMRC','User software licence.','Lifetime Support'] as $f): ?>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><?= $f ?></li>
              <?php endforeach; ?>
            </ul>
            <button class="btn btn-secondary plan-cta" onclick="selectPlan('nonmtd-pro','PRO',135)">
              Get PRO
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
          </div>

          <!-- NON MTD PRO+ (popular) -->
          <div class="plan-card popular" style="width:500px;flex-shrink:0">
            <div class="popular-badge">POPULAR</div>
            <div class="plan-icon" style="background:#FFF4EE;color:#FF7421">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            </div>
            <div class="plan-name">PRO+</div>
            <div class="plan-desc">Best for full-time taxi and ride-share drivers.</div>
            <div class="plan-price">
              <div class="amount"><span>£</span>180</div>
              <div class="period">/ year</div>
            </div>
            <ul class="plan-features">
              <?php foreach (['Support for mortgage/loan applications','HMRC enquiry - our fees included','Any accountancy certificates required for visa applications','General support for any institutions requiring accountancy reports','Mid year accounts required'] as $f): ?>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><?= $f ?></li>
              <?php endforeach; ?>
            </ul>
            <button class="btn btn-primary plan-cta" onclick="selectPlan('nonmtd-pro-plus','PRO+',180)">
              Get PRO+
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
          </div>

        </div>
      </div>

      <!-- Tab Panel: FOR COMPANIES -->
      <div class="plan-tab-panel" data-panel="companies">
        <div style="display:flex;justify-content:center;gap:20px;flex-wrap:wrap;margin-bottom:24px">

          <!-- NON-VAT REGISTERED -->
          <div class="plan-card" style="width:500px;flex-shrink:0">
            <div class="plan-icon" style="background:#EFF6FF;color:#3B82F6">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
            </div>
            <div class="plan-name">Non-VAT Registered</div>
            <div class="plan-desc">Best for small limited companies not registered for VAT.</div>
            <div class="plan-price">
              <div class="amount"><span>£</span>45</div>
              <div class="period">/ month</div>
            </div>
            <ul class="plan-features">
              <?php foreach (['Company formation support','Confirmation Statement (CS01) filing','Annual accounts preparation & submission','Corporation tax return (CT600) filing','Basic bookkeeping','Email support & deadline reminders','HMRC & Companies House compliance'] as $f): ?>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><?= $f ?></li>
              <?php endforeach; ?>
            </ul>
            <button class="btn btn-secondary plan-cta" onclick="selectPlan('non-vat','Non-VAT Registered',45)">
              Get Started
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
          </div>

          <!-- VAT REGISTERED (popular) -->
          <div class="plan-card popular" style="width:500px;flex-shrink:0">
            <div class="popular-badge">POPULAR</div>
            <div class="plan-icon" style="background:#FFF4EE;color:#FF7421">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            </div>
            <div class="plan-name">VAT Registered</div>
            <div class="plan-desc">Best for growing companies registered for VAT.</div>
            <div class="plan-price">
              <div class="amount"><span>£</span>55</div>
              <div class="period">/ month</div>
            </div>
            <ul class="plan-features">
              <?php foreach (['Everything in Non-VAT package','VAT registration','Quarterly VAT return preparation & submission','MTD-compliant bookkeeping','Invoices Management','Bank reconciliation','Corporation tax return (CT600)','Annual accounts submission','Priority support (Email / WhatsApp)','HMRC & Companies House handling'] as $f): ?>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><?= $f ?></li>
              <?php endforeach; ?>
            </ul>
            <button class="btn btn-primary plan-cta" onclick="selectPlan('vat','VAT Registered',55)">
              Get Started
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
          </div>

        </div>
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
const tabDescs = {
  mtd:       'Making Tax Digital — for sole traders &amp; landlords mandated under HMRC MTD scheme',
  nonmtd:    'Non-MTD plans — for individuals &amp; businesses not yet required to use Making Tax Digital',
  companies: 'Limited company plans — full accounting support for UK registered companies'
};

document.querySelectorAll('.plan-tab').forEach(btn => {
  btn.addEventListener('click', () => {
    const tab = btn.dataset.tab;
    document.querySelectorAll('.plan-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.plan-tab-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.querySelector('.plan-tab-panel[data-panel="'+tab+'"]').classList.add('active');
    document.getElementById('tabDesc').innerHTML = tabDescs[tab];
  });
});

function selectPlan(slug, name, price) {
  document.getElementById('checkoutTitle').textContent = 'Upgrade to ' + name;
  document.getElementById('checkoutPrice').textContent = '£' + parseFloat(price).toFixed(2);
  openModal('checkoutModal');
}

function formatCard(el) {
  let v = el.value.replace(/\D/g,'').substring(0,16);
  el.value = v.replace(/(.{4})/g,'$1 ').trim();
}
</script>
</body>
</html>

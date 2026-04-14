<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php require_once __DIR__.'/config/config.php'; require_once __DIR__.'/includes/auth.php'; echo generateCsrf(); ?>">
  <title>Settings — Digital Tax Accounting</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireClientLogin();
$client = getClient();
?>

<div class="app-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="main-content">
    <header class="top-header">
      <button class="header-menu-btn" id="menuBtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <div class="header-title">
        <h1>Settings</h1>
        <p>Manage your account preferences</p>
      </div>
      <div class="header-actions">
        <button class="btn btn-primary btn-sm" onclick="saveProfile()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
          Save Changes
        </button>
      </div>
    </header>

    <div class="page-body">
      <div id="alertContainer"></div>

      <div class="grid" style="grid-template-columns:260px 1fr;gap:24px;align-items:start">
        <!-- Settings Nav -->
        <div class="card">
          <div class="card-body" style="padding:8px">
            <?php
            $tabs = [
              ['id'=>'profile','icon'=>'user','label'=>'Profile'],
              ['id'=>'business','icon'=>'building','label'=>'Business Info'],
              ['id'=>'security','icon'=>'lock','label'=>'Security'],
              ['id'=>'notifications','icon'=>'bell','label'=>'Notifications'],
              ['id'=>'danger','icon'=>'alert','label'=>'Danger Zone'],
            ];
            $iconPaths = [
              'user'     => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
              'building' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
              'lock'     => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
              'bell'     => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
              'alert'    => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            ];
            foreach ($tabs as $tab):
            ?>
              <a href="#<?= $tab['id'] ?>" class="settings-nav-link" onclick="switchTab('<?= $tab['id'] ?>')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><<?= $iconPaths[$tab['icon']] ?>></svg>
                <?= $tab['label'] ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Settings Panels -->
        <div>
          <!-- Profile Panel -->
          <div id="tab-profile" class="tab-panel">
            <div class="card mb-20">
              <div class="card-header"><span class="card-title">Profile Information</span></div>
              <div class="card-body">
                <!-- Avatar -->
                <div style="display:flex;align-items:center;gap:20px;margin-bottom:24px;padding-bottom:24px;border-bottom:1px solid var(--gray-100)">
                  <div style="width:72px;height:72px;border-radius:50%;background:var(--orange);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700;color:white;overflow:hidden" id="avatarPreview">
                    <?php if ($client['avatar']): ?>
                      <img src="/assets/uploads/<?= sanitize($client['avatar']) ?>" style="width:100%;height:100%;object-fit:cover">
                    <?php else: ?>
                      <?= getInitials($client['first_name'].' '.$client['last_name']) ?>
                    <?php endif; ?>
                  </div>
                  <div>
                    <button class="btn btn-ghost btn-sm" onclick="document.getElementById('avatarInput').click()">Change Photo</button>
                    <input type="file" id="avatarInput" accept="image/*" style="display:none" onchange="previewAvatar(this)">
                    <div style="font-size:12px;color:var(--gray-400);margin-top:4px">JPG, PNG up to 5MB</div>
                  </div>
                </div>

                <form id="profileForm">
                  <div class="grid grid-2">
                    <div class="form-group">
                      <label class="form-label">First Name</label>
                      <input type="text" name="first_name" class="form-control" value="<?= sanitize($client['first_name']) ?>" required>
                    </div>
                    <div class="form-group">
                      <label class="form-label">Last Name</label>
                      <input type="text" name="last_name" class="form-control" value="<?= sanitize($client['last_name']) ?>" required>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= sanitize($client['email']) ?>" required>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" value="<?= sanitize($client['phone'] ?? '') ?>">
                  </div>
                  <div class="grid grid-2">
                    <div class="form-group">
                      <label class="form-label">City</label>
                      <input type="text" name="city" class="form-control" value="<?= sanitize($client['city'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                      <label class="form-label">State</label>
                      <input type="text" name="state" class="form-control" value="<?= sanitize($client['state'] ?? '') ?>">
                    </div>
                  </div>
                  <div class="grid grid-2">
                    <div class="form-group">
                      <label class="form-label">ZIP Code</label>
                      <input type="text" name="zip_code" class="form-control" value="<?= sanitize($client['zip_code'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                      <label class="form-label">Address</label>
                      <input type="text" name="address" class="form-control" value="<?= sanitize($client['address'] ?? '') ?>">
                    </div>
                  </div>
                </form>
              </div>
              <div class="card-footer" style="display:flex;justify-content:flex-end">
                <button class="btn btn-primary" onclick="saveProfile()">Save Profile</button>
              </div>
            </div>
          </div>

          <!-- Business Panel -->
          <div id="tab-business" class="tab-panel" style="display:none">
            <div class="card">
              <div class="card-header"><span class="card-title">Business Information</span></div>
              <div class="card-body">
                <form id="businessForm">
                  <div class="form-group">
                    <label class="form-label">Business Name</label>
                    <input type="text" name="business_name" class="form-control" value="<?= sanitize($client['business_name'] ?? '') ?>">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Business Type</label>
                    <select name="business_type" class="form-control">
                      <option value="">— Select —</option>
                      <option <?= ($client['business_type']??'')==='Sole Proprietor'?'selected':'' ?>>Sole Proprietor</option>
                      <option <?= ($client['business_type']??'')==='LLC'?'selected':'' ?>>LLC</option>
                      <option <?= ($client['business_type']??'')==='S-Corp'?'selected':'' ?>>S-Corp</option>
                      <option <?= ($client['business_type']??'')==='C-Corp'?'selected':'' ?>>C-Corp</option>
                      <option <?= ($client['business_type']??'')==='Partnership'?'selected':'' ?>>Partnership</option>
                      <option <?= ($client['business_type']??'')==='Freelancer'?'selected':'' ?>>Freelancer</option>
                    </select>
                  </div>
                </form>
              </div>
              <div class="card-footer" style="display:flex;justify-content:flex-end">
                <button class="btn btn-primary" onclick="saveBusiness()">Save Business Info</button>
              </div>
            </div>
          </div>

          <!-- Security Panel -->
          <div id="tab-security" class="tab-panel" style="display:none">
            <div class="card">
              <div class="card-header"><span class="card-title">Change Password</span></div>
              <div class="card-body">
                <form id="passwordForm">
                  <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" placeholder="••••••••">
                  </div>
                  <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="••••••••">
                    <div class="form-hint">At least 8 characters with uppercase and numbers</div>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="••••••••">
                  </div>
                </form>
              </div>
              <div class="card-footer" style="display:flex;justify-content:flex-end">
                <button class="btn btn-primary" onclick="changePassword()">Update Password</button>
              </div>
            </div>
          </div>

          <!-- Danger Zone -->
          <div id="tab-danger" class="tab-panel" style="display:none">
            <div class="card" style="border-color:var(--danger)">
              <div class="card-header"><span class="card-title" style="color:var(--danger)">Danger Zone</span></div>
              <div class="card-body">
                <div style="padding:16px;background:var(--danger-bg);border-radius:10px;margin-bottom:16px">
                  <div style="font-weight:600;color:#991b1b;margin-bottom:4px">Delete Account</div>
                  <div style="font-size:13px;color:#9b1c1c;margin-bottom:12px">Once you delete your account, there is no going back. All your data will be permanently removed.</div>
                  <button class="btn btn-danger btn-sm" onclick="if(confirm('ARE YOU SURE? This will permanently delete your account and all data.'))alert('Account deletion requires email confirmation. Feature coming soon.')">
                    Delete My Account
                  </button>
                </div>
                <div style="padding:16px;background:var(--warning-bg);border-radius:10px">
                  <div style="font-weight:600;color:#92400e;margin-bottom:4px">Export All Data</div>
                  <div style="font-size:13px;color:#78350f;margin-bottom:12px">Download a copy of all your data including income, expenses, mileage and notes.</div>
                  <button class="btn btn-warning btn-sm" onclick="toast('Data export is being prepared. You will receive an email when ready.','info')" style="background:var(--warning);color:white;border:none;padding:8px 16px;border-radius:8px;font-weight:600;cursor:pointer">
                    Export My Data
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.settings-nav-link {
  display:flex; align-items:center; gap:10px;
  padding:10px 12px; border-radius:8px;
  color:var(--gray-600); font-size:14px; font-weight:500;
  transition:all .2s; text-decoration:none;
}
.settings-nav-link:hover { background:var(--gray-100); color:var(--blue); }
.settings-nav-link.active { background:var(--orange); color:white; }
</style>

<script src="/assets/js/main.js"></script>
<script>
function switchTab(id) {
  document.querySelectorAll('.tab-panel').forEach(p => p.style.display='none');
  document.getElementById('tab-'+id).style.display='block';
  document.querySelectorAll('.settings-nav-link').forEach(l => l.classList.remove('active'));
  event.currentTarget.classList.add('active');
}

// Activate first tab
document.querySelector('.settings-nav-link').classList.add('active');

async function saveProfile() {
  const data = Object.fromEntries(new FormData(document.getElementById('profileForm')));
  const result = await apiCall('/api/settings.php', { action:'update_profile', ...data });
  toast(result.message || (result.success?'Saved!':'Error'), result.success?'success':'danger');
  if (result.success && data.first_name) {
    document.querySelector('.user-name').textContent = data.first_name + ' ' + data.last_name;
  }
}

async function saveBusiness() {
  const data = Object.fromEntries(new FormData(document.getElementById('businessForm')));
  const result = await apiCall('/api/settings.php', { action:'update_business', ...data });
  toast(result.message || (result.success?'Saved!':'Error'), result.success?'success':'danger');
}

async function changePassword() {
  const data = Object.fromEntries(new FormData(document.getElementById('passwordForm')));
  if (data.new_password !== data.confirm_password) { toast('Passwords do not match.','danger'); return; }
  const result = await apiCall('/api/settings.php', { action:'change_password', ...data });
  toast(result.message, result.success?'success':'danger');
}

function previewAvatar(input) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const prev = document.getElementById('avatarPreview');
    prev.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover">`;
  };
  reader.readAsDataURL(file);
}
</script>
</body>
</html>

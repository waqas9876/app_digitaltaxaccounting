<?php
// Sidebar component – included in every client page
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$client = getClient();
$initials = getInitials($_SESSION['client_name'] ?? 'U');

$navItems = [
    ['page' => 'dashboard', 'icon' => 'grid',          'label' => 'Dashboard'],
    ['page' => 'income',    'icon' => 'trending-up',    'label' => 'Income'],
    ['page' => 'expenses',  'icon' => 'trending-down',  'label' => 'Expenses'],
    ['page' => 'mileage',   'icon' => 'map',            'label' => 'Mileage'],
    ['page' => 'summary',   'icon' => 'bar-chart-2',    'label' => 'Summary'],
    ['page' => 'notes',     'icon' => 'file-text',      'label' => 'Notes'],
    ['page' => 'taxes',     'icon' => 'percent',        'label' => 'Taxes'],
    ['page' => 'buy-plan',  'icon' => 'star',           'label' => 'Buy Plan'],
    ['page' => 'settings',  'icon' => 'settings',       'label' => 'Settings'],
];
?>
<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">
      <div class="logo-icon">
        <img src="/assets/images/logo.png" alt="Digital Tax Accounting" style="width:40px;height:40px;object-fit:contain;">
      </div>
      <div class="logo-text">
        <span class="logo-name">Digital Tax</span>
        <span class="logo-sub">Accounting</span>
      </div>
    </div>
    <button class="sidebar-close" id="sidebarClose">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
  </div>

  <!-- User card -->
  <div class="sidebar-user">
    <div class="user-avatar">
      <?php if ($client && $client['avatar']): ?>
        <img src="/assets/uploads/<?= sanitize($client['avatar']) ?>" alt="Avatar">
      <?php else: ?>
        <span><?= $initials ?></span>
      <?php endif; ?>
      <span class="user-status"></span>
    </div>
    <div class="user-info">
      <span class="user-name"><?= sanitize($_SESSION['client_name'] ?? 'Client') ?></span>
      <span class="user-plan plan-badge plan-<?= $client['plan'] ?? 'free' ?>"><?= ucfirst($client['plan'] ?? 'Free') ?></span>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav">
    <ul>
      <?php foreach ($navItems as $item): ?>
        <li class="nav-item <?= $currentPage === $item['page'] ? 'active' : '' ?>">
          <a href="/<?= $item['page'] ?>.php" class="nav-link">
            <span class="nav-icon">
              <?php echo renderIcon($item['icon']); ?>
            </span>
            <span class="nav-label"><?= $item['label'] ?></span>
            <?php if ($item['page'] === 'buy-plan'): ?>
              <span class="nav-badge hot">HOT</span>
            <?php endif; ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <!-- Bottom links -->
  <div class="sidebar-footer">
    <a href="https://t.me/digitaltaxaccounting" class="sidebar-ext-link" target="_blank">
      <span class="ext-icon telegram">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L7.17 13.67l-2.94-.918c-.64-.203-.652-.64.135-.954l11.49-4.43c.533-.194 1.0.13.839.853z"/></svg>
      </span>
      <span>Telegram App</span>
      <span class="nav-badge hot">HOT</span>
    </a>
    <a href="https://discord.gg/digitaltaxaccounting" class="sidebar-ext-link" target="_blank">
      <span class="ext-icon discord">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057c.001.022.015.04.033.05a19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994.021-.041.001-.09-.041-.106a13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>
      </span>
      <span>Join Our Discord</span>
      <span class="nav-badge hot">HOT</span>
    </a>
    <a href="/logout.php" class="sidebar-logout">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
      <span>Log Out</span>
    </a>
  </div>
</aside>

<?php
function renderIcon(string $name): string {
    $icons = [
        'grid'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
        'trending-up'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>',
        'trending-down'=> '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>',
        'map'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>',
        'bar-chart-2'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
        'file-text'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        'percent'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>',
        'star'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'settings'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
    ];
    return $icons[$name] ?? '';
}
?>

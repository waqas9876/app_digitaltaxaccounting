<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php require_once __DIR__.'/config/config.php'; require_once __DIR__.'/includes/auth.php'; echo generateCsrf(); ?>">
  <title>Notes — Digital Tax Accounting</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
  <style>
    .notes-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:18px; }
    .note-card {
      background:white; border-radius:14px; padding:0;
      border: 1px solid var(--gray-200);
      box-shadow:var(--shadow-sm);
      transition:all var(--transition);
      overflow:hidden;
      display:flex; flex-direction:column;
    }
    .note-card:hover { transform:translateY(-3px); box-shadow:var(--shadow); }
    .note-card.pinned { border-color:var(--orange); box-shadow:0 4px 16px rgba(255,116,33,.2); }
    .note-accent { height:4px; }
    .note-body { padding:16px; flex:1; }
    .note-title { font-size:15px; font-weight:700; color:var(--blue); margin-bottom:8px; line-height:1.3; }
    .note-content { font-size:13px; color:var(--gray-600); line-height:1.6; overflow:hidden; display:-webkit-box; -webkit-line-clamp:5; -webkit-box-orient:vertical; }
    .note-footer { padding:10px 16px; border-top:1px solid var(--gray-100); display:flex; align-items:center; justify-content:space-between; }
    .note-meta { font-size:11px; color:var(--gray-400); }
  </style>
</head>
<body>
<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireClientLogin();
$client   = getClient();
$clientId = (int)$_SESSION['client_id'];

$filter = $_GET['category'] ?? 'all';
$q      = isset($_GET['q']) ? trim($_GET['q']) : '';

$where  = 'client_id = ?';
$params = [$clientId];

if ($filter !== 'all') { $where .= ' AND category = ?'; $params[] = $filter; }
if ($q) { $where .= ' AND (title LIKE ? OR content LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }

$stmt = db()->prepare("SELECT * FROM notes WHERE $where ORDER BY is_pinned DESC, created_at DESC");
$stmt->execute($params);
$notes = $stmt->fetchAll();

$catStmt = db()->prepare("SELECT DISTINCT category FROM notes WHERE client_id=? ORDER BY category");
$catStmt->execute([$clientId]);
$categories = array_column($catStmt->fetchAll(), 'category');

$colors = ['#FF7421','#16295A','#10B981','#3B82F6','#F59E0B','#8B5CF6','#EC4899','#14B8A6'];
?>

<div class="app-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="main-content">
    <header class="top-header">
      <button class="header-menu-btn" id="menuBtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <div class="header-title">
        <h1>Notes</h1>
        <p><?= count($notes) ?> note<?= count($notes)!==1?'s':'' ?> · Stay organized with your tax notes</p>
      </div>
      <div class="header-actions">
        <button class="btn btn-primary btn-sm" data-open-modal="addNoteModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          New Note
        </button>
      </div>
    </header>

    <div class="page-body">
      <!-- Filter Bar -->
      <div style="display:flex;gap:10px;align-items:center;margin-bottom:20px;flex-wrap:wrap">
        <form method="GET" style="flex:1;min-width:220px">
          <div class="table-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="q" placeholder="Search notes..." value="<?= sanitize($q) ?>">
          </div>
        </form>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <a href="/notes.php" class="btn <?= $filter==='all'?'btn-secondary':'btn-ghost' ?> btn-sm">All</a>
          <?php foreach ($categories as $cat): ?>
            <a href="/notes.php?category=<?= urlencode($cat) ?>" class="btn <?= $filter===$cat?'btn-secondary':'btn-ghost' ?> btn-sm"><?= sanitize($cat) ?></a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Notes Grid -->
      <?php if (empty($notes)): ?>
        <div class="empty-state" style="padding:80px 24px">
          <div class="empty-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          </div>
          <h3>No notes yet</h3>
          <p><?= $q ? 'No notes match your search.' : 'Capture important tax-related notes here.' ?></p>
          <?php if (!$q): ?>
            <button class="btn btn-primary" data-open-modal="addNoteModal">Create First Note</button>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="notes-grid">
          <?php foreach ($notes as $i => $note): ?>
            <div class="note-card <?= $note['is_pinned'] ? 'pinned' : '' ?>">
              <div class="note-accent" style="background:<?= $note['color'] ?: $colors[$i % count($colors)] ?>"></div>
              <div class="note-body">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:8px">
                  <div class="note-title"><?= sanitize($note['title']) ?></div>
                  <?php if ($note['is_pinned']): ?>
                    <svg viewBox="0 0 24 24" fill="#FF7421" width="16" height="16" style="flex-shrink:0;margin-top:2px"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                  <?php endif; ?>
                </div>
                <div class="note-content"><?= nl2br(sanitize($note['content'])) ?></div>
              </div>
              <div class="note-footer">
                <div>
                  <span class="badge badge-blue" style="font-size:10px"><?= sanitize($note['category']) ?></span>
                  <span class="note-meta" style="margin-left:8px"><?= timeAgo($note['created_at']) ?></span>
                </div>
                <div style="display:flex;gap:4px">
                  <button class="btn btn-ghost btn-icon" onclick="pinNote(<?= $note['id'] ?>,<?= $note['is_pinned'] ?>)" data-tooltip="<?= $note['is_pinned'] ? 'Unpin' : 'Pin' ?>">
                    <svg viewBox="0 0 24 24" fill="<?= $note['is_pinned'] ? '#FF7421' : 'none' ?>" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                  </button>
                  <button class="btn btn-ghost btn-icon" onclick="editNote(<?= $note['id'] ?>)" data-tooltip="Edit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </button>
                  <button class="btn btn-ghost btn-icon" style="color:var(--danger)" onclick="deleteNote(<?= $note['id'] ?>)" data-tooltip="Delete">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Add Note Modal -->
<div class="modal-backdrop" id="addNoteModal">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <span class="modal-title">Create Note</span>
      <button class="modal-close" data-close-modal="addNoteModal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body">
      <form id="addNoteForm">
        <div class="form-group">
          <label class="form-label">Title *</label>
          <input type="text" name="title" class="form-control" placeholder="Note title..." required>
        </div>
        <div class="form-group">
          <label class="form-label">Content</label>
          <textarea name="content" class="form-control" rows="5" placeholder="Write your note here..."></textarea>
        </div>
        <div class="grid grid-2">
          <div class="form-group">
            <label class="form-label">Category</label>
            <input type="text" name="category" class="form-control" placeholder="General" value="General" list="catList">
            <datalist id="catList">
              <option>General</option><option>Tax Tips</option><option>Reminders</option>
              <option>Deductions</option><option>Documents</option><option>Deadlines</option>
            </datalist>
          </div>
          <div class="form-group">
            <label class="form-label">Color</label>
            <div style="display:flex;gap:8px;flex-wrap:wrap;padding-top:6px">
              <?php foreach ($colors as $c): ?>
                <label style="cursor:pointer">
                  <input type="radio" name="color" value="<?= $c ?>" style="display:none" <?= $c==='#FF7421'?'checked':'' ?>>
                  <div style="width:24px;height:24px;border-radius:50%;background:<?= $c ?>;border:2px solid transparent;transition:border-color .2s" onclick="this.previousElementSibling.checked=true;document.querySelectorAll('[name=color]').forEach(r=>r.parentElement.querySelector('div').style.borderColor=r.checked?'#16295A':'transparent')"></div>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label class="checkbox-wrap">
            <input type="checkbox" name="is_pinned" value="1">
            <span style="font-size:13px">Pin this note to the top</span>
          </label>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal="addNoteModal">Cancel</button>
      <button class="btn btn-primary" id="saveNoteBtn" onclick="saveNote()">Save Note</button>
    </div>
  </div>
</div>

<!-- Edit Note Modal -->
<div class="modal-backdrop" id="editNoteModal">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <span class="modal-title">Edit Note</span>
      <button class="modal-close" data-close-modal="editNoteModal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body">
      <form id="editNoteForm">
        <input type="hidden" name="id" id="editNoteId">
        <div class="form-group">
          <label class="form-label">Title *</label>
          <input type="text" name="title" id="editNoteTitle" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Content</label>
          <textarea name="content" id="editNoteContent" class="form-control" rows="5"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Category</label>
          <input type="text" name="category" id="editNoteCategory" class="form-control">
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal="editNoteModal">Cancel</button>
      <button class="btn btn-primary" onclick="updateNote()">Update Note</button>
    </div>
  </div>
</div>

<script src="/assets/js/main.js"></script>
<script>
async function saveNote() {
  const form = document.getElementById('addNoteForm');
  const fd   = new FormData(form);
  const data = Object.fromEntries(fd);
  data.is_pinned = fd.has('is_pinned') ? 1 : 0;
  if (!data.title) { showAlert('Title required.','danger'); return; }
  const btn = document.getElementById('saveNoteBtn');
  setLoading(btn, true);
  const result = await apiCall('/api/notes.php', { action:'create', ...data });
  setLoading(btn, false);
  if (result.success) { toast('Note saved!','success'); setTimeout(()=>location.reload(),700); }
  else showAlert(result.message,'danger');
}

async function editNote(id) {
  const result = await apiCall('/api/notes.php', { action:'get', id });
  if (result.success) {
    const r = result.data;
    document.getElementById('editNoteId').value = r.id;
    document.getElementById('editNoteTitle').value = r.title;
    document.getElementById('editNoteContent').value = r.content || '';
    document.getElementById('editNoteCategory').value = r.category;
    openModal('editNoteModal');
  }
}

async function updateNote() {
  const data = Object.fromEntries(new FormData(document.getElementById('editNoteForm')));
  const result = await apiCall('/api/notes.php', { action:'update', ...data });
  if (result.success) { toast('Updated!','success'); setTimeout(()=>location.reload(),700); }
  else toast(result.message,'danger');
}

async function pinNote(id, isPinned) {
  await apiCall('/api/notes.php', { action:'pin', id, is_pinned: isPinned ? 0 : 1 });
  location.reload();
}

async function deleteNote(id) {
  if (!confirm('Delete this note?')) return;
  const result = await apiCall('/api/notes.php', { action:'delete', id });
  if (result.success) { toast('Deleted!','success'); setTimeout(()=>location.reload(),600); }
}
</script>
</body>
</html>

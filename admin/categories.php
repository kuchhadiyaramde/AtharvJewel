<?php
require_once 'includes/auth.php';
require_once __DIR__ . '/../config.php';

$conn = getDBConnection();
$action = $_GET['action'] ?? 'list';
$editId = (int) ($_GET['id'] ?? 0);
$flash  = '';
$flashType = 'success';

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    $name       = trim($_POST['name'] ?? '');
    $slug       = trim($_POST['slug'] ?? '');
    $image      = trim($_POST['image'] ?? '');
    $sortOrder  = (int) ($_POST['sort_order'] ?? 0);
    $id         = (int) ($_POST['id'] ?? 0);

    if ($postAction === 'add') {
        if ($name === '' || $slug === '') {
            $flash = 'Name and slug are required.'; $flashType = 'danger';
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name, slug, image, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('sssi', $name, $slug, $image, $sortOrder);
            if ($stmt->execute()) {
                $flash = "Category <strong>" . htmlspecialchars($name) . "</strong> added.";
            } else {
                $flash = 'Error: ' . htmlspecialchars($conn->error); $flashType = 'danger';
            }
            $stmt->close();
        }
    } elseif ($postAction === 'update' && $id > 0) {
        if ($name === '' || $slug === '') {
            $flash = 'Name and slug are required.'; $flashType = 'danger';
            $action = 'edit'; $editId = $id;
        } else {
            $stmt = $conn->prepare("UPDATE categories SET name=?, slug=?, image=?, sort_order=? WHERE id=?");
            $stmt->bind_param('sssii', $name, $slug, $image, $sortOrder, $id);
            if ($stmt->execute()) {
                $flash = "Category updated.";
            } else {
                $flash = 'Error: ' . htmlspecialchars($conn->error); $flashType = 'danger';
            }
            $stmt->close();
        }
    } elseif ($postAction === 'delete' && $id > 0) {
        // Check if category has products
        $check = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $check->bind_param('i', $id);
        $check->execute();
        $productCount = (int) $check->get_result()->fetch_row()[0];
        $check->close();
        if ($productCount > 0) {
            $flash = "Cannot delete — this category has $productCount product(s). Remove or reassign them first.";
            $flashType = 'danger';
        } else {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $flash = 'Category deleted.';
            $stmt->close();
        }
    }

    if ($action === 'list' || ($postAction !== 'delete' && $flash && $flashType === 'success')) {
        header('Location: categories.php' . ($flash ? '?msg=' . urlencode($flash) . '&type=' . $flashType : ''));
        exit;
    }
}

if (isset($_GET['msg'])) {
    $flash = htmlspecialchars(urldecode($_GET['msg']));
    $flashType = $_GET['type'] ?? 'success';
}

// ── Fetch data ────────────────────────────────────────────────────────────────
$editRow = null;
if ($action === 'edit' && $editId > 0) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$editRow) { header('Location: categories.php'); exit; }
}

$categories = $conn->query("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id ORDER BY c.sort_order, c.name
");

$pageTitle = 'Categories';
require_once 'includes/header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flashType ?> alert-dismissible fade show">
        <?= $flash ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3">
    <!-- Form panel -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">
                <?= ($action === 'edit') ? 'Edit Category' : 'Add New Category' ?>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="<?= ($action === 'edit') ? 'update' : 'add' ?>">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?= $editRow['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($editRow['name'] ?? $_POST['name'] ?? '') ?>"
                               id="catName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Slug <span class="text-danger">*</span>
                            <span class="text-muted fw-normal">(URL-friendly)</span></label>
                        <input type="text" name="slug" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($editRow['slug'] ?? $_POST['slug'] ?? '') ?>"
                               id="catSlug" pattern="[a-z0-9\-]+" required>
                        <div class="form-text">Lowercase letters, numbers, hyphens only.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Image Path</label>
                        <input type="text" name="image" class="form-control form-control-sm"
                               placeholder="photos/01.jpg"
                               value="<?= htmlspecialchars($editRow['image'] ?? $_POST['image'] ?? '') ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm" min="0"
                               value="<?= htmlspecialchars($editRow['sort_order'] ?? $_POST['sort_order'] ?? 0) ?>">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger btn-sm flex-grow-1">
                            <?= ($action === 'edit') ? 'Update Category' : 'Add Category' ?>
                        </button>
                        <?php if ($action === 'edit'): ?>
                            <a href="categories.php" class="btn btn-outline-secondary btn-sm">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Categories table -->
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>All Categories</span>
                <span class="badge bg-secondary"><?= $categories->num_rows ?></span>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Category</th>
                            <th>Slug</th>
                            <th>Sort</th>
                            <th>Products</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $categories->fetch_assoc()): ?>
                        <tr <?= ($editId === (int)$row['id']) ? 'class="table-warning"' : '' ?>>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="../<?= htmlspecialchars($row['image']) ?>"
                                         style="width:32px;height:32px;object-fit:cover;border-radius:.3rem;"
                                         onerror="this.src='data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'32\' height=\'32\'><rect fill=\'%23ddd\' width=\'32\' height=\'32\'/></svg>'">
                                    <span class="fw-semibold small"><?= htmlspecialchars($row['name']) ?></span>
                                </div>
                            </td>
                            <td class="small text-muted align-middle"><?= htmlspecialchars($row['slug']) ?></td>
                            <td class="align-middle"><?= $row['sort_order'] ?></td>
                            <td class="align-middle">
                                <span class="badge bg-light text-dark border"><?= $row['product_count'] ?></span>
                            </td>
                            <td class="align-middle">
                                <a href="categories.php?action=edit&id=<?= $row['id'] ?>"
                                   class="btn btn-sm btn-outline-secondary py-0 px-2 me-1">Edit</a>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2"
                                            data-confirm="Delete category '<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>'? This cannot be undone.">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-generate slug from name
document.getElementById('catName').addEventListener('input', function () {
    const slugField = document.getElementById('catSlug');
    if (slugField.dataset.manual) return;
    slugField.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
});
document.getElementById('catSlug').addEventListener('input', function () {
    this.dataset.manual = '1';
});
</script>

<?php require_once 'includes/footer.php'; ?>

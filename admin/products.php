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
    $postAction  = $_POST['action'] ?? '';
    $name        = trim($_POST['name'] ?? '');
    $categoryId  = (int) ($_POST['category_id'] ?? 0);
    $currPrice   = trim($_POST['current_price'] ?? '');
    $origPrice   = trim($_POST['original_price'] ?? '');
    $rating      = min(5.0, max(0.0, (float) ($_POST['rating'] ?? 0)));
    $description = trim($_POST['description'] ?? '');
    $image       = trim($_POST['image'] ?? '');
    $isNew       = isset($_POST['is_new']) ? 1 : 0;
    $isActive    = isset($_POST['is_active']) ? 1 : 0;
    $id          = (int) ($_POST['id'] ?? 0);

    if ($postAction === 'add') {
        if ($name === '' || $categoryId < 1 || $currPrice === '') {
            $flash = 'Name, category and current price are required.'; $flashType = 'danger';
            $action = 'add';
        } else {
            $stmt = $conn->prepare("
                INSERT INTO products
                    (name, category_id, current_price, original_price, rating, description, image, is_new, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('sissdssii', $name, $categoryId, $currPrice, $origPrice,
                              $rating, $description, $image, $isNew, $isActive);
            if ($stmt->execute()) {
                $flash = "Product <strong>" . htmlspecialchars($name) . "</strong> added successfully.";
            } else {
                $flash = 'DB error: ' . htmlspecialchars($conn->error); $flashType = 'danger';
            }
            $stmt->close();
        }
    } elseif ($postAction === 'update' && $id > 0) {
        if ($name === '' || $categoryId < 1 || $currPrice === '') {
            $flash = 'Name, category and current price are required.'; $flashType = 'danger';
            $action = 'edit'; $editId = $id;
        } else {
            $stmt = $conn->prepare("
                UPDATE products SET
                    name=?, category_id=?, current_price=?, original_price=?,
                    rating=?, description=?, image=?, is_new=?, is_active=?
                WHERE id=?
            ");
            $stmt->bind_param('sissdssiii', $name, $categoryId, $currPrice, $origPrice,
                              $rating, $description, $image, $isNew, $isActive, $id);
            if ($stmt->execute()) {
                $flash = "Product updated successfully.";
            } else {
                $flash = 'DB error: ' . htmlspecialchars($conn->error); $flashType = 'danger';
            }
            $stmt->close();
        }
    } elseif ($postAction === 'delete' && $id > 0) {
        $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $flash = 'Product deleted.';
        $stmt->close();
    }

    if ($action === 'list' || ($flashType === 'success')) {
        header('Location: products.php?msg=' . urlencode(strip_tags($flash)) . '&type=' . $flashType);
        exit;
    }
}

if (isset($_GET['msg'])) {
    $flash = htmlspecialchars(urldecode($_GET['msg']));
    $flashType = in_array($_GET['type'] ?? '', ['success','danger','warning']) ? $_GET['type'] : 'success';
}

// ── Fetch edit row ────────────────────────────────────────────────────────────
$editRow = null;
if (in_array($action, ['edit', 'add']) && $action === 'edit' && $editId > 0) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$editRow) { header('Location: products.php'); exit; }
}

// Fetch categories for dropdown
$catResult = $conn->query("SELECT id, name FROM categories ORDER BY sort_order, name");
$categoryOptions = [];
while ($c = $catResult->fetch_assoc()) $categoryOptions[] = $c;

// Fetch all products with category name
$filterCat = (int) ($_GET['cat'] ?? 0);
if ($filterCat > 0) {
    $stmt = $conn->prepare("
        SELECT p.*, c.name AS cat_name FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.category_id = ? ORDER BY p.created_at DESC
    ");
    $stmt->bind_param('i', $filterCat);
    $stmt->execute();
    $products = $stmt->get_result();
    $stmt->close();
} else {
    $products = $conn->query("
        SELECT p.*, c.name AS cat_name FROM products p
        JOIN categories c ON p.category_id = c.id
        ORDER BY p.created_at DESC
    ");
}

$pageTitle = 'Products';
require_once 'includes/header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flashType ?> alert-dismissible fade show">
        <?= $flash ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (in_array($action, ['add', 'edit'])): ?>
<!-- ── Add / Edit Form ──────────────────────────────────────────────────────── -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between">
        <span><?= $action === 'edit' ? 'Edit Product' : 'Add New Product' ?></span>
        <a href="products.php" class="btn btn-sm btn-outline-secondary">← Back to List</a>
    </div>
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="action" value="<?= $action === 'edit' ? 'update' : 'add' ?>">
            <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?= $editRow['id'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label small fw-semibold">Product Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= htmlspecialchars($editRow['name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">— Select —</option>
                        <?php foreach ($categoryOptions as $cat): ?>
                            <option value="<?= $cat['id'] ?>"
                                <?= (isset($editRow['category_id']) && $editRow['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Current Price <span class="text-danger">*</span></label>
                    <input type="text" name="current_price" class="form-control" placeholder="₹65,455" required
                           value="<?= htmlspecialchars($editRow['current_price'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Original Price</label>
                    <input type="text" name="original_price" class="form-control" placeholder="₹78,154"
                           value="<?= htmlspecialchars($editRow['original_price'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Rating <span class="text-muted">(0–5)</span></label>
                    <input type="number" name="rating" class="form-control" min="0" max="5" step="0.1"
                           value="<?= htmlspecialchars($editRow['rating'] ?? '0') ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label small fw-semibold">Image Path</label>
                    <input type="text" name="image" class="form-control" placeholder="photos/01.jpg"
                           value="<?= htmlspecialchars($editRow['image'] ?? '') ?>">
                    <div class="form-text">Relative path from site root, e.g. <code>photos/01.jpg</code></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">&nbsp;</label>
                    <div class="d-flex flex-column gap-2 mt-1">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_new" id="chkNew" value="1"
                                   <?= !empty($editRow['is_new']) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="chkNew">Mark as "New"</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="chkActive" value="1"
                                   <?= (!isset($editRow['is_active']) || $editRow['is_active']) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="chkActive">Active (visible on site)</label>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editRow['description'] ?? '') ?></textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-danger">
                        <?= $action === 'edit' ? 'Update Product' : 'Add Product' ?>
                    </button>
                    <a href="products.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ── Products Table ─────────────────────────────────────────────────────────── -->
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="fw-semibold">
            All Products
            <span class="badge bg-secondary ms-1"><?= $products->num_rows ?></span>
        </span>
        <div class="d-flex gap-2 align-items-center">
            <!-- Category filter -->
            <form method="get" class="d-flex gap-1">
                <select name="cat" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categoryOptions as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $filterCat === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <a href="products.php?action=add" class="btn btn-danger btn-sm">
                <i class="fa fa-plus me-1"></i>Add Product
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Rating</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($products->num_rows === 0): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No products found.</td></tr>
                <?php endif; ?>
                <?php while ($row = $products->fetch_assoc()): ?>
                    <tr <?= (!$row['is_active']) ? 'class="table-secondary"' : '' ?>>
                        <td class="text-muted small align-middle"><?= $row['id'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="../<?= htmlspecialchars($row['image']) ?>"
                                     style="width:40px;height:40px;object-fit:cover;border-radius:.4rem;"
                                     onerror="this.src='data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'40\' height=\'40\'><rect fill=\'%23ddd\' width=\'40\' height=\'40\'/></svg>'">
                                <div>
                                    <div class="small fw-semibold"><?= htmlspecialchars($row['name']) ?></div>
                                    <div class="d-flex gap-1 mt-1">
                                        <?php if ($row['is_new']): ?>
                                            <span class="badge text-bg-success" style="font-size:.65rem;">New</span>
                                        <?php endif; ?>
                                        <?php if (!$row['is_active']): ?>
                                            <span class="badge text-bg-secondary" style="font-size:.65rem;">Hidden</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="small text-muted align-middle"><?= htmlspecialchars($row['cat_name']) ?></td>
                        <td class="align-middle">
                            <div class="small fw-semibold"><?= htmlspecialchars($row['current_price']) ?></div>
                            <div class="small text-muted text-decoration-line-through"><?= htmlspecialchars($row['original_price']) ?></div>
                        </td>
                        <td class="align-middle">
                            <span class="text-warning">★</span>
                            <span class="small"><?= number_format($row['rating'], 1) ?></span>
                        </td>
                        <td class="align-middle">
                            <?php if ($row['is_active']): ?>
                                <span class="badge text-bg-success" style="font-size:.7rem;">Active</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary" style="font-size:.7rem;">Hidden</span>
                            <?php endif; ?>
                        </td>
                        <td class="align-middle">
                            <a href="products.php?action=edit&id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-secondary py-0 px-2 me-1">Edit</a>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2"
                                        data-confirm="Delete '<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>'? This cannot be undone.">
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

<?php require_once 'includes/footer.php'; ?>

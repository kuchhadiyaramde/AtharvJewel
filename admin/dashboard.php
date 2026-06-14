<?php
require_once 'includes/auth.php';
require_once __DIR__ . '/../config.php';

$pageTitle = 'Dashboard';
$conn = getDBConnection();

$totalProducts   = (int) $conn->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetch_row()[0];
$totalCategories = (int) $conn->query("SELECT COUNT(*) FROM categories")->fetch_row()[0];
$newProducts     = (int) $conn->query("SELECT COUNT(*) FROM products WHERE is_new = 1 AND is_active = 1")->fetch_row()[0];
$totalProducts0  = (int) $conn->query("SELECT COUNT(*) FROM products WHERE is_active = 0")->fetch_row()[0];

$recentResult = $conn->query("
    SELECT p.id, p.name, p.current_price, p.is_new, p.image, c.name AS cat_name
    FROM products p JOIN categories c ON p.category_id = c.id
    WHERE p.is_active = 1
    ORDER BY p.created_at DESC LIMIT 5
");

require_once 'includes/header.php';
?>

<!-- Stats row -->
<div class="row g-3 mb-4">
    <?php
    $stats = [
        ['label' => 'Active Products',    'value' => $totalProducts,   'icon' => 'fa-boxes-stacked', 'bg' => 'rgba(221,36,118,.1)',  'color' => '#dd2476'],
        ['label' => 'Categories',         'value' => $totalCategories, 'icon' => 'fa-tags',           'bg' => 'rgba(255,81,47,.1)',   'color' => '#ff512f'],
        ['label' => '"New" Tagged',        'value' => $newProducts,     'icon' => 'fa-star',           'bg' => 'rgba(255,193,7,.1)',   'color' => '#ffc107'],
        ['label' => 'Hidden Products',    'value' => $totalProducts0,  'icon' => 'fa-eye-slash',      'bg' => 'rgba(108,117,125,.1)', 'color' => '#6c757d'],
    ];
    foreach ($stats as $s):
    ?>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon" style="background:<?= $s['bg'] ?>; color:<?= $s['color'] ?>;">
                    <i class="fa <?= $s['icon'] ?>"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= $s['value'] ?></div>
                    <div class="text-muted small"><?= $s['label'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Quick Actions + Recent Products -->
<div class="row g-3">
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Quick Actions</div>
            <div class="card-body d-grid gap-2">
                <a href="products.php?action=add" class="btn btn-sm btn-outline-danger">
                    <i class="fa fa-plus me-1"></i> Add New Product
                </a>
                <a href="categories.php?action=add" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-plus me-1"></i> Add New Category
                </a>
                <a href="products.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-list me-1"></i> Manage Products
                </a>
                <a href="categories.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-tags me-1"></i> Manage Categories
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Recent Products</span>
                <a href="products.php" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $recentResult->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="../<?= htmlspecialchars($row['image']) ?>"
                                         style="width:36px;height:36px;object-fit:cover;border-radius:.3rem;"
                                         onerror="this.src='data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'36\' height=\'36\'><rect fill=\'%23ddd\' width=\'36\' height=\'36\'/></svg>'">
                                    <div>
                                        <div class="small fw-semibold"><?= htmlspecialchars($row['name']) ?></div>
                                        <?php if ($row['is_new']): ?>
                                            <span class="badge badge-new">New</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="small text-muted align-middle"><?= htmlspecialchars($row['cat_name']) ?></td>
                            <td class="small align-middle"><?= htmlspecialchars($row['current_price']) ?></td>
                            <td class="align-middle">
                                <a href="products.php?action=edit&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2">Edit</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

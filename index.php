<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

?>

<!-- Hero Section -->
<div class="bg-light py-5 text-center mb-5">
    <h1 class="display-4">Welcome to Our Gadget Store</h1>
    <p class="lead">Discover the latest gadgets at unbeatable prices.</p>
    <a href="<?= BASE_URL ?>product.php" class="btn btn-primary btn-lg">Shop Gadgets</a>
</div>

<div class="container">

<?php
// Featured products
$featured_sql = "SELECT id, name, price, image 
                 FROM products 
                 WHERE is_featured = 1 
                 LIMIT 6";
$featured_result = $conn->query($featured_sql);

if ($featured_result && $featured_result->num_rows > 0):
?>
<h2>Featured Products</h2>
<div class="row mb-5">
<?php while ($row = $featured_result->fetch_assoc()): ?>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <img src="<?= BASE_URL ?>assets/images/<?= e($row['image']) ?>" class="card-img-top" alt="<?= e($row['name']) ?>" style="object-fit:cover; height:200px;">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title"><?= e($row['name']) ?></h5>
                <p class="card-text">$<?= number_format($row['price'], 2) ?></p>
                <a href="<?= BASE_URL ?>product_detail.php?product_id=<?= (int)$row['id'] ?>" class="btn btn-primary mt-auto">View</a>
            </div>
        </div>
    </div>
<?php endwhile; ?>
</div>
<?php else: ?>
<p class="text-muted">No featured products available.</p>
<?php endif; ?>

<?php
// Latest products
$latest_sql = "SELECT id, name, price, image 
               FROM products 
               ORDER BY created_at DESC 
               LIMIT 6";
$latest_result = $conn->query($latest_sql);

if ($latest_result && $latest_result->num_rows > 0):
?>
<h2>Latest Products</h2>
<div class="row mb-5">
<?php while ($row = $latest_result->fetch_assoc()): ?>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <img src="<?= BASE_URL ?>assets/images/<?= e($row['image']) ?>" class="card-img-top" alt="<?= e($row['name']) ?>" style="object-fit:cover; height:200px;">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title"><?= e($row['name']) ?></h5>
                <p class="card-text"><?= format_currency($row['price']) ?></p>
                <a href="<?= BASE_URL ?>product_detail.php?product_id=<?= (int)$row['id'] ?>" class="btn btn-primary mt-auto">View</a>
            </div>
        </div>
    </div>
<?php endwhile; ?>
</div>
<?php else: ?>
<p class="text-muted">No latest products available.</p>
<?php endif; ?>

<?php include_once 'includes/footer.php'; ?>

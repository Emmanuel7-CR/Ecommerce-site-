<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

// fetch products
$stmt = $conn->prepare("SELECT id, name, description, price, image FROM products ORDER BY created_at DESC");
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<style>
/* Hover overlay + zoom effect for product cards */
.card-hover {
    position: relative;
    overflow: hidden;
    transition: transform 0.3s ease;
}

.card-hover img {
    transition: transform 0.3s ease;
}

.card-hover:hover img {
    transform: scale(1.05);
}

.card-hover .overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    color: #fff;
    display: flex;
    justify-content: center;
    align-items: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    text-align: center;
    padding: 10px;
}

.card-hover:hover .overlay {
    opacity: 1;
}
</style>

<div class="container mt-4">
    <h1 class="mb-4">Our Gadgets</h1>

    <?php if ($msg = get_flash()): ?>
        <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <div class="alert alert-secondary">No products available at the moment.</div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($products as $product): ?>
                <div class="col">
                    <a href="<?= BASE_URL ?>product_detail.php?product_id=<?= (int)$product['id'] ?>" class="text-decoration-none text-dark">
                        <div class="card h-100 card-hover">
                            <img 
                                src="<?= !empty($product['image']) && file_exists(__DIR__ . '/uploads/' . $product['image']) 
                                        ? '/uploads/' . htmlspecialchars($product['image']) 
                                        : '/assets/images/placeholder.png' ?>" 
                                class="card-img-top" 
                                style="height:200px;object-fit:cover;" 
                                alt="<?= htmlspecialchars($product['name']) ?>"
                            >
                            <div class="overlay">
                                <span class="btn btn-light">View Details</span>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                <p class="card-text text-truncate"><?= htmlspecialchars($product['description']) ?></p>
                                <p class="fw-bold mt-auto"><?= format_currency($product['price']) ?></p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

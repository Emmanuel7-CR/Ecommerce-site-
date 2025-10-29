<?php
// admin/add_product.php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_auth.php';



require_admin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        error_log("CSRF token validation failed on add_product from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        flash('Invalid request.');
        header('Location: /admin/add_product.php');
        exit;
    }

    // Sanitize and validate inputs
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = $_POST['price'] ?? '';
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    if ($name === '' || !is_numeric($price) || (float)$price < 0) {
        flash('Please provide valid product details.');
        header('Location: /admin/add_product.php');
        exit;
    }

    // Secure image upload (optional)
    $imageName = null;
    if (!empty($_FILES['image']['name'])) {
        $imageName = handle_uploaded_image($_FILES['image']);  // helper in functions.php
        if ($imageName === false) {
            flash('Invalid or oversized image file.');
            header('Location: /admin/add_product.php');
            exit;
        }
    }

    // Insert product into DB
    try {
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, image, is_featured, created_at)
                                VALUES (?, ?, ?, ?, ?, NOW())");
        if (!$stmt) {
            throw new Exception("Prepare failed for products: " . $conn->error);
        }

        $p_price = (float)$price;
        $stmt->bind_param('ssdsi', $name, $description, $p_price, $imageName, $is_featured);

        if (!$stmt->execute()) {
            throw new Exception("Execute failed for products: " . $stmt->error);
        }

        $stmt->close();

        // Log success
        error_log("Product added: {$name}, Admin ID: {$_SESSION['admin_id']}");

        flash('Product added successfully.');
        header('Location: /admin/products.php');
        exit;

    } catch (Exception $e) {
        error_log("Add product failed: " . $e->getMessage());
        flash('Unable to add product. Please try again.');
        header('Location: /admin/add_product.php');
        exit;
    }
}
?>

<h1>Add Product</h1>

<?php if ($msg = get_flash()): ?>
  <div class="alert alert-info"><?= e($msg) ?></div>
<?php endif; ?>

<form method="post" action="/admin/add_product.php" enctype="multipart/form-data" class="row g-3">
    <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">

    <div class="col-md-6">
        <label class="form-label">Product Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>

    <div class="col-md-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="4"></textarea>
    </div>

    <div class="col-md-4">
        <label class="form-label">Price</label>
        <input type="number" step="0.01" name="price" class="form-control" required>
    </div>

    <div class="col-md-4">
        <label class="form-label">Image</label>
        <input type="file" name="image" class="form-control">
    </div>

    <div class="col-md-4 d-flex align-items-center">
        <div class="form-check mt-3">
            <input type="checkbox" class="form-check-input" name="is_featured" id="is_featured">
            <label for="is_featured" class="form-check-label">Featured Product</label>
        </div>
    </div>

    <div class="col-12 text-end">
        <button class="btn btn-primary">Add Product</button>
    </div>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

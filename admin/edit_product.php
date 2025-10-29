<?php
require_once __DIR__.'/header.php';
require_once __DIR__.'/../includes/functions.php'; // for handle_uploaded_image

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);

    if ($name === '' || $price <= 0) $errors[] = 'Provide valid name and price.';

    $imageName = null;
    if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploaded = handle_uploaded_image($_FILES['image']);
        if ($uploaded === false) $errors[] = 'Image upload failed or invalid image.';
        else $imageName = $uploaded;
    }

    if (empty($errors)) {
        if (isset($_POST['id']) && (int)$_POST['id'] > 0) {
            $pid = (int)$_POST['id'];
            if ($imageName) {
                $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, image=? WHERE id=?");
                $stmt->bind_param('ssdsi', $name, $desc, $price, $imageName, $pid);
            } else {
                $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=? WHERE id=?");
                $stmt->bind_param('ssdi', $name, $desc, $price, $pid);
            }
            $stmt->execute();
            $stmt->close();
            flash('Product updated.');
        } else {
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, image, created_at) VALUES (?, ?, ?, ?, NOW())");
            $img = $imageName ?? null;
            $stmt->bind_param('ssds', $name, $desc, $price, $img);
            $stmt->execute();
            $stmt->close();
            flash('Product created.');
        }
        header('Location: products.php');
        exit;
    }
}

// load existing if editing
$product = null;
if ($id > 0) {
    $stmt = $conn->prepare("SELECT id, name, description, price, image FROM products WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<h1><?= $product ? 'Edit' : 'Add' ?> Product</h1>
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
  <ul><?php foreach($errors as $e) echo "<li>".e($e)."</li>"; ?></ul>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <?php if ($product): ?>
    <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
  <?php endif; ?>
  <div class="mb-3">
    <label class="form-label">Name</label>
    <input class="form-control" name="name" value="<?= e($product['name'] ?? '') ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea class="form-control" name="description"><?= e($product['description'] ?? '') ?></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Price</label>
    <input class="form-control" name="price" value="<?= e($product['price'] ?? '') ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Image (optional)</label>
    <input type="file" name="image" class="form-control">
    <?php if (!empty($product['image'])): ?>
      <img src="<?= BASE_URL ?>assets/images/<?= e($product['image']) ?>" style="height:80px;margin-top:8px">
    <?php endif; ?>
  </div>
  <button class="btn btn-primary">Save</button>
  <a class="btn btn-secondary" href="products.php">Cancel</a>
</form>

<?php require_once __DIR__.'/footer.php'; ?>

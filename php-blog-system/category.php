<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if ($action === 'add' && $name) {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$name]);
        $message = "Category added successfully.";
    } elseif ($action === 'edit' && $id && $name) {
        $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        $message = "Category updated successfully.";
    }
    header("Location: category.php?message=" . urlencode($message));
    exit;
}

if ($action === 'delete' && $id) {
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $message = "Category deleted successfully.";
    header("Location: category.php?message=" . urlencode($message));
    exit;
}

$message = $_GET['message'] ?? '';

$stmt = $pdo->query("SELECT * FROM categories ORDER BY created_at DESC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Categories - Blog Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">Blog Management</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link active" href="category.php">Categories</a></li>
        <li class="nav-item"><a class="nav-link" href="blog.php">Blogs</a></li>
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
    <h2>Manage Categories</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($action === 'add' || ($action === 'edit' && $id)): 
        $category = null;
        if ($action === 'edit' && $id) {
            $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    ?>
        <form method="POST" action="?action=<?= htmlspecialchars($action) ?><?= $id ? '&id=' . intval($id) : '' ?>">
            <div class="mb-3">
                <label for="name" class="form-label">Category Name</label>
                <input type="text" name="name" id="name" class="form-control" required value="<?= htmlspecialchars($category['name'] ?? '') ?>" />
            </div>
            <button type="submit" class="btn btn-primary"><?= $action === 'add' ? 'Add' : 'Update' ?> Category</button>
            <a href="category.php" class="btn btn-secondary">Cancel</a>
        </form>
    <?php else: ?>
        <a href="?action=add" class="btn btn-success mb-3">Add New Category</a>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= htmlspecialchars($cat['id']) ?></td>
                        <td><?= htmlspecialchars($cat['name']) ?></td>
                        <td><?= htmlspecialchars($cat['created_at']) ?></td>
                        <td>
                            <a href="?action=edit&id=<?= $cat['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                            <a href="?action=delete&id=<?= $cat['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure to delete this category?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="4" class="text-center">No categories found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>

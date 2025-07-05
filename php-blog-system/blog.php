<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seo_title = trim($_POST['seo_title'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $slug = generateSlug($name);
    $status = $_POST['status'] ?? 'draft';
    $content = $_POST['content'] ?? '';
    $date = $_POST['date'] ?? '';
    $author = trim($_POST['author'] ?? '');
    $seo_keyword = trim($_POST['seo_keyword'] ?? '');
    $seo_description = trim($_POST['seo_description'] ?? '');
    $category_id = $_POST['category_id'] ?? null;

    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = basename($_FILES['image']['name']);
        $targetFilePath = $uploadDir . time() . '_' . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
            $image = $targetFilePath;
        }
    }

    if ($action === 'add' && $name) {
        $stmt = $pdo->prepare("INSERT INTO blogs (seo_title, name, slug, status, content, image, date, author, seo_keyword, seo_description, category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$seo_title, $name, $slug, $status, $content, $image, $date, $author, $seo_keyword, $seo_description, $category_id]);
        $message = "Blog added successfully.";
    } elseif ($action === 'edit' && $id && $name) {
        if ($image) {
            $stmt = $pdo->prepare("UPDATE blogs SET seo_title=?, name=?, slug=?, status=?, content=?, image=?, date=?, author=?, seo_keyword=?, seo_description=?, category_id=? WHERE id=?");
            $stmt->execute([$seo_title, $name, $slug, $status, $content, $image, $date, $author, $seo_keyword, $seo_description, $category_id, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE blogs SET seo_title=?, name=?, slug=?, status=?, content=?, date=?, author=?, seo_keyword=?, seo_description=?, category_id=? WHERE id=?");
            $stmt->execute([$seo_title, $name, $slug, $status, $content, $date, $author, $seo_keyword, $seo_description, $category_id, $id]);
        }
        $message = "Blog updated successfully.";
    }
    header("Location: blog.php?message=" . urlencode($message));
    exit;
}

if ($action === 'delete' && $id) {
    $stmt = $pdo->prepare("DELETE FROM blogs WHERE id = ?");
    $stmt->execute([$id]);
    $message = "Blog deleted successfully.";
    header("Location: blog.php?message=" . urlencode($message));
    exit;
}

$message = $_GET['message'] ?? '';

$stmt = $pdo->query("SELECT b.*, c.name as category_name FROM blogs b LEFT JOIN categories c ON b.category_id = c.id ORDER BY b.created_at DESC");
$blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtCat = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Blogs - Blog Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.ckeditor.com/4.21.0/standard/ckeditor.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">Blog Management</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="category.php">Categories</a></li>
        <li class="nav-item"><a class="nav-link active" href="blog.php">Blogs</a></li>
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
    <h2>Manage Blogs</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($action === 'add' || ($action === 'edit' && $id)): 
        $blog = null;
        if ($action === 'edit' && $id) {
            $stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = ?");
            $stmt->execute([$id]);
            $blog = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    ?>
        <form method="POST" action="?action=<?= htmlspecialchars($action) ?><?= $id ? '&id=' . intval($id) : '' ?>" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="seo_title" class="form-label">SEO Title</label>
                <input type="text" name="seo_title" id="seo_title" class="form-control" value="<?= htmlspecialchars($blog['seo_title'] ?? '') ?>" />
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" name="name" id="name" class="form-control" required value="<?= htmlspecialchars($blog['name'] ?? '') ?>" />
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="draft" <?= (isset($blog['status']) && $blog['status'] === 'draft') ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= (isset($blog['status']) && $blog['status'] === 'published') ? 'selected' : '' ?>>Published</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="content" class="form-label">Blog Content</label>
                <textarea name="content" id="content" class="form-control" rows="6"><?= htmlspecialchars($blog['content'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Main Image</label>
                <input type="file" name="image" id="image" class="form-control" />
                <?php if (!empty($blog['image'])): ?>
                    <img src="<?= htmlspecialchars($blog['image']) ?>" alt="Blog Image" class="img-thumbnail mt-2" style="max-width: 200px;" />
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" name="date" id="date" class="form-control" value="<?= htmlspecialchars($blog['date'] ?? '') ?>" />
            </div>
            <div class="mb-3">
                <label for="author" class="form-label">Author</label>
                <input type="text" name="author" id="author" class="form-control" value="<?= htmlspecialchars($blog['author'] ?? '') ?>" />
            </div>
            <div class="mb-3">
                <label for="seo_keyword" class="form-label">SEO Keyword</label>
                <input type="text" name="seo_keyword" id="seo_keyword" class="form-control" value="<?= htmlspecialchars($blog['seo_keyword'] ?? '') ?>" />
            </div>
            <div class="mb-3">
                <label for="seo_description" class="form-label">SEO Description</label>
                <textarea name="seo_description" id="seo_description" class="form-control" rows="3"><?= htmlspecialchars($blog['seo_description'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label for="category_id" class="form-label">Category</label>
                <select name="category_id" id="category_id" class="form-select">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (isset($blog['category_id']) && $blog['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><?= $action === 'add' ? 'Add' : 'Update' ?> Blog</button>
            <a href="blog.php" class="btn btn-secondary">Cancel</a>
        </form>
        <script>
            CKEDITOR.replace('content');
        </script>
    <?php else: ?>
        <a href="?action=add" class="btn btn-success mb-3">Add New Blog</a>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>SEO Title</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Category</th>
                    <th>Date</th>
                    <th>Author</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($blogs as $blog): ?>
                    <tr>
                        <td><?= htmlspecialchars($blog['id']) ?></td>
                        <td><?= htmlspecialchars($blog['seo_title']) ?></td>
                        <td><?= htmlspecialchars($blog['name']) ?></td>
                        <td><?= htmlspecialchars($blog['slug']) ?></td>
                        <td><?= htmlspecialchars($blog['status']) ?></td>
                        <td><?= htmlspecialchars($blog['category_name']) ?></td>
                        <td><?= htmlspecialchars($blog['date']) ?></td>
                        <td><?= htmlspecialchars($blog['author']) ?></td>
                        <td>
                            <a href="?action=edit&id=<?= $blog['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                            <a href="?action=delete&id=<?= $blog['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure to delete this blog?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($blogs)): ?>
                    <tr><td colspan="9" class="text-center">No blogs found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>

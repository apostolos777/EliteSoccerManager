<?php
// Simple iupload uploader (minimal, for deployments only)
// - saves uploaded files into uploads/iupload
// - allows only certain extensions and limits file size

$uploadDir = __DIR__ . '/../uploads/iupload';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$maxFileSize = 10 * 1024 * 1024; // 10 MB
$allowedExt = ['jpg','jpeg','png','gif','pdf','csv','txt'];

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = 'Upload error: ' . $file['error'];
    } elseif ($file['size'] > $maxFileSize) {
        $message = 'File too large. Max 10MB.';
    } else {
        $name = basename($file['name']);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) {
            $message = 'File type not allowed.';
        } else {
            $target = $uploadDir . '/' . time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
            if (move_uploaded_file($file['tmp_name'], $target)) {
                $message = 'File uploaded successfully.';
            } else {
                $message = 'Failed to move uploaded file.';
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>iupload - Simple Uploader</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;padding:20px}
.container{max-width:600px;margin:0 auto}
.alert{padding:10px;border-radius:4px;margin-bottom:12px}
.alert-success{background:#e6ffed;color:#064e3b}
.alert-error{background:#fee2e2;color:#7f1d1d}
</style>
</head>
<body>
<div class="container">
    <h1>iupload</h1>
    <p>Upload a file (max 10MB). Allowed: <?php echo implode(', ', $allowedExt); ?></p>

    <?php if ($message): ?>
        <div class="alert <?php echo (strpos($message, 'success')!==false)?'alert-success':'alert-error'; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="file" name="file" required>
        <button type="submit">Upload</button>
    </form>

    <hr>
    <p>Uploads are stored in <code>uploads/iupload</code>.</p>
</div>
</body>
</html>

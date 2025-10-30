<?php
$config = require __DIR__ . '/../../src/Config/config.php';
?>

<?php include __DIR__ . '/../components/header.php'; ?>

<div class="main-wrapper">
    <main class="main-content" style="margin-left: 0; display: flex; align-items: center; justify-content: center;">
        <div style="text-align: center; max-width: 600px; padding: 3rem;">
            <div style="font-size: 6rem; font-weight: 700; color: #3b82f6; margin-bottom: 1rem;">404</div>
            <h1 style="font-size: 2rem; margin-bottom: 1rem; color: #111827;">Page Not Found</h1>
            <p style="color: #6b7280; margin-bottom: 2rem; font-size: 1.125rem;">
                The page you are looking for doesn't exist or has been moved.
            </p>
            <a href="/dashboard" class="btn btn-primary" style="display: inline-flex;">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>

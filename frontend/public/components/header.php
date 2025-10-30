<?php $assetVersion = $_ENV['ASSET_VERSION'] ?? '1.0.0'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $config['app']['name'] ?? 'Silo HCI'; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
    <link rel="alternate icon" href="/assets/images/favicon.svg">
    <link rel="apple-touch-icon" href="/assets/images/favicon.svg">
    
    <!-- Offline CSS - Gradient Theme -->
    <link rel="stylesheet" href="/assets/css/theme.css?v=<?php echo htmlspecialchars($assetVersion, ENT_QUOTES); ?>">
    <link rel="stylesheet" href="/assets/fonts/fontawesome.css?v=<?php echo htmlspecialchars($assetVersion, ENT_QUOTES); ?>">
    
    <!-- SweetAlert2 for better notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* Additional inline critical CSS for offline support */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: radial-gradient(circle at 20% 20%, rgba(118, 75, 162, 0.55) 0%, rgba(102, 126, 234, 0.35) 35%, rgba(17, 24, 39, 0.94) 100%);
            color: #f9fafb;
        }
    </style>
</head>
<body>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Backender' ?></title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-base-300">
    <?php if (isset($showNav) && $showNav): ?>
    <div class="navbar bg-base-100 shadow-lg">
        <div class="flex-1 min-w-0">
            <a href="/" class="btn btn-ghost text-base sm:text-xl normal-case">
                <img src="/favicon.png" alt="Backender" class="w-5 h-5 sm:w-6 sm:h-6 flex-shrink-0">
                <span class="hidden xs:inline sm:inline truncate">Backender</span>
            </a>
        </div>
        <div class="flex-none">
            <ul class="menu menu-horizontal px-1 gap-1 text-xs sm:text-sm">
                <li><a href="/" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?> px-2 sm:px-4">Dashboard</a></li>
                <li><a href="/api-keys" class="<?= $currentPage === 'api-keys' ? 'active' : '' ?> px-2 sm:px-4">Keys</a></li>
                <li><a href="/logs" class="<?= $currentPage === 'logs' ? 'active' : '' ?> px-2 sm:px-4">Logs</a></li>
                <li><a href="/logout" class="px-2 sm:px-4">Logout</a></li>
            </ul>
        </div>
    </div>
    <?php endif; ?>
    
    <main class="container mx-auto p-4 max-w-7xl">
        <?php if (isset($message)): ?>
        <div class="alert alert-success mb-4">
            <span><?= htmlspecialchars($message) ?></span>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-error mb-4">
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>
        
        <?= $content ?? '' ?>
    </main>
    
    <footer class="footer footer-center p-4 bg-base-100 text-base-content mt-8">
        <aside>
            <p class="text-xs opacity-60">Backender v<?= \Backender\Core\App::VERSION ?> · Built with ❤️</p>
        </aside>
    </footer>
</body>
</html>

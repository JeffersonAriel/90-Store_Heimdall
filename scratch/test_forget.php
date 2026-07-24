<?php

require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$mailManager = app('mail.manager');
$methods = get_class_methods($mailManager);

echo "METHODS ON MAIL MANAGER:\n";
print_r(array_filter($methods, fn($m) => str_contains($m, 'forget') || str_contains($m, 'purge')));

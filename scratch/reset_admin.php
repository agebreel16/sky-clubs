<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::where('email', 'admin@skyclubs.test')->first();
if (!$user) {
    $user = new User();
    $user->email = 'admin@skyclubs.test';
}
$user->name = 'Super Admin';
$user->password = Hash::make('Admin@12345');
$user->role = 'super_admin';
$user->is_active = true;
$user->email_verified_at = now();
$user->save();

echo "Super Admin reset to admin@skyclubs.test / Admin@12345\n";

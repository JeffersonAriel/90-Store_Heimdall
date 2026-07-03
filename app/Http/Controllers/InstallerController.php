<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;

class InstallerController extends Controller
{
    /**
     * Verifica requisitos do servidor.
     */
    public function checkRequirements(Request $request)
    {
        $requirements = [
            ['name' => 'PHP >= 8.4', 'ok' => version_compare(PHP_VERSION, '8.4.0', '>='), 'value' => PHP_VERSION],
            ['name' => 'Extensão PDO', 'ok' => extension_loaded('pdo'), 'value' => extension_loaded('pdo') ? 'Habilitada' : 'Não encontrada'],
            ['name' => 'Extensão PDO MySQL', 'ok' => extension_loaded('pdo_mysql'), 'value' => extension_loaded('pdo_mysql') ? 'Habilitada' : 'Não encontrada'],
            ['name' => 'Extensão mbstring', 'ok' => extension_loaded('mbstring'), 'value' => extension_loaded('mbstring') ? 'Habilitada' : 'Não encontrada'],
            ['name' => 'Extensão OpenSSL', 'ok' => extension_loaded('openssl'), 'value' => extension_loaded('openssl') ? 'Habilitada' : 'Não encontrada'],
            ['name' => 'Extensão GD', 'ok' => extension_loaded('gd'), 'value' => extension_loaded('gd') ? 'Habilitada' : 'Não encontrada'],
            ['name' => 'Extensão ZIP', 'ok' => extension_loaded('zip'), 'value' => extension_loaded('zip') ? 'Habilitada' : 'Não encontrada'],
            ['name' => 'Extensão Redis', 'ok' => extension_loaded('redis') || extension_loaded('predis'), 'value' => extension_loaded('redis') ? 'Habilitada' : 'Opcional'],
            ['name' => 'storage/ gravável', 'ok' => is_writable(storage_path()), 'value' => is_writable(storage_path()) ? 'Gravável' : 'Sem permissão'],
            ['name' => 'bootstrap/cache/ gravável', 'ok' => is_writable(base_path('bootstrap/cache')), 'value' => is_writable(base_path('bootstrap/cache')) ? 'Gravável' : 'Sem permissão'],
        ];

        $allOk = collect($requirements)->every(fn($r) => $r['ok']);

        return response()->json([
            'requirements' => $requirements,
            'all_ok' => $allOk,
        ]);
    }

    /**
     * Testa conexão com o banco de dados.
     */
    public function testDatabase(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'port' => 'required|integer',
            'database' => 'required|string|alpha_dash',
            'username' => 'required|string',
            'password' => 'nullable|string',
        ]);

        try {
            $connection = new \PDO(
                "mysql:host={$request->host};port={$request->port}",
                $request->username,
                $request->password,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            // Cria o banco se não existir
            $connection->exec("CREATE DATABASE IF NOT EXISTS `{$request->database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            return response()->json(['success' => true, 'message' => 'Conexão estabelecida com sucesso!']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Executa o setup completo do sistema.
     */
    public function setup(Request $request)
    {
        $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'required|integer',
            'db_database' => 'required|string|alpha_dash',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:8|confirmed',
            'company_name' => 'required|string|max:255',
            'company_cnpj' => 'nullable|string|max:18',
        ]);

        try {
            // 1. Gera o .env
            $this->generateEnv($request);

            // 2. Gera APP_KEY
            Artisan::call('key:generate', ['--force' => true]);

            // 3. Executa migrations
            Artisan::call('migrate', ['--force' => true]);

            // 4. Executa seeds base
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\InstallSeeder', '--force' => true]);

            // 5. Cria o administrador
            $this->createAdmin($request);

            // 6. Salva configurações da empresa
            $this->saveCompanySettings($request);

            // 7. Cria o arquivo de lock
            File::put(storage_path('app/installed.lock'), now()->toDateTimeString());

            return response()->json(['success' => true, 'message' => 'Sistema instalado com sucesso!', 'redirect' => '/erp/login']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function generateEnv(Request $request): void
    {
        $envPath = base_path('.env');
        $content = File::get(base_path('.env.example'));

        $replacements = [
            'DB_HOST=127.0.0.1' => "DB_HOST={$request->db_host}",
            'DB_PORT=3306' => "DB_PORT={$request->db_port}",
            'DB_DATABASE=heimdall' => "DB_DATABASE={$request->db_database}",
            'DB_USERNAME=root' => "DB_USERNAME={$request->db_username}",
            'DB_PASSWORD=secret' => "DB_PASSWORD={$request->db_password}",
            'STORE_NAME="90+ Store"' => "STORE_NAME=\"{$request->company_name}\"",
        ];

        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }

        File::put($envPath, $content);
    }

    private function createAdmin(Request $request): void
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);

        $admin = $userModel::create([
            'name' => $request->admin_name,
            'email' => $request->admin_email,
            'password' => Hash::make($request->admin_password),
            'email_verified_at' => now(),
        ]);

        // Atribui role de Super Admin (Spatie)
        $admin->assignRole('super-admin');
    }

    private function saveCompanySettings(Request $request): void
    {
        // Salva nas configurações do sistema
        \App\Models\Setting::updateOrCreate(
            ['key' => 'company.name'],
            ['value' => $request->company_name]
        );

        if ($request->company_cnpj) {
            \App\Models\Setting::updateOrCreate(
                ['key' => 'company.cnpj'],
                ['value' => $request->company_cnpj]
            );
        }
    }
}

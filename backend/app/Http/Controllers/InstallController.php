<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;

class InstallController extends Controller
{
    /**
     * Exibe o assistente de instalação (Wizard) usando Inertia
     */
    public function index()
    {
        // Se a instalação já foi marcada como concluída, não deixa entrar
        try {
            if (Schema::hasTable('instalacao')) {
                $instalacao = DB::table('instalacao')->first();
                if ($instalacao && $instalacao->concluida) {
                    return redirect('/heimdall/login')->with('error', 'O sistema já está instalado.');
                }
            }
        } catch (\Exception $e) {
            // Ignora se tabela não existir
        }

        return Inertia::render('Install/Wizard');
    }

    /**
     * Testa a conexão com o banco de dados fornecido
     */
    public function testConnection(Request $request)
    {
        $request->validate([
            'driver'   => 'required|in:mysql,sqlite',
            'host'     => 'required_if:driver,mysql',
            'port'     => 'required_if:driver,mysql',
            'database' => 'required',
            'username' => 'required_if:driver,mysql',
        ]);

        $driver = $request->driver;
        
        if ($driver === 'sqlite') {
            $dbPath = database_path($request->database);
            if (!file_exists($dbPath)) {
                try {
                    touch($dbPath);
                } catch (\Exception $e) {
                    return response()->json(['success' => false, 'message' => 'Não foi possível criar o arquivo SQLite: ' . $e->getMessage()], 400);
                }
            }
            return response()->json(['success' => true, 'message' => 'Arquivo SQLite criado/verificado com sucesso!']);
        }

        try {
            $dsn = "mysql:host={$request->host};port={$request->port};dbname={$request->database}";
            $options = [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION];
            new \PDO($dsn, $request->username, $request->password ?? '', $options);
            
            return response()->json(['success' => true, 'message' => 'Conexão com MySQL estabelecida com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Falha na conexão: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Salva as configurações de conexão no .env e executa migrações/seeders
     */
    public function runSetup(Request $request)
    {
        $request->validate([
            'driver'   => 'required|in:mysql,sqlite',
            'host'     => 'required_if:driver,mysql',
            'port'     => 'required_if:driver,mysql',
            'database' => 'required',
            'username' => 'required_if:driver,mysql',
        ]);

        try {
            // Escreve/atualiza o arquivo .env
            $this->updateEnvFile([
                'DB_CONNECTION' => $request->driver,
                'DB_HOST'       => $request->driver === 'mysql' ? $request->host : '',
                'DB_PORT'       => $request->driver === 'mysql' ? $request->port : '',
                'DB_DATABASE'   => $request->database,
                'DB_USERNAME'   => $request->driver === 'mysql' ? $request->username : '',
                'DB_PASSWORD'   => $request->driver === 'mysql' ? ($request->password ?? '') : '',
            ]);

            // Altera a configuração em memória dinamicamente para o processo atual rodar as migrações no banco certo
            config([
                'database.default' => $request->driver,
                "database.connections.{$request->driver}.host" => $request->driver === 'mysql' ? $request->host : '',
                "database.connections.{$request->driver}.port" => $request->driver === 'mysql' ? $request->port : '',
                "database.connections.{$request->driver}.database" => $request->database,
                "database.connections.{$request->driver}.username" => $request->driver === 'mysql' ? $request->username : '',
                "database.connections.{$request->driver}.password" => $request->driver === 'mysql' ? ($request->password ?? '') : '',
            ]);

            // Força o Laravel a recriar a conexão com as novas configurações em memória
            DB::purge($request->driver);

            // Gera a chave única do aplicativo caso não esteja definida
            Artisan::call('key:generate', ['--force' => true]);

            // Limpa o cache de configuração do Laravel para ler os novos valores do .env
            Artisan::call('config:clear');

            // Executa as migrações e seeders
            Artisan::call('migrate:fresh', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);

            return response()->json(['success' => true, 'message' => 'Banco de dados configurado, migrado e populado com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao processar banco: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cria o primeiro usuário administrador do sistema e finaliza a instalação
     */
    public function createAdmin(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        try {
            // Busca o perfil de Administrador cadastrado pelo PerfilPermissaoSeeder
            $perfilAdmin = DB::table('perfis_permissao')->where('nome', 'Administrador')->first();

            if (!$perfilAdmin) {
                return response()->json(['success' => false, 'message' => 'Perfil Administrador não encontrado. Rodou os seeders?'], 400);
            }

            // Cria o funcionário admin
            $adminId = DB::table('funcionarios')->insertGetId([
                'perfil_id'  => $perfilAdmin->id,
                'nome'       => $request->name,
                'email'      => $request->email,
                'password'   => Hash::make($request->password),
                'ativo'      => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Grava a flag de instalação concluída
            DB::table('instalacao')->insert([
                'concluida'     => true,
                'admin_id'      => $adminId,
                'versao'        => '1.0.0',
                'instalado_em'  => now(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Administrador criado e instalação concluída com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao criar administrador: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper para atualizar os valores de chaves no arquivo .env
     */
    private function updateEnvFile(array $data)
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            // Se não existir, tenta copiar do .env.example
            if (file_exists(base_path('.env.example'))) {
                copy(base_path('.env.example'), $envPath);
            } else {
                touch($envPath);
            }
        }

        $content = file_get_contents($envPath);

        foreach ($data as $key => $value) {
            // Trata valores com espaço colocando aspas
            if (str_contains($value, ' ')) {
                $value = '"' . $value . '"';
            }

            // Verifica se a chave já existe no .env
            $pattern = "/^{$key}=.*/m";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $content);
    }
}

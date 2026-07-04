<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Log de Acesso / Tráfego ──────────────────────────────
        Schema::create('logs_acesso', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45);
            $table->text('user_agent')->nullable();
            $table->string('metodo', 10)->default('GET');
            $table->string('rota', 500);
            $table->unsignedSmallInteger('status_http')->nullable();
            $table->enum('usuario_tipo', ['cliente', 'funcionario', 'anonimo'])->default('anonimo');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->unsignedInteger('duracao_ms')->nullable();
            $table->timestamps();

            $table->index(['ip', 'created_at']);
            $table->index(['usuario_tipo', 'usuario_id', 'created_at']);
        });

        // ─── Log de Segurança / Ameaças ───────────────────────────
        Schema::create('logs_seguranca', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45);
            $table->enum('tipo', [
                'brute_force',
                'sqli_tentativa',
                'xss_tentativa',
                'rate_limit',
                'ip_banido',
                'acesso_negado',
                'token_invalido',
            ]);
            $table->text('detalhe')->nullable();
            $table->enum('usuario_tipo', ['cliente', 'funcionario', 'anonimo'])->default('anonimo');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->boolean('bloqueado')->default(false);
            $table->string('rota', 500)->nullable();
            $table->timestamps();

            $table->index(['ip', 'tipo', 'created_at']);
            $table->index(['created_at', 'tipo']);
        });

        // ─── Log de Login / Logout ─────────────────────────────────
        Schema::create('logs_login', function (Blueprint $table) {
            $table->id();
            $table->enum('usuario_tipo', ['cliente', 'funcionario']);
            $table->unsignedBigInteger('usuario_id');
            $table->string('email')->nullable();
            $table->string('ip', 45);
            $table->text('user_agent')->nullable();
            $table->boolean('sucesso')->default(false);
            $table->string('motivo_falha', 200)->nullable();
            $table->enum('acao', ['login', 'logout', 'login_2fa'])->default('login');
            $table->timestamps();

            $table->index(['usuario_tipo', 'usuario_id', 'created_at']);
            $table->index(['ip', 'created_at']);
        });

        // ─── Log de Auditoria ─────────────────────────────────────
        Schema::create('logs_auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funcionario_id')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->string('modulo');
            $table->enum('acao', ['create', 'update', 'delete', 'restore']);
            $table->string('modelo', 100);
            $table->unsignedBigInteger('registro_id');
            $table->json('valor_antes_json')->nullable();
            $table->json('valor_depois_json')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['modulo', 'created_at']);
            $table->index(['modelo', 'registro_id']);
            $table->index(['funcionario_id', 'created_at']);
        });

        // ─── IPs Bloqueados ────────────────────────────────────────
        Schema::create('ips_bloqueados', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->unique();
            $table->text('motivo');
            $table->foreignId('bloqueado_por')->nullable()->constrained('funcionarios')->nullOnDelete()
                  ->comment('null = bloqueio automático pelo sistema');
            $table->timestamp('expires_at')->nullable()->comment('null = permanente');
            $table->timestamps();

            $table->index(['ip', 'expires_at']);
        });

        // ─── Histórico de Importações em Lote ─────────────────────
        Schema::create('importacoes_lote', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funcionario_id')->constrained('funcionarios')->restrictOnDelete();
            $table->enum('tipo', ['produtos', 'fornecedores']);
            $table->string('arquivo_original');
            $table->string('arquivo_path')->nullable();
            $table->integer('total_linhas')->default(0);
            $table->integer('criados')->default(0);
            $table->integer('atualizados')->default(0);
            $table->integer('erros')->default(0);
            $table->json('erros_json')->nullable()->comment('Lista de erros por linha');
            $table->json('preview_json')->nullable()->comment('Preview temporário antes de confirmar');
            $table->enum('status', [
                'pendente',
                'validando',
                'aguardando_confirmacao',
                'processando',
                'concluido',
                'erro',
            ])->default('pendente');
            $table->timestamps();

            $table->index(['tipo', 'created_at']);
            $table->index(['funcionario_id', 'created_at']);
        });

        // ─── Configurações do Sistema ──────────────────────────────
        Schema::create('configuracoes', function (Blueprint $table) {
            $table->id();
            $table->string('chave', 100)->unique();
            $table->text('valor')->nullable();
            $table->string('tipo', 30)->default('string')->comment('string|int|bool|json');
            $table->string('descricao')->nullable();
            $table->string('grupo', 60)->default('geral');
            $table->timestamps();
        });

        // ─── Flag de Instalação ────────────────────────────────────
        Schema::create('instalacao', function (Blueprint $table) {
            $table->id();
            $table->boolean('concluida')->default(false);
            $table->foreignId('admin_id')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->string('versao', 20)->nullable();
            $table->timestamp('instalado_em')->nullable();
            $table->timestamps();
        });

        // ─── Cache (database driver) ────────────────────────────────
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        // ─── Jobs (queue driver database) ──────────────────────────
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // ─── Sessions ──────────────────────────────────────────────
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('instalacao');
        Schema::dropIfExists('configuracoes');
        Schema::dropIfExists('importacoes_lote');
        Schema::dropIfExists('ips_bloqueados');
        Schema::dropIfExists('logs_auditoria');
        Schema::dropIfExists('logs_login');
        Schema::dropIfExists('logs_seguranca');
        Schema::dropIfExists('logs_acesso');
    }
};

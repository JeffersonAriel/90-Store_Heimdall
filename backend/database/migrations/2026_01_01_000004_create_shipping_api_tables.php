<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Regras de Frete ───────────────────────────────────────
        Schema::create('fretes_regras', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->enum('tipo', ['local', 'nacional', 'gratis'])->default('nacional');
            $table->decimal('valor_minimo_gratis', 10, 2)->nullable()->comment('Frete grátis acima deste valor');
            $table->decimal('raio_km_local', 8, 2)->default(50)->comment('Raio em km para opções locais');
            $table->decimal('lat_origem', 10, 8)->nullable()->comment('-23.5044 (São Miguel Paulista)');
            $table->decimal('lng_origem', 11, 8)->nullable()->comment('-46.4600 (São Miguel Paulista)');
            $table->string('cep_origem', 10)->nullable();
            $table->json('servicos_locais_json')->nullable()->comment('[{"nome":"Uber Flash","api":"uber_flash"}, ...]');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        // ─── Configuração de APIs ──────────────────────────────────
        Schema::create('apis_configuracao', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique()->comment('Ex: mercadopago, pagseguro, stripe, viacep');
            $table->string('nome');
            $table->enum('tipo', ['gateway', 'frete', 'frete_local', 'cep', 'outro'])->default('outro');
            $table->json('template_campos_json')->nullable()->comment('Schema dos campos de credencial');
            $table->text('credenciais_json')->nullable()->comment('AES-256 encrypted JSON de credenciais');
            $table->string('webhook_url')->nullable();
            $table->boolean('ativo')->default(true);
            $table->boolean('sandbox')->default(false)->comment('Modo sandbox/teste');
            $table->integer('fallback_ordem')->default(99)->comment('Ordem de tentativa para fallback (CEP)');
            $table->timestamps();
        });

        // ─── Logs de API ────────────────────────────────────────────
        Schema::create('logs_api', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_config_id')->nullable()->constrained('apis_configuracao')->nullOnDelete();
            $table->string('slug_api', 60)->nullable()->comment('Redundância para logs órfãos');
            $table->string('rota')->comment('Endpoint chamado');
            $table->string('metodo', 10)->default('GET');
            $table->json('request_json')->nullable();
            $table->json('response_json')->nullable();
            $table->unsignedSmallInteger('status_http')->nullable();
            $table->unsignedInteger('duracao_ms')->nullable();
            $table->boolean('sucesso')->default(false);
            $table->text('erro_mensagem')->nullable();
            $table->timestamps();

            $table->index(['api_config_id', 'created_at']);
            $table->index(['sucesso', 'created_at']);
        });

        // ─── Cache de Frete (cotações) ─────────────────────────────
        Schema::create('fretes_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cep_destino', 10);
            $table->decimal('peso_kg', 8, 3);
            $table->json('cotacoes_json');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['cep_destino', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fretes_cache');
        Schema::dropIfExists('logs_api');
        Schema::dropIfExists('apis_configuracao');
        Schema::dropIfExists('fretes_regras');
    }
};

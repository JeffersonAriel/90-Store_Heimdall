<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Perfis de Permissão ─────────────────────────────────
        Schema::create('perfis_permissao', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('descricao')->nullable();
            $table->boolean('is_admin')->default(false)->comment('Acesso irrestrito, inclui módulo Segurança');
            $table->timestamps();
        });

        // ─── Funcionários ────────────────────────────────────────
        Schema::create('funcionarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perfil_id')->constrained('perfis_permissao')->restrictOnDelete();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('ativo')->default(true);
            $table->string('two_fa_secret')->nullable();
            $table->boolean('two_fa_ativo')->default(false);
            $table->string('foto')->nullable();
            $table->string('telefone')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // ─── Permissões por Módulo ────────────────────────────────
        Schema::create('permissoes_modulo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perfil_id')->constrained('perfis_permissao')->cascadeOnDelete();
            $table->string('modulo'); // produtos, fornecedores, financeiro, pedidos, etc.
            $table->enum('acao', ['view', 'create', 'edit', 'delete']);
            $table->timestamps();

            $table->unique(['perfil_id', 'modulo', 'acao']);
        });

        // ─── Clientes ─────────────────────────────────────────────
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nome_completo');
            $table->text('cpf'); // AES-256 encrypted
            $table->date('data_nascimento')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('telefone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('referral_code', 20)->unique()->nullable();
            $table->foreignId('referral_by')->nullable()->constrained('clientes')->nullOnDelete();
            $table->integer('pontos_saldo')->default(0);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // ─── Endereços do Cliente ─────────────────────────────────
        Schema::create('enderecos_cliente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('apelido', 60)->nullable()->comment('Ex: Casa, Trabalho');
            $table->string('cep', 10);
            $table->string('logradouro');
            $table->string('numero', 20);
            $table->string('complemento', 100)->nullable();
            $table->string('bairro');
            $table->string('cidade');
            $table->string('estado', 2);
            $table->string('ponto_referencia')->nullable();
            $table->boolean('is_principal')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enderecos_cliente');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('permissoes_modulo');
        Schema::dropIfExists('funcionarios');
        Schema::dropIfExists('perfis_permissao');
    }
};

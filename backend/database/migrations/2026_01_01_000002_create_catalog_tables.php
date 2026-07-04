<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Categorias (árvore self-referential) ─────────────────
        Schema::create('categorias_tipo_produto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categorias_tipo_produto')->nullOnDelete();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->text('descricao')->nullable();
            $table->string('icone', 60)->nullable();
            $table->integer('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        // ─── Atributos de Categoria ───────────────────────────────
        Schema::create('atributos_categoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias_tipo_produto')->cascadeOnDelete();
            $table->string('nome'); // Ex: "Time", "Coleção"
            $table->enum('tipo', ['select', 'text', 'number'])->default('select');
            $table->boolean('obrigatorio')->default(false);
            $table->integer('ordem')->default(0);
            $table->timestamps();
        });

        // ─── Opções de Atributo ───────────────────────────────────
        Schema::create('opcoes_atributo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atributo_id')->constrained('atributos_categoria')->cascadeOnDelete();
            $table->string('valor'); // Ex: "Nacional", "Internacional"
            $table->integer('ordem')->default(0);
            $table->timestamps();
        });

        // ─── Fornecedores ─────────────────────────────────────────
        Schema::create('fornecedores', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_pessoa', ['juridica', 'fisica'])->default('juridica');
            $table->string('razao_social');
            $table->string('nome_fantasia')->nullable();
            $table->string('cnpj', 20)->nullable()->unique();
            $table->string('cpf', 20)->nullable()->unique();
            $table->string('email')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('website')->nullable();
            $table->string('cep', 10)->nullable();
            $table->string('logradouro')->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('complemento', 100)->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado', 2)->nullable();
            $table->text('condicao_pagamento')->nullable();
            $table->integer('prazo_medio_dias')->default(0);
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->decimal('avaliacao_media', 3, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // ─── Avaliações de Fornecedor ─────────────────────────────
        Schema::create('avaliacoes_fornecedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fornecedor_id')->constrained('fornecedores')->cascadeOnDelete();
            $table->foreignId('funcionario_id')->constrained('funcionarios')->restrictOnDelete();
            $table->unsignedTinyInteger('estrelas'); // 1-5
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->index(['fornecedor_id', 'created_at']);
        });

        // ─── Produtos ─────────────────────────────────────────────
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fornecedor_id')->constrained('fornecedores')->restrictOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias_tipo_produto')->restrictOnDelete();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->string('sku_base', 100)->unique();
            $table->text('descricao')->nullable();
            $table->text('descricao_curta')->nullable();
            $table->decimal('preco_custo', 10, 2)->default(0);
            $table->decimal('preco_venda', 10, 2);
            $table->boolean('tem_desconto')->default(false);
            $table->decimal('preco_desconto', 10, 2)->nullable();
            $table->boolean('ativo')->default(true);
            $table->boolean('is_destaque')->default(false);
            $table->decimal('peso_kg', 8, 3)->nullable();
            $table->json('dimensoes_json')->nullable()->comment('{"altura": 0, "largura": 0, "comprimento": 0}');
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['categoria_id', 'ativo']);
            $table->index(['fornecedor_id', 'ativo']);
        });

        // ─── Valores de Atributos do Produto ─────────────────────
        Schema::create('produto_atributo_valor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->foreignId('atributo_id')->constrained('atributos_categoria')->restrictOnDelete();
            $table->foreignId('opcao_id')->nullable()->constrained('opcoes_atributo')->nullOnDelete();
            $table->string('valor_livre')->nullable()->comment('Para atributos do tipo text/number');
            $table->timestamps();

            $table->unique(['produto_id', 'atributo_id']);
        });

        // ─── Variações do Produto ─────────────────────────────────
        Schema::create('variacoes_produto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->string('sku', 100)->unique();
            $table->string('tamanho', 30)->nullable();
            $table->string('cor', 60)->nullable();
            $table->decimal('preco_adicional', 10, 2)->default(0)->comment('Soma ao preco_venda do produto');
            $table->enum('tipo_estoque', ['proprio', 'dropshipping'])->default('dropshipping');
            $table->integer('estoque_quantidade')->default(0)->comment('Disponível em estoque próprio');
            $table->integer('estoque_reservado')->default(0)->comment('Reservado em pedidos aguardando pagamento');
            $table->integer('estoque_minimo')->nullable()->comment('⚠️ Alerta amarelo de reposição');
            $table->integer('estoque_critico')->nullable()->comment('🔴 Alerta vermelho urgente no dashboard');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['produto_id', 'ativo']);
            $table->index('tipo_estoque');
        });

        // ─── Fotos do Produto ─────────────────────────────────────
        Schema::create('fotos_produto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->foreignId('variacao_id')->nullable()->constrained('variacoes_produto')->cascadeOnDelete();
            $table->string('url');
            $table->integer('ordem')->default(0);
            $table->boolean('is_capa')->default(false);
            $table->timestamps();
        });

        // ─── Movimentações de Estoque ─────────────────────────────
        Schema::create('movimentacoes_estoque', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variacao_id')->constrained('variacoes_produto')->restrictOnDelete();
            $table->unsignedBigInteger('pedido_id')->nullable()->comment('FK adicionada após tabela pedidos');
            $table->foreignId('funcionario_id')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->enum('tipo', [
                'entrada',          // Compra/reposição
                'reserva',          // Pedido criado, aguardando pagamento
                'baixa_confirmada', // Pagamento confirmado → saída definitiva
                'liberacao_reserva',// Pagamento não confirmado → devolução da reserva
                'ajuste_manual',    // Correção manual pelo funcionário
                'inventario',       // Contagem física
            ]);
            $table->integer('quantidade')->comment('Positivo=entrada, Negativo=saída');
            $table->integer('estoque_antes');
            $table->integer('estoque_depois');
            $table->string('motivo')->nullable();
            $table->string('referencia_externa')->nullable()->comment('ID pedido externo, NF, etc.');
            $table->timestamps();

            $table->index(['variacao_id', 'created_at']);
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_estoque');
        Schema::dropIfExists('fotos_produto');
        Schema::dropIfExists('variacoes_produto');
        Schema::dropIfExists('produto_atributo_valor');
        Schema::dropIfExists('produtos');
        Schema::dropIfExists('avaliacoes_fornecedor');
        Schema::dropIfExists('fornecedores');
        Schema::dropIfExists('opcoes_atributo');
        Schema::dropIfExists('atributos_categoria');
        Schema::dropIfExists('categorias_tipo_produto');
    }
};

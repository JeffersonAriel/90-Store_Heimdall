<?php

namespace App\Services;

use App\Models\Produto;
use App\Models\Fornecedor;
use App\Models\CategoriaTipoProduto;
use App\Models\VariacaoProduto;
use App\Models\MovimentacaoEstoque;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportService
{
    /**
     * Valida um lote de importação (Excel) e retorna as linhas tratadas, marcando quais serão criadas, atualizadas ou possuem erros.
     */
    public function preview(string $filePath, string $tipo): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // O primeiro registro é o cabeçalho
        $headers = array_shift($rows);

        $results = [
            'total' => 0,
            'criar' => 0,
            'atualizar' => 0,
            'erros' => 0,
            'data' => []
        ];

        foreach ($rows as $index => $row) {
            if (empty(array_filter($row))) {
                continue; // Linha vazia
            }

            $results['total']++;
            $lineNum = $index + 2;

            if ($tipo === 'produtos') {
                $parsedRow = $this->parseProductRow($row, $lineNum);
            } else {
                $parsedRow = $this->parseSupplierRow($row, $lineNum);
            }

            if (!$parsedRow['valido']) {
                $results['erros']++;
            } elseif ($parsedRow['acao'] === 'criar') {
                $results['criar']++;
            } else {
                $results['atualizar']++;
            }

            $results['data'][] = $parsedRow;
        }

        return $results;
    }

    /**
     * Executa a importação salvando e auditando no banco de dados.
     */
    public function import(array $rows, string $tipo, int $employeeId): array
    {
        $summary = [
            'criados' => 0,
            'atualizados' => 0,
            'erros' => 0
        ];

        DB::transaction(function () use ($rows, $tipo, $employeeId, &$summary) {
            foreach ($rows as $row) {
                if (!$row['valido']) {
                    $summary['erros']++;
                    continue;
                }

                if ($tipo === 'produtos') {
                    $this->importProduct($row, $summary);
                } else {
                    $this->importSupplier($row, $summary);
                }
            }
        });

        return $summary;
    }

    private function parseProductRow(array $row, int $lineNum): array
    {
        // Colunas completas de exportação de produtos:
        // ID Produto, Nome, Marca, Gênero, SKU Base, Categoria, Subcategorias, ID Variação, SKU Variação, Tamanho, Cor,
        // Preço Custo, Preço Venda, Preço Promocional, Tipo Estoque, Estoque Qtd, Estoque Crítico, Fornecedor ID, Fornecedor Nome,
        // URL Foto, Descrição, Ativo
        $idProduto = !empty($row[0]) ? intval($row[0]) : null;
        $nome = trim($row[1] ?? '');
        $marca = trim($row[2] ?? '');
        $genero = trim($row[3] ?? '');
        $skuBase = trim($row[4] ?? '');
        $categoriaNome = trim($row[5] ?? '');
        $subcategorias = trim($row[6] ?? '');
        $idVariacao = !empty($row[7]) ? intval($row[7]) : null;
        $skuVar = trim($row[8] ?? '');
        $tamanho = trim($row[9] ?? '');
        $cor = trim($row[10] ?? '');
        $custo = floatval($row[11] ?? 0);
        $venda = floatval($row[12] ?? 0);
        $precoPromocional = !empty($row[13]) ? floatval($row[13]) : null;
        $tipoEstoque = strtolower(trim($row[14] ?? 'dropshipping'));
        $estoqueQtd = intval($row[15] ?? 0);
        $estoqueCritico = !empty($row[16]) ? intval($row[16]) : 5;
        $fornecedorId = intval($row[17] ?? 0);
        $fornecedorNome = trim($row[18] ?? '');
        $urlFoto = trim($row[19] ?? '');
        $descricao = trim($row[20] ?? '');
        
        $ativoStr = strtolower(trim($row[21] ?? 'sim'));
        $ativo = ($ativoStr === 'sim' || $ativoStr === '1' || $ativoStr === 'true');

        $erros = [];

        if (empty($nome)) $erros[] = "Nome do produto é obrigatório.";
        if (empty($skuBase)) $erros[] = "SKU Base é obrigatório.";
        if (empty($skuVar)) $erros[] = "SKU da Variação é obrigatório.";
        if ($venda <= 0) $erros[] = "Preço de venda deve ser maior que zero.";
        if (!in_array($tipoEstoque, ['proprio', 'dropshipping'])) $erros[] = "Tipo de estoque inválido (deve ser proprio ou dropshipping).";

        // Verifica existência do fornecedor no banco
        $fornecedorExists = Fornecedor::where('id', $fornecedorId)->exists();
        if (!$fornecedorExists) $erros[] = "Fornecedor com ID {$fornecedorId} não existe no sistema.";

        // Busca ou cria categoria dinamicamente por nome se não existir
        $categoria = CategoriaTipoProduto::where('nome', $categoriaNome)->first();
        if (!$categoria && !empty($categoriaNome)) {
            // Cria categoria genérica temporária
            $categoria = CategoriaTipoProduto::create([
                'nome' => $categoriaNome,
                'slug' => \Illuminate\Support\Str::slug($categoriaNome),
                'ordem' => 99,
                'ativo' => true
            ]);
        }
        if (!$categoria) $erros[] = "Categoria é obrigatória.";

        // Identifica se é criação ou atualização baseando-se no ID da variação ou no SKU
        $variacao = null;
        if ($idVariacao) {
            $variacao = VariacaoProduto::find($idVariacao);
        }
        if (!$variacao) {
            $variacao = VariacaoProduto::where('sku', $skuVar)->first();
        }
        $acao = $variacao ? 'atualizar' : 'criar';

        return [
            'line' => $lineNum,
            'linha' => $lineNum,
            'valido' => empty($erros),
            'erros' => $erros,
            'acao' => $acao,
            'status' => empty($erros) ? $acao : 'erro',
            'chave' => $skuVar ?: $skuBase ?: $nome,
            'info' => empty($erros) ? 'Pronto para processar' : implode(' | ', $erros),
            'data' => [
                'id_produto' => $idProduto,
                'nome' => $nome,
                'marca' => $marca,
                'genero' => $genero,
                'sku_base' => $skuBase,
                'categoria_id' => $categoria?->id,
                'categoria_nome' => $categoriaNome,
                'subcategorias' => $subcategorias,
                'id_variacao' => $idVariacao,
                'sku_var' => $skuVar,
                'tamanho' => $tamanho,
                'cor' => $cor,
                'custo' => $custo,
                'venda' => $venda,
                'preco_promocional' => $precoPromocional,
                'tipo_estoque' => $tipoEstoque,
                'estoque_quantidade' => $estoqueQtd,
                'estoque_critico' => $estoqueCritico,
                'fornecedor_id' => $fornecedorId,
                'fornecedor_nome' => $fornecedorNome,
                'url_foto' => $urlFoto,
                'descricao' => $descricao,
                'ativo' => $ativo,
                'ativo_variacao' => $ativo, // assume variation active is same as product active
            ]
        ];
    }

    private function parseSupplierRow(array $row, int $lineNum): array
    {
        // Colunas completas de exportação de fornecedores:
        // ID, Razão Social, Nome Fantasia, Tipo, CNPJ, CPF, E-mail, Telefone, WhatsApp, Website, CEP, Logradouro, Número, Complemento, Bairro, Cidade, Estado,
        // Condição Pagamento, Prazo Médio (dias), Categorias Fornecidas, Observações, Avaliação Média, Ativo
        $id = !empty($row[0]) ? intval($row[0]) : null;
        $razaoSocial = trim($row[1] ?? '');
        $nomeFantasia = trim($row[2] ?? '');
        $tipoPessoa = strtolower(trim($row[3] ?? 'juridica'));
        if ($tipoPessoa === 'jurídica') $tipoPessoa = 'juridica';
        if ($tipoPessoa === 'física') $tipoPessoa = 'fisica';

        $cnpj = preg_replace('/[^0-9]/', '', $row[4] ?? '');
        $cpf = preg_replace('/[^0-9]/', '', $row[5] ?? '');
        $documento = $tipoPessoa === 'juridica' ? $cnpj : $cpf;

        $email = trim($row[6] ?? '');
        $telefone = trim($row[7] ?? '');
        $whatsapp = trim($row[8] ?? '');
        $website = trim($row[9] ?? '');
        $cep = trim($row[10] ?? '');
        $logradouro = trim($row[11] ?? '');
        $numero = trim($row[12] ?? '');
        $complemento = trim($row[13] ?? '');
        $bairro = trim($row[14] ?? '');
        $cidade = trim($row[15] ?? '');
        $estado = trim($row[16] ?? '');
        $condicaoPagamento = trim($row[17] ?? '');
        $prazoMedioDias = !empty($row[18]) ? intval($row[18]) : 0;
        
        $categoriasFornecidas = [];
        if (!empty($row[19])) {
            $categoriasFornecidas = array_map('trim', explode(',', $row[19]));
        }

        $observacoes = trim($row[20] ?? '');
        $avaliacaoMedia = !empty($row[21]) ? floatval($row[21]) : 5.0;
        $ativoStr = strtolower(trim($row[22] ?? 'sim'));
        $ativo = ($ativoStr === 'sim' || $ativoStr === '1' || $ativoStr === 'true');

        $erros = [];

        if (empty($razaoSocial)) $erros[] = "Razão Social é obrigatória.";
        if (empty($documento)) $erros[] = "CNPJ ou CPF é obrigatório.";
        if (!in_array($tipoPessoa, ['juridica', 'fisica'])) $erros[] = "Tipo de pessoa inválido (deve ser juridica ou fisica).";

        // Verifica existência do fornecedor
        $fornecedor = null;
        if ($id) {
            $fornecedor = Fornecedor::find($id);
        }
        if (!$fornecedor && !empty($documento)) {
            $fornecedor = Fornecedor::where('cnpj', $documento)->orWhere('cpf', $documento)->first();
        }
        $acao = $fornecedor ? 'atualizar' : 'criar';

        return [
            'line' => $lineNum,
            'linha' => $lineNum,
            'valido' => empty($erros),
            'erros' => $erros,
            'acao' => $acao,
            'status' => empty($erros) ? $acao : 'erro',
            'chave' => $documento ?: $razaoSocial,
            'info' => empty($erros) ? 'Pronto para processar' : implode(' | ', $erros),
            'data' => [
                'id' => $fornecedor?->id ?? $id,
                'razao_social' => $razaoSocial,
                'nome_fantasia' => $nomeFantasia,
                'cnpj' => $tipoPessoa === 'juridica' ? $documento : null,
                'cpf' => $tipoPessoa === 'fisica' ? $documento : null,
                'tipo_pessoa' => $tipoPessoa,
                'email' => $email,
                'telefone' => $telefone,
                'whatsapp' => $whatsapp,
                'website' => $website,
                'cep' => $cep,
                'logradouro' => $logradouro,
                'numero' => $numero,
                'complemento' => $complemento,
                'bairro' => $bairro,
                'cidade' => $cidade,
                'estado' => $estado,
                'condicao_pagamento' => $condicaoPagamento,
                'prazo_medio_dias' => $prazoMedioDias,
                'categorias_fornecidas' => $categoriasFornecidas,
                'observacoes' => $observacoes,
                'avaliacao_media' => $avaliacaoMedia,
                'ativo' => $ativo,
            ]
        ];
    }

    private function importProduct(array $row, array &$summary)
    {
        $d = $row['data'];

        // Tenta encontrar produto por ID ou SKU Base
        $product = null;
        if (!empty($d['id_produto'])) {
            $product = Produto::find($d['id_produto']);
        }
        if (!$product) {
            $product = Produto::where('sku_base', $d['sku_base'])->first();
        }

        $productData = [
            'nome' => $d['nome'],
            'marca' => $d['marca'],
            'genero' => $d['genero'],
            'slug' => \Illuminate\Support\Str::slug($d['nome']),
            'categoria_id' => $d['categoria_id'],
            'fornecedor_id' => $d['fornecedor_id'],
            'preco_custo' => $d['custo'],
            'preco_venda' => $d['venda'],
            'descricao' => $d['descricao'],
            'estoque_critico' => $d['estoque_critico'],
            'ativo' => $d['ativo'],
        ];

        if ($d['preco_promocional'] !== null && $d['preco_promocional'] > 0) {
            $productData['tem_desconto'] = true;
            $productData['preco_desconto'] = $d['preco_promocional'];
        } else {
            $productData['tem_desconto'] = false;
            $productData['preco_desconto'] = null;
        }

        if ($product) {
            $product->update($productData);
        } else {
            $productData['sku_base'] = $d['sku_base'];
            $product = Produto::create($productData);
        }

        // Tenta encontrar variação por ID ou SKU
        $variation = null;
        if (!empty($d['id_variacao'])) {
            $variation = VariacaoProduto::find($d['id_variacao']);
        }
        if (!$variation) {
            $variation = VariacaoProduto::where('sku', $d['sku_var'])->first();
        }

        // Foto do produto
        if (!empty($d['url_foto'])) {
            $exists = DB::table('fotos_produto')
                ->where('produto_id', $product->id)
                ->where('url', $d['url_foto'])
                ->exists();
            if (!$exists) {
                DB::table('fotos_produto')->insert([
                    'produto_id' => $product->id,
                    'url' => $d['url_foto'],
                    'is_capa' => true,
                    'ordem' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        if ($variation) {
            $estoqueAntes = $variation->estoque_quantidade;
            $variation->update([
                'tamanho' => $d['tamanho'],
                'cor' => $d['cor'],
                'tipo_estoque' => $d['tipo_estoque'],
                'estoque_quantidade' => $d['estoque_quantidade'],
                'ativo' => $d['ativo_variacao'],
            ]);

            // Se for próprio e mudou estoque, gera log de movimentação do tipo ajuste
            if ($d['tipo_estoque'] === 'proprio' && $estoqueAntes !== $d['estoque_quantidade']) {
                MovimentacaoEstoque::create([
                    'variacao_id' => $variation->id,
                    'quantidade' => $d['estoque_quantidade'] - $estoqueAntes,
                    'estoque_antes' => $estoqueAntes,
                    'estoque_depois' => $d['estoque_quantidade'],
                    'tipo' => 'ajuste_manual',
                    'motivo' => 'Atualização via Importação de Planilha Excel',
                ]);
            }
            $summary['atualizados']++;
        } else {
            $variation = $product->variacoes()->create([
                'sku' => $d['sku_var'],
                'tamanho' => $d['tamanho'],
                'cor' => $d['cor'],
                'tipo_estoque' => $d['tipo_estoque'],
                'estoque_quantidade' => $d['estoque_quantidade'],
                'preco_adicional' => 0,
                'ativo' => $d['ativo_variacao'],
            ]);

            if ($d['tipo_estoque'] === 'proprio' && $d['estoque_quantidade'] > 0) {
                MovimentacaoEstoque::create([
                    'variacao_id' => $variation->id,
                    'quantidade' => $d['estoque_quantidade'],
                    'estoque_antes' => 0,
                    'estoque_depois' => $d['estoque_quantidade'],
                    'tipo' => 'entrada',
                    'motivo' => 'Criação via Importação de Planilha Excel',
                ]);
            }
            $summary['criados']++;
        }
    }

    private function importSupplier(array $row, array &$summary)
    {
        $d = $row['data'];

        if ($d['id']) {
            $supplier = Fornecedor::find($d['id']);
            if ($supplier) {
                $supplier->update($d);
            } else {
                Fornecedor::create($d);
            }
            $summary['atualizados']++;
        } else {
            Fornecedor::create($d);
            $summary['criados']++;
        }
    }
}

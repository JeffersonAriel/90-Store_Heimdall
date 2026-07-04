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
        // Colunas esperadas: Nome, SKU Base, Categoria, SKU Variação, Tamanho, Cor, Custo, Venda, Tipo Estoque, Estoque Qtd, Fornecedor ID
        $nome = trim($row[0] ?? '');
        $skuBase = trim($row[1] ?? '');
        $categoriaNome = trim($row[2] ?? '');
        $skuVar = trim($row[3] ?? '');
        $tamanho = trim($row[4] ?? '');
        $cor = trim($row[5] ?? '');
        $custo = floatval($row[6] ?? 0);
        $venda = floatval($row[7] ?? 0);
        $tipoEstoque = strtolower(trim($row[8] ?? 'dropshipping'));
        $estoqueQtd = intval($row[9] ?? 0);
        $fornecedorId = intval($row[10] ?? 0);

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

        // Identifica se é criação ou atualização baseando-se no SKU da variação
        $variacao = VariacaoProduto::where('sku', $skuVar)->first();
        $acao = $variacao ? 'atualizar' : 'criar';

        return [
            'line' => $lineNum,
            'valido' => empty($erros),
            'erros' => $erros,
            'acao' => $acao,
            'data' => [
                'nome' => $nome,
                'sku_base' => $skuBase,
                'categoria_id' => $categoria?->id,
                'categoria_nome' => $categoriaNome,
                'sku_var' => $skuVar,
                'tamanho' => $tamanho,
                'cor' => $cor,
                'custo' => $custo,
                'venda' => $venda,
                'tipo_estoque' => $tipoEstoque,
                'estoque_quantidade' => $estoqueQtd,
                'fornecedor_id' => $fornecedorId,
            ]
        ];
    }

    private function parseSupplierRow(array $row, int $lineNum): array
    {
        // Colunas: Razão Social, Nome Fantasia, CNPJ/CPF, Tipo (juridica/fisica), E-mail, Telefone, WhatsApp, CEP, Cidade, Estado
        $razaoSocial = trim($row[0] ?? '');
        $nomeFantasia = trim($row[1] ?? '');
        $documento = preg_replace('/[^0-9]/', '', $row[2] ?? '');
        $tipoPessoa = strtolower(trim($row[3] ?? 'juridica'));
        $email = trim($row[4] ?? '');
        $telefone = trim($row[5] ?? '');
        $whatsapp = trim($row[6] ?? '');
        $cep = trim($row[7] ?? '');
        $cidade = trim($row[8] ?? '');
        $estado = trim($row[9] ?? '');

        $erros = [];

        if (empty($razaoSocial)) $erros[] = "Razão Social é obrigatória.";
        if (empty($documento)) $erros[] = "Documento CNPJ/CPF é obrigatório.";
        if (!in_array($tipoPessoa, ['juridica', 'fisica'])) $erros[] = "Tipo de pessoa inválido (deve ser juridica ou fisica).";

        // Verifica existência do fornecedor
        $fornecedor = Fornecedor::where('cnpj', $documento)->orWhere('cpf', $documento)->first();
        $acao = $fornecedor ? 'atualizar' : 'criar';

        return [
            'line' => $lineNum,
            'valido' => empty($erros),
            'erros' => $erros,
            'acao' => $acao,
            'data' => [
                'id' => $fornecedor?->id,
                'razao_social' => $razaoSocial,
                'nome_fantasia' => $nomeFantasia,
                'cnpj' => $tipoPessoa === 'juridica' ? $documento : null,
                'cpf' => $tipoPessoa === 'fisica' ? $documento : null,
                'tipo_pessoa' => $tipoPessoa,
                'email' => $email,
                'telefone' => $telefone,
                'whatsapp' => $whatsapp,
                'cep' => $cep,
                'cidade' => $cidade,
                'estado' => $estado,
            ]
        ];
    }

    private function importProduct(array $row, array &$summary)
    {
        $d = $row['data'];

        // Cria ou atualiza o Produto base
        $product = Produto::updateOrCreate(
            ['sku_base' => $d['sku_base']],
            [
                'nome' => $d['nome'],
                'slug' => \Illuminate\Support\Str::slug($d['nome']),
                'categoria_id' => $d['categoria_id'],
                'fornecedor_id' => $d['fornecedor_id'],
                'preco_custo' => $d['custo'],
                'preco_venda' => $d['venda'],
                'ativo' => true
            ]
        );

        // Cria ou atualiza a variação específica
        $variation = VariacaoProduto::where('sku', $d['sku_var'])->first();

        if ($variation) {
            $estoqueAntes = $variation->estoque_quantidade;
            $variation->update([
                'tamanho' => $d['tamanho'],
                'cor' => $d['cor'],
                'tipo_estoque' => $d['tipo_estoque'],
                'estoque_quantidade' => $d['estoque_quantidade'],
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
                'ativo' => true
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
            $supplier->update($d);
            $summary['atualizados']++;
        } else {
            Fornecedor::create($d);
            $summary['criados']++;
        }
    }
}

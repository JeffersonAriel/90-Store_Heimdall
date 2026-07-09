<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Storage;

class ImportExportController extends Controller
{
    protected $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    public function index()
    {
        $history = DB::table('importacoes_lote')
            ->join('funcionarios', 'importacoes_lote.funcionario_id', '=', 'funcionarios.id')
            ->select('importacoes_lote.*', 'funcionarios.nome as funcionario_nome')
            ->orderBy('importacoes_lote.id', 'desc')
            ->get();

        return Inertia::render('ImportExport/Index', [
            'history' => $history
        ]);
    }

    /**
     * Download do modelo padrão de planilha
     */
    public function downloadTemplate(string $tipo)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($tipo === 'produtos') {
            $sheet->setTitle('Produtos & Variações');
            $headers = [
                'ID Produto', 'Nome', 'Marca', 'Gênero', 'SKU Base', 'Categoria', 'Subcategorias',
                'ID Variação', 'SKU Variação', 'Tamanho', 'Cor',
                'Preço Custo', 'Preço Venda', 'Preço Promocional',
                'Tipo Estoque', 'Estoque Qtd', 'Estoque Crítico',
                'Fornecedor ID', 'Fornecedor Nome',
                'URL Foto', 'Descrição', 'Ativo',
            ];
            $sheet->fromArray([$headers], null, 'A1');

            // Linha de Exemplo
            $exampleRow = [
                '', 'Camiseta Seleção Brasileira Retrô', 'Nike', 'Unissex', 'BR-RETRO', 'Camisetas', '',
                '', 'BR-RETRO-G', 'G', 'Amarela',
                '45.00', '129.90', '',
                'proprio', '50', '5',
                '1', 'Distribuidora Esportiva XPTO Ltda',
                'https://placehold.co/600x400', 'Camiseta retrô clássica da seleção de 1998.', 'Sim'
            ];
            $sheet->fromArray([$exampleRow], null, 'A2');
        } else {
            $sheet->setTitle('Fornecedores');
            $headers = [
                'ID', 'Razão Social', 'Nome Fantasia', 'Tipo', 'CNPJ', 'CPF',
                'E-mail', 'Telefone', 'WhatsApp', 'Website',
                'CEP', 'Logradouro', 'Número', 'Complemento', 'Bairro', 'Cidade', 'Estado',
                'Condição Pagamento', 'Prazo Médio (dias)', 'Categorias Fornecidas',
                'Observações', 'Avaliação Média', 'Ativo',
            ];
            $sheet->fromArray([$headers], null, 'A1');

            $exampleRow = [
                '', 'Distribuidora Esportiva XPTO Ltda', 'XPTO Esportes', 'juridica', '12345678000199', '',
                'comercial@xptoesportes.com', '1122223333', '11999998888', 'www.xptoesportes.com',
                '08010000', 'Rua das Palmeiras', '100', 'Sala 2', 'Santa Cecília', 'São Paulo', 'SP',
                'Boleto 30 dias', '30', 'Camisetas, Calças, Bermudas',
                'Fornecedor parceiro com ótima logística.', '4.8', 'Sim'
            ];
            $sheet->fromArray([$exampleRow], null, 'A2');
        }

        // Auto-ajuste de largura de colunas
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Estilo do cabeçalho
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1a1a2e']],
        ];
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);

        $writer = new Xlsx($spreadsheet);
        $fileName = "template_{$tipo}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. urlencode($fileName) .'"');
        $writer->save('php://output');
        exit;
    }

    /**
     * Sobe o arquivo para pré-visualização e validação
     */
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'tipo' => 'required|in:produtos,fornecedores',
                'file' => 'required|mimes:xlsx,xls|max:5120',
            ]);

            $file = $request->file('file');
            $path = $file->store('imports');

            $fullPath = Storage::path($path);

            $previewData = $this->importService->preview($fullPath, $request->tipo);

            // Grava lote temporário aguardando confirmação do administrador
            $importId = DB::table('importacoes_lote')->insertGetId([
                'funcionario_id' => Auth::guard('admin')->id(),
                'tipo' => $request->tipo,
                'arquivo_original' => $file->getClientOriginalName(),
                'arquivo_path' => $path,
                'total_linhas' => $previewData['total'],
                'criados' => $previewData['criar'],
                'atualizados' => $previewData['atualizar'],
                'erros' => $previewData['erros'],
                'preview_json' => json_encode($previewData['data']),
                'status' => 'aguardando_confirmacao',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'import_id' => $importId,
                'preview' => $previewData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage() . ' (Linha ' . $e->getLine() . ' em ' . basename($e->getFile()) . ')'
            ], 500);
        }
    }

    /**
     * Confirma e processa as linhas validadas gravando as alterações no banco
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'import_id' => 'required|integer',
        ]);

        $import = DB::table('importacoes_lote')->where('id', $request->import_id)->first();

        if (!$import || $import->status !== 'aguardando_confirmacao') {
            return response()->json(['success' => false, 'message' => 'Lote de importação inválido ou já processado.'], 400);
        }

        $rows = json_decode($import->preview_json, true);

        // Processa
        $summary = $this->importService->import($rows, $import->tipo, Auth::guard('admin')->id());

        // Atualiza status do lote
        DB::table('importacoes_lote')->where('id', $request->import_id)->update([
            'criados' => $summary['criados'],
            'atualizados' => $summary['atualizados'],
            'erros' => $summary['erros'],
            'status' => 'concluido',
            'updated_at' => now()
        ]);

        // Exclui o arquivo temporário
        Storage::delete($import->arquivo_path);

        return response()->json([
            'success' => true,
            'message' => "Importação concluída: {$summary['criados']} criado(s), {$summary['atualizados']} atualizado(s) e {$summary['erros']} erro(s) pulado(s)."
        ]);
    }

    /**
     * Exporta os dados cadastrais em planilha Excel (.xlsx)
     */
    public function export(string $tipo)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($tipo === 'fornecedores') {
            $sheet->setTitle('Fornecedores');
            $headers = [
                'ID', 'Razão Social', 'Nome Fantasia', 'Tipo', 'CNPJ', 'CPF',
                'E-mail', 'Telefone', 'WhatsApp', 'Website',
                'CEP', 'Logradouro', 'Número', 'Complemento', 'Bairro', 'Cidade', 'Estado',
                'Condição Pagamento', 'Prazo Médio (dias)', 'Categorias Fornecidas',
                'Observações', 'Avaliação Média', 'Ativo',
            ];
            $sheet->fromArray([$headers], null, 'A1');

            $rows = DB::table('fornecedores')->orderBy('id')->get();
            $rowIndex = 2;
            foreach ($rows as $f) {
                $cats = '';
                if ($f->categorias_fornecidas) {
                    $decoded = json_decode($f->categorias_fornecidas, true);
                    $cats = is_array($decoded) ? implode(', ', $decoded) : $f->categorias_fornecidas;
                }
                $sheet->fromArray([[
                    $f->id, $f->razao_social, $f->nome_fantasia, $f->tipo_pessoa,
                    $f->cnpj, $f->cpf, $f->email, $f->telefone, $f->whatsapp, $f->website,
                    $f->cep, $f->logradouro, $f->numero, $f->complemento, $f->bairro, $f->cidade, $f->estado,
                    $f->condicao_pagamento, $f->prazo_medio_dias, $cats,
                    $f->observacoes, $f->avaliacao_media, $f->ativo ? 'Sim' : 'Não',
                ]], null, "A{$rowIndex}");
                $rowIndex++;
            }
            $fileName = 'exportacao_fornecedores_' . date('Y-m-d') . '.xlsx';

        } else {
            // produtos
            $sheet->setTitle('Produtos');
            $headers = [
                'ID Produto', 'Nome', 'Marca', 'Gênero', 'SKU Base', 'Categoria', 'Subcategorias',
                'ID Variação', 'SKU Variação', 'Tamanho', 'Cor',
                'Preço Custo', 'Preço Venda', 'Preço Promocional',
                'Tipo Estoque', 'Estoque Qtd', 'Estoque Crítico',
                'Fornecedor ID', 'Fornecedor Nome',
                'URL Foto', 'Descrição', 'Ativo',
            ];
            $sheet->fromArray([$headers], null, 'A1');

            $produtos = DB::table('produtos as p')
                ->leftJoin('fornecedores as f', 'p.fornecedor_id', '=', 'f.id')
                ->select('p.*', 'f.razao_social as fornecedor_nome')
                ->orderBy('p.id')
                ->get();

            $rowIndex = 2;
            foreach ($produtos as $produto) {
                // Resolver hierarquia de categorias/subcategorias dinamicamente
                $categoriaNome = '';
                $subcats = '';
                if ($produto->categoria_id) {
                    $ancestrais = [];
                    $atualId = $produto->categoria_id;
                    while ($atualId) {
                        $cat = DB::table('categorias_tipo_produto')->where('id', $atualId)->first();
                        if ($cat) {
                            $ancestrais[] = $cat->nome;
                            $atualId = $cat->parent_id;
                        } else {
                            break;
                        }
                    }
                    $ancestrais = array_reverse($ancestrais);
                    $categoriaNome = $ancestrais[0] ?? '';
                    if (count($ancestrais) > 1) {
                        $subcats = implode(' > ', array_slice($ancestrais, 1));
                    }
                }

                // Buscar variações
                $variacoes = DB::table('variacoes_produto')->where('produto_id', $produto->id)->get();

                if ($variacoes->isEmpty()) {
                    // Produto sem variações — linha única
                    $sheet->fromArray([[
                        $produto->id, $produto->nome, $produto->marca ?? '', $produto->genero ?? '',
                        $produto->sku_base ?? '', $categoriaNome, $subcats,
                        '', '', '', '',
                        $produto->preco_custo ?? '', $produto->preco_venda ?? '', $produto->preco_promocional ?? '',
                        $produto->tipo_estoque ?? 'proprio', $produto->estoque ?? 0, $produto->estoque_critico ?? 5,
                        $produto->fornecedor_id ?? '', $produto->fornecedor_nome ?? '',
                        $produto->foto_url ?? '', $produto->descricao ?? '', $produto->ativo ? 'Sim' : 'Não',
                    ]], null, "A{$rowIndex}");
                    $rowIndex++;
                } else {
                    foreach ($variacoes as $var) {
                        // Foto: tenta foto da variação, depois a foto de capa do produto
                        $fotoUrl = $var->foto_url ?? $produto->foto_url ?? '';

                        $sheet->fromArray([[
                            $produto->id, $produto->nome, $produto->marca ?? '', $produto->genero ?? '',
                            $produto->sku_base ?? '', $categoriaNome, $subcats,
                            $var->id, $var->sku ?? '', $var->tamanho ?? '', $var->cor ?? '',
                            $var->preco_adicional ?? $produto->preco_custo ?? '', $produto->preco_venda ?? '', $produto->preco_promocional ?? '',
                            $produto->tipo_estoque ?? 'proprio', $var->estoque_quantidade ?? 0, $produto->estoque_critico ?? 5,
                            $produto->fornecedor_id ?? '', $produto->fornecedor_nome ?? '',
                            $fotoUrl, $produto->descricao ?? '', $produto->ativo ? 'Sim' : 'Não',
                        ]], null, "A{$rowIndex}");
                        $rowIndex++;
                    }
                }
            }
            $fileName = 'exportacao_produtos_' . date('Y-m-d') . '.xlsx';
        }

        // Auto-ajuste de largura de colunas
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Estilo do cabeçalho
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1a1a2e']],
        ];
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($fileName) . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}

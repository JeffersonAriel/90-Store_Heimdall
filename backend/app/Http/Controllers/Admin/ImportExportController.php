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
            $headers = ['Nome', 'SKU Base', 'Categoria', 'SKU Variação', 'Tamanho', 'Cor', 'Preço Custo', 'Preço Venda', 'Tipo Estoque (proprio/dropshipping)', 'Estoque Qtd', 'Fornecedor ID'];
            $sheet->fromArray([$headers], null, 'A1');

            // Linha de Exemplo
            $exampleRow = ['Camiseta Seleção Brasileira Retrô', 'BR-RETRO', 'Camisetas', 'BR-RETRO-G', 'G', 'Amarela', '45.00', '129.90', 'proprio', '50', '1'];
            $sheet->fromArray([$exampleRow], null, 'A2');
        } else {
            $sheet->setTitle('Fornecedores');
            $headers = ['Razão Social', 'Nome Fantasia', 'CNPJ ou CPF (Apenas números)', 'Tipo (juridica/fisica)', 'E-mail', 'Telefone', 'WhatsApp', 'CEP', 'Cidade', 'Estado'];
            $sheet->fromArray([$headers], null, 'A1');

            $exampleRow = ['Distribuidora Esportiva XPTO Ltda', 'XPTO Esportes', '12345678000199', 'juridica', 'comercial@xptoesportes.com', '1122223333', '11999998888', '08010000', 'São Paulo', 'SP'];
            $sheet->fromArray([$exampleRow], null, 'A2');
        }

        // Auto-ajuste de largura de colunas
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

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
}

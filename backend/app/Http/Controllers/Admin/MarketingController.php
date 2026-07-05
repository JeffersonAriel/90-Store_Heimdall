<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cupom;
use App\Models\PontoFidelidade;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MarketingController extends Controller
{
    /**
     * Lista todos os cupons de desconto ativos e inativos
     */
    public function couponsIndex()
    {
        $coupons = Cupom::orderBy('id', 'desc')->get();
        return Inertia::render('Marketing/Coupons', [
            'coupons' => $coupons
        ]);
    }

    /**
     * Cria novos cupons de desconto (percentual, fixo, frete grátis)
     */
    public function couponsStore(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:cupons,codigo',
            'tipo' => 'required|in:percent,fixed,frete',
            'valor' => 'required|numeric|min:0',
            'valor_minimo_pedido' => 'required|numeric|min:0',
            'limite_uso_total' => 'nullable|integer|min:1',
            'limite_uso_por_cliente' => 'integer|min:1',
            'validade' => 'nullable|date',
            'ativo' => 'boolean',
        ]);

        Cupom::create($validated);

        return back()->with('success', 'Cupom de desconto criado com sucesso!');
    }

    /**
     * Altera estado de um cupom
     */
    public function couponsToggle(int $id)
    {
        $coupon = Cupom::findOrFail($id);
        $coupon->update(['ativo' => !$coupon->ativo]);

        return back()->with('success', 'Status do cupom atualizado!');
    }

    /**
     * Módulo de pontos e histórico de fidelidade
     */
    public function pointsIndex()
    {
        $pointsLogs = DB::table('pontos_fidelidade')
            ->join('clientes', 'pontos_fidelidade.cliente_id', '=', 'clientes.id')
            ->select('pontos_fidelidade.*', 'clientes.nome_completo as cliente_nome')
            ->orderBy('pontos_fidelidade.id', 'desc')
            ->paginate(15);

        $rules = DB::table('regras_pontos')->get();

        return Inertia::render('Marketing/Points', [
            'logs' => $pointsLogs,
            'rules' => $rules
        ]);
    }

    /**
     * Lista todas as indicações ativas (Indique e ganhe)
     */
    public function referralsIndex()
    {
        $referrals = DB::table('indicacoes')
            ->join('clientes as c1', 'indicacoes.cliente_indicador_id', '=', 'c1.id')
            ->leftJoin('clientes as c2', 'indicacoes.cliente_indicado_id', '=', 'c2.id')
            ->select(
                'indicacoes.*',
                'c1.nome_completo as indicador_nome',
                'c2.nome_completo as indicado_nome'
            )
            ->orderBy('indicacoes.id', 'desc')
            ->get();

        return Inertia::render('Marketing/Referrals', [
            'referrals' => $referrals
        ]);
    }
}

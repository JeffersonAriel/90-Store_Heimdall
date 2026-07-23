<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Crm\CrmTemplateMensagem;
use App\Mail\OrderStatusUpdatedMail;
use App\Mail\CrmEmailMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class MailTesterController extends Controller
{
    public function index()
    {
        $pedidos = Pedido::with(['cliente', 'itens.produto'])
            ->latest()
            ->take(15)
            ->get();

        $crmTemplates = CrmTemplateMensagem::where('tipo', 'email')
            ->select('id', 'nome', 'assunto', 'conteudo')
            ->get();

        return Inertia::render('MailTester/Index', [
            'pedidos' => $pedidos,
            'crmTemplates' => $crmTemplates,
            'mailConfig' => [
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'username' => config('mail.mailers.smtp.username'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ],
        ]);
    }

    public function sendTest(Request $request)
    {
        $validated = $request->validate([
            'email_destino'  => 'required|email',
            'tipo_teste'     => 'required|in:status_pedido,crm_template,mensagem_livre',
            'pedido_id'      => 'nullable|exists:pedidos,id',
            'status_simulado'=> 'nullable|string',
            'template_id'    => 'nullable|exists:crm_templates_mensagem,id',
            'assunto_livre'  => 'nullable|string',
            'mensagem_livre' => 'nullable|string',
        ]);

        $emailDestino = $validated['email_destino'];

        try {
            if ($validated['tipo_teste'] === 'status_pedido') {
                $pedido = Pedido::with(['cliente', 'endereco', 'itens.produto'])
                    ->find($validated['pedido_id'] ?? null);

                if (!$pedido) {
                    $pedido = Pedido::with(['cliente', 'endereco', 'itens.produto'])->latest()->first();
                }

                if (!$pedido) {
                    return back()->with('error', 'Nenhum pedido encontrado no sistema para teste. Crie um pedido antes.');
                }

                // Se o usuário selecionou um status simulado
                if (!empty($validated['status_simulado'])) {
                    $pedido->status = $validated['status_simulado'];
                }

                Mail::to($emailDestino)->send(new OrderStatusUpdatedMail($pedido));

                return back()->with('success', "E-mail de teste de Status do Pedido (#{$pedido->id} — " . OrderStatusUpdatedMail::getStatusLabel($pedido->status) . ") enviado com sucesso para {$emailDestino}!");

            } elseif ($validated['tipo_teste'] === 'crm_template') {
                $template = CrmTemplateMensagem::find($validated['template_id'] ?? null);
                
                $assunto = $template ? $template->assunto : ($validated['assunto_livre'] ?: 'Teste de Template CRM');
                $conteudo = $template ? $template->conteudo : ($validated['mensagem_livre'] ?: 'Este é um teste de e-mail do CRM.');
                $nomeCliente = $request->input('nome_cliente', 'Cliente Teste');

                Mail::to($emailDestino)->send(new CrmEmailMail($nomeCliente, $assunto, $conteudo));

                return back()->with('success', "E-mail de teste de Template CRM enviado com sucesso para {$emailDestino}!");

            } else {
                // Mensagem livre
                $assunto = $validated['assunto_livre'] ?: 'Teste de Conexão Titan Mail - 90 Store';
                $conteudo = $validated['mensagem_livre'] ?: 'Este é um e-mail de teste de verificação do Titan Mail (HostGator) enviado do painel Heimdall.';
                $nomeCliente = $request->input('nome_cliente', 'Cliente Teste');

                Mail::to($emailDestino)->send(new CrmEmailMail($nomeCliente, $assunto, $conteudo));

                return back()->with('success', "E-mail de teste livre enviado com sucesso para {$emailDestino}!");
            }

        } catch (\Throwable $e) {
            return back()->with('error', "Falha ao enviar e-mail via HostGator Titan Mail: " . $e->getMessage());
        }
    }
}

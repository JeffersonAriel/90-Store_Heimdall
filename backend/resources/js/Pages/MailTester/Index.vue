<template>
  <AdminLayout title="Testador de E-mails">
    <template #breadcrumb>
      <span>Configurações</span>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">Testador de E-mails</span>
    </template>

    <div class="page-header">
      <div class="page-header-left">
        <h1 class="page-title">
          <span class="page-title-icon">📧</span>
          Testador de E-mails Automáticos
        </h1>
        <p class="page-subtitle">
          Dispare e-mails de teste em tempo real via HostGator Titan Mail para qualquer endereço e valide os modelos de Pedidos e CRM.
        </p>
      </div>
    </div>

    <!-- Card de Configuração do Servidor SMTP Titan Mail -->
    <div class="card mb-6">
      <div class="card-header flex justify-between items-center">
        <h3 class="card-title text-brand">⚙️ Configuração Atual do Servidor (Titan Mail HostGator)</h3>
        <span class="badge badge-success">Porta 465 (SSL / smtps)</span>
      </div>
      <div class="card-body grid-4">
        <div>
          <span class="text-secondary block text-xs">Host SMTP</span>
          <strong class="font-mono">{{ mailConfig.host || 'smtp.titan.email' }}</strong>
        </div>
        <div>
          <span class="text-secondary block text-xs">Porta & Criptografia</span>
          <strong class="font-mono">{{ mailConfig.port || '465' }} (SSL)</strong>
        </div>
        <div>
          <span class="text-secondary block text-xs">Usuário / Remetente</span>
          <strong class="font-mono">{{ mailConfig.username || 'noreply@90store.com.br' }}</strong>
        </div>
        <div>
          <span class="text-secondary block text-xs">Nome do Remetente</span>
          <strong>{{ mailConfig.from_name || '90 Store' }}</strong>
        </div>
      </div>
    </div>

    <!-- Formulário do Testador -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">🚀 Disparar E-mail de Teste</h3>
      </div>
      <div class="card-body">
        <form @submit.prevent="submitTest" class="flex flex-col gap-6">

          <!-- E-mail de Destino -->
          <div class="form-group">
            <label class="form-label font-bold">
              ✉️ Digite o E-mail de Destino para Receber o Teste
            </label>
            <input
              v-model="form.email_destino"
              type="email"
              class="form-control text-lg"
              placeholder="Ex: seuemail@gmail.com ou contato@90store.com.br"
              required
            />
            <span class="form-hint">O teste será entregue diretamente nesta caixa de entrada.</span>
          </div>

          <!-- Seleção do Tipo de Teste -->
          <div class="form-group">
            <label class="form-label font-bold">Qual modelo você deseja testar?</label>
            <div class="grid-3 gap-4">
              <label
                class="p-4 border rounded-lg cursor-pointer flex flex-col gap-2 transition-all"
                :class="form.tipo_teste === 'status_pedido' ? 'border-brand bg-brand-surface' : 'border-border'"
              >
                <div class="flex items-center gap-2">
                  <input type="radio" v-model="form.tipo_teste" value="status_pedido" />
                  <strong>📦 Status do Pedido</strong>
                </div>
                <span class="text-xs text-secondary">
                  Modelos enviados quando o pedido muda para Pago, Enviado com rastreio, Entregue, etc.
                </span>
              </label>

              <label
                class="p-4 border rounded-lg cursor-pointer flex flex-col gap-2 transition-all"
                :class="form.tipo_teste === 'crm_template' ? 'border-brand bg-brand-surface' : 'border-border'"
              >
                <div class="flex items-center gap-2">
                  <input type="radio" v-model="form.tipo_teste" value="crm_template" />
                  <strong>📢 Template CRM / Automações</strong>
                </div>
                <span class="text-xs text-secondary">
                  Modelos de e-mail cadastrados nas campanhas e réguas do CRM.
                </span>
              </label>

              <label
                class="p-4 border rounded-lg cursor-pointer flex flex-col gap-2 transition-all"
                :class="form.tipo_teste === 'mensagem_livre' ? 'border-brand bg-brand-surface' : 'border-border'"
              >
                <div class="flex items-center gap-2">
                  <input type="radio" v-model="form.tipo_teste" value="mensagem_livre" />
                  <strong>✍️ Mensagem Livre / Diagnóstico</strong>
                </div>
                <span class="text-xs text-secondary">
                  E-mail de diagnóstico livre para validar apenas a autenticação SMTP do Titan.
                </span>
              </label>
            </div>
          </div>

          <!-- Campos Específicos para Status do Pedido -->
          <div v-if="form.tipo_teste === 'status_pedido'" class="p-4 rounded-lg bg-elevated border border-border flex flex-col gap-4">
            <h4 class="font-semibold text-brand">Configuração da Simulação do Pedido</h4>

            <div class="grid-2">
              <div class="form-group">
                <label class="form-label">Selecione o Pedido de Exemplo (Opcional)</label>
                <select v-model="form.pedido_id" class="form-control">
                  <option :value="null">Usar último pedido cadastrado no sistema</option>
                  <option v-for="ped in pedidos" :key="ped.id" :value="ped.id">
                    Pedido #{{ ped.id }} — R$ {{ formatMoney(ped.total) }} ({{ ped.cliente ? ped.cliente.nome_completo : 'Cliente' }})
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Status a Simular no E-mail</label>
                <select v-model="form.status_simulado" class="form-control">
                  <option value="aguardando_pagamento">⏳ Aguardando Pagamento</option>
                  <option value="em_separacao">💰 Pagamento Confirmado — Em Separação</option>
                  <option value="em_envio">🚚 Em Rota de Envio</option>
                  <option value="enviado">📦 Pedido Enviado (Com Rastreio)</option>
                  <option value="entregue">✅ Pedido Entregue</option>
                  <option value="cancelado">🚫 Pedido Cancelado</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Campos Específicos para Templates CRM -->
          <div v-if="form.tipo_teste === 'crm_template'" class="p-4 rounded-lg bg-elevated border border-border flex flex-col gap-4">
            <h4 class="font-semibold text-brand">Seleção do Template do CRM</h4>
            <div class="form-group">
              <label class="form-label">Template Cadastrado no CRM</label>
              <select v-model="form.template_id" class="form-control">
                <option :value="null" v-if="crmTemplates.length === 0">Nenhum template de e-mail cadastrado (Usará mensagem padrão)</option>
                <option v-for="tpl in crmTemplates" :key="tpl.id" :value="tpl.id">
                  {{ tpl.nome }} (Assunto: {{ tpl.assunto || 'Sem assunto' }})
                </option>
              </select>
            </div>
          </div>

          <!-- Campos Específicos para Mensagem Livre -->
          <div v-if="form.tipo_teste === 'mensagem_livre' || form.tipo_teste === 'crm_template'" class="flex flex-col gap-4">
            <div class="form-group">
              <label class="form-label">Assunto do E-mail</label>
              <input
                v-model="form.assunto_livre"
                type="text"
                class="form-control"
                placeholder="Ex: Oferta Exclusiva da 90 Store para você!"
              />
            </div>

            <div class="form-group">
              <label class="form-label">Mensagem do E-mail</label>
              <textarea
                v-model="form.mensagem_livre"
                rows="4"
                class="form-control"
                placeholder="Escreva aqui o texto do e-mail..."
              ></textarea>
            </div>
          </div>

          <!-- Botão de Envio -->
          <div class="flex justify-end mt-4">
            <button
              type="submit"
              class="btn btn-primary text-lg"
              :disabled="submitting || !form.email_destino"
            >
              <span v-if="submitting">⏳ Enviando via Titan Mail...</span>
              <span v-else>🚀 Enviar E-mail de Teste Agora</span>
            </button>
          </div>

        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  pedidos: {
    type: Array,
    default: () => []
  },
  crmTemplates: {
    type: Array,
    default: () => []
  },
  mailConfig: {
    type: Object,
    default: () => ({})
  }
})

const submitting = ref(false)

const form = ref({
  email_destino: '',
  tipo_teste: 'status_pedido',
  pedido_id: null,
  status_simulado: 'enviado',
  template_id: null,
  assunto_livre: 'Teste de Envio - Heimdall 90 Store',
  mensagem_livre: 'Olá! Este é um e-mail de teste disparado via HostGator Titan Mail.'
})

function submitTest() {
  submitting.value = true
  router.post(route('admin.mail-tester.send'), form.value, {
    preserveScroll: true,
    onFinish: () => {
      submitting.value = false
    }
  })
}

function formatMoney(val) {
  if (!val) return '0,00'
  return parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.bg-brand-surface {
  background-color: var(--color-brand-surface);
}
.border-brand {
  border-color: var(--color-brand);
}
</style>

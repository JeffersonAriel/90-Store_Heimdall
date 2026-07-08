<template>
  <AdminLayout :title="`Fornecedor: ${supplier.razao_social}`">
    <template #breadcrumb>
      <span>
        <Link :href="route('admin.suppliers.index')" class="text-brand hover:underline" style="color: var(--color-brand)">Fornecedores</Link>
        / Detalhes
      </span>
    </template>

    <div class="page-header mb-6" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
      <div>
        <h1 class="page-title">🏭 {{ supplier.razao_social }}</h1>
        <p class="text-secondary mt-1">{{ supplier.nome_fantasia || 'Sem nome fantasia' }} · Cadastro de Fornecedor</p>
      </div>
      <div class="flex gap-2" style="display: flex; gap: 0.5rem;">
        <Link :href="route('admin.suppliers.edit', supplier.id)" class="btn btn-secondary">
          ✏️ Editar Fornecedor
        </Link>
        <Link :href="route('admin.suppliers.index')" class="btn btn-secondary">
          Voltar
        </Link>
      </div>
    </div>

    <div class="grid-3 gap-6 mb-6" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
      <!-- Coluna 1 & 2: Detalhes do Fornecedor e Produtos -->
      <div style="grid-column: span 2; display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Ficha Cadastral -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">📋 Informações Cadastrais</h3>
          </div>
          <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem;">
              <div>
                <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-secondary); font-weight: bold; margin-bottom: 0.25rem;">Razão Social</label>
                <p style="font-weight: 500; font-size: 1.125rem; margin: 0; color: var(--color-text-primary);">{{ supplier.razao_social }}</p>
              </div>
              <div>
                <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-secondary); font-weight: bold; margin-bottom: 0.25rem;">Nome Fantasia</label>
                <p style="font-weight: 500; font-size: 1.125rem; margin: 0; color: var(--color-text-primary);">{{ supplier.nome_fantasia || '—' }}</p>
              </div>
              <div>
                <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-secondary); font-weight: bold; margin-bottom: 0.25rem;">Documento (CNPJ/CPF)</label>
                <p style="font-family: monospace; color: var(--color-text-secondary); margin: 0;">{{ supplier.tipo_pessoa === 'juridica' ? supplier.cnpj : supplier.cpf }}</p>
              </div>
              <div>
                <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-secondary); font-weight: bold; margin-bottom: 0.25rem;">Tipo de Pessoa</label>
                <p style="color: var(--color-text-secondary); text-transform: capitalize; margin: 0;">{{ supplier.tipo_pessoa }}</p>
              </div>
              <div>
                <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-secondary); font-weight: bold; margin-bottom: 0.25rem;">E-mail</label>
                <p style="color: var(--color-text-secondary); margin: 0;">{{ supplier.email || '—' }}</p>
              </div>
              <div>
                <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-secondary); font-weight: bold; margin-bottom: 0.25rem;">Telefone / WhatsApp</label>
                <p style="color: var(--color-text-secondary); margin: 0;">
                  {{ supplier.telefone || '—' }} 
                  <span v-if="supplier.whatsapp"> / {{ supplier.whatsapp }}</span>
                </p>
              </div>
              <div>
                <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-secondary); font-weight: bold; margin-bottom: 0.25rem;">Condição de Pagamento</label>
                <p style="color: var(--color-text-secondary); margin: 0;">{{ supplier.condicao_pagamento || 'Faturamento padrão' }}</p>
              </div>
              <div>
                <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-secondary); font-weight: bold; margin-bottom: 0.25rem;">Prazo Médio de Entrega</label>
                <p style="color: var(--color-text-secondary); font-weight: bold; margin: 0;">{{ supplier.prazo_medio_dias }} dias</p>
              </div>
            </div>
            
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--color-border);">
              <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-secondary); font-weight: bold; margin-bottom: 0.25rem;">Endereço Completo</label>
              <p style="color: var(--color-text-secondary); margin: 0;" v-if="supplier.logradouro">
                {{ supplier.logradouro }}, {{ supplier.numero }} <span v-if="supplier.complemento">({{ supplier.complemento }})</span><br>
                {{ supplier.bairro }} · {{ supplier.cidade }} - {{ supplier.estado }} · CEP: {{ supplier.cep }}
              </p>
              <p style="color: var(--color-text-secondary); margin: 0;" v-else>— Endereço não informado —</p>
            </div>

            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--color-border);" v-if="supplier.observacoes">
              <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-secondary); font-weight: bold; margin-bottom: 0.25rem;">Observações Internas</label>
              <p style="color: var(--color-text-secondary); font-style: italic; white-space: pre-line; margin: 0;">{{ supplier.observacoes }}</p>
            </div>
          </div>
        </div>

        <!-- Produtos Vinculados -->
        <div class="card">
          <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title">📦 Produtos Fornecidos</h3>
            <span class="badge badge-primary">{{ supplier.produtos?.length || 0 }} produtos</span>
          </div>
          <div class="card-body" style="padding: 0;">
            <div v-if="!supplier.produtos || supplier.produtos.length === 0" class="alert alert-secondary" style="margin: 1.5rem;">
              Nenhum produto associado a este fornecedor.
            </div>
            <div v-else class="table-wrapper" style="max-height: 300px; overflow-y: auto;">
              <table>
                <thead>
                  <tr>
                    <th>SKU Base</th>
                    <th>Nome do Produto</th>
                    <th style="text-align: right;">Preço Venda</th>
                    <th style="text-align: center;">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="prod in supplier.produtos" :key="prod.id">
                    <td class="font-mono text-secondary">{{ prod.sku_base }}</td>
                    <td><strong>{{ prod.nome }}</strong></td>
                    <td style="text-align: right; font-weight: bold; color: var(--color-success);">R$ {{ Number(prod.preco_venda).toFixed(2) }}</td>
                    <td style="text-align: center;">
                      <span :class="prod.ativo ? 'badge badge-success' : 'badge badge-danger'">
                        {{ prod.ativo ? 'Ativo' : 'Inativo' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Coluna 3: Avaliações e Feedback -->
      <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Rating Score Card -->
        <div class="card" style="background: linear-gradient(135deg, rgba(99,102,241,0.05) 0%, rgba(20,20,50,0.2) 100%);">
          <div class="card-body" style="padding: 2rem 1.5rem; text-align: center;">
            <h4 style="color: var(--color-text-secondary); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; font-weight: bold; margin-bottom: 0.5rem; margin-top: 0;">Avaliação Média</h4>
            <div style="font-size: 3rem; font-weight: 800; color: var(--color-text-primary); line-height: 1;">
              {{ Number(supplier.avaliacao_media || 0).toFixed(1) }}
            </div>
            <div style="font-size: 1.5rem; display: flex; justify-content: center; gap: 0.25rem; margin-top: 0.75rem; margin-bottom: 0.75rem;">
              <span v-for="i in 5" :key="i" :style="i <= Math.round(supplier.avaliacao_media || 0) ? 'color: var(--color-warning);' : 'color: var(--color-border);'">
                ★
              </span>
            </div>
            <p style="font-size: 0.75rem; color: var(--color-text-secondary); margin: 0;">Baseado em {{ supplier.avaliacoes?.length || 0 }} avaliações</p>
          </div>
        </div>

        <!-- Formulário de Nova Avaliação -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">⭐ Avaliar Fornecedor</h3>
          </div>
          <div class="card-body">
            <form @submit.prevent="submitEvaluation" style="display: flex; flex-direction: column; gap: 1rem;">
              <div class="form-group">
                <label class="form-label" style="margin-bottom: 0.5rem; display: block;">Estrelas (Pontuação)</label>
                <div style="display: flex; gap: 0.5rem; font-size: 1.75rem;">
                  <button 
                    v-for="star in 5" 
                    :key="star" 
                    type="button" 
                    class="star-btn"
                    :style="star <= evalForm.estrelas ? 'color: var(--color-warning);' : 'color: var(--color-border);'"
                    @click="evalForm.estrelas = star"
                    style="background: none; border: none; cursor: pointer; padding: 0;"
                  >
                    ★
                  </button>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Comentário / Feedback</label>
                <textarea 
                  v-model="evalForm.comentario" 
                  class="form-control" 
                  rows="3" 
                  placeholder="Deixe um comentário sobre a qualidade, prazos de entrega ou atendimento..."
                  required
                ></textarea>
              </div>

              <button type="submit" class="btn btn-primary" style="width: 100%;" :disabled="loading">
                {{ loading ? 'Enviando...' : 'Salvar Avaliação' }}
              </button>
            </form>
          </div>
        </div>

        <!-- Lista de Comentários / Feedbacks -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">💬 Histórico de Feedbacks</h3>
          </div>
          <div class="card-body" style="padding: 0; max-height: 400px; overflow-y: auto;">
            <div v-if="!supplier.avaliacoes || supplier.avaliacoes.length === 0" style="font-size: 0.875rem; text-align: center; padding: 1.5rem 0; color: var(--color-text-secondary);">
              Nenhuma avaliação registrada ainda.
            </div>
            <div v-else style="display: flex; flex-direction: column;">
              <div 
                v-for="av in supplier.avaliacoes" 
                :key="av.id" 
                style="padding: 1rem; border-bottom: 1px solid var(--color-border);"
              >
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                  <span style="font-weight: bold; font-size: 0.875rem; color: var(--color-text-primary);">{{ av.funcionario?.nome || 'Funcionário' }}</span>
                  <span style="font-size: 0.75rem; color: var(--color-text-secondary);">{{ new Date(av.created_at).toLocaleDateString('pt-BR') }}</span>
                </div>
                <div style="display: flex; gap: 0.15rem; font-size: 0.875rem; margin-bottom: 0.5rem;">
                  <span v-for="i in 5" :key="i" :style="i <= av.estrelas ? 'color: var(--color-warning);' : 'color: var(--color-border);'">
                    ★
                  </span>
                </div>
                <p style="font-size: 0.875rem; color: var(--color-text-secondary); margin: 0; line-height: 1.4; word-break: break-word;">{{ av.comentario }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  supplier: { type: Object, required: true }
})

const evalForm = ref({
  estrelas: 5,
  comentario: ''
})

const loading = ref(false)

function submitEvaluation() {
  loading.value = true
  router.post(route('admin.suppliers.evaluate', props.supplier.id), evalForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      evalForm.value.comentario = ''
      evalForm.value.estrelas = 5
      loading.value = false
    },
    onError: () => {
      loading.value = false
    }
  })
}
</script>

<style scoped>
.star-btn {
  transition: transform 0.15s ease;
}
.star-btn:hover {
  transform: scale(1.2);
}
</style>

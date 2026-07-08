<template>
  <AdminLayout :title="supplier.razao_social">
    <template #breadcrumb>
      <span><Link :href="route('admin.suppliers.index')" class="text-brand hover:underline">Fornecedores</Link> / {{ supplier.razao_social }}</span>
    </template>

    <!-- Cabeçalho -->
    <div class="card mb-6" style="background: linear-gradient(135deg, var(--color-bg-elevated), var(--color-bg-card)); border: 1px solid var(--color-border);">
      <div class="card-body">
        <div class="flex items-start justify-between flex-wrap gap-4">
          <div>
            <div class="flex items-center gap-3 mb-1">
              <span class="badge" :class="supplier.ativo ? 'badge-success' : 'badge-danger'">
                {{ supplier.ativo ? 'Ativo' : 'Inativo' }}
              </span>
              <span class="badge badge-secondary text-xs">{{ supplier.tipo_pessoa === 'juridica' ? 'PJ' : 'PF' }}</span>
            </div>
            <h1 class="text-3xl font-bold" style="color: var(--color-text-primary);">{{ supplier.razao_social }}</h1>
            <p v-if="supplier.nome_fantasia" class="text-lg mt-1" style="color: var(--color-text-secondary);">{{ supplier.nome_fantasia }}</p>
          </div>
          <!-- Score médio -->
          <div class="text-center px-6 py-4 rounded-xl" style="background: var(--color-bg); border: 2px solid var(--color-brand-muted);">
            <div class="text-5xl font-black mb-1" :style="{ color: scoreColor }">{{ formattedScore }}</div>
            <div class="flex justify-center gap-1 mb-1">
              <span v-for="i in 5" :key="i" class="text-xl" :style="{ color: i <= Math.round(supplier.avaliacao_media || 0) ? '#f59e0b' : 'var(--color-border)' }">★</span>
            </div>
            <div class="text-xs" style="color: var(--color-text-muted);">{{ supplier.avaliacoes?.length || 0 }} avaliação(ões)</div>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Coluna Esquerda - Ficha Cadastral + Produtos -->
      <div class="lg:col-span-2 flex flex-col gap-6">

        <!-- Ficha Cadastral -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">📋 Dados Cadastrais</h3>
          </div>
          <div class="card-body">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div v-if="supplier.cnpj">
                <p class="text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--color-text-muted);">CNPJ</p>
                <p class="font-mono font-semibold" style="color: var(--color-text-primary);">{{ supplier.cnpj }}</p>
              </div>
              <div v-if="supplier.cpf">
                <p class="text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--color-text-muted);">CPF</p>
                <p class="font-mono font-semibold" style="color: var(--color-text-primary);">{{ supplier.cpf }}</p>
              </div>
              <div v-if="supplier.email">
                <p class="text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--color-text-muted);">E-mail</p>
                <a :href="'mailto:' + supplier.email" class="text-brand hover:underline">{{ supplier.email }}</a>
              </div>
              <div v-if="supplier.telefone">
                <p class="text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--color-text-muted);">Telefone</p>
                <p style="color: var(--color-text-primary);">{{ supplier.telefone }}</p>
              </div>
              <div v-if="supplier.whatsapp">
                <p class="text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--color-text-muted);">WhatsApp</p>
                <a :href="'https://wa.me/55' + supplier.whatsapp.replace(/\D/g,'')" target="_blank" class="text-brand hover:underline">{{ supplier.whatsapp }}</a>
              </div>
              <div v-if="supplier.website">
                <p class="text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--color-text-muted);">Website</p>
                <a :href="supplier.website" target="_blank" class="text-brand hover:underline truncate block">{{ supplier.website }}</a>
              </div>
              <div v-if="endereco" class="sm:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--color-text-muted);">Endereço</p>
                <p style="color: var(--color-text-primary);">{{ endereco }}</p>
              </div>
              <div v-if="supplier.condicao_pagamento">
                <p class="text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--color-text-muted);">Condição de Pagamento</p>
                <p style="color: var(--color-text-primary);">{{ supplier.condicao_pagamento }}</p>
              </div>
              <div v-if="supplier.prazo_medio_dias">
                <p class="text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--color-text-muted);">Prazo Médio</p>
                <p style="color: var(--color-text-primary);">{{ supplier.prazo_medio_dias }} dias</p>
              </div>
              <div v-if="supplier.categorias_fornecidas?.length" class="sm:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-wide mb-2" style="color: var(--color-text-muted);">Categorias Fornecidas</p>
                <div class="flex flex-wrap gap-2">
                  <span v-for="cat in supplier.categorias_fornecidas" :key="cat" class="badge badge-primary">{{ cat }}</span>
                </div>
              </div>
              <div v-if="supplier.observacoes" class="sm:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--color-text-muted);">Observações</p>
                <p class="text-sm" style="color: var(--color-text-secondary); white-space: pre-wrap;">{{ supplier.observacoes }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Produtos -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">📦 Produtos Fornecidos ({{ supplier.produtos?.length || 0 }})</h3>
          </div>
          <div class="card-body p-0">
            <div v-if="!supplier.produtos?.length" class="p-6 text-center" style="color: var(--color-text-muted);">
              Nenhum produto vinculado a este fornecedor.
            </div>
            <table v-else class="w-full text-sm">
              <thead>
                <tr style="border-bottom: 1px solid var(--color-border); background: var(--color-bg-elevated);">
                  <th class="text-left px-4 py-3 font-semibold" style="color: var(--color-text-muted);">SKU</th>
                  <th class="text-left px-4 py-3 font-semibold" style="color: var(--color-text-muted);">Produto</th>
                  <th class="text-right px-4 py-3 font-semibold" style="color: var(--color-text-muted);">Preço</th>
                  <th class="text-center px-4 py-3 font-semibold" style="color: var(--color-text-muted);">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="produto in supplier.produtos" :key="produto.id" style="border-bottom: 1px solid var(--color-border);">
                  <td class="px-4 py-3 font-mono text-xs" style="color: var(--color-text-muted);">{{ produto.sku || '-' }}</td>
                  <td class="px-4 py-3" style="color: var(--color-text-primary);">{{ produto.nome }}</td>
                  <td class="px-4 py-3 text-right font-semibold" style="color: var(--color-brand);">{{ formatPrice(produto.preco_venda) }}</td>
                  <td class="px-4 py-3 text-center">
                    <span class="badge" :class="produto.ativo ? 'badge-success' : 'badge-danger'">{{ produto.ativo ? 'Ativo' : 'Inativo' }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- Coluna Direita - Avaliações -->
      <div class="flex flex-col gap-6">

        <!-- Formulário de Avaliação -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">⭐ Registrar Avaliação</h3>
          </div>
          <div class="card-body">
            <form @submit.prevent="submitEvaluation">
              <!-- Seletor de estrelas -->
              <div class="mb-4">
                <label class="form-label mb-2">Nota *</label>
                <div class="flex gap-2">
                  <button
                    v-for="i in 5"
                    :key="i"
                    type="button"
                    @click="evalForm.estrelas = i"
                    @mouseenter="hoveredStar = i"
                    @mouseleave="hoveredStar = 0"
                    class="text-4xl transition-transform hover:scale-125 focus:outline-none"
                    :style="{ color: i <= (hoveredStar || evalForm.estrelas) ? '#f59e0b' : 'var(--color-border)', cursor: 'pointer' }"
                  >★</button>
                </div>
                <p class="text-xs mt-1" style="color: var(--color-text-muted);">
                  {{ starLabel }}
                </p>
              </div>

              <div class="form-group mb-4">
                <label class="form-label">Comentário *</label>
                <textarea
                  v-model="evalForm.comentario"
                  class="form-textarea"
                  rows="4"
                  placeholder="Descreva sua experiência com este fornecedor..."
                  required
                ></textarea>
              </div>

              <button
                type="submit"
                class="btn btn-primary w-full"
                :disabled="evalForm.processing || !evalForm.estrelas"
              >
                {{ evalForm.processing ? 'Salvando...' : 'Registrar Avaliação' }}
              </button>
            </form>
          </div>
        </div>

        <!-- Histórico de Avaliações -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">📝 Histórico de Avaliações</h3>
          </div>
          <div class="card-body p-0">
            <div v-if="!supplier.avaliacoes?.length" class="p-4 text-center text-sm" style="color: var(--color-text-muted);">
              Nenhuma avaliação registrada ainda.
            </div>
            <div v-else>
              <div
                v-for="avaliacao in supplier.avaliacoes"
                :key="avaliacao.id"
                class="p-4"
                style="border-bottom: 1px solid var(--color-border);"
              >
                <div class="flex items-start justify-between gap-2 mb-2">
                  <div>
                    <p class="text-sm font-semibold" style="color: var(--color-text-primary);">
                      {{ avaliacao.funcionario?.name || 'Funcionário' }}
                    </p>
                    <p class="text-xs" style="color: var(--color-text-muted);">{{ formatDate(avaliacao.created_at) }}</p>
                  </div>
                  <div class="flex gap-0.5">
                    <span v-for="i in 5" :key="i" class="text-sm" :style="{ color: i <= avaliacao.estrelas ? '#f59e0b' : 'var(--color-border)' }">★</span>
                  </div>
                </div>
                <p v-if="avaliacao.comentario" class="text-sm" style="color: var(--color-text-secondary); font-style: italic;">
                  "{{ avaliacao.comentario }}"
                </p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Rodapé de Ações -->
    <div class="mt-6 flex justify-between items-center">
      <Link :href="route('admin.suppliers.index')" class="btn btn-secondary">← Voltar para Fornecedores</Link>
      <Link :href="route('admin.suppliers.edit', supplier.id)" class="btn btn-primary">✏️ Editar Fornecedor</Link>
    </div>

  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  supplier: { type: Object, required: true }
});

// Formulário de avaliação
const evalForm = useForm({
  estrelas: 0,
  comentario: '',
});

const hoveredStar = ref(0);

const starLabels = {
  0: 'Selecione uma nota',
  1: '1 ★ — Muito ruim',
  2: '2 ★★ — Ruim',
  3: '3 ★★★ — Regular',
  4: '4 ★★★★ — Bom',
  5: '5 ★★★★★ — Excelente!',
};

const starLabel = computed(() => starLabels[hoveredStar.value || evalForm.estrelas]);

function submitEvaluation() {
  evalForm.post(route('admin.suppliers.evaluate', props.supplier.id), {
    onSuccess: () => {
      evalForm.reset();
    }
  });
}

// Computed helpers
const endereco = computed(() => {
  const s = props.supplier;
  const parts = [
    s.logradouro,
    s.numero ? `nº ${s.numero}` : null,
    s.complemento,
    s.bairro,
    s.cidade && s.estado ? `${s.cidade} - ${s.estado}` : s.cidade || s.estado,
    s.cep,
  ].filter(Boolean);
  return parts.join(', ');
});

const formattedScore = computed(() => {
  const score = props.supplier.avaliacao_media;
  if (!score) return '—';
  return Number(score).toFixed(1);
});

const scoreColor = computed(() => {
  const score = props.supplier.avaliacao_media || 0;
  if (score >= 4.5) return '#22c55e';
  if (score >= 3)   return '#f59e0b';
  if (score >= 1)   return '#ef4444';
  return 'var(--color-text-muted)';
});

function formatPrice(value) {
  if (!value) return '-';
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

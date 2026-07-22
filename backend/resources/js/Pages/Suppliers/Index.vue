<template>
  <AdminLayout title="Fornecedores">
    <template #breadcrumb>
      <span class="text-muted">Catálogo</span>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">Fornecedores</span>
    </template>

    <div class="page-header">
      <div class="page-header-left">
        <h1 class="page-title">
          <span class="page-title-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
          </span>
          Fornecedores
        </h1>
        <p class="page-subtitle">Cadastre, filtre e avalie os fornecedores da sua loja.</p>
      </div>
      <div class="page-actions">
        <Link :href="route('admin.suppliers.create')" class="btn btn-primary">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Cadastrar Fornecedor
        </Link>
      </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-6">
      <div class="card-body">
        <form @submit.prevent="handleSearch" class="flex gap-3 items-end flex-wrap">
          <div class="form-group flex-1" style="min-width: 220px; margin-bottom: 0;">
            <label class="form-label">Buscar fornecedor ou produto</label>
            <div class="form-input-wrap">
              <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
              </svg>
              <input v-model="form.search" type="text" class="form-input" placeholder="Razão social, CNPJ, website ou nome do produto..." />
            </div>
          </div>

          <div class="form-group" style="min-width: 220px; margin-bottom: 0;">
            <label class="form-label">Filtrar por Produto</label>
            <select v-model="form.product_id" class="form-select">
              <option value="">Todos os produtos</option>
              <option v-for="prod in products" :key="prod.id" :value="prod.id">
                {{ prod.nome }}
              </option>
            </select>
          </div>

          <div class="flex gap-2" style="flex-shrink: 0;">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <button type="button" @click="resetFilters" class="btn btn-secondary">Limpar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabela -->
    <div class="card">
      <div v-if="suppliers.data.length === 0" class="empty-state">
        <div class="empty-state-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
          </svg>
        </div>
        <p class="empty-state-title">Nenhum fornecedor encontrado</p>
        <p class="empty-state-desc">Tente alterar os termos de busca ou cadastrar um novo fornecedor.</p>
      </div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Nome / Razão Social</th>
              <th>Documento</th>
              <th>Contato & Website</th>
              <th>Prazo Médio</th>
              <th>Avaliação</th>
              <th>Produtos Cadastrados</th>
              <th>Status</th>
              <th style="width: 140px; text-align: right;">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="sup in suppliers.data" :key="sup.id">
              <td data-label="Nome">
                <div class="font-semibold">{{ sup.razao_social }}</div>
                <div v-if="sup.nome_fantasia" class="text-muted" style="font-size: 0.8125rem;">{{ sup.nome_fantasia }}</div>
              </td>
              <td data-label="Documento">
                <span class="font-mono text-secondary" style="font-size: 0.8125rem;">{{ sup.tipo_pessoa === 'juridica' ? sup.cnpj : sup.cpf }}</span>
              </td>
              <td data-label="Contato & Website">
                <div v-if="sup.telefone" class="text-secondary" style="font-size: 0.8125rem;">📞 {{ sup.telefone }}</div>
                <div v-if="sup.whatsapp" class="mt-0.5">
                  <a :href="`https://wa.me/${sup.whatsapp.replace(/\D/g, '')}`" target="_blank" class="text-brand" style="font-size: 0.8125rem;">
                    💬 {{ sup.whatsapp }}
                  </a>
                </div>
                <div v-if="sup.website" class="mt-1">
                  <a :href="formatUrl(sup.website)" target="_blank" class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded bg-blue-900/30 text-blue-300 hover:text-blue-200 border border-blue-700/40" style="font-size: 0.75rem; text-decoration: none;">
                    <svg width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; flex-shrink: 0; display: inline-block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    <span>Website / Catálogo</span>
                  </a>
                </div>
              </td>
              <td data-label="Prazo Médio">
                <span class="badge badge-secondary">{{ sup.prazo_medio_dias }} dias</span>
              </td>
              <td data-label="Avaliação">
                <div class="flex items-center gap-1">
                  <span v-for="i in 5" :key="i" style="font-size: 0.875rem;" :style="i <= Math.round(sup.avaliacao_media) ? 'color: var(--color-warning);' : 'color: var(--color-border);'">★</span>
                  <span class="text-muted" style="font-size: 0.75rem;">({{ sup.avaliacao_media }})</span>
                </div>
              </td>
              <td data-label="Produtos Cadastrados">
                <div class="flex flex-col gap-1">
                  <div class="flex items-center gap-2">
                    <span class="badge badge-primary font-bold">{{ sup.produtos_count || 0 }} {{ sup.produtos_count === 1 ? 'item' : 'itens' }}</span>
                  </div>
                  <div v-if="sup.produtos && sup.produtos.length > 0" class="flex flex-wrap gap-1 mt-1 max-w-xs">
                    <span v-for="prod in sup.produtos.slice(0, 3)" :key="prod.id" class="px-2 py-0.5 text-xs rounded bg-slate-800 text-slate-300 border border-slate-700 truncate max-w-[140px]" :title="prod.nome">
                      {{ prod.nome }}
                    </span>
                    <span v-if="sup.produtos.length > 3" class="px-2 py-0.5 text-xs rounded bg-slate-800 text-slate-400 border border-slate-700 font-semibold" :title="sup.produtos.slice(3).map(p => p.nome).join(', ')">
                      +{{ sup.produtos.length - 3 }} outros
                    </span>
                  </div>
                </div>
              </td>
              <td data-label="Status">
                <span :class="sup.ativo ? 'badge badge-success' : 'badge badge-danger'">
                  <span class="badge-dot"></span>{{ sup.ativo ? 'Ativo' : 'Inativo' }}
                </span>
              </td>
              <td data-label="Ações" style="text-align: right;">
                <div class="flex gap-2 justify-end">
                  <Link :href="route('admin.suppliers.show', sup.id)" class="btn btn-ghost btn-sm">Ver</Link>
                  <Link :href="route('admin.suppliers.edit', sup.id)" class="btn btn-secondary btn-sm">Editar</Link>
                  <button @click="deleteSupplier(sup.id)" class="btn btn-danger btn-sm">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6"/>
                      <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="suppliers.links && suppliers.links.length > 3" class="pagination">
        <Link v-for="(link, idx) in suppliers.links" :key="idx" :href="link.url || '#'" class="page-btn" :class="{ active: link.active, disabled: !link.url }" v-html="link.label" />
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  suppliers: { type: Object, required: true },
  products:  { type: Array, default: () => [] },
  filters:   { type: Object, default: () => ({}) }
})

const form = ref({
  search: props.filters.search || '',
  product_id: props.filters.product_id || ''
})

function formatUrl(url) {
  if (!url) return '#'
  if (url.startsWith('http://') || url.startsWith('https://')) return url
  return `https://${url}`
}

function handleSearch() {
  router.get(route('admin.suppliers.index'), form.value, { preserveState: true })
}

function resetFilters() {
  form.value.search = ''
  form.value.product_id = ''
  handleSearch()
}

function deleteSupplier(id) {
  if (confirm('Tem certeza que deseja remover este fornecedor?')) {
    router.delete(route('admin.suppliers.destroy', id))
  }
}
</script>

<style scoped>
.page-btn.disabled { pointer-events: none; opacity: 0.35; }
</style>

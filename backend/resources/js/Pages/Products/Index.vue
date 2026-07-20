<template>
  <AdminLayout title="Produtos">
    <template #breadcrumb>
      <span class="text-muted">Catálogo</span>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">Produtos</span>
    </template>

    <!-- Page Header -->
    <div class="page-header">
      <div class="page-header-left">
        <h1 class="page-title">
          <span class="page-title-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
              <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
              <line x1="12" y1="22.08" x2="12" y2="12"/>
            </svg>
          </span>
          Produtos
        </h1>
        <p class="page-subtitle">Gerencie o catálogo de produtos e suas variações.</p>
      </div>
      <div class="page-actions">
        <button class="btn btn-secondary" @click="showInsumosModal = true">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
          </svg>
          Insumos
        </button>
        <Link :href="route('admin.products.create')" class="btn btn-primary">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Cadastrar Produto
        </Link>
      </div>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
      <div class="card-body">
        <form @submit.prevent="handleSearch" class="flex gap-3 items-end flex-wrap">
          <div class="form-group flex-1" style="min-width: 220px; margin-bottom: 0;">
            <label class="form-label">Buscar produto</label>
            <div class="form-input-wrap">
              <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
              </svg>
              <input v-model="form.search" type="text" class="form-input" placeholder="Nome ou SKU..." />
            </div>
          </div>

          <div class="form-group" style="min-width: 160px; margin-bottom: 0;">
            <label class="form-label">Categoria</label>
            <select v-model="form.categoria_id" class="form-select">
              <option value="">Todas</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.nome }}</option>
            </select>
          </div>

          <div class="form-group" style="min-width: 180px; margin-bottom: 0;">
            <label class="form-label">Fornecedor</label>
            <select v-model="form.fornecedor_id" class="form-select">
              <option value="">Todos</option>
              <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">{{ sup.razao_social }}</option>
            </select>
          </div>

          <div class="flex gap-2" style="flex-shrink: 0;">
            <button type="submit" class="btn btn-primary">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
              </svg>
              Filtrar
            </button>
            <button type="button" @click="resetFilters" class="btn btn-secondary">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="1 4 1 10 7 10"/>
                <path d="M3.51 15a9 9 0 1 0 .49-3.96"/>
              </svg>
              Limpar
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Table Card -->
    <div class="card">
      <!-- Empty State -->
      <div v-if="products.data.length === 0" class="empty-state">
        <div class="empty-state-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
          </svg>
        </div>
        <p class="empty-state-title">Nenhum produto encontrado</p>
        <p class="empty-state-desc">Tente ajustar os filtros ou cadastre um novo produto.</p>
        <Link :href="route('admin.products.create')" class="btn btn-primary btn-sm mt-4">
          Cadastrar Produto
        </Link>
      </div>

      <!-- Table -->
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th style="width: 60px;">Capa</th>
              <th>Nome / SKU</th>
              <th>Categoria</th>
              <th>Fornecedor</th>
              <th>Preço Venda</th>
              <th>Custo</th>
              <th>Variações</th>
              <th>Status</th>
              <th style="width: 100px; text-align: right;">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="prod in products.data" :key="prod.id">
              <td data-label="Capa">
                <img
                  :src="prod.foto_capa ? prod.foto_capa.url : '/storage/products/placeholder.png'"
                  style="width: 44px; height: 44px; object-fit: cover; border-radius: var(--radius-md); border: 1px solid var(--color-border);"
                  :alt="prod.nome"
                />
              </td>
              <td data-label="Nome / SKU">
                <div class="font-semibold" style="font-size: 0.875rem;">{{ prod.nome }}</div>
                <div class="text-muted font-mono" style="font-size: 0.75rem;">{{ prod.sku_base }}</div>
              </td>
              <td data-label="Categoria">
                <span class="badge badge-secondary">{{ prod.categoria ? prod.categoria.nome : '—' }}</span>
              </td>
              <td data-label="Fornecedor" class="text-secondary" style="font-size: 0.8125rem;">
                {{ prod.fornecedor ? prod.fornecedor.razao_social : '—' }}
              </td>
              <td data-label="Preço Venda">
                <div v-if="prod.tem_desconto">
                  <span class="font-bold text-danger">R$ {{ formatMoney(prod.preco_desconto) }}</span>
                  <div class="text-muted" style="font-size: 0.75rem; text-decoration: line-through;">
                    R$ {{ formatMoney(prod.preco_venda) }}
                  </div>
                </div>
                <span v-else class="font-bold">R$ {{ formatMoney(prod.preco_venda) }}</span>
              </td>
              <td data-label="Custo" class="text-secondary" style="font-size: 0.8125rem;">
                R$ {{ formatMoney(prod.preco_custo) }}
              </td>
              <td data-label="Variações">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="v in prod.variacoes"
                    :key="v.id"
                    class="badge"
                    :class="v.tipo_estoque === 'proprio' ? 'badge-primary' : 'badge-secondary'"
                    :title="`SKU: ${v.sku} — Estoque: ${v.estoque_quantidade}`"
                  >
                    {{ v.tamanho || '' }}{{ v.cor ? '/' + v.cor : '' }}
                    <span class="text-muted">({{ v.tipo_estoque === 'proprio' ? v.estoque_quantidade : '∞' }})</span>
                  </span>
                </div>
              </td>
              <td data-label="Status">
                <span :class="prod.ativo ? 'badge badge-success' : 'badge badge-danger'">
                  <span class="badge-dot"></span>
                  {{ prod.ativo ? 'Ativo' : 'Inativo' }}
                </span>
              </td>
              <td data-label="Ações" style="text-align: right;">
                <div class="flex gap-2 justify-end">
                  <Link :href="route('admin.products.edit', prod.id)" class="btn btn-secondary btn-sm">
                    Editar
                  </Link>
                  <button @click="deleteProduct(prod.id)" class="btn btn-danger btn-sm">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6"/>
                      <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                      <path d="M10 11v6"/><path d="M14 11v6"/>
                      <path d="M9 6V4h6v2"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="products.links && products.links.length > 3" class="pagination">
        <Link
          v-for="(link, idx) in products.links"
          :key="idx"
          :href="link.url || '#'"
          class="page-btn"
          :class="{ active: link.active, disabled: !link.url }"
          v-html="link.label"
        />
      </div>
    </div>

    <!-- ═══ Modal — Gerenciamento de Insumos ═══ -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="showInsumosModal" class="modal-overlay" @click.self="showInsumosModal = false">
          <div class="modal modal-lg">
            <div class="modal-header">
              <h3 class="modal-title">
                <span style="width: 28px; height: 28px; border-radius: var(--radius-sm); background: var(--color-brand-surface); color: var(--color-brand); display: inline-flex; align-items: center; justify-content: center; margin-right: 0.5rem; flex-shrink: 0;">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                  </svg>
                </span>
                Gerenciamento de Insumos
              </h3>
              <button @click="showInsumosModal = false" class="btn-icon" aria-label="Fechar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>

            <div class="modal-body" style="display: grid; grid-template-columns: 1fr 1.6fr; gap: 1.5rem;">
              <!-- Form (left) -->
              <div>
                <h4 class="font-semibold mb-4" style="font-size: 0.9375rem;">
                  {{ isEditingInsumo ? '✎ Editar Insumo' : '+ Novo Insumo' }}
                </h4>
                <form @submit.prevent="submitInsumoForm" class="flex flex-col gap-4">
                  <div class="form-group">
                    <label class="form-label form-label-required">Nome do Insumo</label>
                    <input v-model="insumoForm.nome" type="text" class="form-input" placeholder="Ex: Saco Zip" required />
                  </div>

                  <div class="form-group">
                    <label class="form-label form-label-required">Custo Unitário (R$)</label>
                    <input v-model="insumoForm.custo" type="number" step="0.01" min="0" class="form-input" placeholder="0,00" required />
                  </div>

                  <div class="form-group">
                    <label class="form-label form-label-required">Estoque Atual</label>
                    <input v-model="insumoForm.estoque" type="number" min="0" class="form-input" placeholder="0" required />
                  </div>

                  <div class="form-group">
                    <label class="form-label form-label-required">Categoria Vinculada</label>
                    <select v-model="insumoForm.categoria_id" class="form-select" required>
                      <option value="" disabled>Selecione...</option>
                      <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.nome }}</option>
                    </select>
                    <span class="form-hint">O estoque será debitado ao vender um produto desta categoria.</span>
                  </div>

                  <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary flex-1" :class="{ 'btn-loading': submittingInsumo }" :disabled="submittingInsumo">
                      {{ submittingInsumo ? 'Salvando...' : (isEditingInsumo ? 'Salvar Edição' : 'Adicionar Insumo') }}
                    </button>
                    <button v-if="isEditingInsumo" type="button" @click="openCreateInsumo" class="btn btn-ghost">
                      Cancelar
                    </button>
                  </div>
                </form>
              </div>

              <!-- List (right) -->
              <div>
                <h4 class="font-semibold mb-4" style="font-size: 0.9375rem;">Insumos Cadastrados</h4>

                <div v-if="insumos.length === 0" class="empty-state" style="padding: 2rem;">
                  <div class="empty-state-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    </svg>
                  </div>
                  <p class="empty-state-title" style="font-size: 0.875rem;">Nenhum insumo cadastrado</p>
                </div>

                <div v-else class="table-wrapper" style="max-height: 380px; overflow-y: auto;">
                  <table style="font-size: 0.8125rem;">
                    <thead>
                      <tr>
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>Custo</th>
                        <th>Estoque</th>
                        <th style="width: 72px;"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="i in insumos" :key="i.id">
                        <td data-label="Nome"><strong>{{ i.nome }}</strong></td>
                        <td data-label="Categoria">
                          <span class="badge badge-secondary">{{ i.categoria ? i.categoria.nome : '—' }}</span>
                        </td>
                        <td data-label="Custo" class="text-secondary">R$ {{ formatMoney(i.custo) }}</td>
                        <td data-label="Estoque" :class="i.estoque <= 10 ? 'text-danger font-bold' : ''">
                          {{ i.estoque }} un
                        </td>
                        <td>
                          <div class="flex gap-1 justify-end">
                            <button @click="openEditInsumo(i)" class="btn btn-secondary btn-xs" title="Editar">
                              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                              </svg>
                            </button>
                            <button @click="deleteInsumo(i.id)" class="btn btn-danger btn-xs" title="Excluir">
                              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
              </div>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  products:   { type: Object, required: true },
  categories: { type: Array,  required: true },
  suppliers:  { type: Array,  required: true },
  insumos:    { type: Array,  required: true },
  filters:    { type: Object, default: () => ({}) }
})

// Filters
const form = ref({
  search:       props.filters.search       || '',
  categoria_id: props.filters.categoria_id || '',
  fornecedor_id: props.filters.fornecedor_id || '',
})

function handleSearch() {
  router.get(route('admin.products.index'), form.value, { preserveState: true })
}

function resetFilters() {
  form.value = { search: '', categoria_id: '', fornecedor_id: '' }
  handleSearch()
}

function deleteProduct(id) {
  if (confirm('Tem certeza que deseja remover este produto? Isso removerá todas as fotos e variações associadas.')) {
    router.delete(route('admin.products.destroy', id))
  }
}

// Insumos State
const showInsumosModal   = ref(false)
const isEditingInsumo    = ref(false)
const editingInsumoId    = ref(null)
const submittingInsumo   = ref(false)

const insumoForm = ref({
  nome:        '',
  custo:       '',
  estoque:     '',
  categoria_id: props.categories[0]?.id || ''
})

function openCreateInsumo() {
  isEditingInsumo.value  = false
  editingInsumoId.value  = null
  insumoForm.value = { nome: '', custo: '', estoque: '', categoria_id: props.categories[0]?.id || '' }
}

function openEditInsumo(i) {
  isEditingInsumo.value  = true
  editingInsumoId.value  = i.id
  insumoForm.value = { nome: i.nome, custo: i.custo, estoque: i.estoque, categoria_id: i.categoria_id }
}

function submitInsumoForm() {
  submittingInsumo.value = true
  const done = () => { submittingInsumo.value = false }

  if (isEditingInsumo.value) {
    router.put(route('admin.insumos.update', editingInsumoId.value), insumoForm.value, {
      onSuccess: () => { done(); openCreateInsumo() },
      onError:   done
    })
  } else {
    router.post(route('admin.insumos.store'), insumoForm.value, {
      onSuccess: () => { done(); openCreateInsumo() },
      onError:   done
    })
  }
}

function deleteInsumo(id) {
  if (confirm('Tem certeza que deseja remover este insumo?')) {
    router.delete(route('admin.insumos.destroy', id))
  }
}

function formatMoney(value) {
  if (value === null || value === undefined) return '0,00'
  return parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.page-btn.disabled { pointer-events: none; opacity: 0.35; }
</style>

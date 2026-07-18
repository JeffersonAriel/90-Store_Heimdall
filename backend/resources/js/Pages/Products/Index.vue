<template>
  <AdminLayout title="Produtos">
    <template #breadcrumb>
      <span>Catálogo / Produtos</span>
    </template>

    <div class="page-header mb-6">
      <div>
        <h1 class="page-title">📦 Produtos</h1>
        <p class="text-secondary mt-1">Gerencie o catálogo de produtos e suas variações da loja.</p>
      </div>
      <div class="flex gap-2">
        <button class="btn btn-secondary" @click="showInsumosModal = true">
          📦 Gerenciar Insumos
        </button>
        <Link :href="route('admin.products.create')" class="btn btn-primary">
          + Cadastrar Produto
        </Link>
      </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-6">
      <div class="card-body">
        <form @submit.prevent="handleSearch" class="flex gap-4 items-end flex-wrap">
          <div class="form-group mb-0 flex-1" style="min-width: 200px;">
            <label class="form-label">Buscar por nome ou SKU</label>
            <input v-model="form.search" type="text" class="form-input" placeholder="Ex: Camiseta Retrô..." />
          </div>

          <div class="form-group mb-0" style="min-width: 150px;">
            <label class="form-label">Categoria</label>
            <select v-model="form.categoria_id" class="form-select">
              <option value="">Todas</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.nome }}</option>
            </select>
          </div>

          <div class="form-group mb-0" style="min-width: 150px;">
            <label class="form-label">Fornecedor</label>
            <select v-model="form.fornecedor_id" class="form-select">
              <option value="">Todos</option>
              <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">{{ sup.razao_social }}</option>
            </select>
          </div>

          <button type="submit" class="btn btn-primary" style="height: 38px;">Filtrar</button>
          <button type="button" @click="resetFilters" class="btn btn-secondary" style="height: 38px;">Limpar</button>
        </form>
      </div>
    </div>

    <!-- Tabela -->
    <div class="card">
      <div class="card-body" style="padding:0;">
        <div v-if="products.data.length === 0" class="alert alert-warning" style="margin: 1.5rem;">
          Nenhum produto cadastrado com os filtros atuais.
        </div>
        <div v-else class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th style="width: 70px;">Capa</th>
                <th>Nome / SKU Base</th>
                <th>Categoria</th>
                <th>Fornecedor</th>
                <th>Preço Venda</th>
                <th>Preço Custo</th>
                <th>Variações</th>
                <th>Status</th>
                <th style="width: 100px;">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="prod in products.data" :key="prod.id">
                <td>
                  <img :src="prod.foto_capa ? prod.foto_capa.url : '/storage/products/placeholder.png'" 
                       style="width: 48px; height: 48px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--color-border);" 
                       alt="Capa" />
                </td>
                <td>
                  <div>
                    <strong>{{ prod.nome }}</strong>
                    <div class="text-secondary font-mono" style="font-size: 0.75rem;">{{ prod.sku_base }}</div>
                  </div>
                </td>
                <td>
                  <span class="badge badge-secondary">{{ prod.categoria ? prod.categoria.nome : '—' }}</span>
                </td>
                <td>
                  <span class="text-secondary">{{ prod.fornecedor ? prod.fornecedor.razao_social : '—' }}</span>
                </td>
                <td>
                  <div v-if="prod.tem_desconto">
                    <span class="text-danger font-bold">R$ {{ formatMoney(prod.preco_desconto) }}</span>
                    <div class="text-muted" style="font-size: 0.75rem; text-decoration: line-through;">R$ {{ formatMoney(prod.preco_venda) }}</div>
                  </div>
                  <span v-else class="font-bold">R$ {{ formatMoney(prod.preco_venda) }}</span>
                </td>
                <td class="text-secondary">
                  R$ {{ formatMoney(prod.preco_custo) }}
                </td>
                <td>
                  <div class="flex flex-wrap gap-1">
                    <span v-for="v in prod.variacoes" :key="v.id" class="badge" :class="v.tipo_estoque === 'proprio' ? 'badge-primary' : 'badge-secondary'" :title="`SKU: ${v.sku} - Estoque: ${v.estoque_quantidade}`">
                      {{ v.tamanho || '' }}{{ v.cor ? '/' + v.cor : '' }} ({{ v.tipo_estoque === 'proprio' ? v.estoque_quantidade : '∞' }})
                    </span>
                  </div>
                </td>
                <td>
                  <span :class="prod.ativo ? 'badge badge-success' : 'badge badge-danger'">
                    {{ prod.ativo ? 'Ativo' : 'Inativo' }}
                  </span>
                </td>
                <td>
                  <div class="flex gap-2">
                    <Link :href="route('admin.products.edit', prod.id)" class="btn btn-secondary btn-sm" style="padding: 4px 8px;">Editar</Link>
                    <button @click="deleteProduct(prod.id)" class="btn btn-danger btn-sm" style="padding: 4px 8px;">Excluir</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginação Simples -->
        <div v-if="products.links && products.links.length > 3" class="flex gap-2" style="padding: 1.5rem; justify-content: flex-end;">
          <Link v-for="(link, idx) in products.links" :key="idx" 
                :href="link.url || '#'" 
                class="btn btn-secondary btn-sm" 
                :class="{ active: link.active, disabled: !link.url }"
                v-html="link.label">
          </Link>
        </div>
      </div>
    </div>

    <!-- Modal Gerenciamento de Insumos -->
    <div v-if="showInsumosModal" class="modal-backdrop" @click.self="showInsumosModal = false">
      <div class="modal-container" style="max-width: 850px; width: 90%;">
        <div class="modal-header">
          <h3>📦 Gerenciamento de Insumos por Categoria</h3>
          <button @click="showInsumosModal = false" class="modal-close-btn">✕</button>
        </div>
        
        <div class="modal-body grid-2 gap-6" style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 1.5rem; padding: 1.5rem;">
          <!-- Formulário (Esquerda) -->
          <div class="card" style="padding: 1rem; background-color: var(--color-bg-alt); border-radius: var(--radius-md); box-shadow: none; border: 1px solid var(--color-border);">
            <h4 class="font-bold mb-4">{{ isEditingInsumo ? '✏️ Editar Insumo' : '➕ Novo Insumo' }}</h4>
            <form @submit.prevent="submitInsumoForm" style="display: flex; flex-direction: column; gap: 1rem;">
              <div class="form-group">
                <label class="form-label">Nome do Insumo</label>
                <input v-model="insumoForm.nome" type="text" class="form-input" placeholder="Ex: Saco Zip" required />
              </div>
              
              <div class="form-group">
                <label class="form-label">Custo Unitário (R$)</label>
                <input v-model="insumoForm.custo" type="number" step="0.01" min="0" class="form-input" placeholder="0,00" required />
              </div>

              <div class="form-group">
                <label class="form-label">Estoque Atual</label>
                <input v-model="insumoForm.estoque" type="number" min="0" class="form-input" placeholder="0" required />
              </div>

              <div class="form-group">
                <label class="form-label">Categoria do Produto Vinculada</label>
                <select v-model="insumoForm.categoria_id" class="form-select" required>
                  <option value="" disabled>Selecione...</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.nome }}</option>
                </select>
                <small class="text-secondary mt-1 block" style="font-size:0.7rem; line-height:1.2;">O estoque deste insumo será debitado e lançado como despesa de caixa quando um produto desta categoria for comprado.</small>
              </div>

              <div class="flex gap-2 mt-2">
                <button type="submit" class="btn btn-primary btn-sm" :disabled="submittingInsumo" style="flex: 1;">
                  {{ submittingInsumo ? 'Salvando...' : 'Salvar Insumo' }}
                </button>
                <button v-if="isEditingInsumo" type="button" @click="openCreateInsumo" class="btn btn-secondary btn-sm">
                  Cancelar
                </button>
              </div>
            </form>
          </div>

          <!-- Tabela/Lista (Direita) -->
          <div>
            <h4 class="font-bold mb-4">Insumos Ativos</h4>
            <div v-if="insumos.length === 0" class="alert alert-secondary">
              Nenhum insumo cadastrado ainda.
            </div>
            <div v-else class="table-wrapper" style="max-height: 400px; overflow-y: auto;">
              <table style="font-size: 0.85rem;">
                <thead>
                  <tr>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Custo</th>
                    <th>Estoque</th>
                    <th style="width: 80px; text-align: right;">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="i in insumos" :key="i.id">
                    <td><strong>{{ i.nome }}</strong></td>
                    <td><span class="badge badge-secondary">{{ i.categoria ? i.categoria.nome : '—' }}</span></td>
                    <td>R$ {{ formatMoney(i.custo) }}</td>
                    <td :class="i.estoque <= 10 ? 'text-danger font-bold' : ''">{{ i.estoque }} un</td>
                    <td style="text-align: right;">
                      <div style="display: inline-flex; gap: 0.25rem;">
                        <button @click="openEditInsumo(i)" class="btn btn-secondary btn-sm" style="padding: 2px 6px; font-size: 0.75rem;">✏️</button>
                        <button @click="deleteInsumo(i.id)" class="btn btn-danger btn-sm" style="padding: 2px 6px; font-size: 0.75rem; background-color: var(--color-danger); border-color: var(--color-danger);">🗑️</button>
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
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  products: { type: Object, required: true },
  categories: { type: Array, required: true },
  suppliers: { type: Array, required: true },
  insumos: { type: Array, required: true },
  filters: { type: Object, default: () => ({}) }
})

const form = ref({
  search: props.filters.search || '',
  categoria_id: props.filters.categoria_id || '',
  fornecedor_id: props.filters.fornecedor_id || '',
})

// Insumos State & Methods
const showInsumosModal = ref(false)
const isEditingInsumo = ref(false)
const editingInsumoId = ref(null)
const submittingInsumo = ref(false)

const insumoForm = ref({
  nome: '',
  custo: '',
  estoque: '',
  categoria_id: props.categories[0]?.id || ''
})

function openCreateInsumo() {
  isEditingInsumo.value = false
  editingInsumoId.value = null
  insumoForm.value = {
    nome: '',
    custo: '',
    estoque: '',
    categoria_id: props.categories[0]?.id || ''
  }
}

function openEditInsumo(i) {
  isEditingInsumo.value = true
  editingInsumoId.value = i.id
  insumoForm.value = {
    nome: i.nome,
    custo: i.custo,
    estoque: i.estoque,
    categoria_id: i.categoria_id
  }
}

function submitInsumoForm() {
  submittingInsumo.value = true
  if (isEditingInsumo.value) {
    router.put(route('admin.insumos.update', editingInsumoId.value), insumoForm.value, {
      onSuccess: () => {
        submittingInsumo.value = false
        openCreateInsumo()
      },
      onError: () => {
        submittingInsumo.value = false
      }
    })
  } else {
    router.post(route('admin.insumos.store'), insumoForm.value, {
      onSuccess: () => {
        submittingInsumo.value = false
        openCreateInsumo()
      },
      onError: () => {
        submittingInsumo.value = false
      }
    })
  }
}

function deleteInsumo(id) {
  if (confirm('Tem certeza que deseja remover este insumo?')) {
    router.delete(route('admin.insumos.destroy', id))
  }
}

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

function formatMoney(value) {
  if (value === null || value === undefined) return '0,00'
  return parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.disabled {
  pointer-events: none;
  opacity: 0.5;
}
.active {
  background: var(--color-brand);
  color: white;
  border-color: var(--color-brand);
}
</style>

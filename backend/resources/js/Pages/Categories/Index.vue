<template>
  <AdminLayout title="Categorias">
    <template #breadcrumb>
      <span class="text-muted">Catálogo</span>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">Categorias</span>
    </template>

    <div class="page-header">
      <div class="page-header-left">
        <h1 class="page-title">
          <span class="page-title-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
              <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
            </svg>
          </span>
          Categorias
        </h1>
        <p class="page-subtitle">Organize seu catálogo em categorias, subcategorias e atributos dinâmicos.</p>
      </div>
      <div class="page-actions">
        <button class="btn btn-primary" @click="openCreateModal">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Nova Categoria
        </button>
      </div>
    </div>

    <div class="grid-3" style="gap: 1.5rem; align-items: start;">
      <!-- Lista -->
      <div class="card" style="grid-column: span 2;">
        <div v-if="categories.length === 0" class="empty-state">
          <div class="empty-state-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
              <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
            </svg>
          </div>
          <p class="empty-state-title">Nenhuma categoria cadastrada</p>
        </div>
        <div v-else class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th style="width: 60px;">Ordem</th>
                <th>Nome / Slug</th>
                <th>Pai</th>
                <th style="text-align: center;">Produtos</th>
                <th>Status</th>
                <th style="width: 140px; text-align: right;">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cat in categories" :key="cat.id">
                <td data-label="Ordem" class="text-center font-mono text-muted">{{ cat.ordem }}</td>
                <td data-label="Nome">
                  <div class="font-semibold">{{ cat.nome }}</div>
                  <div class="text-muted font-mono" style="font-size: 0.75rem;">/{{ cat.slug }}</div>
                </td>
                <td data-label="Pai">
                  <span v-if="cat.parent" class="badge badge-secondary">{{ cat.parent.nome }}</span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td data-label="Produtos" style="text-align: center;">
                  <span class="badge badge-primary">{{ cat.produtos_count || 0 }}</span>
                </td>
                <td data-label="Status">
                  <span :class="cat.ativo ? 'badge badge-success' : 'badge badge-danger'">
                    <span class="badge-dot"></span>{{ cat.ativo ? 'Ativo' : 'Inativo' }}
                  </span>
                </td>
                <td data-label="Ações" style="text-align: right;">
                  <div class="flex gap-2 justify-end">
                    <button @click="openEditModal(cat)" class="btn btn-secondary btn-sm">Editar</button>
                    <button @click="deleteCategory(cat.id)" class="btn btn-danger btn-sm">
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
      </div>

      <!-- Sidebar Info -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">
            <span class="card-title-icon" style="background: var(--color-brand-surface); color: var(--color-brand);">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
              </svg>
            </span>
            Estrutura Esportiva
          </h3>
        </div>
        <div class="card-body">
          <p class="text-secondary" style="font-size: 0.875rem;">
            Crie uma categoria principal como <strong>Camisetas</strong> e adicione atributos dinâmicos específicos como <strong>Time</strong> ou <strong>Tipo de Gola</strong>.
          </p>
          <div class="info-box mt-4">
            <strong style="font-size: 0.8125rem; display: block; margin-bottom: 0.5rem;">Exemplos de Atributos:</strong>
            <ul class="text-secondary" style="font-size: 0.8125rem; padding-left: 1.25rem; line-height: 1.8; margin: 0;">
              <li>Nacional (Time Nacional)</li>
              <li>Internacional (Time Internacional)</li>
              <li>Seleções (Copas, Países)</li>
              <li>Feminino / Infantil / Retrô</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Form Categoria -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
          <div class="modal modal-md">
            <div class="modal-header">
              <h3 class="modal-title">{{ isEdit ? 'Editar Categoria' : 'Nova Categoria' }}</h3>
              <button @click="showModal = false" class="btn-icon" aria-label="Fechar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="saveCategory" id="categoryForm" class="flex flex-col gap-4">
                <div class="form-group">
                  <label class="form-label form-label-required">Nome da Categoria</label>
                  <input v-model="form.nome" type="text" class="form-input" placeholder="Ex: Chuteiras, Camisetas" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Categoria Pai (Subcategoria de...)</label>
                  <select v-model="form.parent_id" class="form-select">
                    <option :value="null">Nenhuma (Categoria Principal)</option>
                    <option v-for="cat in rootCategories" :key="cat.id" :value="cat.id">{{ cat.nome }}</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Descrição</label>
                  <textarea v-model="form.descricao" class="form-textarea" rows="3" placeholder="Descrição opcional para SEO..."></textarea>
                </div>
                <div class="form-group">
                  <label class="form-label">URL do Banner Promocional (Mega Menu)</label>
                  <input v-model="form.banner_path" type="text" class="form-input" placeholder="https://..." />
                  <span class="form-hint">Usado na coluna lateral do mega menu de navegação.</span>
                </div>
                <div class="grid-2">
                  <div class="form-group">
                    <label class="form-label">Ordem de exibição</label>
                    <input v-model="form.ordem" type="number" class="form-input" min="0" />
                  </div>
                  <div class="form-group" style="padding-top: 1.75rem;">
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input v-model="form.ativo" type="checkbox" style="width: 1rem; height: 1rem; accent-color: var(--color-brand);" />
                      <span class="form-label" style="margin: 0;">Ativo no menu</span>
                    </label>
                  </div>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showModal = false">Cancelar</button>
              <button type="submit" form="categoryForm" class="btn btn-primary">Salvar</button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ categories: { type: Array, required: true } })

const showModal  = ref(false)
const isEdit     = ref(false)
const selectedId = ref(null)

const form = ref({ parent_id: null, nome: '', descricao: '', icone: '', banner_path: '', ordem: 0, ativo: true })

const rootCategories = computed(() => props.categories.filter(c => !c.parent_id && c.id !== selectedId.value))

function openCreateModal() {
  isEdit.value = false; selectedId.value = null
  form.value = { parent_id: null, nome: '', descricao: '', icone: '', banner_path: '', ordem: 0, ativo: true }
  showModal.value = true
}

function openEditModal(cat) {
  isEdit.value = true; selectedId.value = cat.id
  form.value = { parent_id: cat.parent_id, nome: cat.nome, descricao: cat.descricao, icone: cat.icone, banner_path: cat.banner_path || '', ordem: cat.ordem, ativo: !!cat.ativo }
  showModal.value = true
}

function saveCategory() {
  if (isEdit.value) {
    router.put(route('admin.categories.update', selectedId.value), form.value, { onSuccess: () => showModal.value = false })
  } else {
    router.post(route('admin.categories.store'), form.value, { onSuccess: () => showModal.value = false })
  }
}

function deleteCategory(id) {
  if (confirm('Tem certeza que deseja remover esta categoria?')) router.delete(route('admin.categories.destroy', id))
}
</script>

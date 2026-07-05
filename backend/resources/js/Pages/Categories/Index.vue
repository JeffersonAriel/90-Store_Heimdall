<template>
  <AdminLayout title="Categorias">
    <template #breadcrumb>
      <span>Catálogo / Categorias</span>
    </template>

    <div class="page-header mb-6">
      <div>
        <h1 class="page-title">🗂️ Categorias de Produtos</h1>
        <p class="text-secondary mt-1">Organize seu catálogo esportivo em categorias, subcategorias e defina atributos dinâmicos.</p>
      </div>
      <button class="btn btn-primary" @click="openCreateModal">
        + Nova Categoria
      </button>
    </div>

    <div class="grid-3 gap-6">
      <!-- Lista de Categorias -->
      <div class="col-span-2 card">
        <div class="card-body" style="padding: 0;">
          <div v-if="categories.length === 0" class="alert alert-warning" style="margin: 1.5rem;">
            Nenhuma categoria cadastrada.
          </div>
          <div v-else class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th style="width: 60px;">Ordem</th>
                  <th>Nome</th>
                  <th>Parente (Pai)</th>
                  <th>Produtos</th>
                  <th>Status</th>
                  <th style="width: 150px;">Ações</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="cat in categories" :key="cat.id">
                  <td class="text-center font-mono">{{ cat.ordem }}</td>
                  <td>
                    <div>
                      <strong>{{ cat.nome }}</strong>
                      <div class="text-secondary font-mono" style="font-size: 0.75rem;">/{{ cat.slug }}</div>
                    </div>
                  </td>
                  <td>
                    <span v-if="cat.parent" class="badge badge-secondary">{{ cat.parent.nome }}</span>
                    <span v-else class="text-muted">—</span>
                  </td>
                  <td>
                    <span class="badge badge-primary">{{ cat.produtos_count || 0 }}</span>
                  </td>
                  <td>
                    <span :class="cat.ativo ? 'badge badge-success' : 'badge badge-danger'">
                      {{ cat.ativo ? 'Ativo' : 'Inativo' }}
                    </span>
                  </td>
                  <td>
                    <div class="flex gap-2">
                      <button @click="openEditModal(cat)" class="btn btn-secondary btn-sm" style="padding: 4px 8px;">Editar</button>
                      <button @click="deleteCategory(cat.id)" class="btn btn-danger btn-sm" style="padding: 4px 8px;">Excluir</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Informações / Atributos Rápidos -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">💡 Estrutura Esportiva</h3>
        </div>
        <div class="card-body">
          <p class="text-secondary" style="font-size: 0.875rem;">
            Dica: Crie uma categoria principal como <strong>Camisetas</strong> e adicione atributos dinâmicos específicos, como <strong>Time</strong> ou <strong>Tipo de Gola</strong>, para classificar os produtos na loja.
          </p>
          <div class="info-box mt-4">
            <strong>Exemplos de Atributos:</strong>
            <ul class="text-secondary mt-2" style="font-size: 0.8125rem; padding-left: 1rem;">
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
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal modal-md">
        <div class="modal-header">
          <h2 class="card-title m-0">{{ isEdit ? 'Editar Categoria' : 'Nova Categoria' }}</h2>
          <button @click="showModal = false" class="btn-icon" style="background: none; border: none; font-size: 1.25rem; line-height: 1; padding: 0.25rem;">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveCategory" id="categoryForm">
            <div class="form-group mb-4">
              <label class="form-label">Nome da Categoria</label>
              <input v-model="form.nome" type="text" class="form-input" placeholder="Ex: Chuteiras, Camisetas" required />
            </div>

            <div class="form-group mb-4">
              <label class="form-label">Categoria Pai (Subcategoria de...)</label>
              <select v-model="form.parent_id" class="form-select">
                <option :value="null">Nenhuma (Categoria Principal)</option>
                <option v-for="cat in rootCategories" :key="cat.id" :value="cat.id">{{ cat.nome }}</option>
              </select>
            </div>

            <div class="form-group mb-4">
              <label class="form-label">Descrição</label>
              <textarea v-model="form.descricao" class="form-textarea" rows="3" placeholder="Descrição opcional para SEO..."></textarea>
            </div>

            <div class="grid-2 gap-4">
              <div class="form-group">
                <label class="form-label">Ordem de exibição</label>
                <input v-model="form.ordem" type="number" class="form-input" min="0" />
              </div>
              <div class="form-group flex items-center" style="padding-top: 1.75rem;">
                <label class="flex items-center gap-2 cursor-pointer form-label font-bold text-lg mb-0">
                  <input v-model="form.ativo" type="checkbox" class="w-5 h-5 rounded" style="accent-color: var(--color-brand)" />
                  <span>Ativo no menu</span>
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
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  categories: { type: Array, required: true }
})

const showModal = ref(false)
const isEdit = ref(false)
const selectedId = ref(null)

const form = ref({
  parent_id: null,
  nome: '',
  descricao: '',
  icone: '',
  ordem: 0,
  ativo: true
})

const rootCategories = computed(() => {
  return props.categories.filter(c => !c.parent_id && c.id !== selectedId.value)
})

function openCreateModal() {
  isEdit.value = false
  selectedId.value = null
  form.value = { parent_id: null, nome: '', descricao: '', icone: '', ordem: 0, ativo: true }
  showModal.value = true
}

function openEditModal(category) {
  isEdit.value = true
  selectedId.value = category.id
  form.value = {
    parent_id: category.parent_id,
    nome: category.nome,
    descricao: category.descricao,
    icone: category.icone,
    ordem: category.ordem,
    ativo: !!category.ativo
  }
  showModal.value = true
}

function saveCategory() {
  if (isEdit.value) {
    router.put(route('admin.categories.update', selectedId.value), form.value, {
      onSuccess: () => showModal.value = false
    })
  } else {
    router.post(route('admin.categories.store'), form.value, {
      onSuccess: () => showModal.value = false
    })
  }
}

function deleteCategory(id) {
  if (confirm('Tem certeza que deseja remover esta categoria?')) {
    router.delete(route('admin.categories.destroy', id))
  }
}
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  z-index: 200;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(4px);
}

.modal-box {
  background: var(--color-bg-secondary);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: 2rem;
  width: 100%;
  max-width: 500px;
  box-shadow: 0 24px 64px rgba(0,0,0,0.4);
}

.modal-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin-bottom: 1.5rem;
}
</style>

<template>
  <AdminLayout title="Fornecedores">
    <template #breadcrumb>
      <span>Catálogo / Fornecedores</span>
    </template>

    <div class="page-header mb-6">
      <div>
        <h1 class="page-title">🏭 Fornecedores</h1>
        <p class="text-secondary mt-1">Cadastre e avalie os fornecedores da sua loja esportiva.</p>
      </div>
      <Link :href="route('admin.suppliers.create')" class="btn btn-primary">
        + Cadastrar Fornecedor
      </Link>
    </div>

    <!-- Filtros -->
    <div class="card mb-6">
      <div class="card-body">
        <form @submit.prevent="handleSearch" class="flex gap-4 items-end">
          <div class="form-group mb-0 flex-1">
            <label class="form-label">Buscar fornecedor</label>
            <input v-model="form.search" type="text" class="form-input" placeholder="Razão social, Nome fantasia, CNPJ ou CPF..." />
          </div>
          <button type="submit" class="btn btn-primary" style="height: 38px;">Filtrar</button>
          <button type="button" @click="resetFilters" class="btn btn-secondary" style="height: 38px;">Limpar</button>
        </form>
      </div>
    </div>

    <!-- Tabela -->
    <div class="card">
      <div class="card-body" style="padding:0;">
        <div v-if="suppliers.data.length === 0" class="alert alert-warning" style="margin: 1.5rem;">
          Nenhum fornecedor cadastrado com o termo buscado.
        </div>
        <div v-else class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Nome / Razão Social</th>
                <th>Documento</th>
                <th>Contato</th>
                <th>Prazo Médio</th>
                <th>Avaliação</th>
                <th>Produtos</th>
                <th>Status</th>
                <th style="width: 150px;">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="sup in suppliers.data" :key="sup.id">
                <td>
                  <div>
                    <strong>{{ sup.razao_social }}</strong>
                    <div v-if="sup.nome_fantasia" class="text-secondary" style="font-size: 0.8125rem;">{{ sup.nome_fantasia }}</div>
                  </div>
                </td>
                <td>
                  <span class="font-mono text-secondary">{{ sup.tipo_pessoa === 'juridica' ? sup.cnpj : sup.cpf }}</span>
                </td>
                <td>
                  <div>
                    <div v-if="sup.telefone" style="font-size: 0.875rem;">📞 {{ sup.telefone }}</div>
                    <div v-if="sup.whatsapp" style="font-size: 0.875rem;">
                      <a :href="`https://wa.me/${sup.whatsapp.replace(/\D/g, '')}`" target="_blank" class="text-brand">
                        💬 {{ sup.whatsapp }}
                      </a>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="badge badge-secondary">{{ sup.prazo_medio_dias }} dias</span>
                </td>
                <td>
                  <div class="flex items-center gap-1">
                    <span v-for="i in 5" :key="i" style="font-size: 1rem;" :style="i <= Math.round(sup.avaliacao_media) ? 'color: var(--color-warning);' : 'color: var(--color-border);'">
                      ★
                    </span>
                    <span class="text-secondary" style="font-size: 0.75rem;">({{ sup.avaliacao_media }})</span>
                  </div>
                </td>
                <td>
                  <span class="badge badge-primary">{{ sup.produtos_count || 0 }}</span>
                </td>
                <td>
                  <span :class="sup.ativo ? 'badge badge-success' : 'badge badge-danger'">
                    {{ sup.ativo ? 'Ativo' : 'Inativo' }}
                  </span>
                </td>
                <td>
                  <div class="flex gap-2">
                    <Link :href="route('admin.suppliers.show', sup.id)" class="btn btn-primary btn-sm" style="padding: 4px 8px;">Ver</Link>
                    <Link :href="route('admin.suppliers.edit', sup.id)" class="btn btn-secondary btn-sm" style="padding: 4px 8px;">Editar</Link>
                    <button @click="deleteSupplier(sup.id)" class="btn btn-danger btn-sm" style="padding: 4px 8px;">Excluir</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginação -->
        <div v-if="suppliers.links && suppliers.links.length > 3" class="flex gap-2" style="padding: 1.5rem; justify-content: flex-end;">
          <Link v-for="(link, idx) in suppliers.links" :key="idx" 
                :href="link.url || '#'" 
                class="btn btn-secondary btn-sm" 
                :class="{ active: link.active, disabled: !link.url }"
                v-html="link.label">
          </Link>
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
  suppliers: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) }
})

const form = ref({
  search: props.filters.search || '',
})

function handleSearch() {
  router.get(route('admin.suppliers.index'), form.value, { preserveState: true })
}

function resetFilters() {
  form.value.search = ''
  handleSearch()
}

function deleteSupplier(id) {
  if (confirm('Tem certeza que deseja remover este fornecedor? Esta ação não poderá ser desfeita.')) {
    router.delete(route('admin.suppliers.destroy', id))
  }
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

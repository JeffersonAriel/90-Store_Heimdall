<template>
  <AdminLayout title="Barra de Benefícios">
    <template #breadcrumb>
      <span class="text-muted">Marketing & Vitrine</span>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">Barra de Benefícios</span>
    </template>

    <div class="page-header">
      <div class="page-header-left">
        <h1 class="page-title">
          <span class="page-title-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
              <line x1="7" y1="7" x2="7.01" y2="7"/>
            </svg>
          </span>
          Barra de Benefícios
        </h1>
        <p class="page-subtitle">Gerencie os benefícios exibidos na barra topo da loja virtual.</p>
      </div>
      <div class="page-actions">
        <button @click="openModal()" class="btn btn-primary">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Adicionar Benefício
        </button>
      </div>
    </div>

    <!-- Tabela de Benefícios -->
    <div class="card">
      <div v-if="!benefits.length" class="empty-state">
        <div class="empty-state-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
            <line x1="7" y1="7" x2="7.01" y2="7"/>
          </svg>
        </div>
        <p class="empty-state-title">Nenhum benefício cadastrado</p>
        <p class="empty-state-desc">Adicione benefícios para exibir na barra de vantagens da loja.</p>
      </div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th style="width: 60px;">Ícone</th>
              <th>Título / Descrição</th>
              <th style="width: 80px; text-align: center;">Ordem</th>
              <th>Status</th>
              <th style="width: 120px; text-align: right;">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="benefit in benefits" :key="benefit.id">
              <td data-label="Ícone" style="font-size: 1.5rem; line-height: 1;">{{ benefit.icon }}</td>
              <td data-label="Título">
                <div class="font-semibold" style="font-size: 0.875rem;">{{ benefit.title }}</div>
                <div class="text-secondary" style="font-size: 0.8125rem;">{{ benefit.description }}</div>
              </td>
              <td data-label="Ordem" style="text-align: center;" class="font-mono text-muted">{{ benefit.order }}</td>
              <td data-label="Status">
                <span class="badge" :class="benefit.is_active ? 'badge-success' : 'badge-danger'">
                  <span class="badge-dot"></span>{{ benefit.is_active ? 'Ativo' : 'Inativo' }}
                </span>
              </td>
              <td data-label="Ações" style="text-align: right;">
                <div class="flex gap-2 justify-end">
                  <button @click="openModal(benefit)" class="btn btn-secondary btn-sm">Editar</button>
                  <button @click="deleteBenefit(benefit.id)" class="btn btn-danger btn-sm">
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

    <!-- Modal Form -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="isModalOpen" class="modal-overlay" @click.self="closeModal">
          <div class="modal modal-sm">
            <div class="modal-header">
              <h3 class="modal-title">{{ form.id ? 'Editar Benefício' : 'Novo Benefício' }}</h3>
              <button @click="closeModal" class="btn-icon" aria-label="Fechar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
            <form @submit.prevent="submitForm">
              <div class="modal-body flex flex-col gap-4">
                <div class="form-group">
                  <label class="form-label form-label-required">Ícone (Emoji)</label>
                  <input type="text" v-model="form.icon" required placeholder="Ex: 🚚" class="form-input" />
                  <span class="form-hint">Use um emoji para representar o benefício.</span>
                </div>
                <div class="form-group">
                  <label class="form-label form-label-required">Título</label>
                  <input type="text" v-model="form.title" required class="form-input" placeholder="Ex: Frete Grátis" />
                </div>
                <div class="form-group">
                  <label class="form-label">Descrição</label>
                  <input type="text" v-model="form.description" class="form-input" placeholder="Ex: Acima de R$ 199,90" />
                </div>
                <div class="grid-2">
                  <div class="form-group">
                    <label class="form-label">Ordem</label>
                    <input type="number" v-model="form.order" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Status</label>
                    <select v-model="form.is_active" class="form-select">
                      <option :value="true">Ativo</option>
                      <option :value="false">Inativo</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" @click="closeModal" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar</button>
              </div>
            </form>
          </div>
        </div>
      </transition>
    </teleport>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ benefits: Array })

const isModalOpen = ref(false)

const form = useForm({ id: null, icon: '', title: '', description: '', order: 0, is_active: true })

const openModal = (benefit = null) => {
  if (benefit) { form.id = benefit.id; form.icon = benefit.icon; form.title = benefit.title; form.description = benefit.description; form.order = benefit.order; form.is_active = benefit.is_active }
  else { form.reset() }
  isModalOpen.value = true
}
const closeModal = () => { isModalOpen.value = false; form.reset() }
const submitForm = () => {
  if (form.id) { form.put(route('admin.benefits.update', form.id), { onSuccess: () => closeModal() }) }
  else { form.post(route('admin.benefits.store'), { onSuccess: () => closeModal() }) }
}
const deleteBenefit = (id) => {
  if (confirm('Tem certeza que deseja excluir este benefício?')) router.delete(route('admin.benefits.destroy', id))
}
</script>

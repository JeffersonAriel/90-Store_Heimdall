<template>
  <AdminLayout title="Cupons de Desconto">
    <template #breadcrumb>
      <span class="text-muted">Marketing</span>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">Cupons de Desconto</span>
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
          Cupons de Desconto
        </h1>
        <p class="page-subtitle">Crie e gerencie cupons de desconto para seus clientes.</p>
      </div>
      <div class="page-actions">
        <button class="btn btn-primary" @click="showCreateModal = true">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Novo Cupom
        </button>
      </div>
    </div>

    <!-- Tabela -->
    <div class="card">
      <div v-if="coupons.length === 0" class="empty-state">
        <div class="empty-state-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
            <line x1="7" y1="7" x2="7.01" y2="7"/>
          </svg>
        </div>
        <p class="empty-state-title">Nenhum cupom cadastrado</p>
        <p class="empty-state-desc">Crie o primeiro cupom para seus clientes.</p>
      </div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Código</th>
              <th>Tipo</th>
              <th>Desconto</th>
              <th>Pedido Mínimo</th>
              <th>Validade</th>
              <th>Usos</th>
              <th>Status</th>
              <th style="text-align: right;">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="coupon in coupons" :key="coupon.id">
              <td data-label="Código">
                <span class="font-mono font-bold" style="font-size: 0.875rem;">{{ coupon.codigo }}</span>
              </td>
              <td data-label="Tipo">
                <span class="badge" :class="{ 'badge-secondary': coupon.tipo === 'percent', 'badge-primary': coupon.tipo === 'fixed', 'badge-success': coupon.tipo === 'frete' }">
                  {{ tipoLabel(coupon.tipo) }}
                </span>
              </td>
              <td data-label="Desconto" class="font-semibold">{{ formatDiscount(coupon) }}</td>
              <td data-label="Pedido Mínimo" class="text-secondary">{{ formatCurrency(coupon.valor_minimo_pedido) }}</td>
              <td data-label="Validade" class="text-secondary" style="font-size: 0.8125rem;">
                {{ formatDate(coupon.validade) || '—' }}
              </td>
              <td data-label="Usos" class="text-secondary" style="font-size: 0.8125rem;">
                {{ coupon.usos_atuais }} / {{ coupon.limite_uso_total || '∞' }}
              </td>
              <td data-label="Status">
                <span class="badge" :class="coupon.ativo ? 'badge-success' : 'badge-danger'">
                  <span class="badge-dot"></span>{{ coupon.ativo ? 'Ativo' : 'Inativo' }}
                </span>
              </td>
              <td data-label="Ações" style="text-align: right;">
                <button class="btn btn-ghost btn-sm" @click="toggleStatus(coupon)">
                  {{ coupon.ativo ? 'Desativar' : 'Ativar' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Criar Cupom -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="showCreateModal" class="modal-overlay" @click.self="showCreateModal = false">
          <div class="modal modal-md">
            <div class="modal-header">
              <h3 class="modal-title">Novo Cupom de Desconto</h3>
              <button @click="showCreateModal = false" class="btn-icon" aria-label="Fechar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
            <form @submit.prevent="createCoupon">
              <div class="modal-body flex flex-col gap-4">
                <div class="grid-2">
                  <div class="form-group">
                    <label class="form-label form-label-required">Código do Cupom</label>
                    <input v-model="form.codigo" type="text" class="form-input" placeholder="Ex: BEMVINDO10" required />
                    <span v-if="form.errors.codigo" class="form-error">{{ form.errors.codigo }}</span>
                  </div>
                  <div class="form-group">
                    <label class="form-label form-label-required">Tipo de Desconto</label>
                    <select v-model="form.tipo" class="form-select" required>
                      <option value="percent">Porcentagem (%)</option>
                      <option value="fixed">Valor Fixo (R$)</option>
                      <option value="frete">Frete Grátis</option>
                    </select>
                  </div>
                </div>
                <div class="grid-2">
                  <div class="form-group">
                    <label class="form-label">Valor do Desconto</label>
                    <input v-model.number="form.valor" type="number" step="0.01" class="form-input" :disabled="form.tipo === 'frete'" />
                    <span v-if="form.tipo === 'frete'" class="form-hint">Não aplicável ao Frete Grátis.</span>
                  </div>
                  <div class="form-group">
                    <label class="form-label form-label-required">Pedido Mínimo (R$)</label>
                    <input v-model.number="form.valor_minimo_pedido" type="number" step="0.01" class="form-input" required />
                  </div>
                </div>
                <div class="grid-2">
                  <div class="form-group">
                    <label class="form-label">Limite de Usos (Total)</label>
                    <input v-model.number="form.limite_uso_total" type="number" class="form-input" placeholder="Vazio = Ilimitado" />
                  </div>
                  <div class="form-group">
                    <label class="form-label form-label-required">Usos por Cliente</label>
                    <input v-model.number="form.limite_uso_por_cliente" type="number" class="form-input" required />
                  </div>
                </div>
                <div class="grid-2">
                  <div class="form-group">
                    <label class="form-label">Data de Validade</label>
                    <input v-model="form.validade" type="date" class="form-input" />
                    <span class="form-hint">Deixe vazio para sem validade.</span>
                  </div>
                  <div class="form-group" style="padding-top: 1.75rem;">
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input v-model="form.ativo" type="checkbox" style="width: 1rem; height: 1rem; accent-color: var(--color-brand);" />
                      <span class="form-label" style="margin: 0;">Ativar imediatamente</span>
                    </label>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="showCreateModal = false">Cancelar</button>
                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                  {{ form.processing ? 'Criando...' : 'Criar Cupom' }}
                </button>
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

const props = defineProps({ coupons: Array })

const showCreateModal = ref(false)

const form = useForm({
  codigo: '', tipo: 'percent', valor: 0, valor_minimo_pedido: 0,
  limite_uso_total: null, limite_uso_por_cliente: 1, validade: '', ativo: true
})

function createCoupon() {
  if (form.tipo === 'frete') form.valor = 0
  form.post(route('admin.marketing.coupons.store'), {
    onSuccess: () => { showCreateModal.value = false; form.reset() }
  })
}

function toggleStatus(coupon) { router.patch(route('admin.marketing.coupons.toggle', coupon.id)) }

function tipoLabel(tipo) { return { percent: 'Porcentagem', fixed: 'Valor Fixo', frete: 'Frete Grátis' }[tipo] || tipo }
function formatDiscount(c) {
  if (c.tipo === 'percent') return `${c.valor}%`
  if (c.tipo === 'fixed') return `R$ ${Number(c.valor).toFixed(2).replace('.', ',')}`
  return 'Grátis'
}
function formatCurrency(v) { return 'R$ ' + Number(v).toFixed(2).replace('.', ',') }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('pt-BR') : null }
</script>

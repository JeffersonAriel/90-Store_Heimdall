<template>
  <AdminLayout title="Cupons de Desconto">
    <template #breadcrumb>
      <span>Marketing / Cupons de Desconto</span>
    </template>

    <div class="page-header mb-6 flex-between">
      <div>
        <h1 class="page-title">🎟️ Cupons de Desconto</h1>
        <p class="text-secondary mt-1">Crie e gerencie cupons de desconto para seus clientes.</p>
      </div>
      <button class="btn btn-primary" @click="showCreateModal = true">
        <i class="fas fa-plus mr-2"></i> Novo Cupom
      </button>
    </div>

    <!-- Tabela de Cupons -->
    <div class="card">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Código</th>
              <th>Tipo</th>
              <th>Valor / Desconto</th>
              <th>Pedido Mínimo</th>
              <th>Validade</th>
              <th>Usos (Total)</th>
              <th>Status</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="coupon in coupons" :key="coupon.id">
              <td><strong>{{ coupon.codigo }}</strong></td>
              <td>
                <span class="badge" :class="{
                  'badge-dark': coupon.tipo === 'percent',
                  'badge-primary': coupon.tipo === 'fixed',
                  'badge-success': coupon.tipo === 'frete'
                }">
                  {{ tipoLabel(coupon.tipo) }}
                </span>
              </td>
              <td>{{ formatDiscount(coupon) }}</td>
              <td>{{ formatCurrency(coupon.valor_minimo_pedido) }}</td>
              <td>{{ formatDate(coupon.validade) || 'Sem Validade' }}</td>
              <td>{{ coupon.usos_atuais }} / {{ coupon.limite_uso_total || '∞' }}</td>
              <td>
                <span class="badge" :class="coupon.ativo ? 'badge-success' : 'badge-danger'">
                  {{ coupon.ativo ? 'Ativo' : 'Inativo' }}
                </span>
              </td>
              <td>
                <button class="btn btn-sm btn-outline text-xs mr-2" @click="toggleStatus(coupon)">
                  {{ coupon.ativo ? 'Desativar' : 'Ativar' }}
                </button>
              </td>
            </tr>
            <tr v-if="coupons.length === 0">
              <td colspan="8" class="text-center py-6 text-secondary">Nenhum cupom cadastrado.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Criar Cupom -->
    <div v-if="showCreateModal" class="modal-backdrop" @click.self="showCreateModal = false">
      <div class="modal-box" style="max-width: 600px;">
        <h2 class="modal-title">Novo Cupom</h2>
        <form @submit.prevent="createCoupon">
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="form-group">
              <label class="form-label">Código do Cupom</label>
              <input v-model="form.codigo" type="text" class="form-control" placeholder="Ex: BEMVINDO10" required />
            </div>
            <div class="form-group">
              <label class="form-label">Tipo de Desconto</label>
              <select v-model="form.tipo" class="form-control" required>
                <option value="percent">Porcentagem (%)</option>
                <option value="fixed">Valor Fixo (R$)</option>
                <option value="frete">Frete Grátis</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="form-group">
              <label class="form-label">Valor (Desconto)</label>
              <input v-model.number="form.valor" type="number" step="0.01" class="form-control" :disabled="form.tipo === 'frete'" required />
              <small class="text-secondary" v-if="form.tipo === 'frete'">Não se aplica a Frete Grátis.</small>
            </div>
            <div class="form-group">
              <label class="form-label">Valor Mínimo do Pedido (R$)</label>
              <input v-model.number="form.valor_minimo_pedido" type="number" step="0.01" class="form-control" required />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="form-group">
              <label class="form-label">Limite de Usos (Total)</label>
              <input v-model.number="form.limite_uso_total" type="number" class="form-control" placeholder="Vazio = Sem limite" />
            </div>
            <div class="form-group">
              <label class="form-label">Usos por Cliente</label>
              <input v-model.number="form.limite_uso_por_cliente" type="number" class="form-control" required />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="form-group">
              <label class="form-label">Data de Validade</label>
              <input v-model="form.validade" type="date" class="form-control" />
            </div>
            <div class="form-group flex items-end pb-2">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.ativo" type="checkbox" />
                <strong>Cupom Ativo Imediatamente</strong>
              </label>
            </div>
          </div>

          <div class="flex justify-end gap-3">
            <button type="button" class="btn btn-secondary" @click="showCreateModal = false">Cancelar</button>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">Criar Cupom</button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  coupons: Array
})

const showCreateModal = ref(false)

const form = useForm({
  codigo: '',
  tipo: 'percent',
  valor: 0,
  valor_minimo_pedido: 0,
  limite_uso_total: null,
  limite_uso_por_cliente: 1,
  validade: '',
  ativo: true
})

function createCoupon() {
  if (form.tipo === 'frete') form.valor = 0
  
  form.post(route('admin.marketing.coupons.store'), {
    onSuccess: () => {
      showCreateModal.value = false
      form.reset()
    }
  })
}

function toggleStatus(coupon) {
  router.patch(route('admin.marketing.coupons.toggle', coupon.id))
}

function tipoLabel(tipo) {
  const map = { percent: 'Porcentagem', fixed: 'Valor Fixo', frete: 'Frete Grátis' }
  return map[tipo] || tipo
}

function formatDiscount(coupon) {
  if (coupon.tipo === 'percent') return `${coupon.valor}%`
  if (coupon.tipo === 'fixed') return `R$ ${Number(coupon.valor).toFixed(2).replace('.', ',')}`
  if (coupon.tipo === 'frete') return 'Grátis'
  return '-'
}

function formatCurrency(val) {
  return 'R$ ' + Number(val).toFixed(2).replace('.', ',')
}

function formatDate(dateStr) {
  if (!dateStr) return null
  return new Date(dateStr).toLocaleDateString('pt-BR')
}
</script>

<style scoped>
.flex-between {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.badge-danger {
  background-color: var(--color-red);
  color: #fff;
}
</style>

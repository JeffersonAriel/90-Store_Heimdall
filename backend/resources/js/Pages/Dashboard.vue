<template>
  <AdminLayout title="Dashboard">
    <template #breadcrumb>
      <span>Dashboard Geral</span>
    </template>

    <!-- KPIs Grid -->
    <div class="grid-4 mb-6">
      <div class="kpi-card">
        <div class="kpi-value">R$ {{ formatMoney(kpis.vendas_dia) }}</div>
        <div class="kpi-label">Vendas Hoje</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-value">R$ {{ formatMoney(kpis.vendas_mes) }}</div>
        <div class="kpi-label">Vendas no Mês</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-value">R$ {{ formatMoney(kpis.ticket_medio) }}</div>
        <div class="kpi-label">Ticket Médio</div>
      </div>
      <div class="kpi-card" :style="kpis.critico_estoque > 0 ? 'border-color: var(--color-danger);' : ''">
        <div class="kpi-value" :class="{ 'text-danger': kpis.critico_estoque > 0 }">
          {{ kpis.critico_estoque }}
        </div>
        <div class="kpi-label">Itens com Estoque Crítico</div>
      </div>
    </div>

    <!-- Main Dashboard Split -->
    <div class="grid-2">
      <!-- Alertas de Estoque -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">⚠️ Reposição de Estoque Urgente</h3>
          <span v-if="alertasEstoque.length" class="badge badge-danger">
            {{ alertasEstoque.length }} alerta(s)
          </span>
        </div>
        <div class="card-body" style="padding: 0;">
          <div v-if="!alertasEstoque.length" class="alert alert-success">
            Nenhum produto próprio está abaixo do estoque mínimo.
          </div>
          <div v-else class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Produto</th>
                  <th>SKU</th>
                  <th>Tam/Cor</th>
                  <th>Atual</th>
                  <th>Nível</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in alertasEstoque" :key="item.sku">
                  <td><strong>{{ item.nome }}</strong></td>
                  <td class="font-mono">{{ item.sku }}</td>
                  <td>{{ item.tamanho || '-' }} / {{ item.cor || '-' }}</td>
                  <td>
                    <span :class="item.estoque_quantidade <= item.estoque_critico ? 'text-danger font-bold' : 'text-warning font-bold'">
                      {{ item.estoque_quantidade }}
                    </span>
                  </td>
                  <td>
                    <span v-if="item.estoque_quantidade <= item.estoque_critico" class="badge badge-danger">Crítico</span>
                    <span v-else class="badge badge-warning">Mínimo</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Pendências Operacionais -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">📋 Resumo Operacional</h3>
        </div>
        <div class="card-body">
          <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between p-3" style="background: var(--color-bg-elevated); border-radius: var(--radius-md);">
              <span class="text-secondary">Pedidos Aguardando Pagamento</span>
              <span class="badge" :class="kpis.pedidos_pendentes > 0 ? 'badge-warning' : 'badge-secondary'">
                {{ kpis.pedidos_pendentes }}
              </span>
            </div>

            <div class="flex items-center justify-between p-3" style="background: var(--color-bg-elevated); border-radius: var(--radius-md);">
              <span class="text-secondary">WhatsApp Prontos para Envio</span>
              <span class="text-brand font-bold">Manual</span>
            </div>
            
            <div class="info-box">
              <strong>Integrações Ativas (Sandbox):</strong>
              <p class="text-secondary mt-1" style="font-size: 0.8125rem;">
                Gateways Mercado Pago, PagSeguro e Stripe estão prontos. Adicione credenciais em APIs para ativar.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
  kpis: { type: Object, required: true },
  alertasEstoque: { type: Array, required: true },
})

function formatMoney(value) {
  if (value === null || value === undefined) return '0,00'
  return parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

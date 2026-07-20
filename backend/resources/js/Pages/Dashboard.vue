<template>
  <AdminLayout title="Dashboard">
    <template #breadcrumb>
      <span class="breadcrumb-current">Dashboard</span>
    </template>

    <!-- Page Header -->
    <div class="page-header">
      <div class="page-header-left">
        <h1 class="page-title">Visão Geral</h1>
        <p class="page-subtitle">Resumo operacional em tempo real do seu negócio.</p>
      </div>
    </div>

    <!-- KPIs Grid -->
    <div class="kpi-grid">
      <!-- Vendas Hoje -->
      <div class="kpi-card">
        <div class="kpi-accent-bar kpi-accent-bar--brand"></div>
        <div class="kpi-card-header">
          <div class="kpi-icon kpi-icon--brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="1" x2="12" y2="23"/>
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
          </div>
          <span class="kpi-trend kpi-trend--flat">Hoje</span>
        </div>
        <div class="kpi-value">R$ {{ formatMoney(kpis.vendas_dia) }}</div>
        <div class="kpi-label">Vendas Hoje</div>
      </div>

      <!-- Vendas no Mês -->
      <div class="kpi-card">
        <div class="kpi-accent-bar kpi-accent-bar--success"></div>
        <div class="kpi-card-header">
          <div class="kpi-icon kpi-icon--success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>
              <polyline points="16 7 22 7 22 13"/>
            </svg>
          </div>
          <span class="kpi-trend kpi-trend--up">Este mês</span>
        </div>
        <div class="kpi-value">R$ {{ formatMoney(kpis.vendas_mes) }}</div>
        <div class="kpi-label">Vendas no Mês</div>
      </div>

      <!-- Ticket Médio -->
      <div class="kpi-card">
        <div class="kpi-accent-bar kpi-accent-bar--warning"></div>
        <div class="kpi-card-header">
          <div class="kpi-icon kpi-icon--accent">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
              <line x1="7" y1="7" x2="7.01" y2="7"/>
            </svg>
          </div>
          <span class="kpi-trend kpi-trend--flat">Médio</span>
        </div>
        <div class="kpi-value">R$ {{ formatMoney(kpis.ticket_medio) }}</div>
        <div class="kpi-label">Ticket Médio</div>
      </div>

      <!-- Estoque Crítico -->
      <div class="kpi-card" :style="kpis.critico_estoque > 0 ? 'border-color: var(--color-danger-border);' : ''">
        <div class="kpi-accent-bar" :class="kpis.critico_estoque > 0 ? 'kpi-accent-bar--danger' : 'kpi-accent-bar--success'"></div>
        <div class="kpi-card-header">
          <div class="kpi-icon" :class="kpis.critico_estoque > 0 ? 'kpi-icon--danger' : 'kpi-icon--success'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
            </svg>
          </div>
          <span
            v-if="kpis.critico_estoque > 0"
            class="kpi-trend kpi-trend--down"
          >Atenção</span>
          <span v-else class="kpi-trend kpi-trend--up">OK</span>
        </div>
        <div class="kpi-value" :class="{ 'text-danger': kpis.critico_estoque > 0 }">
          {{ kpis.critico_estoque }}
        </div>
        <div class="kpi-label">Itens com Estoque Crítico</div>
      </div>
    </div>

    <!-- Main Grid -->
    <div class="grid-2">
      <!-- Alertas de Estoque -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">
            <span class="card-title-icon" style="background: var(--color-warning-bg); color: var(--color-warning);">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
              </svg>
            </span>
            Reposição Urgente
          </h3>
          <span v-if="alertasEstoque.length" class="badge badge-danger">
            {{ alertasEstoque.length }} alerta(s)
          </span>
        </div>

        <!-- Empty State -->
        <div v-if="!alertasEstoque.length" class="empty-state" style="padding: 2.5rem 1.5rem;">
          <div class="empty-state-icon" style="background: var(--color-success-bg);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--color-success);">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
              <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
          </div>
          <p class="empty-state-title">Estoque normalizado</p>
          <p class="empty-state-desc">Nenhum produto abaixo do nível mínimo.</p>
        </div>

        <!-- Table -->
        <div v-else class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Produto</th>
                <th>SKU</th>
                <th>Tam/Cor</th>
                <th style="text-align: center;">Qtd</th>
                <th>Nível</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in alertasEstoque" :key="item.sku">
                <td data-label="Produto"><strong>{{ item.nome }}</strong></td>
                <td data-label="SKU" class="font-mono text-xs text-muted">{{ item.sku }}</td>
                <td data-label="Tam/Cor" class="text-secondary">
                  {{ item.tamanho || '—' }} / {{ item.cor || '—' }}
                </td>
                <td data-label="Qtd" style="text-align: center;">
                  <span
                    class="font-bold"
                    :class="item.estoque_quantidade <= item.estoque_critico ? 'text-danger' : 'text-warning'"
                  >{{ item.estoque_quantidade }}</span>
                </td>
                <td data-label="Nível">
                  <span v-if="item.estoque_quantidade <= item.estoque_critico" class="badge badge-danger">
                    <span class="badge-dot"></span>Crítico
                  </span>
                  <span v-else class="badge badge-warning">
                    <span class="badge-dot"></span>Mínimo
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Resumo Operacional -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">
            <span class="card-title-icon" style="background: var(--color-brand-surface); color: var(--color-brand);">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8"  y1="2" x2="8"  y2="6"/>
                <line x1="3"  y1="10" x2="21" y2="10"/>
              </svg>
            </span>
            Resumo Operacional
          </h3>
        </div>
        <div class="card-body">
          <div class="flex flex-col gap-3">
            <!-- Pedidos pendentes -->
            <div class="stat-row">
              <div class="stat-row-label">
                <div class="stat-row-icon" style="background: var(--color-warning-bg); color: var(--color-warning);">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 0 1-8 0"/>
                  </svg>
                </div>
                Pedidos Aguardando Pagamento
              </div>
              <span class="badge" :class="kpis.pedidos_pendentes > 0 ? 'badge-warning' : 'badge-success'">
                {{ kpis.pedidos_pendentes }}
              </span>
            </div>

            <!-- WhatsApp -->
            <div class="stat-row">
              <div class="stat-row-label">
                <div class="stat-row-icon" style="background: rgba(37, 211, 102, 0.1); color: #25d366;">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                  </svg>
                </div>
                WhatsApp Prontos para Envio
              </div>
              <span class="badge badge-secondary">Manual</span>
            </div>

            <!-- Integrações -->
            <div class="info-box mt-2">
              <div class="flex items-center gap-2 mb-2">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--color-brand)" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/>
                  <line x1="12" y1="8" x2="12" y2="12"/>
                  <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <strong style="font-size: 0.8125rem; color: var(--color-text-primary);">Integrações Ativas (Sandbox)</strong>
              </div>
              <p class="text-sm text-muted">
                Gateways Mercado Pago, PagSeguro e Stripe estão prontos. Adicione credenciais em <strong>APIs</strong> para ativar.
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
  kpis:           { type: Object, required: true },
  alertasEstoque: { type: Array,  required: true },
})

function formatMoney(value) {
  if (value === null || value === undefined) return '0,00'
  return parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

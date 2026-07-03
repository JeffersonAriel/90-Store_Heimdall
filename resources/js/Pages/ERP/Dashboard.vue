<template>
  <ERPLayout>
    <div class="animate-fade-in-up">
      <!-- Page header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-2xl font-bold text-slate-100">Dashboard</h1>
          <p class="text-sm text-slate-500 mt-1">Bem-vindo de volta, {{ $page.props.auth.user.name }}! 👋</p>
        </div>
        <div class="flex items-center gap-3">
          <select class="form-input w-auto text-sm">
            <option>Hoje</option>
            <option>Esta semana</option>
            <option selected>Este mês</option>
            <option>Este ano</option>
          </select>
          <button class="btn btn-primary text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Exportar
          </button>
        </div>
      </div>

      <!-- KPI Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8 stagger-children">
        <div v-for="kpi in kpis" :key="kpi.label" class="stat-card animate-fade-in-up">
          <div class="flex items-start justify-between mb-4">
            <div class="kpi-icon" :style="{ background: kpi.gradient }">
              <component :is="kpi.icon" class="w-5 h-5 text-white" />
            </div>
            <span
              class="text-xs font-semibold px-2 py-1 rounded-full"
              :class="kpi.trend > 0 ? 'text-emerald-400 bg-emerald-500/10' : 'text-red-400 bg-red-500/10'"
            >
              {{ kpi.trend > 0 ? '+' : '' }}{{ kpi.trend }}%
            </span>
          </div>
          <p class="text-3xl font-bold text-slate-100 mb-1">{{ kpi.value }}</p>
          <p class="text-sm text-slate-500">{{ kpi.label }}</p>
          <p class="text-xs text-slate-600 mt-1">vs. mês anterior</p>
        </div>
      </div>

      <!-- Charts row -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
        <!-- Sales chart -->
        <div class="card p-6 xl:col-span-2">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-slate-200">Vendas por Período</h2>
            <div class="flex gap-2">
              <button
                v-for="period in ['7d', '30d', '90d']"
                :key="period"
                class="text-xs px-3 py-1 rounded-full transition-all"
                :class="activePeriod === period
                  ? 'bg-indigo-500/20 text-indigo-400 border border-indigo-500/30'
                  : 'text-slate-500 hover:text-slate-400'"
                @click="activePeriod = period"
              >{{ period }}</button>
            </div>
          </div>
          <div class="h-64">
            <Line :data="salesChartData" :options="chartOptions" />
          </div>
        </div>

        <!-- Top products -->
        <div class="card p-6">
          <h2 class="text-lg font-semibold text-slate-200 mb-4">Top Produtos</h2>
          <div class="space-y-3">
            <div v-for="(product, i) in topProducts" :key="i" class="flex items-center gap-3">
              <span class="text-xs font-bold text-slate-600 w-4">{{ i + 1 }}</span>
              <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center text-base">
                {{ product.emoji }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-300 truncate">{{ product.name }}</p>
                <p class="text-xs text-slate-600">{{ product.sales }} vendas</p>
              </div>
              <span class="text-sm font-semibold text-emerald-400">{{ product.revenue }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom row -->
      <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <!-- Recent orders -->
        <div class="card p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-200">Pedidos Recentes</h2>
            <a href="/erp/orders" class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors">Ver todos →</a>
          </div>
          <table class="data-table">
            <thead>
              <tr>
                <th>Pedido</th>
                <th>Cliente</th>
                <th>Valor</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in recentOrders" :key="order.id">
                <td class="font-mono text-indigo-400 text-xs">#{{ order.id }}</td>
                <td class="text-sm">{{ order.customer }}</td>
                <td class="font-semibold">{{ order.total }}</td>
                <td>
                  <span class="badge" :class="statusBadge(order.status)">{{ order.status }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Stock alerts -->
        <div class="card p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-200">Alertas de Estoque</h2>
            <a href="/erp/stock" class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors">Ver estoque →</a>
          </div>
          <div class="space-y-3">
            <div v-for="item in stockAlerts" :key="item.id" class="flex items-center gap-3 p-3 rounded-lg bg-slate-800/50">
              <div class="w-2 h-2 rounded-full flex-shrink-0" :class="item.qty === 0 ? 'bg-red-500' : 'bg-amber-500'" />
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-300 truncate">{{ item.product }}</p>
                <p class="text-xs text-slate-600">SKU: {{ item.sku }}</p>
              </div>
              <span
                class="text-xs font-bold px-2 py-1 rounded"
                :class="item.qty === 0 ? 'bg-red-500/10 text-red-400' : 'bg-amber-500/10 text-amber-400'"
              >
                {{ item.qty === 0 ? 'Sem estoque' : `${item.qty} restantes` }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </ERPLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale,
  PointElement, LineElement, Tooltip, Filler
} from 'chart.js'
import ERPLayout from '@/Layouts/ERPLayout.vue'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Filler)

const activePeriod = ref('30d')

const kpis = [
  { label: 'Faturamento', value: 'R$ 48.320', trend: 12.5, icon: 'IconCoin', gradient: 'linear-gradient(135deg, #6366f1, #4f46e5)' },
  { label: 'Pedidos', value: '284', trend: 8.2, icon: 'IconShoppingCart', gradient: 'linear-gradient(135deg, #10b981, #059669)' },
  { label: 'Clientes Novos', value: '67', trend: -3.1, icon: 'IconUsers', gradient: 'linear-gradient(135deg, #f59e0b, #d97706)' },
  { label: 'Ticket Médio', value: 'R$ 170', trend: 5.8, icon: 'IconTrendingUp', gradient: 'linear-gradient(135deg, #ec4899, #db2777)' },
]

const topProducts = [
  { emoji: '👕', name: 'Camisa Flamengo 2024', sales: 84, revenue: 'R$ 12.4k' },
  { emoji: '⚽', name: 'Bola Nike Premier League', sales: 62, revenue: 'R$ 7.8k' },
  { emoji: '👟', name: 'Chuteira Adidas Predator', sales: 51, revenue: 'R$ 15.3k' },
  { emoji: '👕', name: 'Camisa Palmeiras Treino', sales: 48, revenue: 'R$ 6.2k' },
  { emoji: '🧤', name: 'Luva Goleiro Nike', sales: 39, revenue: 'R$ 4.7k' },
]

const recentOrders = [
  { id: '10284', customer: 'João Silva', total: 'R$ 289,90', status: 'Enviado' },
  { id: '10283', customer: 'Maria Santos', total: 'R$ 459,00', status: 'Processando' },
  { id: '10282', customer: 'Carlos Oliveira', total: 'R$ 119,90', status: 'Entregue' },
  { id: '10281', customer: 'Ana Lima', total: 'R$ 829,90', status: 'Processando' },
  { id: '10280', customer: 'Pedro Costa', total: 'R$ 199,90', status: 'Cancelado' },
]

const stockAlerts = [
  { id: 1, product: 'Camisa Brasil Edição Especial GG', sku: 'CBR-EE-GG', qty: 0 },
  { id: 2, product: 'Chuteira Nike Mercurial N42', sku: 'NM-N42', qty: 2 },
  { id: 3, product: 'Bola Futebol Society Penalty G', sku: 'PEN-SOC-G', qty: 3 },
  { id: 4, product: 'Meião Umbro Azul P', sku: 'UMB-MEI-AZ-P', qty: 1 },
]

const statusBadge = (status: string) => {
  const map: Record<string, string> = {
    'Enviado': 'badge-info',
    'Processando': 'badge-warning',
    'Entregue': 'badge-success',
    'Cancelado': 'badge-danger',
  }
  return map[status] || 'badge-neutral'
}

const salesChartData = {
  labels: ['01/06', '05/06', '10/06', '15/06', '20/06', '25/06', '30/06'],
  datasets: [
    {
      label: 'Vendas (R$)',
      data: [8500, 12300, 9800, 14200, 11600, 16800, 13400],
      borderColor: '#6366f1',
      backgroundColor: 'rgba(99, 102, 241, 0.1)',
      borderWidth: 2,
      pointBackgroundColor: '#6366f1',
      pointRadius: 4,
      tension: 0.4,
      fill: true,
    },
  ],
}

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { color: 'rgba(51, 65, 85, 0.5)' }, ticks: { color: '#64748b', font: { size: 11 } } },
    y: { grid: { color: 'rgba(51, 65, 85, 0.5)' }, ticks: { color: '#64748b', font: { size: 11 }, callback: (v: number) => `R$ ${(v/1000).toFixed(0)}k` } },
  },
}
</script>

<style scoped>
.kpi-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
</style>

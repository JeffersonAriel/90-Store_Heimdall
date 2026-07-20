<template>
  <AdminLayout title="Pontos & Fidelidade">
    <template #breadcrumb>
      <span class="text-muted">Marketing</span>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">Pontos & Fidelidade</span>
    </template>

    <div class="page-header">
      <div class="page-header-left">
        <h1 class="page-title">
          <span class="page-title-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
          </span>
          Pontos & Fidelidade
        </h1>
        <p class="page-subtitle">Acompanhe as transações de pontos e regras de fidelidade dos clientes.</p>
      </div>
    </div>

    <!-- Regras Ativas -->
    <div class="grid-3 mb-6" style="gap: 1.5rem;" v-if="rules.length">
      <div v-for="rule in rules" :key="rule.id" class="card">
        <div class="kpi-accent-bar kpi-accent-bar--brand"></div>
        <div class="card-body">
          <h3 class="font-semibold" style="color: var(--color-brand); font-size: 0.9375rem; margin-bottom: 0.5rem;">{{ rule.nome }}</h3>
          <p class="text-secondary" style="font-size: 0.8125rem; line-height: 1.5;">{{ rule.descricao }}</p>
          <div class="flex justify-between items-center mt-3 pt-3" style="border-top: 1px solid var(--color-border);">
            <span class="text-muted" style="font-size: 0.75rem;">Mecânica:</span>
            <span class="badge badge-primary">
              {{ rule.tipo === 'ganho' ? `Ganha ${rule.pontos} pts` : `Desconto de ${rule.pontos} pts` }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Histórico de Transações -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">
          <span class="card-title-icon" style="background: var(--color-brand-surface); color: var(--color-brand);">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="1 4 1 10 7 10"/>
              <path d="M3.51 15a9 9 0 1 0 .49-3.96"/>
            </svg>
          </span>
          Histórico de Transações
        </h3>
      </div>

      <div v-if="logs.data.length === 0" class="empty-state">
        <div class="empty-state-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
          </svg>
        </div>
        <p class="empty-state-title">Nenhuma transação de pontos</p>
        <p class="empty-state-desc">As transações aparecerão aqui quando os clientes começarem a acumular pontos.</p>
      </div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Cliente</th>
              <th>Tipo</th>
              <th style="text-align: center;">Pontos</th>
              <th>Descrição</th>
              <th>Data</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs.data" :key="log.id">
              <td data-label="ID" class="font-mono text-muted" style="font-size: 0.8125rem;">#{{ log.id }}</td>
              <td data-label="Cliente"><strong>{{ log.cliente_nome }}</strong></td>
              <td data-label="Tipo">
                <span class="badge" :class="log.tipo === 'ganho' ? 'badge-success' : 'badge-danger'">
                  <span class="badge-dot"></span>{{ log.tipo === 'ganho' ? 'Ganho' : 'Resgate' }}
                </span>
              </td>
              <td data-label="Pontos" style="text-align: center;">
                <span class="font-bold font-mono" :class="log.tipo === 'ganho' ? 'text-success' : 'text-danger'">
                  {{ log.tipo === 'ganho' ? '+' : '-' }}{{ log.pontos }}
                </span>
              </td>
              <td data-label="Descrição" class="text-secondary" style="font-size: 0.8125rem;">{{ log.descricao }}</td>
              <td data-label="Data" class="text-secondary" style="font-size: 0.8125rem;">{{ formatDate(log.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="logs.last_page > 1" class="pagination">
        <button class="page-btn" :class="{ disabled: logs.current_page === 1 }" @click="changePage(logs.current_page - 1)">← Anterior</button>
        <span class="page-btn active">{{ logs.current_page }} / {{ logs.last_page }}</span>
        <button class="page-btn" :class="{ disabled: logs.current_page === logs.last_page }" @click="changePage(logs.current_page + 1)">Próxima →</button>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ logs: Object, rules: Array })

function formatDate(d) { return d ? new Date(d).toLocaleString('pt-BR') : '—' }
function changePage(page) { router.get(route('admin.marketing.points'), { page }, { preserveState: true }) }
</script>

<style scoped>
.page-btn.disabled { pointer-events: none; opacity: 0.35; }
</style>

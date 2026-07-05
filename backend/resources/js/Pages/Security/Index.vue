<template>
  <AdminLayout title="Segurança & Logs">
    <template #breadcrumb>
      <span>Administração / Segurança e Auditoria</span>
    </template>

    <div class="page-header mb-6">
      <div>
        <h1 class="page-title">🔐 Segurança & Logs de Sistema</h1>
        <p class="text-secondary mt-1">Exclusivo do Administrador. Monitore tráfego, tentativas de invasão, logins e logs de auditoria.</p>
      </div>
      <div class="flex gap-2">
        <a :href="route('admin.security.export-csv', 'seguranca')" target="_blank" class="btn btn-secondary">
          Exportar Logs Ameaças (CSV)
        </a>
        <a :href="route('admin.security.export-csv', 'acesso')" target="_blank" class="btn btn-secondary">
          Exportar Logs Tráfego (CSV)
        </a>
      </div>
    </div>

    <!-- Alertas de Comportamento Suspeito -->
    <div v-if="alerts.brute_force_counts > 0 || alerts.high_traffic_ips.length > 0" class="alert alert-danger mb-6">
      <div class="flex flex-col gap-1">
        <strong style="font-size: 1rem;">⚠️ Possível comportamento anômalo detectado na última hora!</strong>
        <span v-if="alerts.brute_force_counts > 0">
          • {{ alerts.brute_force_counts }} tentativas de força bruta (brute force) registradas.
        </span>
        <span v-if="alerts.high_traffic_ips.length > 0">
          • {{ alerts.high_traffic_ips.length }} IP(s) ultrapassaram o limite normal de 100 requisições/hora.
        </span>
      </div>
    </div>

    <!-- Splits de Logs -->
    <div class="grid-3 gap-6 mb-6">
      <!-- Painel de Bloqueio de IP -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">🚫 Banir IP Suspeito</h3>
        </div>
        <div class="card-body">
          <form @submit.prevent="submitBlockIp" class="flex flex-col gap-4">
            <div class="form-group mb-0">
              <label class="form-label">Endereço IP</label>
              <input v-model="blockForm.ip" type="text" class="form-control" placeholder="Ex: 198.51.100.42" required />
            </div>
            <div class="form-group mb-0">
              <label class="form-label">Justificativa / Motivo</label>
              <input v-model="blockForm.motivo" type="text" class="form-control" placeholder="Ataques XSS, Brute force..." required />
            </div>
            <button type="submit" class="btn btn-danger w-full">Aplicar Bloqueio</button>
          </form>

          <hr class="my-4" style="border-color: var(--color-border);" />

          <h4 class="font-bold mb-2">Lista Negra (IPs Banidos)</h4>
          <div v-if="ipsBloqueados.length === 0" class="text-secondary" style="font-size: 0.8125rem;">
            Nenhum IP banido no momento.
          </div>
          <div v-else class="flex flex-col gap-2" style="max-height: 200px; overflow-y: auto;">
            <div v-for="ip in ipsBloqueados" :key="ip.ip" class="flex justify-between items-center p-2" style="background: var(--color-bg-elevated); border-radius: var(--radius-sm);">
              <div>
                <span class="font-mono text-danger font-bold" style="font-size: 0.8125rem;">{{ ip.ip }}</span>
                <div class="text-secondary" style="font-size: 0.75rem;">{{ ip.motivo }}</div>
              </div>
              <button @click="unblockIp(ip.ip)" class="btn btn-secondary btn-sm" style="padding: 2px 6px;">Remover</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Logs de Ameaças / Tentativas de Login -->
      <div class="col-span-2 card">
        <div class="card-header">
          <h3 class="card-title">🔴 Tentativas de Invasão & Ameaças Recentes</h3>
        </div>
        <div class="card-body" style="padding:0;">
          <div v-if="logsSeguranca.data.length === 0" class="alert alert-success" style="margin:1.5rem;">
            Nenhuma atividade suspeita registrada.
          </div>
          <div v-else class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Data</th>
                  <th>IP</th>
                  <th>Tipo</th>
                  <th>Detalhe</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="log in logsSeguranca.data" :key="log.id">
                  <td style="font-size:0.75rem;">{{ formatDate(log.created_at) }}</td>
                  <td class="font-mono text-danger">{{ log.ip }}</td>
                  <td>
                    <span class="badge badge-danger">{{ log.tipo }}</span>
                  </td>
                  <td style="font-size: 0.8125rem;">{{ log.detalhe }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Logs de Auditoria (Modificações de Dados) -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">📝 Trilha de Auditoria Geral (Spatie Activity Log / Custom)</h3>
      </div>
      <div class="card-body" style="padding:0;">
        <div v-if="auditLogs.length === 0" class="alert alert-secondary" style="margin:1.5rem;">
          Nenhuma alteração registrada em tabelas ainda.
        </div>
        <div v-else class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Data</th>
                <th>Funcionário</th>
                <th>Ação / Módulo</th>
                <th>Valor Antigo</th>
                <th>Novo Valor</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="audit in auditLogs" :key="audit.id">
                <td style="font-size:0.75rem;">{{ formatDate(audit.created_at) }}</td>
                <td><strong>{{ audit.funcionario_nome }}</strong></td>
                <td>
                  <span class="badge badge-primary">{{ audit.acao }}</span>
                  <div class="text-secondary" style="font-size:0.75rem;">{{ audit.tabela }} (ID: {{ audit.registro_id }})</div>
                </td>
                <td class="font-mono text-secondary" style="font-size: 0.75rem; white-space: pre-wrap;">{{ audit.valor_antigo || '—' }}</td>
                <td class="font-mono text-success" style="font-size: 0.75rem; white-space: pre-wrap;">{{ audit.valor_novo }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  logsSeguranca: { type: Object, required: true },
  logsAcesso: { type: Array, required: true },
  ipsBloqueados: { type: Array, required: true },
  auditLogs: { type: Array, required: true },
  alerts: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) }
})

const blockForm = ref({
  ip: '',
  motivo: ''
})

function submitBlockIp() {
  router.post(route('admin.security.block-ip'), blockForm.value, {
    onSuccess: () => {
      blockForm.value = { ip: '', motivo: '' }
    }
  })
}

function unblockIp(ip) {
  if (confirm(`Confirmar desbloqueio do IP ${ip}?`)) {
    router.delete(route('admin.security.unblock-ip', ip))
  }
}

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleString('pt-BR')
}
</script>

<style scoped>
.my-4 { margin-top: 1rem; margin-bottom: 1rem; }
</style>

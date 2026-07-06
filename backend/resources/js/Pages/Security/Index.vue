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
        <button @click="runMigrations" class="btn btn-primary" :disabled="runningMigrations" style="display: flex; align-items: center; gap: 0.5rem;">
          {{ runningMigrations ? 'Atualizando Banco...' : '🔄 Atualizar Banco (Migrations)' }}
        </button>
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
    <!-- Perfis & Permissões Section -->
    <div class="card mb-6">
      <div class="card-header border-b pb-4">
        <h3 class="card-title">🔑 Perfis de Acesso & Permissões Dinâmicas</h3>
        <p class="text-secondary mt-1">Configure o nível de acesso e o que cada perfil pode visualizar, criar, editar ou excluir nos módulos do sistema.</p>
      </div>
      <div class="card-body" style="padding: 0;">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Perfil</th>
                <th>Descrição</th>
                <th>Status / Tipo</th>
                <th style="width: 180px;"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="perf in perfis" :key="perf.id">
                <td><strong>{{ perf.nome }}</strong></td>
                <td class="text-secondary">{{ perf.descricao }}</td>
                <td>
                  <span :class="perf.is_admin ? 'badge badge-primary' : 'badge badge-secondary'">
                    {{ perf.is_admin ? 'Administrador' : 'Customizado' }}
                  </span>
                </td>
                <td style="text-align: right; padding-right: 1.5rem;">
                  <button v-if="!perf.is_admin" class="btn btn-secondary btn-sm" @click="openPermissions(perf)">
                    ⚙️ Editar Permissões
                  </button>
                  <span v-else class="text-secondary text-xs font-bold">Acesso Total (Admin)</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Editar Permissões do Perfil -->
    <div v-if="showPermissionsModal" class="modal-backdrop" @click.self="showPermissionsModal = false">
      <div class="modal-box" style="max-width: 800px; width: 90%;">
        <div class="flex justify-between items-center mb-4" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--color-border); padding-bottom: 1rem;">
          <h2 class="modal-title" style="margin-bottom: 0;">Permissões: {{ selectedPerfil?.nome }}</h2>
          <div class="flex gap-2">
            <button type="button" class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 0.75rem;" @click="toggleAll(true)">Marcar Todos</button>
            <button type="button" class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 0.75rem;" @click="toggleAll(false)">Limpar Todos</button>
          </div>
        </div>

        <form @submit.prevent="savePermissions">
          <div class="table-wrapper" style="max-height: 400px; overflow-y: auto; margin-bottom: 1.5rem; border: 1px solid var(--color-border); border-radius: var(--radius-md);">
            <table style="width: 100%; border-collapse: collapse;">
              <thead style="position: sticky; top: 0; background: var(--color-bg-secondary); z-index: 10;">
                <tr>
                  <th style="text-align: left; padding: 0.75rem 1rem;">Módulo / Funcionalidade</th>
                  <th v-for="act in actions" :key="act.key" style="text-align: center; width: 100px; padding: 0.75rem 1rem;">
                    {{ act.label }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="mod in modules" :key="mod.key" style="border-bottom: 1px solid var(--color-border);">
                  <td style="padding: 0.75rem 1rem; font-weight: 500;">
                    {{ mod.label }}
                  </td>
                  <td v-for="act in actions" :key="act.key" style="text-align: center; padding: 0.75rem 1rem;">
                    <input 
                      type="checkbox" 
                      v-model="localPermissions[mod.key][act.key]" 
                      style="width: 16px; height: 16px; accent-color: var(--color-brand); cursor: pointer;"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex gap-3" style="display: flex; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid var(--color-border); padding-top: 1rem;">
            <button type="button" class="btn btn-secondary" @click="showPermissionsModal = false">Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar Permissões</button>
          </div>
        </form>
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
  filters: { type: Object, default: () => ({}) },
  perfis: { type: Array, required: true },
  permissoesMap: { type: Array, required: true },
})

const blockForm = ref({
  ip: '',
  motivo: ''
})

const runningMigrations = ref(false)

function runMigrations() {
  if (confirm('Deseja realmente executar as migrações e sementes do banco de dados agora? Isso atualizará a estrutura do banco no servidor de hospedagem.')) {
    runningMigrations.value = true
    router.post(route('admin.security.run-migrations'), {}, {
      onFinish: () => {
        runningMigrations.value = false
      }
    })
  }
}

const selectedPerfil = ref(null)
const showPermissionsModal = ref(false)
const localPermissions = ref({})

const modules = [
  { key: 'produtos', label: '📦 Produtos & Catálogo' },
  { key: 'categorias', label: '🏷️ Categorias' },
  { key: 'fornecedores', label: '🤝 Fornecedores' },
  { key: 'pedidos', label: '🛒 Pedidos & Vendas' },
  { key: 'estoque', label: '🏢 Controle de Estoque' },
  { key: 'financeiro', label: '💰 Controle Financeiro' },
  { key: 'frete', label: '🚚 Regras de Frete' },
  { key: 'api_config', label: '🔌 Configuração de APIs' },
  { key: 'funcionarios', label: '👥 Funcionários & Equipe' },
  { key: 'marketing', label: '📢 Marketing & Cupons' },
  { key: 'importacao', label: '📥 Importação/Exportação' },
  { key: 'seguranca', label: '🔐 Segurança & Logs' },
  { key: 'clientes', label: '👤 Clientes' },
]

const actions = [
  { key: 'view', label: 'Visualizar' },
  { key: 'create', label: 'Criar' },
  { key: 'edit', label: 'Editar' },
  { key: 'delete', label: 'Excluir' },
]

function openPermissions(perfil) {
  selectedPerfil.value = perfil
  
  const matrix = {}
  modules.forEach(m => {
    matrix[m.key] = { view: false, create: false, edit: false, delete: false }
  })

  props.permissoesMap.forEach(p => {
    if (p.perfil_id === perfil.id) {
      if (matrix[p.modulo]) {
        matrix[p.modulo][p.acao] = true
      }
    }
  })

  localPermissions.value = matrix
  showPermissionsModal.value = true
}

function savePermissions() {
  router.post(route('admin.security.profiles.permissions.update', selectedPerfil.value.id), {
    permissions: localPermissions.value
  }, {
    onSuccess: () => {
      showPermissionsModal.value = false
    }
  })
}

function toggleAll(value) {
  modules.forEach(m => {
    actions.forEach(a => {
      localPermissions.value[m.key][a.key] = value
    })
  })
}

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
  box-shadow: 0 24px 64px rgba(0,0,0,0.4);
}

.modal-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin-bottom: 1.5rem;
}
</style>

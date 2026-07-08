<template>
  <AdminLayout title="Segurança">
    <template #breadcrumb>
      <span>Administração / Centro de Segurança Heimdall</span>
    </template>

    <!-- ── Hero Header ─────────────────────────────────────── -->
    <div class="sec-hero">
      <div class="sec-hero-left">
        <div class="sec-score-ring" :class="`score-${scoreStatus}`">
          <svg viewBox="0 0 120 120" class="score-svg">
            <circle cx="60" cy="60" r="52" class="score-track"/>
            <circle cx="60" cy="60" r="52" class="score-fill" :style="scoreDashStyle"/>
          </svg>
          <div class="score-center">
            <span class="score-number">{{ securityScore }}</span>
            <span class="score-label">/ 100</span>
          </div>
        </div>
        <div class="sec-hero-info">
          <div class="sec-status-badge" :class="`status-${scoreStatus}`">
            <span class="status-dot"></span>
            {{ scoreStatus === 'seguro' ? '🛡️ SISTEMA SEGURO' : scoreStatus === 'alerta' ? '⚠️ ALERTAS ATIVOS' : '🚨 AÇÃO CRÍTICA NECESSÁRIA' }}
          </div>
          <h1 class="sec-title">Centro de Segurança</h1>
          <p class="sec-subtitle">Vigilância total do sistema Heimdall · Atualizado agora</p>
          <div class="sec-actions">
            <a :href="route('admin.security.export-csv', 'seguranca')" target="_blank" class="sec-btn sec-btn-outline">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7,10 12,15 17,10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              Exportar Ameaças CSV
            </a>
            <button @click="runMigrations" :disabled="runningMigrations" class="sec-btn sec-btn-outline">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23,4 23,10 17,10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
              {{ runningMigrations ? 'Atualizando...' : 'Sync Banco' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Runa decorativa -->
      <div class="sec-hero-runes" aria-hidden="true">
        <span v-for="r in bgRunes" :key="r.c" :style="r.s">{{ r.c }}</span>
      </div>
    </div>

    <!-- ── Alerta Crítico ──────────────────────────────────── -->
    <div v-if="alerts.brute_force_counts > 0 || alerts.high_traffic_ips.length > 0" class="sec-critical-alert">
      <div class="alert-pulse"></div>
      <div class="alert-content">
        <strong>🚨 Comportamento Anômalo Detectado na Última Hora!</strong>
        <span v-if="alerts.brute_force_counts > 0">{{ alerts.brute_force_counts }} tentativa(s) de força bruta registrada(s).</span>
        <span v-if="alerts.high_traffic_ips.length > 0">{{ alerts.high_traffic_ips.length }} IP(s) ultrapassaram 100 req/hora.</span>
      </div>
    </div>

    <!-- ── KPI Cards ───────────────────────────────────────── -->
    <div class="kpi-grid">
      <div class="kpi-card kpi-threats">
        <div class="kpi-top">
          <div class="kpi-icon">⚡</div>
          <div class="kpi-body">
            <span class="kpi-value">{{ threats1h }}</span>
            <span class="kpi-name">Ameaças (1h)</span>
          </div>
        </div>
        <div class="kpi-trend" :class="threats1h > 0 ? 'trend-bad' : 'trend-good'">
          {{ threats1h > 0 ? '↑ Alerta' : '✓ Normal' }}
        </div>
      </div>

      <div class="kpi-card kpi-blocked">
        <div class="kpi-top">
          <div class="kpi-icon">🚫</div>
          <div class="kpi-body">
            <span class="kpi-value">{{ ipsBloqueados.length }}</span>
            <span class="kpi-name">IPs Bloqueados</span>
          </div>
        </div>
        <div class="kpi-trend" :class="ipsBloqueados.length > 0 ? 'trend-warn' : 'trend-good'">
          {{ ipsBloqueados.length > 0 ? 'Ativos' : '✓ Limpo' }}
        </div>
      </div>

      <div class="kpi-card kpi-logins">
        <div class="kpi-top">
          <div class="kpi-icon">🔐</div>
          <div class="kpi-body">
            <span class="kpi-value">{{ loginsFailed24h }}</span>
            <span class="kpi-name">Logins Falhos (24h)</span>
          </div>
        </div>
        <div class="kpi-trend" :class="loginsFailed24h > 5 ? 'trend-bad' : 'trend-good'">
          {{ loginsOk24h }} ok / {{ loginsFailed24h }} falhos
        </div>
      </div>

      <div class="kpi-card kpi-traffic">
        <div class="kpi-top">
          <div class="kpi-icon">🌐</div>
          <div class="kpi-body">
            <span class="kpi-value">{{ totalRequests24h.toLocaleString('pt-BR') }}</span>
            <span class="kpi-name">Requisições (24h)</span>
          </div>
        </div>
        <div class="kpi-trend trend-info">{{ blockedAttempts24h }} bloqueadas</div>
      </div>

      <div class="kpi-card kpi-threats24">
        <div class="kpi-top">
          <div class="kpi-icon">🔍</div>
          <div class="kpi-body">
            <span class="kpi-value">{{ threats24h }}</span>
            <span class="kpi-name">Ameaças (24h)</span>
          </div>
        </div>
        <div class="kpi-trend" :class="threats24h > 0 ? 'trend-warn' : 'trend-good'">
          Últimas 24 horas
        </div>
      </div>

      <div class="kpi-card kpi-audit">
        <div class="kpi-top">
          <div class="kpi-icon">📋</div>
          <div class="kpi-body">
            <span class="kpi-value">{{ auditLogs.length }}</span>
            <span class="kpi-name">Eventos Auditoria</span>
          </div>
        </div>
        <div class="kpi-trend trend-info">Últimos registros</div>
      </div>
    </div>

    <!-- ── Sistema Health Check ───────────────────────────── -->
    <div class="sec-section">
      <div class="sec-section-header">
        <div class="sec-section-title">
          <span class="sec-section-icon">🩺</span>
          Diagnóstico do Sistema
        </div>
        <span class="sec-section-badge">
          {{ systemChecks.filter(c => c.status === 'pass').length }}/{{ systemChecks.length }} verificações OK
        </span>
      </div>
      <div class="health-grid">
        <div
          v-for="check in systemChecks"
          :key="check.label"
          class="health-item"
          :class="`health-${check.status}`"
        >
          <span class="health-icon">
            {{ check.status === 'pass' ? '✅' : check.status === 'warn' ? '⚠️' : '❌' }}
          </span>
          <div class="health-body">
            <span class="health-label">{{ check.label }}</span>
            <span class="health-detail">{{ check.detail }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Threat Center + Top Attackers ─────────────────── -->
    <div class="sec-row">
      <!-- Centro de Ameaças -->
      <div class="sec-section sec-flex-2">
        <div class="sec-section-header">
          <div class="sec-section-title">
            <span class="sec-section-icon">🔴</span>
            Centro de Ameaças
          </div>
          <div class="threat-filters">
            <select v-model="filterTipo" @change="applyFilter" class="threat-select">
              <option value="">Todos os tipos</option>
              <option value="brute_force">Brute Force</option>
              <option value="sqli_tentativa">SQL Injection</option>
              <option value="xss_tentativa">XSS</option>
              <option value="rate_limit">Rate Limit</option>
              <option value="ip_banido">IP Banido</option>
              <option value="acesso_negado">Acesso Negado</option>
            </select>
            <input v-model="filterSearch" @input="applyFilter" placeholder="Pesquisar IP, detalhe..." class="threat-search" />
          </div>
        </div>
        <div v-if="logsSeguranca.data.length === 0" class="sec-empty">
          <span>🛡️</span>
          <p>Nenhuma atividade suspeita registrada.</p>
        </div>
        <div v-else class="threat-table-wrap">
          <table class="threat-table">
            <thead>
              <tr>
                <th>Data & Hora</th>
                <th>IP</th>
                <th>Tipo</th>
                <th>Detalhe</th>
                <th>Ação</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in logsSeguranca.data" :key="log.id" class="threat-row">
                <td class="threat-date">{{ formatDate(log.created_at) }}</td>
                <td>
                  <code class="threat-ip">{{ log.ip }}</code>
                </td>
                <td>
                  <span class="threat-badge" :class="`badge-${log.tipo}`">{{ threatLabel(log.tipo) }}</span>
                </td>
                <td class="threat-detail">{{ log.detalhe || '—' }}</td>
                <td>
                  <button @click="quickBlock(log.ip)" class="btn-quickblock" title="Bloquear IP">🚫</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Paginação -->
        <div class="threat-pagination" v-if="logsSeguranca.last_page > 1">
          <button v-for="page in logsSeguranca.last_page" :key="page"
            @click="goToPage(page)"
            :class="['page-btn', { active: page === logsSeguranca.current_page }]">
            {{ page }}
          </button>
        </div>
      </div>

      <!-- Top Atacantes + Tipos -->
      <div class="sec-side">
        <!-- Top Attackers -->
        <div class="sec-section">
          <div class="sec-section-header">
            <div class="sec-section-title">
              <span class="sec-section-icon">🎯</span>
              Top Atacantes (7 dias)
            </div>
          </div>
          <div v-if="topAttackers.length === 0" class="sec-empty-sm">Nenhum atacante registrado.</div>
          <div v-else class="attacker-list">
            <div v-for="(atk, i) in topAttackers" :key="atk.ip" class="attacker-item">
              <span class="attacker-rank">{{ i + 1 }}</span>
              <code class="attacker-ip">{{ atk.ip }}</code>
              <span class="attacker-count">{{ atk.total }}x</span>
              <button @click="quickBlock(atk.ip)" class="btn-quickblock-sm">🚫</button>
            </div>
          </div>
        </div>

        <!-- Distribuição por Tipo -->
        <div class="sec-section" style="margin-top: 1.5rem;">
          <div class="sec-section-header">
            <div class="sec-section-title">
              <span class="sec-section-icon">📊</span>
              Ameaças por Tipo (7 dias)
            </div>
          </div>
          <div v-if="topThreats.length === 0" class="sec-empty-sm">Sem ameaças registradas.</div>
          <div v-else class="threat-type-list">
            <div v-for="t in topThreats" :key="t.tipo" class="threat-type-item">
              <div class="threat-type-bar-wrap">
                <div class="threat-type-label">
                  <span class="threat-badge sm" :class="`badge-${t.tipo}`">{{ threatLabel(t.tipo) }}</span>
                  <span class="threat-type-count">{{ t.total }}</span>
                </div>
                <div class="threat-type-bar">
                  <div class="threat-type-fill" :class="`fill-${t.tipo}`"
                    :style="`width: ${Math.min(100, (t.total / topThreats[0].total) * 100)}%`">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Bloqueio de IPs ─────────────────────────────────── -->
    <div class="sec-row">
      <!-- Formulário de Bloqueio -->
      <div class="sec-section sec-flex-1">
        <div class="sec-section-header">
          <div class="sec-section-title">
            <span class="sec-section-icon">🔒</span>
            Bloquear IP Manualmente
          </div>
        </div>
        <form @submit.prevent="submitBlockIp" class="block-form">
          <div class="block-field">
            <label>Endereço IP</label>
            <input v-model="blockForm.ip" type="text" placeholder="Ex: 198.51.100.42" required />
          </div>
          <div class="block-field">
            <label>Justificativa</label>
            <input v-model="blockForm.motivo" type="text" placeholder="Motivo do bloqueio..." required />
          </div>
          <button type="submit" class="block-submit">🚫 Aplicar Bloqueio</button>
        </form>
      </div>

      <!-- Lista Negra -->
      <div class="sec-section sec-flex-2">
        <div class="sec-section-header">
          <div class="sec-section-title">
            <span class="sec-section-icon">⛔</span>
            Lista Negra de IPs
          </div>
          <span class="sec-section-badge" :class="ipsBloqueados.length > 0 ? 'badge-danger' : ''">
            {{ ipsBloqueados.length }} banidos
          </span>
        </div>
        <div v-if="ipsBloqueados.length === 0" class="sec-empty-sm">✅ Lista negra vazia. Sistema limpo.</div>
        <div v-else class="blacklist-grid">
          <div v-for="ip in ipsBloqueados" :key="ip.ip" class="blacklist-item">
            <div class="blacklist-info">
              <code class="blacklist-ip">{{ ip.ip }}</code>
              <span class="blacklist-motivo">{{ ip.motivo }}</span>
              <span v-if="ip.expires_at" class="blacklist-expiry">Expira: {{ formatDate(ip.expires_at) }}</span>
              <span v-else class="blacklist-permanent">🔴 Permanente</span>
            </div>
            <button @click="unblockIp(ip.ip)" class="blacklist-remove">Remover</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Trilha de Auditoria ─────────────────────────────── -->
    <div class="sec-section">
      <div class="sec-section-header">
        <div class="sec-section-title">
          <span class="sec-section-icon">📝</span>
          Trilha de Auditoria — Alterações de Dados
        </div>
        <a :href="route('admin.security.export-csv', 'acesso')" target="_blank" class="sec-btn-sm">
          Exportar CSV
        </a>
      </div>
      <div v-if="auditLogs.length === 0" class="sec-empty">
        <span>📋</span>
        <p>Nenhuma alteração de dados registrada ainda.</p>
      </div>
      <div v-else class="audit-timeline">
        <div v-for="audit in auditLogs" :key="audit.id" class="audit-item">
          <div class="audit-dot" :class="`audit-${audit.acao}`"></div>
          <div class="audit-line"></div>
          <div class="audit-content">
            <div class="audit-header">
              <span class="audit-action-badge" :class="`audit-badge-${audit.acao}`">
                {{ auditActionLabel(audit.acao) }}
              </span>
              <code class="audit-model">{{ audit.tabela || audit.modelo }} #{{ audit.registro_id }}</code>
              <span class="audit-date">{{ formatDate(audit.created_at) }}</span>
            </div>
            <div class="audit-who">
              <strong>{{ audit.funcionario_nome }}</strong>
            </div>
            <div v-if="audit.valor_antigo || audit.valor_novo" class="audit-diff">
              <span v-if="audit.valor_antigo" class="diff-old">{{ audit.valor_antigo }}</span>
              <span v-if="audit.valor_novo" class="diff-arrow">→</span>
              <span v-if="audit.valor_novo" class="diff-new">{{ audit.valor_novo }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Perfis & Permissões ─────────────────────────────── -->
    <div class="sec-section">
      <div class="sec-section-header">
        <div class="sec-section-title">
          <span class="sec-section-icon">🔑</span>
          Perfis de Acesso & Permissões
        </div>
      </div>
      <div class="profiles-grid">
        <div v-for="perf in perfis" :key="perf.id" class="profile-card" :class="{ 'profile-admin': perf.is_admin }">
          <div class="profile-icon">{{ perf.is_admin ? '👑' : '👤' }}</div>
          <div class="profile-info">
            <strong>{{ perf.nome }}</strong>
            <span>{{ perf.descricao }}</span>
          </div>
          <div class="profile-badge" :class="perf.is_admin ? 'badge-admin' : 'badge-custom'">
            {{ perf.is_admin ? 'Admin Total' : 'Customizado' }}
          </div>
          <button v-if="!perf.is_admin" @click="openPermissions(perf)" class="profile-edit">
            ⚙️ Permissões
          </button>
          <span v-else class="profile-full-access">Acesso Irrestrito</span>
        </div>
      </div>
    </div>

    <!-- ── Modal de Permissões ────────────────────────────── -->
    <div v-if="showPermissionsModal" class="modal-backdrop" @click.self="showPermissionsModal = false">
      <div class="modal-box">
        <div class="modal-header">
          <h2>⚙️ Permissões: {{ selectedPerfil?.nome }}</h2>
          <div class="modal-actions">
            <button @click="toggleAll(true)" class="sec-btn-sm">Marcar Todos</button>
            <button @click="toggleAll(false)" class="sec-btn-sm">Limpar Todos</button>
            <button @click="showPermissionsModal = false" class="modal-close">✕</button>
          </div>
        </div>
        <form @submit.prevent="savePermissions">
          <div class="perm-table-wrap">
            <table class="perm-table">
              <thead>
                <tr>
                  <th>Módulo</th>
                  <th v-for="act in actions" :key="act.key">{{ act.label }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="mod in modules" :key="mod.key">
                  <td class="perm-module">{{ mod.label }}</td>
                  <td v-for="act in actions" :key="act.key" class="perm-cell">
                    <label class="toggle-switch">
                      <input type="checkbox" v-model="localPermissions[mod.key][act.key]" />
                      <span class="toggle-thumb"></span>
                    </label>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="modal-footer">
            <button type="button" @click="showPermissionsModal = false" class="sec-btn sec-btn-outline">Cancelar</button>
            <button type="submit" class="sec-btn sec-btn-primary">Salvar Permissões</button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  logsSeguranca:      { type: Object, required: true },
  logsAcesso:         { type: Array, required: true },
  ipsBloqueados:      { type: Array, required: true },
  auditLogs:          { type: Array, required: true },
  alerts:             { type: Object, required: true },
  filters:            { type: Object, default: () => ({}) },
  perfis:             { type: Array, required: true },
  permissoesMap:      { type: Array, required: true },
  securityScore:      { type: Number, default: 100 },
  scoreStatus:        { type: String, default: 'seguro' },
  threats1h:          { type: Number, default: 0 },
  threats24h:         { type: Number, default: 0 },
  loginsFailed24h:    { type: Number, default: 0 },
  loginsOk24h:        { type: Number, default: 0 },
  totalRequests24h:   { type: Number, default: 0 },
  blockedAttempts24h: { type: Number, default: 0 },
  topThreats:         { type: Array, default: () => [] },
  threatChart:        { type: Array, default: () => [] },
  topAttackers:       { type: Array, default: () => [] },
  systemChecks:       { type: Array, default: () => [] },
})

// ── Score ring ─────────────────────────────────────────────
const scoreDashStyle = computed(() => {
  const r = 52
  const circ = 2 * Math.PI * r
  const offset = circ - (props.securityScore / 100) * circ
  return { strokeDasharray: `${circ}`, strokeDashoffset: `${offset}` }
})

// ── Background runes ────────────────────────────────────────
const runeArr = ['ᚺ','ᛖ','ᛁ','ᛗ','ᛞ','ᚨ','ᛚ','ᚠ','ᚢ','ᚦ','ᚱ','ᚲ','ᚷ']
const bgRunes = runeArr.map((c, i) => ({
  c,
  s: {
    left:              `${(i / runeArr.length) * 100}%`,
    top:               `${Math.random() * 100}%`,
    fontSize:          `${14 + (i % 4) * 6}px`,
    opacity:           0.04 + (i % 5) * 0.01,
    animationDelay:    `${i * 0.5}s`,
    animationDuration: `${8 + i}s`,
  }
}))

// ── Filters ─────────────────────────────────────────────────
const filterSearch = ref(props.filters?.search || '')
const filterTipo   = ref(props.filters?.tipo   || '')

function applyFilter() {
  router.get(route('admin.security.index'), { search: filterSearch.value, tipo: filterTipo.value }, { preserveState: true, replace: true })
}

function goToPage(page) {
  router.get(route('admin.security.index'), { page, search: filterSearch.value, tipo: filterTipo.value }, { preserveState: true })
}

// ── Quick block ─────────────────────────────────────────────
function quickBlock(ip) {
  if (confirm(`Bloquear IP ${ip} permanentemente?`)) {
    router.post(route('admin.security.block-ip'), { ip, motivo: 'Bloqueio rápido pelo Centro de Segurança' })
  }
}

// ── IP block form ───────────────────────────────────────────
const blockForm = ref({ ip: '', motivo: '' })

function submitBlockIp() {
  router.post(route('admin.security.block-ip'), blockForm.value, {
    onSuccess: () => { blockForm.value = { ip: '', motivo: '' } }
  })
}

function unblockIp(ip) {
  if (confirm(`Remover bloqueio do IP ${ip}?`)) {
    router.delete(route('admin.security.unblock-ip', ip))
  }
}

// ── Migrations ──────────────────────────────────────────────
const runningMigrations = ref(false)

function runMigrations() {
  if (confirm('Executar migrações e seeders no banco de dados?')) {
    runningMigrations.value = true
    router.post(route('admin.security.run-migrations'), {}, { onFinish: () => runningMigrations.value = false })
  }
}

// ── Permissions modal ───────────────────────────────────────
const selectedPerfil       = ref(null)
const showPermissionsModal = ref(false)
const localPermissions     = ref({})

const modules = [
  { key: 'produtos',     label: '📦 Produtos & Catálogo' },
  { key: 'categorias',   label: '🏷️ Categorias' },
  { key: 'fornecedores', label: '🤝 Fornecedores' },
  { key: 'pedidos',      label: '🛒 Pedidos & Vendas' },
  { key: 'estoque',      label: '🏢 Controle de Estoque' },
  { key: 'financeiro',   label: '💰 Controle Financeiro' },
  { key: 'frete',        label: '🚚 Regras de Frete' },
  { key: 'api_config',   label: '🔌 Configuração de APIs' },
  { key: 'funcionarios', label: '👥 Funcionários & Equipe' },
  { key: 'marketing',    label: '📢 Marketing & Cupons' },
  { key: 'importacao',   label: '📥 Importação/Exportação' },
  { key: 'seguranca',    label: '🔐 Segurança & Logs' },
  { key: 'clientes',     label: '👤 Clientes' },
  { key: 'agenda',       label: '📅 Agenda & Compromissos' },
]
const actions = [
  { key: 'view',   label: 'Ver' },
  { key: 'create', label: 'Criar' },
  { key: 'edit',   label: 'Editar' },
  { key: 'delete', label: 'Excluir' },
]

function openPermissions(perfil) {
  selectedPerfil.value = perfil
  const matrix = {}
  modules.forEach(m => { matrix[m.key] = { view: false, create: false, edit: false, delete: false } })
  props.permissoesMap.forEach(p => {
    if (p.perfil_id === perfil.id && matrix[p.modulo]) matrix[p.modulo][p.acao] = true
  })
  localPermissions.value = matrix
  showPermissionsModal.value = true
}

function savePermissions() {
  router.post(route('admin.security.profiles.permissions.update', selectedPerfil.value.id),
    { permissions: localPermissions.value },
    { onSuccess: () => showPermissionsModal.value = false }
  )
}

function toggleAll(val) {
  modules.forEach(m => actions.forEach(a => localPermissions.value[m.key][a.key] = val))
}

// ── Helpers ─────────────────────────────────────────────────
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('pt-BR', { day:'2-digit', month:'2-digit', year:'2-digit', hour:'2-digit', minute:'2-digit' })
}

const threatLabels = {
  brute_force:    'Brute Force',
  sqli_tentativa: 'SQL Injection',
  xss_tentativa:  'XSS',
  rate_limit:     'Rate Limit',
  ip_banido:      'IP Banido',
  acesso_negado:  'Acesso Negado',
  token_invalido: 'Token Inválido',
}
function threatLabel(t) { return threatLabels[t] || t }

const auditLabels = { create: 'Criação', update: 'Edição', delete: 'Exclusão', restore: 'Restauração' }
function auditActionLabel(a) { return auditLabels[a] || a }
</script>

<style scoped>
/* ── Hero ────────────────────────────────────────────────── */
.sec-hero {
  position: relative;
  background: linear-gradient(135deg, rgba(15,15,35,0.95) 0%, rgba(20,20,50,0.9) 100%);
  border: 1px solid rgba(99,102,241,0.2);
  border-radius: 20px;
  padding: 2rem 2.5rem;
  display: flex;
  align-items: center;
  gap: 2.5rem;
  margin-bottom: 1.75rem;
  overflow: hidden;
}

.sec-hero-left { display: flex; align-items: center; gap: 2rem; z-index: 1; flex: 1; }

.sec-hero-runes {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.sec-hero-runes span {
  position: absolute;
  color: #818cf8;
  font-family: serif;
  animation: runeFloat linear infinite;
  user-select: none;
}
@keyframes runeFloat {
  0%,100% { transform: translateY(0); }
  50%      { transform: translateY(-10px); }
}

/* Score ring */
.sec-score-ring {
  position: relative;
  width: 110px;
  height: 110px;
  flex-shrink: 0;
}
.score-svg { width: 100%; height: 100%; transform: rotate(-90deg); }
.score-track { fill: none; stroke: rgba(255,255,255,0.06); stroke-width: 8; }
.score-fill  {
  fill: none; stroke-width: 8;
  stroke-linecap: round;
  transition: stroke-dashoffset 1s ease;
}
.score-seguro  .score-fill { stroke: #22c55e; filter: drop-shadow(0 0 6px #22c55e); }
.score-alerta  .score-fill { stroke: #f59e0b; filter: drop-shadow(0 0 6px #f59e0b); }
.score-critico .score-fill { stroke: #ef4444; filter: drop-shadow(0 0 6px #ef4444); }

.score-center {
  position: absolute; inset: 0;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
}
.score-number { font-size: 1.75rem; font-weight: 800; color: #e0e7ff; line-height: 1; }
.score-label  { font-size: 0.65rem; color: rgba(165,180,252,0.6); }

.sec-status-badge {
  display: inline-flex; align-items: center; gap: 0.5rem;
  font-size: 0.7rem; font-weight: 700; letter-spacing: 0.12em;
  padding: 0.3rem 0.8rem; border-radius: 99px;
  margin-bottom: 0.5rem;
}
.status-seguro  { background: rgba(34,197,94,0.12); color: #4ade80; border: 1px solid rgba(34,197,94,0.3); }
.status-alerta  { background: rgba(245,158,11,0.12); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
.status-critico { background: rgba(239,68,68,0.12);  color: #f87171; border: 1px solid rgba(239,68,68,0.3); }

.status-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: currentColor; animation: blink 1.5s ease-in-out infinite;
}
@keyframes blink { 0%,100%{ opacity:1 } 50%{ opacity:0.3 } }

.sec-title    { font-size: 1.5rem; font-weight: 800; color: #e0e7ff; margin: 0 0 0.25rem; font-family: 'Cinzel', serif; }
.sec-subtitle { font-size: 0.8rem; color: rgba(165,180,252,0.6); margin: 0 0 1rem; }

.sec-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; }

.sec-btn {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.5rem 1rem; border-radius: 8px;
  font-size: 0.82rem; font-weight: 600; cursor: pointer;
  transition: all 0.2s; border: none;
}
.sec-btn svg { width: 14px; height: 14px; }
.sec-btn-outline {
  background: rgba(99,102,241,0.1); color: #a5b4fc;
  border: 1px solid rgba(99,102,241,0.3);
}
.sec-btn-outline:hover {
  background: rgba(99,102,241,0.2); border-color: rgba(99,102,241,0.6);
}
.sec-btn-primary {
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  color: white; box-shadow: 0 4px 15px rgba(99,102,241,0.4);
}
.sec-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(99,102,241,0.5); }

.sec-btn-sm {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.35rem 0.75rem; border-radius: 6px;
  font-size: 0.75rem; font-weight: 600; cursor: pointer;
  background: rgba(99,102,241,0.1); color: #a5b4fc;
  border: 1px solid rgba(99,102,241,0.25);
  text-decoration: none; transition: all 0.2s;
}
.sec-btn-sm:hover { background: rgba(99,102,241,0.2); }

/* ── Critical Alert ──────────────────────────────────────── */
.sec-critical-alert {
  position: relative;
  background: rgba(239,68,68,0.08);
  border: 1px solid rgba(239,68,68,0.35);
  border-radius: 12px;
  padding: 1rem 1.25rem 1rem 1.5rem;
  display: flex; align-items: center; gap: 1rem;
  margin-bottom: 1.5rem;
  overflow: hidden;
}
.alert-pulse {
  position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
  background: #ef4444;
  animation: alertPulse 1.5s ease-in-out infinite;
}
@keyframes alertPulse { 0%,100%{ opacity:1 } 50%{ opacity:0.3 } }
.alert-content { display: flex; flex-direction: column; gap: 0.25rem; }
.alert-content strong { color: #f87171; font-size: 0.9rem; }
.alert-content span   { font-size: 0.8rem; color: rgba(252,165,165,0.8); }

/* ── KPI Grid ────────────────────────────────────────────── */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
  margin-bottom: 1.75rem;
}

.kpi-card {
  background: rgba(255,255,255,0.02);
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: 14px;
  padding: 1.25rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  position: relative;
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}
.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.3);
}

.kpi-top {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.kpi-icon { font-size: 1.75rem; line-height: 1; flex-shrink: 0; }
.kpi-body { flex: 1; }
.kpi-value { display: block; font-size: 1.75rem; font-weight: 800; color: #e0e7ff; line-height: 1; }
.kpi-name  { display: block; font-size: 0.72rem; color: rgba(165,180,252,0.7); margin-top: 0.35rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }

.kpi-trend {
  display: inline-block;
  align-self: flex-start;
  font-size: 0.7rem; font-weight: 600;
  padding: 0.2rem 0.6rem; border-radius: 99px;
  margin-top: 0.25rem;
}
.trend-good { background: rgba(34,197,94,0.12);  color: #4ade80; }
.trend-bad  { background: rgba(239,68,68,0.12);  color: #f87171; }
.trend-warn { background: rgba(245,158,11,0.12); color: #fbbf24; }
.trend-info { background: rgba(99,102,241,0.12); color: #818cf8; }

.kpi-threats  { border-left: 3px solid rgba(239,68,68,0.5); }
.kpi-blocked  { border-left: 3px solid rgba(245,158,11,0.5); }
.kpi-logins   { border-left: 3px solid rgba(99,102,241,0.5); }
.kpi-traffic  { border-left: 3px solid rgba(59,130,246,0.5); }
.kpi-threats24{ border-left: 3px solid rgba(168,85,247,0.5); }
.kpi-audit    { border-left: 3px solid rgba(34,197,94,0.5); }

/* ── Sections ────────────────────────────────────────────── */
.sec-section {
  background: rgba(255,255,255,0.02);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 16px;
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.sec-section-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

.sec-section-title {
  display: flex; align-items: center; gap: 0.6rem;
  font-size: 0.95rem; font-weight: 700; color: #e0e7ff;
}
.sec-section-icon { font-size: 1rem; }

.sec-section-badge {
  font-size: 0.72rem; font-weight: 600;
  padding: 0.2rem 0.6rem; border-radius: 99px;
  background: rgba(99,102,241,0.1); color: #818cf8;
  border: 1px solid rgba(99,102,241,0.2);
}
.sec-section-badge.badge-danger {
  background: rgba(239,68,68,0.1); color: #f87171;
  border-color: rgba(239,68,68,0.3);
}

.sec-row {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}
@media (max-width: 900px) { .sec-row { grid-template-columns: 1fr; } }

.sec-flex-2 { flex: 2; }
.sec-flex-1 { flex: 1; }
.sec-side { display: flex; flex-direction: column; }

.sec-empty {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; padding: 3rem; gap: 0.5rem;
  color: rgba(165,180,252,0.4);
}
.sec-empty span { font-size: 2rem; }
.sec-empty p    { font-size: 0.875rem; margin: 0; }
.sec-empty-sm   { padding: 1.25rem 1.5rem; font-size: 0.8rem; color: rgba(165,180,252,0.4); }

/* ── Health Grid ─────────────────────────────────────────── */
.health-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 0.75rem;
  padding: 1.25rem 1.5rem;
}

.health-item {
  display: flex; align-items: flex-start; gap: 0.75rem;
  padding: 0.875rem 1rem; border-radius: 10px;
  border: 1px solid transparent;
  transition: all 0.2s;
}
.health-pass { background: rgba(34,197,94,0.05);  border-color: rgba(34,197,94,0.15);  }
.health-warn { background: rgba(245,158,11,0.05); border-color: rgba(245,158,11,0.15); }
.health-fail { background: rgba(239,68,68,0.05);  border-color: rgba(239,68,68,0.15);  }
.health-icon { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }
.health-body { display: flex; flex-direction: column; gap: 0.15rem; }
.health-label  { font-size: 0.8rem; font-weight: 600; color: #e0e7ff; }
.health-detail { font-size: 0.72rem; color: rgba(165,180,252,0.55); }

/* ── Threat Table ────────────────────────────────────────── */
.threat-filters {
  display: flex; gap: 0.5rem;
}
.threat-select, .threat-search {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 8px;
  color: #e0e7ff; padding: 0.4rem 0.75rem;
  font-size: 0.8rem; outline: none;
}
.threat-search { width: 200px; }
.threat-table-wrap { overflow-x: auto; }
.threat-table { width: 100%; border-collapse: collapse; }
.threat-table thead th {
  background: rgba(255,255,255,0.03);
  padding: 0.75rem 1rem;
  text-align: left; font-size: 0.72rem; font-weight: 700;
  color: rgba(165,180,252,0.6); text-transform: uppercase; letter-spacing: 0.08em;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}
.threat-row {
  border-bottom: 1px solid rgba(255,255,255,0.04);
  transition: background 0.15s;
}
.threat-row:hover { background: rgba(99,102,241,0.05); }
.threat-row td { padding: 0.75rem 1rem; vertical-align: middle; }
.threat-date   { font-size: 0.72rem; color: rgba(165,180,252,0.5); white-space: nowrap; }
.threat-ip     { font-family: monospace; font-size: 0.82rem; color: #f87171; background: rgba(239,68,68,0.08); padding: 0.15rem 0.4rem; border-radius: 4px; }
.threat-detail { font-size: 0.78rem; color: rgba(165,180,252,0.6); max-width: 250px; }

.threat-badge {
  display: inline-block; font-size: 0.68rem; font-weight: 700;
  padding: 0.2rem 0.55rem; border-radius: 99px;
  letter-spacing: 0.05em; white-space: nowrap;
}
.badge-brute_force    { background: rgba(239,68,68,0.15);   color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
.badge-sqli_tentativa { background: rgba(168,85,247,0.15);  color: #c084fc; border: 1px solid rgba(168,85,247,0.3); }
.badge-xss_tentativa  { background: rgba(245,158,11,0.15);  color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
.badge-rate_limit     { background: rgba(59,130,246,0.15);  color: #60a5fa; border: 1px solid rgba(59,130,246,0.3); }
.badge-ip_banido      { background: rgba(239,68,68,0.1);    color: #fc8181; border: 1px solid rgba(239,68,68,0.2); }
.badge-acesso_negado  { background: rgba(249,115,22,0.15);  color: #fb923c; border: 1px solid rgba(249,115,22,0.3); }
.badge-token_invalido { background: rgba(107,114,128,0.15); color: #9ca3af; border: 1px solid rgba(107,114,128,0.3); }

.btn-quickblock    { background: none; border: none; cursor: pointer; font-size: 1rem; opacity: 0.5; transition: opacity 0.2s; }
.btn-quickblock:hover { opacity: 1; }
.btn-quickblock-sm { background: none; border: none; cursor: pointer; font-size: 0.85rem; opacity: 0.5; transition: opacity 0.2s; }
.btn-quickblock-sm:hover { opacity: 1; }

.threat-pagination { display: flex; gap: 0.4rem; padding: 1rem 1.5rem; flex-wrap: wrap; }
.page-btn {
  padding: 0.3rem 0.7rem; border-radius: 6px; font-size: 0.78rem;
  border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.03);
  color: rgba(165,180,252,0.7); cursor: pointer; transition: all 0.15s;
}
.page-btn.active { background: rgba(99,102,241,0.25); border-color: #6366f1; color: #e0e7ff; }

/* ── Attackers ───────────────────────────────────────────── */
.attacker-list { display: flex; flex-direction: column; gap: 0; }
.attacker-item {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.75rem 1.5rem;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  transition: background 0.15s;
}
.attacker-item:hover { background: rgba(239,68,68,0.04); }
.attacker-rank  { width: 18px; font-size: 0.72rem; color: rgba(165,180,252,0.4); font-weight: 700; }
.attacker-ip    { flex: 1; font-family: monospace; font-size: 0.8rem; color: #f87171; }
.attacker-count { font-size: 0.75rem; font-weight: 700; color: #fbbf24; }

/* ── Threat type bars ────────────────────────────────────── */
.threat-type-list { padding: 0.75rem 1.5rem 1rem; display: flex; flex-direction: column; gap: 0.75rem; }
.threat-type-item {}
.threat-type-label { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem; }
.threat-badge.sm { font-size: 0.63rem; padding: 0.1rem 0.4rem; }
.threat-type-count { font-size: 0.72rem; color: rgba(165,180,252,0.5); font-weight: 600; }
.threat-type-bar { height: 4px; background: rgba(255,255,255,0.06); border-radius: 99px; overflow: hidden; }
.threat-type-fill {
  height: 100%; border-radius: 99px; transition: width 1s ease;
  background: #6366f1;
}
.fill-brute_force    { background: #ef4444; }
.fill-sqli_tentativa { background: #a855f7; }
.fill-xss_tentativa  { background: #f59e0b; }
.fill-rate_limit     { background: #3b82f6; }
.fill-ip_banido      { background: #fc8181; }
.fill-acesso_negado  { background: #f97316; }

/* ── Block Form ──────────────────────────────────────────── */
.block-form { display: flex; flex-direction: column; gap: 1rem; padding: 1.25rem 1.5rem; }
.block-field label { display: block; font-size: 0.72rem; font-weight: 600; color: rgba(165,180,252,0.7); margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.06em; }
.block-field input {
  width: 100%;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 8px; color: #e0e7ff;
  padding: 0.625rem 0.875rem; font-size: 0.875rem; outline: none;
  transition: border-color 0.2s;
  box-sizing: border-box;
}
.block-field input:focus { border-color: rgba(239,68,68,0.5); }

.block-submit {
  background: rgba(239,68,68,0.15); color: #f87171;
  border: 1px solid rgba(239,68,68,0.3);
  border-radius: 8px; padding: 0.625rem; font-size: 0.85rem;
  font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.block-submit:hover { background: rgba(239,68,68,0.25); }

/* ── Blacklist ───────────────────────────────────────────── */
.blacklist-grid { display: flex; flex-direction: column; max-height: 280px; overflow-y: auto; }
.blacklist-item {
  display: flex; align-items: center; gap: 1rem;
  padding: 0.875rem 1.5rem;
  border-bottom: 1px solid rgba(255,255,255,0.05);
}
.blacklist-info { flex: 1; display: flex; flex-direction: column; gap: 0.15rem; }
.blacklist-ip   { font-family: monospace; font-size: 0.85rem; font-weight: 700; color: #f87171; }
.blacklist-motivo  { font-size: 0.75rem; color: rgba(165,180,252,0.5); }
.blacklist-expiry  { font-size: 0.7rem; color: rgba(245,158,11,0.7); }
.blacklist-permanent { font-size: 0.7rem; color: #f87171; }
.blacklist-remove {
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
  color: rgba(165,180,252,0.7); border-radius: 6px; padding: 0.3rem 0.6rem;
  font-size: 0.75rem; cursor: pointer; transition: all 0.2s; flex-shrink: 0;
}
.blacklist-remove:hover { background: rgba(239,68,68,0.15); color: #f87171; border-color: rgba(239,68,68,0.3); }

/* ── Audit Timeline ──────────────────────────────────────── */
.audit-timeline { padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 0; }
.audit-item {
  display: flex; gap: 1rem; padding: 0.875rem 0;
  border-bottom: 1px solid rgba(255,255,255,0.04); position: relative;
}
.audit-dot {
  width: 10px; height: 10px; border-radius: 50%;
  flex-shrink: 0; margin-top: 5px;
}
.audit-create  { background: #22c55e; box-shadow: 0 0 6px #22c55e; }
.audit-update  { background: #f59e0b; box-shadow: 0 0 6px #f59e0b; }
.audit-delete  { background: #ef4444; box-shadow: 0 0 6px #ef4444; }
.audit-restore { background: #6366f1; box-shadow: 0 0 6px #6366f1; }

.audit-content { flex: 1; }
.audit-header { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.25rem; }
.audit-action-badge {
  font-size: 0.68rem; font-weight: 700; padding: 0.2rem 0.5rem;
  border-radius: 99px; letter-spacing: 0.04em;
}
.audit-badge-create  { background: rgba(34,197,94,0.12);  color: #4ade80; }
.audit-badge-update  { background: rgba(245,158,11,0.12); color: #fbbf24; }
.audit-badge-delete  { background: rgba(239,68,68,0.12);  color: #f87171; }
.audit-badge-restore { background: rgba(99,102,241,0.12); color: #818cf8; }

.audit-model { font-family: monospace; font-size: 0.75rem; color: rgba(165,180,252,0.5); }
.audit-date  { font-size: 0.7rem; color: rgba(165,180,252,0.35); margin-left: auto; }
.audit-who   { font-size: 0.78rem; color: rgba(165,180,252,0.7); margin-bottom: 0.2rem; }
.audit-diff  { display: flex; align-items: center; gap: 0.5rem; font-size: 0.72rem; flex-wrap: wrap; }
.diff-old    { color: rgba(239,68,68,0.7); font-family: monospace; text-decoration: line-through; }
.diff-arrow  { color: rgba(165,180,252,0.3); }
.diff-new    { color: rgba(34,197,94,0.8);  font-family: monospace; }

/* ── Profiles ────────────────────────────────────────────── */
.profiles-grid { display: flex; flex-direction: column; }
.profile-card {
  display: flex; align-items: center; gap: 1rem;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  transition: background 0.15s;
}
.profile-card:hover { background: rgba(99,102,241,0.04); }
.profile-admin { background: rgba(99,102,241,0.03); }

.profile-icon { font-size: 1.5rem; flex-shrink: 0; }
.profile-info { flex: 1; display: flex; flex-direction: column; gap: 0.1rem; }
.profile-info strong { font-size: 0.9rem; color: #e0e7ff; }
.profile-info span   { font-size: 0.75rem; color: rgba(165,180,252,0.5); }

.profile-badge { font-size: 0.68rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 99px; }
.badge-admin   { background: rgba(99,102,241,0.15); color: #818cf8; border: 1px solid rgba(99,102,241,0.3); }
.badge-custom  { background: rgba(107,114,128,0.12); color: #9ca3af; border: 1px solid rgba(107,114,128,0.2); }

.profile-edit {
  background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.25);
  color: #a5b4fc; border-radius: 8px; padding: 0.35rem 0.75rem;
  font-size: 0.78rem; cursor: pointer; transition: all 0.2s;
}
.profile-edit:hover { background: rgba(99,102,241,0.2); }
.profile-full-access { font-size: 0.72rem; color: rgba(165,180,252,0.35); font-style: italic; }

/* ── Modal ───────────────────────────────────────────────── */
.modal-backdrop {
  position: fixed; inset: 0; background: rgba(0,0,0,0.7);
  z-index: 300; display: flex; align-items: center; justify-content: center;
  backdrop-filter: blur(6px);
}
.modal-box {
  background: var(--color-bg-secondary, #12122a);
  border: 1px solid rgba(99,102,241,0.25);
  border-radius: 20px; padding: 0;
  max-width: 820px; width: 94%; max-height: 90vh;
  overflow: hidden; display: flex; flex-direction: column;
  box-shadow: 0 24px 80px rgba(0,0,0,0.6);
}
.modal-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid rgba(255,255,255,0.07);
}
.modal-header h2 { font-size: 1rem; font-weight: 700; color: #e0e7ff; margin: 0; }
.modal-actions { display: flex; align-items: center; gap: 0.5rem; }
.modal-close {
  width: 28px; height: 28px; border-radius: 50%;
  background: rgba(255,255,255,0.06); border: none;
  color: rgba(165,180,252,0.7); cursor: pointer; font-size: 0.8rem;
  transition: background 0.2s;
}
.modal-close:hover { background: rgba(239,68,68,0.2); color: #f87171; }

.perm-table-wrap { overflow-y: auto; max-height: 50vh; }
.perm-table { width: 100%; border-collapse: collapse; }
.perm-table thead { position: sticky; top: 0; background: rgba(15,15,35,0.98); z-index: 10; }
.perm-table th {
  padding: 0.75rem 1rem; text-align: center;
  font-size: 0.72rem; font-weight: 700; color: rgba(165,180,252,0.6);
  text-transform: uppercase; letter-spacing: 0.07em;
  border-bottom: 1px solid rgba(255,255,255,0.07);
}
.perm-table th:first-child { text-align: left; }
.perm-table td { padding: 0.75rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.04); }
.perm-module { font-size: 0.83rem; font-weight: 500; color: #e0e7ff; }
.perm-cell { text-align: center; }

/* Toggle switch */
.toggle-switch { position: relative; display: inline-flex; cursor: pointer; }
.toggle-switch input { opacity: 0; width: 0; height: 0; position: absolute; }
.toggle-thumb {
  width: 32px; height: 18px; background: rgba(255,255,255,0.1);
  border-radius: 99px; position: relative; transition: all 0.25s;
}
.toggle-thumb::after {
  content: ''; position: absolute;
  top: 2px; left: 2px;
  width: 14px; height: 14px; border-radius: 50%;
  background: rgba(255,255,255,0.4); transition: all 0.25s;
}
.toggle-switch input:checked + .toggle-thumb {
  background: rgba(99,102,241,0.6);
}
.toggle-switch input:checked + .toggle-thumb::after {
  transform: translateX(14px); background: white;
}

.modal-footer {
  display: flex; justify-content: flex-end; gap: 0.75rem;
  padding: 1.25rem 1.5rem;
  border-top: 1px solid rgba(255,255,255,0.07);
}
</style>

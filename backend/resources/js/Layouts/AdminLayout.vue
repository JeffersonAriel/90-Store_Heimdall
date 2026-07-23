<template>
  <div class="admin-wrapper">
    <!-- Sidebar Overlay (mobile) -->
    <div
      v-if="sidebarOpen"
      class="sidebar-overlay"
      @click="sidebarOpen = false"
    />

    <!-- ═══ Sidebar ═══ -->
    <aside class="admin-sidebar" :class="{ open: sidebarOpen }">

      <!-- Logo -->
      <div class="sidebar-logo">
        <img :src="logoUrl" class="sidebar-logo-icon" alt="Heimdall" />
        <span class="sidebar-logo-text">Heimdall</span>
      </div>

      <!-- Navigation -->
      <nav class="sidebar-nav">
        <!-- Dashboard -->
        <NavItem
          :href="route('admin.dashboard')"
          icon="dashboard"
          label="Dashboard"
          :active="$page.url === '/heimdall' || $page.url.startsWith('/heimdall/dashboard')"
        />

        <!-- Catálogo -->
        <div class="nav-section-label">Catálogo</div>
        <NavItem v-if="can('produtos', 'view')"     :href="route('admin.products.index')"   icon="products"   label="Produtos"    :active="$page.url.startsWith('/heimdall/products')" />
        <NavItem v-if="can('fornecedores', 'view')" :href="route('admin.suppliers.index')"  icon="suppliers"  label="Fornecedores" :active="$page.url.startsWith('/heimdall/suppliers')" />
        <NavItem v-if="can('produtos', 'view')"     :href="route('admin.categories.index')" icon="categories" label="Categorias"  :active="$page.url.startsWith('/heimdall/categories')" />

        <!-- Operações -->
        <div class="nav-section-label">Operações</div>
        <NavItem v-if="can('pedidos', 'view')"    :href="route('admin.orders.index')"   icon="orders"    label="Pedidos"        :active="$page.url.startsWith('/heimdall/orders')"    :badge="$page.props.counts?.pendingOrders" />
        <NavItem v-if="can('clientes', 'view')"   :href="route('admin.clients.index')"  icon="employees" label="Clientes"       :active="$page.url.startsWith('/heimdall/clients')" />
        <NavItem v-if="can('estoque', 'view')"    :href="route('admin.stock.index')"    icon="stock"     label="Estoque"        :active="$page.url.startsWith('/heimdall/stock')"    :badge="$page.props.counts?.criticalStock" badge-type="danger" />
        <NavItem v-if="can('financeiro', 'view')" :href="route('admin.financial.index')" icon="financial" label="Financeiro"    :active="$page.url.startsWith('/heimdall/financial')" />
        <NavItem v-if="can('agenda', 'view')"     :href="route('admin.agenda.index')"   icon="calendar"  label="Agenda"        :active="$page.url.startsWith('/heimdall/agenda')" />
        <NavItem v-if="can('crm', 'view')"        :href="route('admin.crm.dashboard')"  icon="analytics" label="CRM Enterprise" :active="$page.url.startsWith('/heimdall/crm')" />

        <!-- Configurações -->
        <div class="nav-section-label">Configurações</div>
        <NavItem v-if="can('frete', 'view')"       :href="route('admin.shipping.index')"    icon="shipping"  label="Frete"        :active="$page.url.startsWith('/heimdall/shipping')" />
        <NavItem v-if="can('api_config', 'view')"  :href="route('admin.api-config.index')"  icon="api"       label="APIs"         :active="$page.url.startsWith('/heimdall/api-config')" />
        <NavItem :href="route('admin.mail-tester.index')" icon="api" label="Testador E-mails" :active="$page.url.startsWith('/heimdall/mail-tester')" />
        <NavItem v-if="can('funcionarios', 'view')" :href="route('admin.employees.index')"  icon="employees" label="Funcionários" :active="$page.url.startsWith('/heimdall/employees')" />

        <!-- Marketing & Vitrine -->
        <div class="nav-section-label">Marketing & Vitrine</div>
        <NavItem v-if="can('marketing', 'view')" :href="route('admin.marketing.coupons')"    icon="coupons"   label="Cupons"              :active="$page.url.startsWith('/heimdall/marketing/coupons')" />
        <NavItem v-if="can('marketing', 'view')" :href="route('admin.marketing.points')"     icon="points"    label="Pontos"              :active="$page.url.startsWith('/heimdall/marketing/points')" />
        <NavItem v-if="can('marketing', 'view')" :href="route('admin.marketing.referrals')"  icon="referrals" label="Indicações"          :active="$page.url.startsWith('/heimdall/marketing/referrals')" />
        <NavItem v-if="can('marketing', 'view')" :href="route('admin.marketing.highlights')" icon="star"      label="Destaques"           :active="$page.url.startsWith('/heimdall/marketing/highlights')" />
        <NavItem v-if="can('marketing', 'view')" :href="route('admin.banners.index')"        icon="image"     label="Banners Vitrine"     :active="$page.url.startsWith('/heimdall/vitrine/banners')" />
        <NavItem v-if="can('marketing', 'view')" :href="route('admin.benefits.index')"       icon="star"      label="Barra de Benefícios" :active="$page.url.startsWith('/heimdall/vitrine/beneficios')" />

        <!-- Ferramentas -->
        <div class="nav-section-label">Ferramentas</div>
        <NavItem v-if="can('importacao', 'view')" :href="route('admin.import-export.index')" icon="import" label="Import/Export" :active="$page.url.startsWith('/heimdall/import-export')" />

        <!-- Administração — ADMIN ONLY -->
        <template v-if="$page.props.auth.employee?.is_admin">
          <div class="nav-section-label">Administração</div>
          <NavItem :href="route('admin.security.index')" icon="security" label="Segurança & Logs" :active="$page.url.startsWith('/heimdall/security')" badge-type="warning" />
        </template>
      </nav>

      <!-- Sidebar Footer — User Panel -->
      <div class="sidebar-footer">
        <!-- User Dropdown Menu -->
        <transition name="slide-up">
          <div v-if="showUserDropdown" class="user-dropdown-menu">
            <Link
              :href="route('admin.profile.show')"
              @click="showUserDropdown = false"
              class="dropdown-item"
            >
              <!-- Profile icon -->
              <svg class="dropdown-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
              </svg>
              Meus Dados / Perfil
            </Link>
            <button @click="logout" class="dropdown-item danger">
              <!-- Logout icon -->
              <svg class="dropdown-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
              </svg>
              Sair do Painel
            </button>
          </div>
        </transition>

        <div class="sidebar-user" @click="showUserDropdown = !showUserDropdown">
          <div class="sidebar-user-avatar">
            {{ initials($page.props.auth.employee.nome) }}
          </div>
          <div class="sidebar-user-info">
            <div class="sidebar-user-name">{{ $page.props.auth.employee.nome }}</div>
            <div class="sidebar-user-role">{{ $page.props.auth.employee.perfil?.nome }}</div>
          </div>
          <!-- Chevron SVG -->
          <svg
            class="sidebar-user-chevron"
            :class="{ open: showUserDropdown }"
            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
          >
            <polyline points="18 15 12 9 6 15"/>
          </svg>
        </div>
      </div>
    </aside>

    <!-- ═══ Main Content ═══ -->
    <div class="admin-main">

      <!-- Topbar -->
      <header class="admin-topbar">
        <!-- Mobile menu button -->
        <button class="btn-icon mobile-menu-btn" @click="sidebarOpen = !sidebarOpen" aria-label="Menu">
          <MenuIcon />
        </button>

        <!-- Breadcrumb -->
        <div class="topbar-breadcrumb">
          <slot name="breadcrumb">
            <span class="breadcrumb-current">{{ title }}</span>
          </slot>
        </div>

        <!-- Right actions -->
        <div class="topbar-right">
          <!-- Theme toggle -->
          <button class="btn-icon" @click="toggleTheme" :title="isDark ? 'Modo Claro' : 'Modo Escuro'">
            <!-- Sun icon (dark mode active) -->
            <svg v-if="isDark" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="5"/>
              <line x1="12" y1="1"  x2="12" y2="3"/>
              <line x1="12" y1="21" x2="12" y2="23"/>
              <line x1="4.22" y1="4.22"   x2="5.64" y2="5.64"/>
              <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
              <line x1="1"  y1="12" x2="3"  y2="12"/>
              <line x1="21" y1="12" x2="23" y2="12"/>
              <line x1="4.22" y1="19.78"   x2="5.64" y2="18.36"/>
              <line x1="18.36" y1="5.64"   x2="19.78" y2="4.22"/>
            </svg>
            <!-- Moon icon (light mode active) -->
            <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
            </svg>
          </button>

          <!-- Alerts indicator -->
          <button
            v-if="hasAlerts"
            class="topbar-alerts-indicator"
            @click="showAlertsPanel = true"
            :title="`${totalAlerts} alerta(s) crítico(s)`"
            aria-label="Alertas críticos"
          >
            <BellIcon />
            <span class="alert-dot" />
          </button>
          <button
            v-else
            class="btn-icon"
            title="Sem alertas críticos"
            @click="showAlertsPanel = true"
          >
            <BellIcon />
          </button>
        </div>
      </header>

      <!-- Page Content -->
      <main class="admin-content">
        <!-- Flash messages -->
        <transition name="slide-up">
          <div v-if="flash.success" class="alert alert-success alert-dismissible mb-4" role="alert">
            <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
            <span>{{ flash.success }}</span>
            <button class="alert-dismiss-btn" @click="dismissFlash('success')" aria-label="Fechar">
              <XIcon />
            </button>
          </div>
        </transition>
        <transition name="slide-up">
          <div v-if="flash.error" class="alert alert-danger alert-dismissible mb-4" role="alert">
            <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span>{{ flash.error }}</span>
            <button class="alert-dismiss-btn" @click="dismissFlash('error')" aria-label="Fechar">
              <XIcon />
            </button>
          </div>
        </transition>

        <slot />
      </main>
    </div>

    <!-- ═══ Alerts Panel (Drawer) ═══ -->
    <transition name="slide-left">
      <div
        v-if="showAlertsPanel"
        class="alerts-drawer-backdrop"
        role="dialog"
        aria-modal="true"
        aria-labelledby="alerts-panel-title"
      >
        <!-- Backdrop -->
        <div class="alerts-drawer-overlay" @click="showAlertsPanel = false" />

        <!-- Panel -->
        <div class="alerts-drawer-panel">
          <div class="alerts-drawer-header">
            <div class="alerts-drawer-title-wrap">
              <div class="alerts-drawer-icon">
                <BellIcon />
              </div>
              <h2 id="alerts-panel-title" class="alerts-drawer-title">Alertas Críticos</h2>
              <span v-if="hasAlerts" class="badge badge-danger">{{ totalAlerts }}</span>
            </div>
            <button @click="showAlertsPanel = false" class="btn-icon">
              <XIcon />
            </button>
          </div>

          <div class="alerts-drawer-body">
            <!-- Pending Orders Alert -->
            <div v-if="$page.props.counts?.pendingOrders > 0" class="alert-card alert-card--warning">
              <div class="alert-card-header">
                <div class="alert-card-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 0 1-8 0"/>
                  </svg>
                </div>
                <span class="alert-card-title">Pedidos Pendentes</span>
                <span class="badge badge-warning">{{ $page.props.counts.pendingOrders }}</span>
              </div>
              <p class="alert-card-desc">
                {{ $page.props.counts.pendingOrders }} pedido(s) aguardando processamento.
              </p>
              <Link
                :href="route('admin.orders.index')"
                @click="showAlertsPanel = false"
                class="alert-card-link"
              >
                Ver pedidos →
              </Link>
            </div>

            <!-- Critical Stock Alert -->
            <div v-if="$page.props.counts?.criticalStock > 0" class="alert-card alert-card--danger">
              <div class="alert-card-header">
                <div class="alert-card-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                  </svg>
                </div>
                <span class="alert-card-title">Estoque Crítico</span>
                <span class="badge badge-danger">{{ $page.props.counts.criticalStock }}</span>
              </div>
              <p class="alert-card-desc">
                {{ $page.props.counts.criticalStock }} produto(s) com estoque abaixo do mínimo.
              </p>
              <Link
                :href="route('admin.stock.index')"
                @click="showAlertsPanel = false"
                class="alert-card-link"
              >
                Verificar estoque →
              </Link>
            </div>

            <!-- No alerts state -->
            <div v-if="!hasAlerts" class="empty-state">
              <div class="empty-state-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                  <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
              </div>
              <p class="empty-state-title">Tudo em ordem!</p>
              <p class="empty-state-desc">Nenhum alerta crítico no momento.</p>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, usePage, Link } from '@inertiajs/vue3'
import NavItem from '@/Components/UI/NavItem.vue'
import MenuIcon from '@/Components/Icons/MenuIcon.vue'
import BellIcon from '@/Components/Icons/BellIcon.vue'
import XIcon    from '@/Components/Icons/XIcon.vue'

const props = defineProps({
  title: { type: String, default: '' },
})

const page = usePage()
const sidebarOpen      = ref(false)
const showAlertsPanel  = ref(false)
const isDark           = ref(false)
const showUserDropdown = ref(false)

// Local flash so we can dismiss it
const localFlash = ref({ success: null, error: null })

const flash = computed(() => {
  const f = page.props.flash || {}
  return {
    success: localFlash.value.success ?? f.success,
    error:   localFlash.value.error   ?? f.error,
  }
})

function dismissFlash(type) {
  localFlash.value[type] = null
}

// Reset local flash on navigation
router.on('navigate', () => {
  localFlash.value = { success: null, error: null }
})

const hasAlerts = computed(() => {
  const c = page.props.counts || {}
  return (c.criticalStock > 0) || (c.pendingOrders > 0)
})

const totalAlerts = computed(() => {
  const c = page.props.counts || {}
  return (c.criticalStock || 0) + (c.pendingOrders || 0)
})

// Theme toggle
function toggleTheme() {
  isDark.value = !isDark.value
  document.documentElement.classList.toggle('dark-mode', isDark.value)
  localStorage.setItem('theme', isDark.value ? 'dark' : 'light')
}

onMounted(() => {
  const saved = localStorage.getItem('theme')
  isDark.value = saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)
  document.documentElement.classList.toggle('dark-mode', isDark.value)
})

function can(module, action = 'view') {
  if (page.props.auth.employee?.is_admin) return true
  const permissions = page.props.auth?.permissions || []
  return permissions.some(p => p.modulo === module && p.acao === action)
}

function initials(name) {
  if (!name) return '?'
  return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()
}

function logout() {
  showUserDropdown.value = false
  router.post(route('admin.logout'))
}

const basePath = typeof window !== 'undefined' && window.location.pathname.includes('/~jeff2892') ? '/~jeff2892' : ''
const logoUrl = computed(() => {
  const file = isDark.value ? 'logo-heimdall.png' : 'logo-heimdall-dark.png'
  return `${basePath}/${file}?v=4`
})
</script>

<style scoped>
/* ── Sidebar overlay ── */
.sidebar-overlay {
  position: fixed;
  inset: 0;
  background: var(--color-bg-overlay);
  z-index: 109;
  display: none;
  backdrop-filter: blur(2px);
}
@media (max-width: 768px) { .sidebar-overlay { display: block; } }

/* ── Topbar right group ── */
.topbar-right {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

/* ── Drawer backdrop ── */
.alerts-drawer-backdrop {
  position: fixed;
  inset: 0;
  z-index: 150;
  display: flex;
  justify-content: flex-end;
}

.alerts-drawer-overlay {
  position: absolute;
  inset: 0;
  background: var(--color-bg-overlay);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}

.alerts-drawer-panel {
  position: relative;
  width: 380px;
  height: 100vh;
  background: var(--color-bg-card);
  border-left: 1px solid var(--color-border);
  box-shadow: var(--shadow-xl);
  display: flex;
  flex-direction: column;
  z-index: 1;
}

.alerts-drawer-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border);
  flex-shrink: 0;
}

.alerts-drawer-title-wrap {
  display: flex;
  align-items: center;
  gap: 0.625rem;
}

.alerts-drawer-icon {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-md);
  background: var(--color-brand-surface);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-brand);
}

.alerts-drawer-icon svg { width: 16px; height: 16px; }

.alerts-drawer-title {
  font-family: 'Outfit', sans-serif;
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin: 0;
}

.alerts-drawer-body {
  flex: 1;
  overflow-y: auto;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.875rem;
}

/* ── Alert cards inside drawer ── */
.alert-card {
  border-radius: var(--radius-lg);
  padding: 1rem 1.125rem;
  border: 1px solid transparent;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.alert-card--warning {
  background: var(--color-warning-bg);
  border-color: var(--color-warning-border);
}

.alert-card--danger {
  background: var(--color-danger-bg);
  border-color: var(--color-danger-border);
}

.alert-card-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.alert-card-icon {
  width: 28px;
  height: 28px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.alert-card--warning .alert-card-icon { background: rgba(217, 119, 6, 0.15); color: var(--color-warning); }
.alert-card--danger  .alert-card-icon { background: rgba(220, 38, 38, 0.15); color: var(--color-danger); }

.alert-card-icon svg { width: 14px; height: 14px; }

.alert-card-title {
  font-size: 0.875rem;
  font-weight: 600;
  flex: 1;
}

.alert-card--warning .alert-card-title { color: var(--color-warning); }
.alert-card--danger  .alert-card-title { color: var(--color-danger); }

.alert-card-desc {
  font-size: 0.8125rem;
  line-height: 1.5;
  color: var(--color-text-secondary);
  margin: 0;
}

.alert-card-link {
  font-size: 0.8125rem;
  font-weight: 600;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  margin-top: 0.25rem;
}

.alert-card--warning .alert-card-link { color: var(--color-warning); }
.alert-card--danger  .alert-card-link { color: var(--color-danger); }

.alert-card-link:hover { text-decoration: underline; }

/* ── Slide-left transition (drawer) ── */
.slide-left-enter-active,
.slide-left-leave-active {
  transition: opacity 0.25s ease;
}
.slide-left-enter-active .alerts-drawer-panel,
.slide-left-leave-active .alerts-drawer-panel {
  transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-left-enter-from .alerts-drawer-panel,
.slide-left-leave-to   .alerts-drawer-panel {
  transform: translateX(100%);
}
.slide-left-enter-from,
.slide-left-leave-to { opacity: 0; }

/* ── Slide-up transition (flash, dropdown) ── */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: opacity var(--transition-normal), transform var(--transition-normal);
}
.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

/* ── Mobile drawer panel full-width ── */
@media (max-width: 480px) {
  .alerts-drawer-panel { width: 100%; }
}
</style>

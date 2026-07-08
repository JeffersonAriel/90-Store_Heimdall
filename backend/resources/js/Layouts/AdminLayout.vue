<template>
  <div class="admin-wrapper">
    <!-- Sidebar Overlay (mobile) -->
    <div
      v-if="sidebarOpen"
      class="sidebar-overlay"
      @click="sidebarOpen = false"
    />

    <!-- Sidebar -->
    <aside class="admin-sidebar" :class="{ open: sidebarOpen }">
      <!-- Logo -->
      <div class="sidebar-logo">
        <img :src="logoUrl" class="sidebar-logo-icon" style="object-fit: contain; background: none; border-radius: 0;" alt="Heimdall" />
        <span class="sidebar-logo-text">Heimdall</span>
      </div>

      <!-- Navigation -->
      <nav class="sidebar-nav">
        <!-- Dashboard -->
        <NavItem :href="route('admin.dashboard')" icon="dashboard" label="Dashboard" :active="$page.url === '/heimdall' || $page.url.startsWith('/heimdall/dashboard')" />

        <!-- Catálogo -->
        <div class="nav-section-label">Catálogo</div>
        <NavItem v-if="can('produtos', 'view')"     :href="route('admin.products.index')"    icon="products"    label="Produtos"    :active="$page.url.startsWith('/heimdall/products')" />
        <NavItem v-if="can('fornecedores', 'view')" :href="route('admin.suppliers.index')"   icon="suppliers"   label="Fornecedores" :active="$page.url.startsWith('/heimdall/suppliers')" />
        <NavItem v-if="can('produtos', 'view')"     :href="route('admin.categories.index')"  icon="categories"  label="Categorias"  :active="$page.url.startsWith('/heimdall/categories')" />

        <!-- Operações -->
        <div class="nav-section-label">Operações</div>
        <NavItem v-if="can('pedidos', 'view')"   :href="route('admin.orders.index')"   icon="orders"   label="Pedidos"  :active="$page.url.startsWith('/heimdall/orders')"  :badge="$page.props.counts?.pendingOrders" />
        <NavItem v-if="can('clientes', 'view')"  :href="route('admin.clients.index')"  icon="employees" label="Clientes" :active="$page.url.startsWith('/heimdall/clients')" />
        <NavItem v-if="can('estoque', 'view')"   :href="route('admin.stock.index')"    icon="stock"    label="Estoque"  :active="$page.url.startsWith('/heimdall/stock')"   :badge="$page.props.counts?.criticalStock" badge-type="danger" />
        <NavItem v-if="can('financeiro', 'view')" :href="route('admin.financial.index')" icon="financial" label="Financeiro" :active="$page.url.startsWith('/heimdall/financial')" />
        <NavItem v-if="can('pedidos', 'view') || can('financeiro', 'view')" :href="route('admin.agenda.index')" icon="calendar" label="Agenda" :active="$page.url.startsWith('/heimdall/agenda')" />

        <!-- Configurações -->
        <div class="nav-section-label">Configurações</div>
        <NavItem v-if="can('frete', 'view')"      :href="route('admin.shipping.index')"    icon="shipping"    label="Frete"       :active="$page.url.startsWith('/heimdall/shipping')" />
        <NavItem v-if="can('api_config', 'view')" :href="route('admin.api-config.index')"  icon="api"         label="APIs"        :active="$page.url.startsWith('/heimdall/api-config')" />
        <NavItem v-if="can('funcionarios', 'view')" :href="route('admin.employees.index')" icon="employees"   label="Funcionários" :active="$page.url.startsWith('/heimdall/employees')" />

        <!-- Marketing & Vitrine -->
        <div class="nav-section-label">Marketing & Vitrine</div>
        <NavItem v-if="can('marketing', 'view')" :href="route('admin.marketing.coupons')" icon="coupons" label="Cupons"  :active="$page.url.startsWith('/heimdall/marketing/coupons')" />
        <NavItem v-if="can('marketing', 'view')" :href="route('admin.marketing.points')"  icon="points"  label="Pontos"  :active="$page.url.startsWith('/heimdall/marketing/points')" />
        <NavItem v-if="can('marketing', 'view')" :href="route('admin.marketing.referrals')" icon="referrals" label="Indicações" :active="$page.url.startsWith('/heimdall/marketing/referrals')" />
        <NavItem v-if="can('marketing', 'view')" :href="route('admin.banners.index')" icon="image" label="Banners Vitrine" :active="$page.url.startsWith('/heimdall/vitrine/banners')" />
        <NavItem v-if="can('marketing', 'view')" :href="route('admin.benefits.index')" icon="star" label="Barra de Benefícios" :active="$page.url.startsWith('/heimdall/vitrine/beneficios')" />

        <!-- Import/Export -->
        <div class="nav-section-label">Ferramentas</div>
        <NavItem v-if="can('importacao', 'view')" :href="route('admin.import-export.index')" icon="import" label="Import/Export" :active="$page.url.startsWith('/heimdall/import-export')" />

        <!-- Segurança — ADMIN ONLY -->
        <template v-if="$page.props.auth.employee?.is_admin">
          <div class="nav-section-label">Administração</div>
          <NavItem :href="route('admin.security.index')" icon="security" label="Segurança & Logs" :active="$page.url.startsWith('/heimdall/security')" badge-type="warning" />
        </template>
      </nav>

      <!-- Sidebar Footer -->
      <div class="sidebar-footer" style="position: relative; padding: 0.75rem 1rem;">
        <!-- User Dropdown Menu -->
        <transition name="slide-up">
          <div v-if="showUserDropdown" class="user-dropdown-menu" style="position: absolute; bottom: 100%; left: 0.75rem; right: 0.75rem; background: var(--color-bg-secondary); border: 1px solid var(--color-border); border-radius: var(--radius-md); box-shadow: 0 -10px 25px -5px rgba(0,0,0,0.3); z-index: 100; margin-bottom: 0.5rem; display: flex; flex-direction: column; overflow: hidden;">
            <Link :href="route('admin.profile.show')" @click="showUserDropdown = false" class="dropdown-item" style="padding: 0.75rem 1rem; color: var(--color-text-primary); text-decoration: none; display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; border-bottom: 1px solid var(--color-border); transition: background 0.2s; cursor: pointer;">
              <span>⚙️</span> Meus Dados / Perfil
            </Link>
            <button @click="logout" class="dropdown-item text-red" style="padding: 0.75rem 1rem; text-align: left; display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #ef4444; border: none; background: transparent; cursor: pointer; transition: background 0.2s; width: 100%;">
              <span>🚪</span> Sair do Painel
            </button>
          </div>
        </transition>

        <div class="sidebar-user" @click="showUserDropdown = !showUserDropdown" style="display: flex; align-items: center; gap: 0.75rem; width: 100%; cursor: pointer;">
          <div class="sidebar-user-avatar" style="flex-shrink: 0;">
            {{ initials($page.props.auth.employee.nome) }}
          </div>
          <div class="sidebar-user-info" style="flex-grow: 1; min-width: 0; overflow: hidden;">
            <div class="sidebar-user-name" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $page.props.auth.employee.nome }}</div>
            <div class="sidebar-user-role" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $page.props.auth.employee.perfil?.nome }}</div>
          </div>
          <span style="font-size: 0.7rem; color: var(--color-text-muted); opacity: 0.6; margin-left: auto; transition: transform 0.2s;" :style="showUserDropdown ? 'transform: rotate(180deg);' : ''">▲</span>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="admin-main">
      <!-- Topbar -->
      <header class="admin-topbar">
        <button class="btn-icon mobile-menu-btn" @click="sidebarOpen = !sidebarOpen">
          <MenuIcon />
        </button>

        <div class="topbar-breadcrumb">
          <slot name="breadcrumb">
            <span class="text-muted">{{ title }}</span>
          </slot>
        </div>

        <div class="topbar-actions" style="margin-left: auto; display:flex; align-items:center; gap:0.75rem;">
          <!-- Theme toggle -->
          <button class="btn-icon" @click="toggleTheme" title="Alternar Modo Claro/Escuro">
            <span v-if="isDark" style="font-size: 1.25rem;">☀️</span>
            <span v-else style="font-size: 1.25rem;">🌙</span>
          </button>

          <!-- Global search -->
          <button class="btn-icon" title="Buscar">
            <SearchIcon />
          </button>

          <!-- Critical alerts indicator -->
          <div v-if="hasAlerts" class="topbar-alerts-indicator" @click="showAlertsPanel = true">
            <BellIcon />
            <span class="alert-dot" />
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="admin-content">
        <!-- Flash messages -->
        <transition name="slide-up">
          <div v-if="flash.success" class="alert alert-success mb-4">
            <CheckIcon />
            <span>{{ flash.success }}</span>
          </div>
        </transition>
        <transition name="slide-up">
          <div v-if="flash.error" class="alert alert-danger mb-4">
            <XIcon />
            <span>{{ flash.error }}</span>
          </div>
        </transition>

        <slot />
      </main>
    </div>

    <!-- Alerts Panel (Drawer) -->
    <transition name="slide-left">
      <div v-if="showAlertsPanel" class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true" style="position: fixed; inset: 0; z-index: 150; display: flex; justify-content: flex-end;">
        <!-- Backdrop -->
        <div class="absolute inset-0 transition-opacity" style="position: absolute; inset: 0; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px);" @click="showAlertsPanel = false"></div>

        <div class="pointer-events-auto w-screen max-w-md transform transition-all" style="position: relative; width: 400px; height: 100vh; background: var(--color-bg-card); border-left: 1px solid var(--color-border); box-shadow: -10px 0 25px -5px rgba(0,0,0,0.3); display: flex; flex-direction: column;">
          <div class="flex h-full flex-col py-6">
            <!-- Header -->
            <div class="px-6 flex items-center justify-between border-b pb-4" style="border-bottom: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center;">
              <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2" style="display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                <span>🔔</span> Alertas Críticos
              </h2>
              <button @click="showAlertsPanel = false" class="btn-icon" style="padding: 4px;">
                <XIcon style="width: 20px; height: 20px;" />
              </button>
            </div>

            <!-- Content -->
            <div class="relative flex-1 px-6 py-6" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; overflow-y: auto;">
              <!-- Pending Orders Alert -->
              <div v-if="$page.props.counts?.pendingOrders > 0" class="p-4 rounded-xl border flex flex-col gap-2" style="padding: 1rem; border-radius: 12px; background: var(--color-warning-bg); border: 1px solid rgba(217, 119, 6, 0.2); color: var(--color-warning); display: flex; flex-direction: column; gap: 0.5rem;">
                <div class="flex items-center gap-2 font-bold" style="font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                  <span>📦</span> Pedidos Pendentes
                </div>
                <p class="text-sm" style="margin: 0; font-size: 0.875rem;">Existem <strong>{{ $page.props.counts.pendingOrders }}</strong> pedidos aguardando processamento.</p>
                <Link :href="route('admin.orders.index')" @click="showAlertsPanel = false" class="text-xs underline font-bold mt-1 block" style="font-size: 0.75rem; text-decoration: underline; font-weight: 700; color: var(--color-warning); display: block; margin-top: 0.25rem;">
                  Ir para Pedidos →
                </Link>
              </div>

              <!-- Critical Stock Alert -->
              <div v-if="$page.props.counts?.criticalStock > 0" class="p-4 rounded-xl border flex flex-col gap-2" style="padding: 1rem; border-radius: 12px; background: var(--color-danger-bg); border: 1px solid rgba(220, 38, 38, 0.2); color: var(--color-danger); display: flex; flex-direction: column; gap: 0.5rem;">
                <div class="flex items-center gap-2 font-bold" style="font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                  <span>⚠️</span> Estoque Crítico
                </div>
                <p class="text-sm" style="margin: 0; font-size: 0.875rem;">Existem <strong>{{ $page.props.counts.criticalStock }}</strong> produtos com estoque abaixo do mínimo.</p>
                <Link :href="route('admin.stock.index')" @click="showAlertsPanel = false" class="text-xs underline font-bold mt-1 block" style="font-size: 0.75rem; text-decoration: underline; font-weight: 700; color: var(--color-danger); display: block; margin-top: 0.25rem;">
                  Verificar Estoque →
                </Link>
              </div>

              <!-- No alerts -->
              <div v-if="!hasAlerts" class="text-center py-12 text-gray-500" style="text-align: center; padding: 3rem 0; color: #9ca3af;">
                🎉 Nenhum alerta crítico pendente!
              </div>
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

// Icons (inline SVG components)
import MenuIcon from '@/Components/Icons/MenuIcon.vue'
import LogoutIcon from '@/Components/Icons/LogoutIcon.vue'
import SearchIcon from '@/Components/Icons/SearchIcon.vue'
import BellIcon from '@/Components/Icons/BellIcon.vue'
import CheckIcon from '@/Components/Icons/CheckIcon.vue'
import XIcon from '@/Components/Icons/XIcon.vue'

const props = defineProps({
  title: { type: String, default: '' },
})

const page = usePage()
const sidebarOpen = ref(false)
const showAlertsPanel = ref(false)
const isDark = ref(false)
const showUserDropdown = ref(false)

const flash = computed(() => page.props.flash || {})

const hasAlerts = computed(() => {
  const counts = page.props.counts || {}
  return counts.criticalStock > 0 || counts.pendingOrders > 0
})

// Toggle Theme
function toggleTheme() {
  isDark.value = !isDark.value
  if (isDark.value) {
    document.documentElement.classList.add('dark-mode')
    localStorage.setItem('theme', 'dark')
  } else {
    document.documentElement.classList.remove('dark-mode')
    localStorage.setItem('theme', 'light')
  }
}

onMounted(() => {
  const savedTheme = localStorage.getItem('theme')
  if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    isDark.value = true
    document.documentElement.classList.add('dark-mode')
  }
})

function can(module, action = 'view') {
  const permissions = page.props.auth?.permissions || []
  return permissions.some(p => p.modulo === module && p.acao === action)
}

function initials(name) {
  if (!name) return '?'
  return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()
}

function logout() {
  router.post(route('admin.logout'))
}

const basePath = typeof window !== 'undefined' && window.location.pathname.includes('/~jeff2892') ? '/~jeff2892' : ''
const logoUrl = computed(() => {
  const file = isDark.value ? 'logo-heimdall.png' : 'logo-heimdall-dark.png'
  return `${basePath}/${file}?v=4`
})
</script>

<style scoped>
.sidebar-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 99;
  display: none;
}

@media (max-width: 768px) {
  .sidebar-overlay { display: block; }
}

.mobile-menu-btn { display: none; }
@media (max-width: 768px) { .mobile-menu-btn { display: flex; } }

.topbar-breadcrumb {
  font-size: 0.9375rem;
  font-weight: 500;
  color: var(--color-text-secondary);
}

.topbar-alerts-indicator {
  position: relative;
  cursor: pointer;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-md);
  transition: background var(--transition-fast);
}

.topbar-alerts-indicator:hover { background: var(--color-bg-elevated); }

.alert-dot {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 8px;
  height: 8px;
  background: var(--color-danger);
  border-radius: 50%;
  border: 2px solid var(--color-bg-secondary);
  animation: pulse-critical 2s infinite;
}

.sidebar-footer {
  border-top: 1px solid var(--color-border);
  padding: 0.875rem 1rem;
}

.sidebar-user {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.sidebar-user-avatar {
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, var(--color-brand), var(--color-brand-dark));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
  flex-shrink: 0;
}

.sidebar-user-name {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sidebar-user-role {
  font-size: 0.75rem;
  color: var(--color-text-muted);
}

.sidebar-user-info {
  flex: 1;
  min-width: 0;
}

/* Drawer slide-in animation */
.slide-left-enter-active, .slide-left-leave-active {
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s linear;
}
.slide-left-enter-from, .slide-left-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.dropdown-item:hover {
  background: var(--color-bg-elevated) !important;
}

/* Slide-up transition for dropdown */
.slide-up-enter-active, .slide-up-leave-active {
  transition: transform 0.15s ease, opacity 0.15s ease;
}
.slide-up-enter-from, .slide-up-leave-to {
  transform: translateY(10px);
  opacity: 0;
}
</style>

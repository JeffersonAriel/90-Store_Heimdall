<template>
  <div class="erp-wrapper">
    <!-- Sidebar -->
    <aside class="erp-sidebar" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
      <!-- Logo -->
      <div class="sidebar-logo">
        <div class="logo-icon">
          <svg viewBox="0 0 24 24" fill="none" class="w-6 h-6">
            <path d="M12 2L2 7l10 5 10-5-10-5z" fill="currentColor" opacity="0.9"/>
            <path d="M2 17l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <Transition name="fade">
          <span v-if="!sidebarCollapsed" class="logo-text">HEIMDALL</span>
        </Transition>
      </div>

      <!-- Navigation -->
      <nav class="sidebar-nav">
        <div v-for="section in navigation" :key="section.title">
          <p v-if="!sidebarCollapsed" class="nav-section-title">{{ section.title }}</p>
          <Link
            v-for="item in section.items"
            :key="item.name"
            :href="item.href"
            class="nav-item"
            :class="{ active: isActive(item.href) }"
            :title="sidebarCollapsed ? item.name : ''"
          >
            <component :is="item.icon" class="nav-icon" />
            <Transition name="fade">
              <span v-if="!sidebarCollapsed">{{ item.name }}</span>
            </Transition>
            <span
              v-if="item.badge && !sidebarCollapsed"
              class="badge badge-danger ml-auto text-xs"
            >{{ item.badge }}</span>
          </Link>
        </div>
      </nav>

      <!-- User info -->
      <div class="sidebar-user">
        <div class="user-avatar">{{ userInitials }}</div>
        <Transition name="fade">
          <div v-if="!sidebarCollapsed" class="user-info">
            <p class="user-name">{{ $page.props.auth?.user?.name }}</p>
            <p class="user-role">{{ $page.props.auth?.user?.role }}</p>
          </div>
        </Transition>
      </div>
    </aside>

    <!-- Main content -->
    <div class="erp-main">
      <!-- Header -->
      <header class="erp-header">
        <div class="header-left">
          <button class="sidebar-toggle" @click="sidebarCollapsed = !sidebarCollapsed">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
          <Breadcrumbs />
        </div>
        <div class="header-right">
          <SearchBar />
          <NotificationBell />
          <UserMenu />
        </div>
      </header>

      <!-- Page content -->
      <main class="erp-content">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import Breadcrumbs from '@/Components/ERP/Breadcrumbs.vue'
import SearchBar from '@/Components/ERP/SearchBar.vue'
import NotificationBell from '@/Components/ERP/NotificationBell.vue'
import UserMenu from '@/Components/ERP/UserMenu.vue'

const page = usePage()
const sidebarCollapsed = ref(false)

const userInitials = computed(() => {
  const name = (page.props.auth as any)?.user?.name || 'A'
  return name.split(' ').map((n: string) => n[0]).slice(0, 2).join('').toUpperCase()
})

const isActive = (href: string) => {
  return window.location.pathname.startsWith(href)
}

const navigation = [
  {
    title: 'Principal',
    items: [
      { name: 'Dashboard', href: '/erp/dashboard', icon: 'IconDashboard' },
    ],
  },
  {
    title: 'Catálogo',
    items: [
      { name: 'Produtos', href: '/erp/products', icon: 'IconPackage' },
      { name: 'Categorias', href: '/erp/categories', icon: 'IconFolder' },
      { name: 'Marcas', href: '/erp/brands', icon: 'IconTag' },
    ],
  },
  {
    title: 'Comercial',
    items: [
      { name: 'Pedidos', href: '/erp/orders', icon: 'IconShoppingCart', badge: '3' },
      { name: 'Clientes', href: '/erp/customers', icon: 'IconUsers' },
      { name: 'CRM', href: '/erp/crm', icon: 'IconHeart' },
      { name: 'Marketing', href: '/erp/marketing', icon: 'IconMegaphone' },
    ],
  },
  {
    title: 'Operações',
    items: [
      { name: 'Estoque', href: '/erp/stock', icon: 'IconWarehouse' },
      { name: 'Compras', href: '/erp/purchases', icon: 'IconTruck' },
    ],
  },
  {
    title: 'Financeiro',
    items: [
      { name: 'Financeiro', href: '/erp/financial', icon: 'IconCoin' },
      { name: 'Relatórios', href: '/erp/reports', icon: 'IconChart' },
      { name: 'BI', href: '/erp/bi', icon: 'IconTrendingUp' },
    ],
  },
  {
    title: 'Sistema',
    items: [
      { name: 'IA', href: '/erp/ai', icon: 'IconSparkles' },
      { name: 'Usuários', href: '/erp/users', icon: 'IconUserCog' },
      { name: 'Permissões', href: '/erp/permissions', icon: 'IconLock' },
      { name: 'Auditoria', href: '/erp/audit', icon: 'IconShield' },
      { name: 'Configurações', href: '/erp/settings', icon: 'IconSettings' },
    ],
  },
]
</script>

<style scoped>
.erp-wrapper {
  display: flex;
  min-height: 100vh;
}

.sidebar-logo {
  height: 64px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 20px;
  border-bottom: 1px solid rgba(99, 102, 241, 0.15);
}

.logo-icon {
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, #6366f1, #4f46e5);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.logo-text {
  font-size: 16px;
  font-weight: 800;
  background: linear-gradient(135deg, #c7d2fe, #a5b4fc);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  letter-spacing: 0.1em;
}

.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  padding: 16px 0;
}

.sidebar-user {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 16px;
  border-top: 1px solid rgba(99, 102, 241, 0.15);
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #4f46e5);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
}

.user-name {
  font-size: 13px;
  font-weight: 600;
  color: #e2e8f0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-role {
  font-size: 11px;
  color: #64748b;
  white-space: nowrap;
}

.header-left, .header-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-left { flex: 1; }

.sidebar-toggle {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 8px;
  border-radius: 8px;
  transition: all 0.15s;
}

.sidebar-toggle:hover {
  background: rgba(99, 102, 241, 0.1);
  color: #a5b4fc;
}

.erp-header {
  display: flex;
  align-items: center;
  padding: 0 24px;
  justify-content: space-between;
}

.erp-content {
  padding: 24px;
}

.sidebar-collapsed {
  width: 72px !important;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s, transform 0.2s;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateX(-8px);
}
</style>

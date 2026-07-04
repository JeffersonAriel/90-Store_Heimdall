<template>
  <div class="app-container">
    <header class="navbar">
      <RouterLink to="/" class="logo-link">
        <span>⚡ 90-Store</span>
      </RouterLink>

      <nav class="nav-links">
        <RouterLink to="/" class="nav-item">Vitrine</RouterLink>
        <RouterLink to="/carrinho" class="nav-item">
          Carrinho ({{ store.cart.reduce((t, i) => t + i.quantidade, 0) }})
        </RouterLink>
        
        <template v-if="store.isAuthenticated">
          <RouterLink to="/minha-conta" class="nav-item">Minha Conta</RouterLink>
          <a href="#" class="nav-item" @click.prevent="logout">Sair</a>
        </template>
        <template v-else>
          <RouterLink to="/login" class="nav-item">Entrar</RouterLink>
        </template>
      </nav>
    </header>

    <main class="main-content">
      <RouterView />
    </main>

    <footer class="text-secondary text-center mt-6" style="padding: 2rem; border-top: 1px solid var(--border-color); font-size: 0.8125rem;">
      <p>© 2026 90-Store Sports E-commerce. Todos os direitos reservados.</p>
      <p class="text-muted mt-1">Desenvolvido integrado ao ecossistema de gestão Heimdall.</p>
    </footer>
  </div>
</template>

<script setup>
import { RouterView, RouterLink, useRouter } from 'vue-router'
import { useStore } from '@/store/main'
import { onMounted } from 'vue'

const store = useStore()
const router = useRouter()

onMounted(() => {
  store.fetchUser()
})

function logout() {
  store.logout()
  router.push('/')
}
</script>

<style>
.text-center { text-align: center; }
.mt-6 { margin-top: 2rem; }
.text-muted { color: var(--text-muted); }
.text-secondary { color: var(--text-secondary); }
</style>

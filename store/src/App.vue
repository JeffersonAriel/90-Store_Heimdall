<template>
  <div class="app-layout">
    <!-- Header -->
    <header class="header">
      <div class="container header-top">
        <div class="logo">
          <RouterLink to="/" class="logo-link">
            <img src="/logo.svg" alt="90+ Store" class="logo-img" />
          </RouterLink>
        </div>
        
        <div class="search-container">
          <input type="text" placeholder="O que você procura? (ex: chuteira nike)" class="search-input" />
          <button class="search-btn">🔍</button>
        </div>

        <div class="header-actions">
          <button class="action-btn" title="Favoritos" @click="isFavoritesOpen = true">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
            <span class="badge-count" v-if="favoriteCount > 0">{{ favoriteCount }}</span>
          </button>
          
          <RouterLink to="/minha-conta" class="action-btn" title="Minha Conta">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </RouterLink>
          
          <button class="action-btn cart-btn" title="Carrinho" @click="isCartOpen = true">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="9" cy="21" r="1"></circle>
              <circle cx="20" cy="21" r="1"></circle>
              <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
            <span class="badge-count" v-if="cartCount > 0">{{ cartCount }}</span>
          </button>
        </div>
      </div>

      <!-- Mega Menu -->
      <nav class="nav-menu">
        <div class="container">
          <nav class="main-nav">
            <div v-for="cat in categories" :key="cat.id" class="nav-item">
              <RouterLink :to="`/catalogo?categoria=${cat.slug}`" class="nav-link">{{ cat.nome }}</RouterLink>
              
              <div class="mega-menu" v-if="cat.children && cat.children.length > 0">
                <div class="mega-menu-content">
                  <div class="mega-col">
                    <h4>{{ cat.nome }}</h4>
                    <ul>
                      <li v-for="child in cat.children" :key="child.id">
                        <RouterLink :to="`/catalogo?categoria=${child.slug}`">{{ child.nome }}</RouterLink>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </nav>
        </div>
      </nav>

      <!-- Benefits Bar -->
      <div class="benefits-bar" v-if="benefits.length > 0">
        <div class="container benefits-grid">
          <div v-for="b in benefits" :key="b.id" class="benefit-item">
            <span class="icon">{{ b.icon }}</span>
            <div class="benefit-text">
              <strong>{{ b.title }}</strong>
              <span>{{ b.description }}</span>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
      <RouterView />
    </main>

    <!-- Footer -->
    <footer class="footer">
      <div class="container footer-grid">
        <div class="footer-col brand-col">
          <div class="logo">
            <img src="/logo.svg" alt="90+ Store" class="logo-img" />
          </div>
          <p class="footer-desc">A sua loja de artigos esportivos. Alta performance, estilo e as melhores marcas do mundo.</p>
          <div class="social-links">
            <a href="https://www.instagram.com/sou90mais/" target="_blank" rel="noopener noreferrer">Instagram</a>
            <a href="https://wa.me/5511945342493" target="_blank" rel="noopener noreferrer">WhatsApp</a>
            <a href="#">YouTube</a>
            <a href="#">TikTok</a>
          </div>
        </div>

        <div class="footer-col">
          <h4>Institucional</h4>
          <RouterLink to="/">Quem Somos</RouterLink>
          <RouterLink to="/">Nossas Lojas</RouterLink>
          <RouterLink to="/">Trabalhe Conosco</RouterLink>
          <RouterLink to="/">Política de Privacidade</RouterLink>
        </div>

        <div class="footer-col">
          <h4>Ajuda</h4>
          <RouterLink to="/">Central de Atendimento</RouterLink>
          <RouterLink to="/">Trocas e Devoluções</RouterLink>
          <RouterLink to="/">Prazos de Entrega</RouterLink>
          <RouterLink to="/">Regras de Desconto</RouterLink>
        </div>

        <div class="footer-col">
          <h4>Pagamento e Segurança</h4>
          <div class="payment-methods">
            <span class="badge badge-dark">PIX</span>
            <span class="badge badge-dark">VISA</span>
            <span class="badge badge-dark">MASTERCARD</span>
          </div>
          <div class="security-badges mt-4">
            <span class="badge badge-dark">🔒 SITE SEGURO</span>
          </div>
        </div>
      </div>
      
      <div class="footer-bottom">
        <div class="container footer-bottom-flex">
          <p>&copy; {{ new Date().getFullYear() }} 90+ Store. Todos os direitos reservados. </p>
          <div class="developer-info">
            <span>Desenvolvido por: <strong>Jefferson Santos</strong></span>
            <a href="https://www.linkedin.com/in/jefferson-ariel-santos/" target="_blank" rel="noopener noreferrer" title="LinkedIn" class="dev-link">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="dev-icon"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
            </a>
            <a href="https://wa.me/5511940112438" target="_blank" rel="noopener noreferrer" title="WhatsApp" class="dev-link">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="dev-icon"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
            </a>
          </div>
        </div>
      </div>
    </footer>

    <!-- Cart Drawer -->
    <CartDrawer :is-open="isCartOpen" @close="isCartOpen = false" />
    
    <!-- Favorites Drawer -->
    <FavoritesDrawer :is-open="isFavoritesOpen" @close="isFavoritesOpen = false" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink, RouterView } from 'vue-router'
import axios from 'axios'
import CartDrawer from './components/CartDrawer.vue'
import FavoritesDrawer from './components/FavoritesDrawer.vue'
import { useStore } from './store/main'

const store = useStore()
const cartCount = computed(() => store.cart.reduce((total, item) => total + item.quantidade, 0))
const favoriteCount = computed(() => store.favorites?.length || 0)

const isCartOpen = ref(false)
const isFavoritesOpen = ref(false)
const categories = ref([])
const benefits = ref([])

onMounted(async () => {
  try {
    const res = await axios.get('/api/store-settings')
    categories.value = res.data.categories || []
    benefits.value = res.data.benefits || []
  } catch (error) {
    console.error('Failed to load store settings', error)
  }

  window.addEventListener('open-cart-drawer', () => {
    isCartOpen.value = true
  })
})
</script>

<style scoped>
.app-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.main-content {
  flex: 1;
}

/* Header */
.header {
  background-color: var(--color-black);
  border-bottom: 1px solid var(--color-black-lighter);
  position: sticky;
  top: 0;
  z-index: var(--z-header);
}

.header-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-4) 0;
  gap: var(--spacing-8);
}

.logo-link {
  display: flex;
  align-items: center;
}

.logo-img {
  height: 40px;
  object-fit: contain;
}

/* Search */
.search-container {
  flex: 1;
  max-width: 600px;
  position: relative;
  display: flex;
}

.search-input {
  width: 100%;
  background-color: var(--color-black-light);
  border: 2px solid var(--color-black-lighter);
  color: var(--color-white);
  padding: var(--spacing-3) var(--spacing-4);
  padding-right: 3rem;
  border-radius: var(--border-radius-full);
  font-family: var(--font-body);
  transition: var(--transition);
}

.search-input:focus {
  outline: none;
  border-color: var(--color-red);
}

.search-btn {
  position: absolute;
  right: var(--spacing-3);
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-gray);
}

/* Actions */
.header-actions {
  display: flex;
  align-items: center;
  gap: var(--spacing-4);
}

.action-btn {
  color: var(--color-white);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  background: none;
  border: none;
  cursor: pointer;
  padding: var(--spacing-2);
  transition: var(--transition);
}

.action-btn:hover {
  color: var(--color-red);
}

.badge-count {
  position: absolute;
  top: -4px;
  right: -4px;
  background-color: var(--color-red);
  color: var(--color-white);
  font-size: 0.7rem;
  font-weight: bold;
  width: 16px;
  height: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}

/* Nav Menu */
.nav-menu {
  background-color: var(--color-black-light);
  border-bottom: 1px solid var(--color-black-lighter);
}

.menu-list {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.menu-item {
  position: relative;
}

.menu-item > a {
  display: block;
  padding: var(--spacing-3) var(--spacing-2);
  font-family: var(--font-title);
  font-size: 1rem;
  text-transform: uppercase;
  font-weight: 500;
  letter-spacing: 0.5px;
}

.menu-item > a:hover {
  color: var(--color-red);
}

.highlight > a { color: var(--color-white); }
.highlight-red > a { color: var(--color-red); }

/* Mega Dropdown */
.mega-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  background-color: var(--color-black-light);
  width: 100vw;
  transform: translateX(-50%);
  left: 50%;
  padding: var(--spacing-6) 0;
  border-top: 2px solid var(--color-red);
  box-shadow: 0 10px 30px rgba(0,0,0,0.5);
  opacity: 0;
  visibility: hidden;
  transition: var(--transition);
  z-index: var(--z-dropdown);
}

.menu-item.has-dropdown:hover .mega-dropdown {
  opacity: 1;
  visibility: visible;
}

.dropdown-grid {
  display: flex;
  gap: var(--spacing-8);
}

.dropdown-column h4 {
  color: var(--color-gray);
  margin-bottom: var(--spacing-3);
  font-size: 1rem;
}

.dropdown-column a {
  display: block;
  padding: var(--spacing-1) 0;
  color: var(--color-white);
  font-size: 0.9rem;
}

.dropdown-column a:hover {
  color: var(--color-red);
  padding-left: var(--spacing-2);
}

/* Benefits Bar */
.benefits-bar {
  background-color: var(--color-red);
  color: var(--color-white);
  padding: var(--spacing-2) 0;
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.benefits-grid {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.benefit-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
}

/* Footer */
.footer {
  background-color: var(--color-black-light);
  border-top: 1px solid var(--color-black-lighter);
  padding-top: var(--spacing-12);
}

.footer-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: var(--spacing-8);
  margin-bottom: var(--spacing-8);
}

.brand-col .footer-desc {
  color: var(--color-gray);
  margin-top: var(--spacing-4);
  margin-bottom: var(--spacing-4);
  max-width: 300px;
}

.social-links {
  display: flex;
  gap: var(--spacing-4);
}

.social-links a {
  color: var(--color-white);
  font-weight: 500;
}

.social-links a:hover {
  color: var(--color-red);
}

.footer-col h4 {
  margin-bottom: var(--spacing-4);
  font-size: 1.1rem;
}

.footer-col a {
  display: block;
  color: var(--color-gray);
  margin-bottom: var(--spacing-2);
  font-size: 0.9rem;
}

.footer-col a:hover {
  color: var(--color-white);
}

.payment-methods {
  display: flex;
  gap: var(--spacing-2);
  flex-wrap: wrap;
}

.mt-4 {
  margin-top: var(--spacing-4);
}

.footer-bottom {
  background-color: var(--color-black);
  padding: var(--spacing-4) 0;
  color: var(--color-gray-dark);
  font-size: 0.8rem;
  border-top: 1px solid var(--color-black-lighter);
}

.footer-bottom-flex {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: var(--spacing-2);
}

.developer-info {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
}

.developer-info strong {
  color: var(--color-white);
}

.dev-link {
  color: var(--color-gray);
  display: flex;
  align-items: center;
  transition: var(--transition);
}

.dev-link:hover {
  color: var(--color-red);
}

.dev-icon {
  width: 16px;
  height: 16px;
}

@media (max-width: 1024px) {
  .header-top { flex-wrap: wrap; }
  .search-container { order: 3; max-width: 100%; margin-top: var(--spacing-4); }
  .menu-list { overflow-x: auto; padding-bottom: 5px; }
  .footer-grid { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 768px) {
  .benefits-grid { flex-wrap: wrap; justify-content: center; gap: var(--spacing-2); }
  .footer-grid { grid-template-columns: 1fr; }
  .footer-bottom-flex {
    flex-direction: column;
    text-align: center;
  }
}
</style>

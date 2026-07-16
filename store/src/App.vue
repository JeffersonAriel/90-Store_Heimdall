<template>
  <div class="app-layout">
    <!-- Header -->
    <header class="header">
      <div class="container header-top">
        <div class="logo">
          <RouterLink to="/" class="logo-link">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 55" class="logo-svg">
              <text x="0" y="45" font-family="'Oswald', sans-serif" font-size="52" font-weight="700" fill="#ffffff" letter-spacing="-1.5">90</text>
              <text x="88" y="32" font-family="'Oswald', sans-serif" font-size="34" font-weight="700" fill="#e30613">+</text>
            </svg>
          </RouterLink>
        </div>
        
        <div class="search-container">
          <input 
            type="text" 
            placeholder="O que você procura? (ex: chuteira nike)" 
            class="search-input" 
            v-model="searchQuery"
            @keyup.enter="handleSearch"
          />
          <button class="search-btn" @click="handleSearch">🔍</button>
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
          <ul class="menu-list">
            <li v-for="cat in categories" :key="cat.id" class="menu-item" :class="{ 'has-dropdown': cat.children && cat.children.length > 0 }">
              <RouterLink :to="`/catalogo?categoria=${cat.slug}`" class="nav-link">{{ cat.nome }}</RouterLink>
              
              <div class="mega-dropdown" v-if="cat.children && cat.children.length > 0">
                <div class="container mega-container-flex">
                  <div class="dropdown-grid">
                    <template v-for="child in cat.children" :key="child.id">
                      <div v-for="(grandchild, idx) in child.children" :key="grandchild.id" class="dropdown-column" :class="{ 'has-divider': idx > 0 }">
                        <h4>{{ grandchild.nome }}</h4>
                        <ul class="grandchild-list">
                          <li v-for="sub in (isColumnExpanded(grandchild.id) ? (grandchild.children || []) : (grandchild.children || []).slice(0, 7))" :key="sub.id" class="grandchild-item">
                            <RouterLink :to="`/catalogo?categoria=${sub.slug}`" class="grandchild-link-plain">
                              {{ sub.nome }}
                            </RouterLink>
                          </li>
                          <li v-if="(grandchild.children || []).length > 7" class="ver-mais-li">
                            <button type="button" @click.prevent="toggleColumn(grandchild.id)" class="sub-grandchild-ver-mais">
                              {{ isColumnExpanded(grandchild.id) ? 'Ver Menos -' : 'Ver Mais +' }}
                            </button>
                          </li>
                        </ul>
                      </div>
                    </template>
                  </div>

                  <!-- Right Side Promo Banner -->
                  <div class="mega-promo-banner" 
                       v-if="getMegaMenuBannerForCategory(cat.id)"
                       :style="{ aspectRatio: (getMegaMenuBannerForCategory(cat.id).aspect_ratio || '4:3').replace(':', '/') }">
                    <img :src="getMegaMenuBannerForCategory(cat.id).image_path" :alt="cat.nome" class="promo-img" />
                    <div class="promo-content">
                      <h5 v-if="getMegaMenuBannerForCategory(cat.id).title">{{ getMegaMenuBannerForCategory(cat.id).title }}</h5>
                      <p v-if="getMegaMenuBannerForCategory(cat.id).subtitle" style="font-size: 0.8rem; color: rgba(255,255,255,0.7); margin-bottom: var(--spacing-2)">
                        {{ getMegaMenuBannerForCategory(cat.id).subtitle }}
                      </p>
                      <a v-if="getMegaMenuBannerForCategory(cat.id).link_url" :href="getMegaMenuBannerForCategory(cat.id).link_url" class="btn btn-primary btn-sm promo-btn">
                        Confira
                      </a>
                    </div>
                  </div>
                  <div class="mega-promo-banner" v-else-if="getPromoBanner(cat.slug)" style="aspect-ratio: 4/3">
                    <img :src="getPromoBanner(cat.slug).image" :alt="cat.nome" class="promo-img" />
                    <div class="promo-content">
                      <h5>{{ cat.nome }}</h5>
                      <RouterLink :to="`/catalogo?categoria=${cat.slug}`" class="btn btn-primary btn-sm promo-btn">
                        Confira
                      </RouterLink>
                    </div>
                  </div>
                </div>
              </div>
            </li>
          </ul>
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
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 55" class="logo-svg">
              <text x="0" y="45" font-family="'Oswald', sans-serif" font-size="52" font-weight="700" fill="#ffffff" letter-spacing="-1.5">90</text>
              <text x="88" y="32" font-family="'Oswald', sans-serif" font-size="34" font-weight="700" fill="#e30613">+</text>
            </svg>
          </div>
          <p class="footer-desc">A sua loja de artigos esportivos. Alta performance, estilo e as melhores marcas do mundo.</p>
          <div class="social-links">
            <a href="https://www.instagram.com/sou90mais/" target="_blank" rel="noopener noreferrer">Instagram</a>
            <a href="https://wa.me/5511945342493" target="_blank" rel="noopener noreferrer">WhatsApp</a>
            <a href="https://www.youtube.com/@Sou90Mais" target="_blank" rel="noopener noreferrer">YouTube</a>
            <a href="https://www.tiktok.com/@90_mais" target="_blank" rel="noopener noreferrer">TikTok</a>
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
          <div class="security-badges mt-4" style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
            <!-- SiteLock Seal -->
            <a href="https://www.sitelock.com/free-website-scan/?domain=www.90store.com.br" target="_blank" rel="noopener noreferrer" title="SiteLock Secure" style="text-decoration: none;">
              <img src="https://www.sitelock.com/images/sitelock-logo.svg" alt="SiteLock Secure" style="height: 40px; background: transparent; transition: transform 0.2s ease-in-out;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'" />
            </a>
            <!-- E-bit Seal -->
            <a href="https://www.ebit.com.br/" target="_blank" rel="noopener noreferrer" title="E-bit Excelente" style="text-decoration: none;">
              <img src="https://www.agenciaeplus.com.br/wp-content/uploads/2019/09/medalha_diamante_excelente.png" alt="E-bit Excelente" style="height: 48px; background: transparent; transition: transform 0.2s ease-in-out;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'" />
            </a>
            <!-- Reclame Aqui RA1000 Seal -->
            <a href="https://www.reclameaqui.com.br/" target="_blank" rel="noopener noreferrer" title="Reclame Aqui RA1000" style="text-decoration: none;">
              <img src="https://br.adp.com/-/media/adpbr/images/about-us/press-centre/certificado-ra1000.png?rev=1f64aa1c20cd4d9fae1192e159fe0a15&hash=904D041FFD1E3455B62A7087601944EC" alt="Reclame Aqui RA1000" style="height: 48px; background: transparent; transition: transform 0.2s ease-in-out;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'" />
            </a>
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

    <!-- Botão Flutuante do WhatsApp -->
    <a 
      href="https://wa.me/5511945342493?text=Ol%C3%A1%20Time%2090%2B%2C%20gostaria%20de%20obter%20mais%20informa%C3%A7%C3%B5es.%20Vim%20do%20site." 
      target="_blank" 
      rel="noopener noreferrer" 
      class="whatsapp-float"
      title="Fale conosco no WhatsApp"
    >
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="whatsapp-icon">
        <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
      </svg>
    </a>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import axios from 'axios'
import CartDrawer from './components/CartDrawer.vue'
import FavoritesDrawer from './components/FavoritesDrawer.vue'
import { useStore } from './store/main'

const router = useRouter()
const store = useStore()
const cartCount = computed(() => store.cart.reduce((total, item) => total + item.quantidade, 0))
const favoriteCount = computed(() => store.favorites?.length || 0)

const searchQuery = ref('')
const expandedColumns = ref(new Set())

function toggleColumn(columnId) {
  if (expandedColumns.value.has(columnId)) {
    expandedColumns.value.delete(columnId)
  } else {
    expandedColumns.value.add(columnId)
  }
}

function isColumnExpanded(columnId) {
  return expandedColumns.value.has(columnId)
}

const isCartOpen = ref(false)
const isFavoritesOpen = ref(false)
const categories = ref([])
const megaMenuBanners = ref([])
const benefits = ref([])

onMounted(async () => {
  try {
    const res = await axios.get('/api/store-settings')
    categories.value = res.data.categories || []
    megaMenuBanners.value = res.data.megaMenuBanners || []
    benefits.value = res.data.benefits || []
  } catch (error) {
    console.error('Failed to load store settings', error)
  }

  window.addEventListener('open-cart-drawer', () => {
    isCartOpen.value = true
  })
})

function getMegaMenuBannerForCategory(catId) {
  return megaMenuBanners.value.find(b => b.category_id === catId);
}

function getPromoBanner(slug) {
  const banners = {
    'camisetas': {
      image: 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?w=400&auto=format&fit=crop',
      title: 'Mantos de Seleções',
      buttonText: 'Confira'
    },
    'calcados': {
      image: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&auto=format&fit=crop',
      title: 'Chuteiras Elite',
      buttonText: 'Ver Coleção'
    }
  }
  return banners[slug] || {
    image: 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=400&auto=format&fit=crop',
    title: 'Coleção Alta Performance',
    buttonText: 'Aproveite'
  }
}

function handleSearch() {
  if (searchQuery.value.trim()) {
    router.push({
      path: '/catalogo',
      query: { search: searchQuery.value.trim() }
    })
  }
}
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

.logo-svg {
  height: 40px;
  width: auto;
  display: block;
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
  position: relative;
}

.menu-list {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: var(--spacing-8);
  list-style: none;
  margin: 0;
  padding: 0;
}

.menu-item {
  position: static;
  list-style: none;
}

.menu-item > .nav-link {
  display: block;
  padding: var(--spacing-4) 0;
  font-family: var(--font-title);
  font-size: 0.95rem;
  text-transform: uppercase;
  font-weight: 600;
  letter-spacing: 0.5px;
  color: var(--color-white);
}

.menu-item > .nav-link:hover {
  color: var(--color-red);
}

/* Mega Dropdown */
.mega-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  width: 100%;
  background: rgba(18, 18, 18, 0.95);
  backdrop-filter: blur(15px);
  padding: var(--spacing-8) 0;
  border-top: 3px solid var(--color-red);
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6);
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  transform: translateY(10px);
  z-index: var(--z-dropdown);
  max-height: 80vh;
  overflow-y: auto;
}

.menu-item.has-dropdown:hover .mega-dropdown {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.mega-container-flex {
  display: flex;
  justify-content: space-between;
  gap: var(--spacing-8);
}

.dropdown-grid {
  flex: 1;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--spacing-6);
}

.dropdown-column {
  padding-left: var(--spacing-4);
}

.dropdown-column.has-divider {
  border-left: 1px solid rgba(255, 255, 255, 0.08);
}

.dropdown-column h4 {
  color: var(--color-red);
  margin-bottom: var(--spacing-4);
  font-size: 0.8rem;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  font-weight: 800;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  padding-bottom: var(--spacing-2);
}

.grandchild-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.grandchild-item {
  margin-bottom: var(--spacing-4);
}

.grandchild-link {
  display: block;
  color: var(--color-white);
  font-size: 0.95rem;
  font-weight: 600;
  transition: all 0.2s ease;
  text-decoration: none;
}

.grandchild-link:hover {
  color: var(--color-red);
  transform: translateX(4px);
}

.grandchild-link-plain {
  display: block;
  color: var(--color-gray);
  font-size: 0.9rem;
  font-weight: 500;
  padding: 4px 0;
  text-decoration: none;
  transition: all 0.2s ease;
}

.grandchild-link-plain:hover {
  color: var(--color-white);
  padding-left: 4px;
}

.sub-grandchild-list {
  list-style: none;
  padding: 0;
  margin-top: var(--spacing-2);
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-2);
}

.sub-grandchild-link {
  display: inline-block;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 4px 12px;
  border-radius: var(--border-radius-full);
  color: var(--color-gray);
  font-size: 0.775rem;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.2s ease;
}

.sub-grandchild-link:hover {
  background: var(--color-red);
  border-color: var(--color-red);
  color: var(--color-white);
  box-shadow: 0 4px 12px rgba(227, 6, 19, 0.35);
  transform: translateY(-1px);
}

.ver-mais-li {
  margin-top: var(--spacing-2);
}

.sub-grandchild-ver-mais {
  font-size: 0.725rem;
  font-weight: 700;
  color: var(--color-red);
  text-transform: uppercase;
  text-decoration: none;
  transition: all 0.2s ease;
  display: inline-block;
  padding: 2px 0;
  background: none;
  border: none;
  cursor: pointer;
  font-family: inherit;
}

.sub-grandchild-ver-mais:hover {
  color: var(--color-white);
  text-shadow: 0 0 5px var(--color-red);
}

/* Mega Promo Banner */
.mega-promo-banner {
  width: 260px;
  position: relative;
  border-radius: var(--border-radius);
  overflow: hidden;
  display: flex;
  align-items: flex-end;
}

.mega-promo-banner .promo-img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.mega-promo-banner:hover .promo-img {
  transform: scale(1.05);
}

.mega-promo-banner::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.3) 60%, transparent 100%);
  z-index: 1;
}

.promo-content {
  position: relative;
  z-index: 2;
  padding: var(--spacing-4);
  width: 100%;
}

.promo-content h5 {
  font-family: var(--font-title);
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-white);
  margin-bottom: var(--spacing-2);
  text-transform: uppercase;
}

.promo-btn {
  padding: 4px 12px;
  font-size: 0.7rem;
  font-weight: 700;
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

/* WhatsApp Float Button */
.whatsapp-float {
  position: fixed;
  width: 60px;
  height: 60px;
  bottom: 30px;
  right: 30px;
  background-color: #25d366;
  color: #fff;
  border-radius: 50px;
  text-align: center;
  box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  transition: all 0.3s ease;
}

.whatsapp-float:hover {
  background-color: #128c7e;
  transform: scale(1.1);
  box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.4);
  color: #fff;
}

.whatsapp-icon {
  width: 32px;
  height: 32px;
  fill: currentColor;
}

@media (max-width: 768px) {
  .whatsapp-float {
    width: 50px;
    height: 50px;
    bottom: 20px;
    right: 20px;
  }
  .whatsapp-icon {
    width: 26px;
    height: 26px;
  }
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

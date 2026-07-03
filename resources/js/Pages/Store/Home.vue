<template>
  <div :data-theme="theme">
    <!-- Header -->
    <StoreHeader :theme="theme" @toggle-theme="toggleTheme" />

    <!-- Hero Banner -->
    <section class="hero-banner">
      <div class="hero-content">
        <div class="animate-fade-in-up">
          <span class="hero-badge">🔥 Novidades da temporada 2024/25</span>
          <h1 class="hero-title">
            Viva o <span class="hero-highlight">Esporte</span><br>
            com Estilo
          </h1>
          <p class="hero-desc">
            Camisas oficiais, chuteiras, tênis e acessórios dos seus times favoritos.
            Os melhores produtos, os melhores preços.
          </p>
          <div class="hero-actions">
            <a href="/produtos" class="store-btn store-btn-primary">
              Ver Coleção Completa
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
              </svg>
            </a>
            <a href="/produtos?categoria=lancamentos" class="store-btn store-btn-outline">
              Lançamentos
            </a>
          </div>
        </div>
        <div class="hero-image animate-float">
          <img src="/images/hero-product.png" alt="Produto em destaque" class="hero-product-img" />
        </div>
      </div>

      <!-- Stats bar -->
      <div class="hero-stats">
        <div v-for="stat in heroStats" :key="stat.label" class="hero-stat">
          <span class="hero-stat-value">{{ stat.value }}</span>
          <span class="hero-stat-label">{{ stat.label }}</span>
        </div>
      </div>
    </section>

    <!-- Categories strip -->
    <section class="section-container py-12">
      <div class="flex items-center justify-between mb-6">
        <h2 class="section-title">Explorar por Categoria</h2>
        <a href="/produtos" class="text-sm text-orange-400 hover:text-orange-300">Ver todas →</a>
      </div>
      <div class="categories-grid stagger-children">
        <a
          v-for="cat in categories"
          :key="cat.slug"
          :href="`/categoria/${cat.slug}`"
          class="category-card animate-fade-in-up"
        >
          <div class="category-icon">{{ cat.icon }}</div>
          <span class="category-name">{{ cat.name }}</span>
          <span class="category-count">{{ cat.count }} produtos</span>
        </a>
      </div>
    </section>

    <!-- Featured Products -->
    <section class="section-container py-12">
      <div class="flex items-center justify-between mb-6">
        <h2 class="section-title">Mais Vendidos</h2>
        <div class="flex gap-2">
          <button
            v-for="tab in productTabs"
            :key="tab"
            class="tab-btn"
            :class="{ active: activeTab === tab }"
            @click="activeTab = tab"
          >{{ tab }}</button>
        </div>
      </div>

      <div class="products-grid stagger-children">
        <div
          v-for="product in filteredProducts"
          :key="product.id"
          class="product-card animate-fade-in-up"
          @click="router.visit(`/produtos/${product.slug}`)"
        >
          <div class="product-card-image">
            <img :src="product.image" :alt="product.name" loading="lazy" />
            <span v-if="product.badge" class="product-card-badge">{{ product.badge }}</span>
            <button class="product-card-wishlist" @click.stop="toggleWishlist(product.id)">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
              </svg>
            </button>
          </div>
          <div class="product-card-body">
            <p class="product-card-team">{{ product.team }}</p>
            <h3 class="product-card-name">{{ product.name }}</h3>
            <div class="product-card-price">
              <span v-if="product.original_price" class="price-original">R$ {{ product.original_price }}</span>
              <span class="price-current">R$ {{ product.price }}</span>
            </div>
            <p class="price-installments">em até 12x sem juros</p>
            <button class="store-btn store-btn-cart mt-3" @click.stop="addToCart(product)">
              Adicionar ao Carrinho
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Brands -->
    <section class="brands-section py-14">
      <div class="section-container">
        <h2 class="section-title mb-8 text-center" style="display: block;">As Melhores Marcas</h2>
        <div class="brands-row">
          <div v-for="brand in brands" :key="brand.name" class="brand-logo">
            <a :href="`/marca/${brand.slug}`" :title="brand.name">{{ brand.name }}</a>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Banner -->
    <section class="section-container py-12">
      <div class="cta-banner">
        <div class="cta-content">
          <h2 class="cta-title">Frete Grátis acima de R$ 299</h2>
          <p class="cta-desc">Para todo o Brasil. Entregas rápidas em São Paulo pela Entrega Metrô e Uber Moto.</p>
          <a href="/produtos" class="store-btn store-btn-primary">Aproveitar Agora</a>
        </div>
        <div class="cta-deco">🚀</div>
      </div>
    </section>

    <!-- Footer -->
    <StoreFooter />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import StoreHeader from '@/Components/Store/StoreHeader.vue'
import StoreFooter from '@/Components/Store/StoreFooter.vue'

const theme = ref<'dark' | 'light'>('dark')
const toggleTheme = () => { theme.value = theme.value === 'dark' ? 'light' : 'dark' }

const activeTab = ref('Todos')
const productTabs = ['Todos', 'Camisas', 'Chuteiras', 'Tênis', 'Bolas']

const heroStats = [
  { value: '5.000+', label: 'Produtos' },
  { value: '500+', label: 'Times & Seleções' },
  { value: '10+', label: 'Marcas Premium' },
  { value: '4.9★', label: 'Avaliação média' },
]

const categories = [
  { slug: 'camisas', name: 'Camisas', icon: '👕', count: 1240 },
  { slug: 'chuteiras', name: 'Chuteiras', icon: '⚽', count: 486 },
  { slug: 'tenis', name: 'Tênis', icon: '👟', count: 728 },
  { slug: 'bolas', name: 'Bolas', icon: '🏐', count: 184 },
  { slug: 'acessorios', name: 'Acessórios', icon: '🧢', count: 632 },
  { slug: 'goleiro', name: 'Goleiro', icon: '🧤', count: 97 },
]

const products = ref([
  { id: 1, slug: 'camisa-flamengo-home-2024', team: 'Flamengo', name: 'Camisa Flamengo Home 2024/25 Adidas', price: '289,90', original_price: '349,90', image: '/images/products/camisa-flamengo.jpg', badge: '-17%', category: 'Camisas' },
  { id: 2, slug: 'bola-nike-premier-league', team: 'Nike', name: 'Bola Nike Premier League Strike 2024', price: '199,90', original_price: null, image: '/images/products/bola-nike.jpg', badge: 'Novo', category: 'Bolas' },
  { id: 3, slug: 'chuteira-adidas-predator', team: 'Adidas', name: 'Chuteira Adidas Predator 24 Elite FG', price: '899,90', original_price: '1.199,90', image: '/images/products/chuteira-adidas.jpg', badge: '-25%', category: 'Chuteiras' },
  { id: 4, slug: 'camisa-brasil-home-2024', team: 'Seleção Brasileira', name: 'Camisa Brasil Home Nike 2024/25 Torcedor', price: '349,90', original_price: null, image: '/images/products/camisa-brasil.jpg', badge: 'Oficial', category: 'Camisas' },
  { id: 5, slug: 'tenis-puma-king', team: 'Puma', name: 'Tênis Puma King Indoor Futsal', price: '449,90', original_price: '559,90', image: '/images/products/tenis-puma.jpg', badge: '-20%', category: 'Tênis' },
  { id: 6, slug: 'camisa-palmeiras-2024', team: 'Palmeiras', name: 'Camisa Palmeiras Home Puma 2024', price: '279,90', original_price: null, image: '/images/products/camisa-palmeiras.jpg', badge: 'Novo', category: 'Camisas' },
  { id: 7, slug: 'luva-goleiro-nike', team: 'Nike', name: 'Luva Goleiro Nike Vapor Grip3 Promo', price: '399,90', original_price: '499,90', image: '/images/products/luva-nike.jpg', badge: '-20%', category: 'Acessórios' },
  { id: 8, slug: 'chuteira-mizuno', team: 'Mizuno', name: 'Chuteira Mizuno Morelia Neo IV Beta', price: '1.099,90', original_price: null, image: '/images/products/chuteira-mizuno.jpg', badge: 'Premium', category: 'Chuteiras' },
])

const filteredProducts = computed(() => {
  if (activeTab.value === 'Todos') return products.value
  return products.value.filter(p => p.category === activeTab.value)
})

const brands = [
  { name: 'Nike', slug: 'nike' },
  { name: 'Adidas', slug: 'adidas' },
  { name: 'Puma', slug: 'puma' },
  { name: 'Umbro', slug: 'umbro' },
  { name: 'Mizuno', slug: 'mizuno' },
  { name: 'Penalty', slug: 'penalty' },
  { name: 'New Balance', slug: 'new-balance' },
  { name: 'Fila', slug: 'fila' },
]

const addToCart = (product: any) => {
  // TODO: integrar com Pinia cart store
  console.log('Add to cart:', product.id)
}

const toggleWishlist = (id: number) => {
  // TODO: integrar com Pinia wishlist store
  console.log('Toggle wishlist:', id)
}
</script>

<style scoped>
.hero-content {
  max-width: 1280px;
  margin: 0 auto;
  padding: 80px 40px 40px;
  display: flex;
  align-items: center;
  gap: 60px;
}

.hero-badge {
  display: inline-block;
  background: rgba(249, 115, 22, 0.15);
  border: 1px solid rgba(249, 115, 22, 0.3);
  color: #fb923c;
  padding: 6px 14px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 20px;
}

.hero-title {
  font-size: clamp(42px, 5vw, 72px);
  font-weight: 900;
  line-height: 1.1;
  color: #f1f5f9;
  margin-bottom: 20px;
}

.hero-highlight {
  background: linear-gradient(135deg, #f97316, #fb923c, #fcd34d);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.hero-desc {
  font-size: 18px;
  color: #94a3b8;
  line-height: 1.7;
  max-width: 500px;
  margin-bottom: 32px;
}

.hero-actions { display: flex; gap: 16px; flex-wrap: wrap; }

.hero-image {
  flex-shrink: 0;
  width: 360px;
}

.hero-product-img {
  width: 100%;
  height: auto;
  filter: drop-shadow(0 30px 60px rgba(249, 115, 22, 0.3));
}

.hero-stats {
  display: flex;
  gap: 0;
  border-top: 1px solid rgba(249, 115, 22, 0.1);
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 40px;
}

.hero-stat {
  flex: 1;
  padding: 24px 32px;
  border-right: 1px solid rgba(249, 115, 22, 0.1);
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.hero-stat:last-child { border-right: none; }

.hero-stat-value {
  font-size: 28px;
  font-weight: 800;
  color: #f97316;
}

.hero-stat-label {
  font-size: 13px;
  color: #64748b;
}

.section-container {
  max-width: 1280px;
  margin: 0 auto;
  padding-left: 40px;
  padding-right: 40px;
}

.categories-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 16px;
}

.category-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 20px 12px;
  background: rgba(26, 26, 38, 0.9);
  border: 1px solid rgba(249, 115, 22, 0.1);
  border-radius: 16px;
  text-decoration: none;
  transition: all 0.25s ease;
  cursor: pointer;
}

.category-card:hover {
  border-color: rgba(249, 115, 22, 0.4);
  transform: translateY(-4px);
  box-shadow: 0 12px 40px rgba(249, 115, 22, 0.15);
}

.category-icon { font-size: 32px; }
.category-name { font-size: 14px; font-weight: 600; color: #e2e8f0; }
.category-count { font-size: 12px; color: #64748b; }

.products-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

.tab-btn {
  padding: 6px 16px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  border: 1px solid transparent;
  background: transparent;
  color: #64748b;
  transition: all 0.15s;
}

.tab-btn.active, .tab-btn:hover {
  background: rgba(249, 115, 22, 0.1);
  color: #fb923c;
  border-color: rgba(249, 115, 22, 0.3);
}

.brands-section {
  background: rgba(17, 17, 24, 0.5);
  border-top: 1px solid rgba(249, 115, 22, 0.08);
  border-bottom: 1px solid rgba(249, 115, 22, 0.08);
}

.brands-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 48px;
  flex-wrap: wrap;
}

.brand-logo a {
  font-size: 18px;
  font-weight: 800;
  color: #334155;
  text-decoration: none;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  transition: color 0.2s;
}

.brand-logo a:hover { color: #f97316; }

.cta-banner {
  background: linear-gradient(135deg, rgba(249, 115, 22, 0.15) 0%, rgba(234, 88, 12, 0.1) 100%);
  border: 1px solid rgba(249, 115, 22, 0.25);
  border-radius: 24px;
  padding: 48px 56px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  overflow: hidden;
}

.cta-title {
  font-size: 32px;
  font-weight: 800;
  color: #f1f5f9;
  margin-bottom: 10px;
}

.cta-desc {
  font-size: 16px;
  color: #94a3b8;
  margin-bottom: 24px;
  max-width: 480px;
}

.cta-deco {
  font-size: 96px;
  opacity: 0.3;
  transform: rotate(-15deg);
}

@media (max-width: 1024px) {
  .categories-grid { grid-template-columns: repeat(3, 1fr); }
  .products-grid { grid-template-columns: repeat(2, 1fr); }
  .hero-image { display: none; }
}

@media (max-width: 640px) {
  .categories-grid { grid-template-columns: repeat(2, 1fr); }
  .products-grid { grid-template-columns: repeat(1, 1fr); }
  .hero-content { padding: 40px 20px; }
  .section-container { padding-left: 20px; padding-right: 20px; }
}
</style>

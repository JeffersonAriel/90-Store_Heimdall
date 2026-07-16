<template>
  <div class="home-view">
    <!-- Hero Banner (Dynamic Carousel) -->
    <section class="hero-carousel" v-if="banners.length > 0">
      <div v-for="(banner, index) in banners" :key="banner.id" 
           class="hero-slide" 
           :class="{ active: currentBannerIndex === index }"
           :style="{ backgroundImage: `url('${banner.image_path}')` }">
        <div class="hero-content container">
          <span class="hero-badge animate-fade-in" v-if="banner.subtitle">{{ banner.subtitle }}</span>
          <h1 class="title-xl animate-fade-in" style="animation-delay: 0.2s" v-if="banner.title">{{ banner.title }}</h1>
          <RouterLink v-if="banner.link_url" :to="banner.link_url" class="btn btn-primary animate-fade-in" style="animation-delay: 0.4s">
            COMPRAR AGORA
          </RouterLink>
        </div>
      </div>
      
      <!-- Controles do Carrossel -->
      <div class="carousel-dots" v-if="banners.length > 1">
        <span v-for="(banner, index) in banners" :key="'dot-'+index" 
              class="dot" 
              :class="{ active: currentBannerIndex === index }"
              @click="currentBannerIndex = index"></span>
      </div>
    </section>

    <!-- Lançamentos -->
    <section class="section-container container">
      <div class="section-header">
        <h2 class="title-md">VEJA POR LANÇAMENTOS</h2>
        <div class="carousel-nav">
          <button class="nav-btn" @click="scrollSlider('left')">←</button>
          <button class="nav-btn" @click="scrollSlider('right')">→</button>
        </div>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Carregando lançamentos...</p>
      </div>

      <div v-else class="slider-wrapper">
        <div ref="sliderRef" class="products-slider">
          <ProductCard 
            v-for="product in latestProducts" 
            :key="product.id" 
            :product="product" 
            @quick-view="openQuickView"
            class="slider-item"
          />
        </div>
      </div>
    </section>

    <!-- Marcas Parceiras (Carrossel Horizontal) -->
    <section class="brands-section">
      <div class="container">
        <div class="brands-track">
          <RouterLink v-for="brand in brandsList" :key="brand.slug" :to="`/catalogo?marca=${brand.slug}`" :title="`Filtrar por ${brand.name}`">
            <img v-if="!brand.error" :src="brand.logo" :alt="brand.name" @error="handleBrandImageError(brand)" />
            <span v-else class="brand-text-fallback">{{ brand.name }}</span>
          </RouterLink>
        </div>
      </div>
    </section>

    <!-- Mais Vendidos -->
    <section class="section-container container">
      <div class="section-header">
        <h2 class="title-md">MAIS VENDIDOS</h2>
        <RouterLink to="/catalogo?sort=sales" class="view-all-link">Ver Todos</RouterLink>
      </div>

      <div class="grid grid-cols-4 gap-6">
        <ProductCard 
          v-for="product in bestSellers.slice(0, visibleBestSellers)" 
          :key="product.id" 
          :product="product" 
          @quick-view="openQuickView"
        />
      </div>

      <!-- Botão Ver Mais -->
      <div v-if="bestSellers.length > visibleBestSellers" class="load-more-container mt-12 text-center" style="display: flex; justify-content: center; width: 100%;">
        <button class="btn btn-primary" style="padding: var(--spacing-3) var(--spacing-8); font-family: var(--font-title); font-size: 1.1rem;" @click="visibleBestSellers += 12">
          Ver Mais
        </button>
      </div>
    </section>

    <!-- Newsletter -->
    <section class="newsletter-section">
      <div class="container newsletter-grid">
        <div class="newsletter-text">
          <h2 class="title-md">ENTRE PRO NOSSO TIME</h2>
          <p>Receba ofertas exclusivas, dicas de treino e novidades em primeira mão.</p>
        </div>
        <form class="newsletter-form" @submit.prevent="subscribeNewsletter">
          <input type="email" placeholder="Digite seu melhor e-mail" class="input-field" required />
          <button type="submit" class="btn btn-primary">CADASTRAR</button>
        </form>
      </div>
    </section>

    <!-- Quick View Drawer -->
    <ProductQuickView 
      :is-open="!!quickViewProduct" 
      :product="quickViewProduct" 
      @close="closeQuickView"
    />

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useHead } from '@vueuse/head'
import axios from 'axios'
import ProductCard from '../components/ProductCard.vue'
import ProductQuickView from '../components/ProductQuickView.vue'

const latestProducts = ref([])
const bestSellers = ref([])
const banners = ref([])
const loading = ref(true)
const quickViewProduct = ref(null)
const visibleBestSellers = ref(8)
const currentBannerIndex = ref(0)
let bannerInterval = null

const sliderRef = ref(null)

function scrollSlider(direction) {
  if (!sliderRef.value) return
  const scrollAmount = 320 // largura de scroll (card + gap)
  if (direction === 'left') {
    sliderRef.value.scrollLeft -= scrollAmount
  } else {
    sliderRef.value.scrollLeft += scrollAmount
  }
}

const brandsList = ref([
  { name: 'Adidas', slug: 'adidas', logo: './images/brands/adidas.svg', error: false },
  { name: 'Nike', slug: 'nike', logo: './images/brands/nike.svg', error: false },
  { name: 'Puma', slug: 'puma', logo: './images/brands/puma.svg', error: false },
  { name: 'Lacoste', slug: 'lacoste', logo: './images/brands/lacoste.svg', error: false },
  { name: 'New Balance', slug: 'new balance', logo: './images/brands/newbalance.svg', error: false },
  { name: 'Under Armour', slug: 'under armour', logo: './images/brands/underarmour.svg', error: false }
])

function handleBrandImageError(brand) {
  brand.error = true
}

useHead({
  title: '90+ Store | Performance e Estilo',
  meta: [
    { name: 'description', content: 'A sua loja de artigos esportivos. Compre online com entrega rápida e frete grátis.' }
  ]
})

onMounted(async () => {
  await fetchHomeData()
})

async function fetchHomeData() {
  loading.value = true
  try {
    const settingsRes = await axios.get('/api/store-settings')
    banners.value = settingsRes.data.banners || []
    if (banners.value.length > 1) {
      bannerInterval = setInterval(() => {
        currentBannerIndex.value = (currentBannerIndex.value + 1) % banners.value.length
      }, 5000)
    }

    const resLanc = await axios.get('/api/catalog?sort=newest&limit=12')
    latestProducts.value = resLanc.data.produtos || []

    const resBest = await axios.get('/api/catalog?limit=32')
    bestSellers.value = resBest.data.produtos || []
  } catch (err) {
    console.error('Erro ao carregar vitrines', err)
  } finally {
    loading.value = false
  }
}

function openQuickView(product) {
  quickViewProduct.value = product
  document.body.style.overflow = 'hidden'
}

function closeQuickView() {
  quickViewProduct.value = null
  document.body.style.overflow = ''
}

function subscribeNewsletter() {
  alert('E-mail cadastrado com sucesso!')
}
onUnmounted(() => {
  if (bannerInterval) clearInterval(bannerInterval)
})
</script>

<style scoped>
/* Hero Carousel */
.hero-carousel {
  position: relative;
  height: 600px;
  background-color: var(--color-black);
  overflow: hidden;
}

.hero-slide {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-size: cover;
  background-position: center;
  display: flex;
  align-items: center;
  opacity: 0;
  transition: opacity 0.5s ease-in-out;
  z-index: 1;
}

.hero-slide.active {
  opacity: 1;
  z-index: 2;
}

.carousel-dots {
  position: absolute;
  bottom: var(--spacing-6);
  left: 0;
  width: 100%;
  display: flex;
  justify-content: center;
  gap: var(--spacing-2);
  z-index: 3;
}

.dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background-color: rgba(255,255,255,0.3);
  cursor: pointer;
  transition: var(--transition);
}

.dot.active {
  background-color: var(--color-red);
}

.hero-content {
  max-width: 600px;
  z-index: 4;
  text-shadow: 0 2px 12px rgba(0,0,0,0.8);
}

.hero-badge {
  display: inline-block;
  background-color: var(--color-red);
  color: var(--color-white);
  padding: var(--spacing-1) var(--spacing-3);
  font-family: var(--font-title);
  text-transform: uppercase;
  font-weight: 700;
  margin-bottom: var(--spacing-4);
  opacity: 0;
}

.title-xl {
  opacity: 0;
  margin-bottom: var(--spacing-4);
}

.hero-desc {
  font-size: 1.125rem;
  color: var(--color-gray);
  margin-bottom: var(--spacing-8);
  opacity: 0;
}

/* Benefits Strip (Hero bottom) */
.benefits-strip {
  background-color: var(--color-black-light);
  border-bottom: 1px solid var(--color-black-lighter);
  padding: var(--spacing-6) 0;
}

.benefits-grid {
  display: flex;
  justify-content: space-between;
}

.benefit-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-4);
}

.benefit-item .icon {
  font-size: 2rem;
}

.benefit-item strong {
  display: block;
  font-family: var(--font-title);
  font-size: 1.125rem;
  text-transform: uppercase;
}

.benefit-item span {
  color: var(--color-gray);
  font-size: 0.875rem;
}

/* Sections */
.section-container {
  padding: var(--spacing-12) 0;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: var(--spacing-8);
  border-bottom: 2px solid var(--color-black-lighter);
  padding-bottom: var(--spacing-4);
}

.carousel-nav {
  display: flex;
  gap: var(--spacing-2);
}

.nav-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: var(--color-black-light);
  color: var(--color-white);
  border: 1px solid var(--color-black-lighter);
  display: flex;
  align-items: center;
  justify-content: center;
}

.nav-btn:hover {
  background-color: var(--color-red);
  border-color: var(--color-red);
}

.view-all-link {
  color: var(--color-red);
  font-family: var(--font-title);
  text-transform: uppercase;
  font-weight: 700;
}

.view-all-link:hover {
  text-decoration: underline;
}

/* Brands Section */
.brands-section {
  background-color: var(--color-black-light);
  padding: var(--spacing-8) 0;
  overflow: hidden;
}

.brands-track {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--spacing-8);
  flex-wrap: wrap;
}

.brands-track a {
  display: flex;
  align-items: center;
  justify-content: center;
}

.brands-track img {
  height: 35px;
  max-width: 110px;
  object-fit: contain;
  filter: brightness(0) invert(1);
  transition: var(--transition);
}

.brands-track img:hover {
  filter: brightness(0) invert(1) drop-shadow(0 0 6px rgba(255,255,255,0.4));
  transform: translateY(-2px);
}

.brand-text-fallback {
  font-family: var(--font-title);
  font-size: 1.2rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 2px;
  color: var(--color-white);
  opacity: 0.6;
  transition: var(--transition);
}

.brand-text-fallback:hover {
  opacity: 1;
  color: var(--color-red);
}

/* Newsletter */
.newsletter-section {
  background-color: var(--color-red);
  padding: var(--spacing-12) 0;
  color: var(--color-white);
}

.newsletter-grid {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-8);
}

.newsletter-text p {
  color: rgba(255, 255, 255, 0.8);
  margin-top: var(--spacing-2);
}

.newsletter-form {
  display: flex;
  gap: var(--spacing-2);
  width: 100%;
  max-width: 500px;
}

.newsletter-form .input-field {
  flex: 1;
  background-color: var(--color-white);
  color: var(--color-black);
  border: none;
}

.newsletter-form .btn {
  background-color: var(--color-black);
  color: var(--color-white);
}

.newsletter-form .btn:hover {
  background-color: var(--color-black-lighter);
}
@media (max-width: 768px) {
  .benefits-grid { flex-direction: column; align-items: flex-start; gap: var(--spacing-6); }
  .brands-track { overflow-x: auto; padding-bottom: var(--spacing-4); }
  .newsletter-grid { flex-direction: column; text-align: center; }
  .hero-banner { height: 400px; }
  .title-xl { font-size: 2.5rem; }
}

.slider-wrapper {
  position: relative;
  width: 100%;
}

.products-slider {
  display: flex;
  gap: 1.5rem;
  overflow-x: auto;
  scroll-behavior: smooth;
  padding: 0.5rem 0;
  scrollbar-width: none; /* Firefox */
}

.products-slider::-webkit-scrollbar {
  display: none; /* Safari and Chrome */
}

.slider-item {
  flex: 0 0 calc(25% - 1.125rem); /* Mostra 4 itens */
  min-width: 250px;
}

@media (max-width: 1024px) {
  .slider-item {
    flex: 0 0 calc(33.333% - 1rem); /* Mostra 3 itens */
  }
}

@media (max-width: 768px) {
  .slider-item {
    flex: 0 0 calc(50% - 0.75rem); /* Mostra 2 itens */
  }
}

@media (max-width: 480px) {
  .slider-item {
    flex: 0 0 100%; /* Mostra 1 item */
  }
}
</style>

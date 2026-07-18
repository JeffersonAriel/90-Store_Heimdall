<template>
  <div class="product-view container">
    
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Carregando produto...</p>
    </div>

    <div v-else-if="!product" class="empty-state">
      <h2>Produto não encontrado</h2>
      <RouterLink to="/catalogo" class="btn btn-primary mt-4">Voltar ao Catálogo</RouterLink>
    </div>

    <template v-else>
      <!-- Breadcrumb -->
      <div class="breadcrumb">
        <RouterLink to="/">Home</RouterLink> / 
        <RouterLink to="/catalogo">Catálogo</RouterLink> / 
        <span>{{ product.nome }}</span>
      </div>

      <!-- Hero Produto -->
      <div class="product-hero grid grid-cols-2 gap-8">
        
        <!-- Galeria -->
        <div class="product-gallery">
          <div class="main-image-wrapper" style="position: relative;">
            <!-- Seta Esquerda -->
            <button v-if="allPhotos.length > 1" class="gallery-nav-btn left" @click.prevent="navigatePhoto('prev')" title="Foto Anterior">
              ‹
            </button>

            <img :src="mainImage" class="main-image" :alt="product.nome" />

            <!-- Seta Direita -->
            <button v-if="allPhotos.length > 1" class="gallery-nav-btn right" @click.prevent="navigatePhoto('next')" title="Próxima Foto">
              ›
            </button>

            <button class="favorite-btn" title="Favoritar" @click.prevent="toggleFavorite" :class="{ 'is-favorited': isFavorite }">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" :fill="isFavorite ? 'var(--color-red)' : 'none'" :stroke="isFavorite ? 'var(--color-red)' : 'currentColor'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
              </svg>
            </button>
          </div>
          <div class="thumbnails mt-4">
             <img 
               v-for="(foto, idx) in product.fotos || [product.foto_capa]" 
               :key="idx" 
               :src="foto?.url" 
               class="thumbnail"
               :class="{ active: mainImage === foto?.url }"
               @click="mainImage = foto?.url"
             />
          </div>
        </div>

        <!-- Coluna da Direita (Infos) -->
        <div class="product-info-col">
          
          <div class="product-meta">
            <span class="brand">{{ product.marca || 'MARCA ORIGINAL' }}</span>
            <span class="gender-badge" v-if="product.genero">{{ product.genero }}</span>
          </div>
          
          <h1 class="product-title">{{ product.nome }}</h1>
          
          <div class="rating">
            <span class="stars">{{ getStarsString(getRatingAverage(product.id)) }}</span>
            <span class="reviews-count">({{ getRatingCount(product.id) }} avaliações)</span>
          </div>

          <div class="price-section mt-6">
            <template v-if="product.tem_desconto">
              <span class="price-old">R$ {{ formatMoney(displayedOldPrice) }}</span>
              <div class="price-new">
                R$ {{ formatMoney(displayedPrice) }}
                <span class="discount-badge">-{{ calculateDiscountPercent() }}%</span>
              </div>
            </template>
            <template v-else>
              <div class="price-new">R$ {{ formatMoney(displayedPrice) }}</div>
            </template>
          </div>

          <!-- Seletor de Variações -->
          <div class="variations-section mt-6" v-if="!product.esgotado && (availableColors.length > 0 || availableSizes.length > 0)">
            <div class="variation-group" v-if="availableColors.length > 0">
              <label>Cor: <strong>{{ selectedColor || 'Selecione' }}</strong></label>
              <div class="color-options">
                <button 
                  v-for="color in availableColors" 
                  :key="color"
                  class="color-btn" 
                  :class="{ active: selectedColor === color }" 
                  :style="{ backgroundColor: getColorCode(color) }" 
                  @click="selectedColor = color; selectedSize = ''"
                  :title="color"
                ></button>
              </div>
            </div>
            
            <div class="variation-group mt-4" v-if="sizesToShow.length > 0">
              <div class="size-header">
                <label>Tamanho: <strong>{{ selectedSize || 'Selecione' }}</strong></label>
                <button type="button" class="size-guide-btn" style="background: none; border: none; cursor: pointer; text-decoration: underline;" @click.prevent="isSizebayOpen = true">
                  Tabela de Medidas
                </button>
              </div>
              <div class="size-options">
                <button 
                  v-for="size in sizesToShow" 
                  :key="size"
                  class="size-btn" 
                  :class="{ 
                    active: selectedSize === size,
                    disabled: !availableSizes.includes(size)
                  }" 
                  :disabled="!availableSizes.includes(size)"
                  @click="selectedSize = size"
                >{{ size }}</button>
              </div>
            </div>
          </div>

          <div v-if="!product.esgotado && stockWarning" class="stock-warning mt-4">
            ⚠️ Últimas unidades em estoque!
          </div>

          <!-- Personalização de Camisa -->
          <div v-if="!product.esgotado && (product.permite_personalizacao || isClothingProduct)" class="personalization-container mt-6">
            <h3 class="personalization-title">Deseja personalizar seu manto?</h3>
            <p class="personalization-subtitle">Adicione nome e número oficial nas costas por apenas R$ 70,00</p>
            
            <div class="personalization-options-pills mt-3">
              <button 
                type="button" 
                class="pill-btn" 
                :class="{ active: !customizationEnabled }" 
                @click="customizationEnabled = false"
              >
                ❌ Não, sem personalização
              </button>
              <button 
                type="button" 
                class="pill-btn" 
                :class="{ active: customizationEnabled }" 
                @click="customizationEnabled = true"
              >
                👕 Sim, personalizar (+ R$ 70,00)
              </button>
            </div>
            
            <div v-if="customizationEnabled" class="personalization-fields mt-4 animate-fade-in">
              <div class="field-row">
                <div class="field-item">
                  <label>Nome na Camisa (Max. 10 letras)</label>
                  <input 
                    type="text" 
                    :value="customizationName" 
                    @input="handleNameInput" 
                    placeholder="Ex: SILVA" 
                    class="input-field" 
                  />
                </div>
                <div class="field-item">
                  <label>Número (Max. 2 dígitos)</label>
                  <input 
                    type="text" 
                    :value="customizationNumber" 
                    @input="handleNumberInput" 
                    placeholder="10" 
                    class="input-field" 
                  />
                </div>
              </div>
              <span class="personalization-disclaimer">
                * Camisas personalizadas não podem ser devolvidas ou trocadas.
              </span>
            </div>
          </div>

          <!-- Ações padrão -->
          <div class="action-section mt-6" v-if="!product.esgotado">
            <div class="qty-selector">
              <button @click="quantity > 1 ? quantity-- : 1">-</button>
              <input type="number" v-model="quantity" min="1" readonly />
              <button @click="quantity++">+</button>
            </div>
            <button class="btn btn-primary add-to-cart-btn" @click="addToCart">
              🛒 ADICIONAR AO CARRINHO
            </button>
          </div>

          <!-- Form Me Avise se Esgotado -->
          <div class="notify-me-section mt-6 p-6 rounded-lg" style="background: var(--color-black-light); border: 1px solid var(--color-black-lighter);" v-else>
            <h4 class="font-bold text-lg mb-2 text-white" style="font-family: var(--font-title); letter-spacing: 1px; color: var(--color-red, #ef4444) !important;">PRODUTO EM BREVE</h4>
            <p class="text-gray text-sm mb-4">Deixe seu nome e e-mail abaixo. Avisaremos você assim que este item estiver disponível em estoque!</p>
            
            <form @submit.prevent="submitNotifyMe" class="flex flex-col gap-3">
              <input type="text" v-model="notifyForm.nome" placeholder="Seu nome completo" class="input-field" required style="width: 100%;" />
              <input type="email" v-model="notifyForm.email" placeholder="Seu melhor e-mail" class="input-field" required style="width: 100%;" />
              <button type="submit" class="btn btn-primary" :disabled="notifyLoading" style="width: 100%;">
                {{ notifyLoading ? 'Enviando...' : 'ME AVISE QUANDO CHEGAR' }}
              </button>
            </form>
            <p v-if="notifySuccess" class="mt-3 text-sm font-bold" style="color: var(--color-red);">✓ {{ notifySuccess }}</p>
          </div>

          <!-- Frete -->
          <div class="shipping-section mt-8">
            <label>Calcule o Frete e Prazo</label>
            <div class="shipping-input-group">
              <input type="text" v-model="cep" placeholder="00000-000" class="input-field" maxlength="9" />
              <button class="btn btn-outline" @click="calculateShipping" :disabled="shippingLoading">
                {{ shippingLoading ? 'Calculando...' : 'OK' }}
              </button>
            </div>
            <div v-if="shippingResult" class="shipping-result mt-4">
              <div v-if="shippingOptions.length > 0">
                <div v-for="option in shippingOptions" :key="option.servico" class="shipping-option">
                  <span>{{ option.servico }}</span>
                  <strong v-if="option.a_combinar">A combinar</strong>
                  <strong v-else>
                    {{ parseFloat(option.valor) === 0 ? 'Grátis' : 'R$ ' + formatMoney(option.valor) }}
                    <small>({{ option.prazo_dias }} {{ option.prazo_dias === 1 ? 'dia útil' : 'dias úteis' }})</small>
                  </strong>
                </div>
              </div>
              <div v-else>
                <p class="text-sm text-gray">Nenhuma opção de frete disponível para este CEP.</p>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Detalhes e Infos -->
      <div class="product-details mt-12">
        <h3 class="section-title">DESCRIÇÃO DO PRODUTO</h3>
        <div class="desc-content">
          <p>{{ product.descricao || 'Este produto não possui uma descrição detalhada.' }}</p>
        </div>
      </div>

      <!-- Avaliações -->
      <div class="product-reviews mt-12">
        <h3 class="section-title">AVALIAÇÕES ({{ getRatingCount(product.id) }})</h3>
        <div v-for="review in getProductReviews(product.id)" :key="review.id" class="review-item">
          <div class="review-header">
            <span class="stars">{{ review.estrelas }}</span>
            <strong>{{ review.autor }}</strong>
            <span class="review-date">{{ review.data }}</span>
          </div>
          <p class="review-text">{{ review.texto }}</p>
        </div>
      </div>

      <!-- Relacionados -->
      <div class="related-products mt-12 mb-12">
        <h3 class="section-title">QUEM VIU, COMPROU TAMBÉM</h3>
        <div class="grid grid-cols-4 gap-6 mt-6">
          <ProductCard 
            v-for="rel in relatedProducts" 
            :key="rel.id" 
            :product="rel" 
            @quick-view="$emit('open-drawer')"
          />
        </div>
      </div>
      <!-- Guia de Tamanhos / Tabela de Medidas Modal -->
      <div v-if="isSizebayOpen" class="sizebay-modal-overlay" @click.self="isSizebayOpen = false">
        <div class="sizebay-modal-content">
          <!-- Tabbed Header -->
          <div class="sizebay-modal-header-tabs">
            <div class="tabs-group">
              <button 
                type="button"
                class="modal-tab-btn" 
                :class="{ active: activeModalTab === 'vfr' }" 
                @click="activeModalTab = 'vfr'"
              >
                Provador Virtual 🤖
              </button>
              <button 
                type="button"
                class="modal-tab-btn" 
                :class="{ active: activeModalTab === 'chart' }" 
                @click="activeModalTab = 'chart'"
              >
                Tabela de Medidas 📏
              </button>
            </div>
            <button class="close-modal-btn" @click="isSizebayOpen = false" title="Fechar">×</button>
          </div>
          
          <div class="size-guide-content">
            <!-- ABA 1: PROVADOR VIRTUAL INTERATIVO -->
            <div v-if="activeModalTab === 'vfr'" class="vfr-tab-wrapper">
              <div v-if="!vfrResult" class="vfr-form-container">
                <p class="guide-intro">Informe seus dados para estimarmos o seu tamanho ideal:</p>
                
                <!-- Formulário Camisas -->
                <div v-if="isClothingProduct" class="vfr-form">
                  <div class="vfr-field-group">
                    <label>Altura (cm)</label>
                    <input type="number" v-model="vfrHeight" placeholder="Ex: 175" class="input-field" />
                  </div>
                  <div class="vfr-field-group">
                    <label>Peso (kg)</label>
                    <input type="number" v-model="vfrWeight" placeholder="Ex: 75" class="input-field" />
                  </div>
                  <div class="vfr-field-group">
                    <label>Idade (anos)</label>
                    <input type="number" v-model="vfrAge" placeholder="Ex: 28" class="input-field" />
                  </div>
                  <div class="vfr-field-group full-width">
                    <label>Como você prefere o caimento das suas roupas?</label>
                    <div class="fit-options">
                      <label class="fit-option">
                        <input type="radio" v-model="vfrFit" value="justo" /> Justo
                      </label>
                      <label class="fit-option">
                        <input type="radio" v-model="vfrFit" value="regular" /> Regular
                      </label>
                      <label class="fit-option">
                        <input type="radio" v-model="vfrFit" value="folgado" /> Folgado
                      </label>
                    </div>
                  </div>
                </div>

                <!-- Formulário Calçados -->
                <div v-else class="vfr-form">
                  <div class="vfr-field-group full-width">
                    <label>Comprimento do pé (cm)</label>
                    <input type="number" step="0.1" v-model="vfrFootLength" placeholder="Ex: 26.5" class="input-field" />
                    <small class="field-hint">Dica: Desenhe o contorno do seu pé em um papel e meça do calcanhar ao dedão com uma régua.</small>
                  </div>
                </div>

                <button class="btn btn-primary w-full mt-6" @click="calculateRecommendedSize">
                  Calcular Meu Tamanho
                </button>
              </div>

              <!-- Tela de Resultado -->
              <div v-else class="vfr-result-container text-center">
                <div class="vfr-result-icon">👕</div>
                <h3 class="title-md">Tamanho Recomendado:</h3>
                <div class="recommended-size-badge">{{ vfrResult }}</div>
                <p class="vfr-result-desc">Com base nas suas respostas, o tamanho mais indicado para você é o <strong>{{ vfrResult }}</strong>.</p>
                <div class="vfr-result-actions mt-8">
                  <button class="btn btn-primary" @click="isSizebayOpen = false; selectedSize = vfrResult">
                    Escolher Tamanho {{ vfrResult }}
                  </button>
                  <button class="btn btn-outline" style="margin-left: 1rem;" @click="resetVfr">
                    Recomeçar
                  </button>
                </div>
              </div>
            </div>

            <!-- ABA 2: TABELA DE MEDIDAS ESTÁTICA -->
            <div v-else class="chart-tab-wrapper">
              <!-- Camisetas -->
              <div v-if="isClothingProduct" class="table-wrapper">
                <p class="guide-intro">Compare as medidas abaixo com uma camiseta de seu uso para encontrar o tamanho ideal:</p>
                <table class="size-table">
                  <thead>
                    <tr>
                      <th>Tamanho</th>
                      <th>Largura (A)</th>
                      <th>Comprimento (B)</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td><strong>PP</strong></td>
                      <td>48 cm</td>
                      <td>68 cm</td>
                    </tr>
                    <tr>
                      <td><strong>P</strong></td>
                      <td>50 cm</td>
                      <td>70 cm</td>
                    </tr>
                    <tr>
                      <td><strong>M</strong></td>
                      <td>52 cm</td>
                      <td>72 cm</td>
                    </tr>
                    <tr>
                      <td><strong>G</strong></td>
                      <td>54 cm</td>
                      <td>74 cm</td>
                    </tr>
                    <tr>
                      <td><strong>GG</strong></td>
                      <td>56 cm</td>
                      <td>76 cm</td>
                    </tr>
                    <tr>
                      <td><strong>XG / GGG</strong></td>
                      <td>58 cm</td>
                      <td>78 cm</td>
                    </tr>
                  </tbody>
                </table>
                <div class="measurement-instructions">
                  <p><strong>Largura (A):</strong> Medida horizontal sob a costura das mangas (peito).</p>
                  <p><strong>Comprimento (B):</strong> Medida vertical do ponto mais alto do ombro até a barra final da peça.</p>
                  <p class="text-xs text-gray-500 mt-2">* As medidas podem variar em até 2cm para mais ou para menos devido ao processo de fabricação.</p>
                </div>
              </div>

              <!-- Calçados / Tênis -->
              <div v-else class="table-wrapper">
                <p class="guide-intro">Meça a palmilha de um calçado confortável que você já possui:</p>
                <table class="size-table">
                  <thead>
                    <tr>
                      <th>Tamanho</th>
                      <th>Comprimento da Palmilha</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td><strong>37</strong></td>
                      <td>24,5 cm</td>
                    </tr>
                    <tr>
                      <td><strong>38</strong></td>
                      <td>25,0 cm</td>
                    </tr>
                    <tr>
                      <td><strong>39</strong></td>
                      <td>25,5 cm</td>
                    </tr>
                    <tr>
                      <td><strong>40</strong></td>
                      <td>26,5 cm</td>
                    </tr>
                    <tr>
                      <td><strong>41</strong></td>
                      <td>27,5 cm</td>
                    </tr>
                    <tr>
                      <td><strong>42</strong></td>
                      <td>28,0 cm</td>
                    </tr>
                    <tr>
                      <td><strong>43</strong></td>
                      <td>29,0 cm</td>
                    </tr>
                    <tr>
                      <td><strong>44</strong></td>
                      <td>30,0 cm</td>
                    </tr>
                  </tbody>
                </table>
                <div class="measurement-instructions">
                  <p><strong>Como medir a palmilha:</strong> Remova a palmilha de um calçado que você já usa frequentemente e meça com uma régua ou fita métrica do calcanhar até a ponta mais proeminente.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </template>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useHead } from '@vueuse/head'
import axios from 'axios'
import ProductCard from '../components/ProductCard.vue'
import { useStore } from '../store/main'
import { getRatingCount, getRatingAverage, getStarsString, getProductReviews } from '../utils/rating'

const store = useStore()
const isFavorite = computed(() => store.favorites?.some(item => item.id === product.value?.id))

function toggleFavorite() {
  if (product.value) {
    store.toggleFavorite(product.value)
  }
}

const route = useRoute()

const product = ref(null)
const relatedProducts = ref([])
const loading = ref(true)

const mainImage = ref('')
const selectedColor = ref('')
const selectedSize = ref('')
const quantity = ref(1)
const stockWarning = ref(true)

// Sizebay Virtual Fitting Room state
const isSizebayOpen = ref(false)
const isClothingProduct = computed(() => {
  if (!product.value || !product.value.variacoes) return true
  const actualSizes = product.value.variacoes.map(v => v.tamanho).filter(Boolean)
  return actualSizes.some(s => ['PP', 'P', 'M', 'G', 'GG', 'GGG', 'XG', 'XXG', 'G1', 'G2', 'G3'].includes(s.toUpperCase()))
})

// Custom Interactive Virtual Size Assistant
const activeModalTab = ref('vfr')
const vfrHeight = ref('')
const vfrWeight = ref('')
const vfrAge = ref('')
const vfrFit = ref('regular')
const vfrResult = ref('')
const vfrFootLength = ref('')

function calculateRecommendedSize() {
  if (isClothingProduct.value) {
    if (!vfrHeight.value || !vfrWeight.value) {
      alert('Por favor, informe seu peso e sua altura.')
      return
    }
    const w = parseFloat(vfrWeight.value)
    const h = parseFloat(vfrHeight.value)
    let size = 'M'
    
    if (w < 55) size = 'PP'
    else if (w < 65) size = 'P'
    else if (w < 78) size = 'M'
    else if (w < 90) size = 'G'
    else if (w < 102) size = 'GG'
    else size = 'XG'

    if (h > 185 && ['PP', 'P', 'M'].includes(size)) {
      if (size === 'PP') size = 'P'
      else if (size === 'P') size = 'M'
      else if (size === 'M') size = 'G'
    }

    if (vfrFit.value === 'justo') {
      if (size === 'XG') size = 'GG'
      else if (size === 'GG') size = 'G'
      else if (size === 'G') size = 'M'
      else if (size === 'M') size = 'P'
      else if (size === 'P') size = 'PP'
    } else if (vfrFit.value === 'folgado') {
      if (size === 'PP') size = 'P'
      else if (size === 'P') size = 'M'
      else if (size === 'M') size = 'G'
      else if (size === 'G') size = 'GG'
      else if (size === 'GG') size = 'XG'
    }
    vfrResult.value = size
  } else {
    if (!vfrFootLength.value) {
      alert('Por favor, informe o comprimento do pé em centímetros.')
      return
    }
    const len = parseFloat(vfrFootLength.value)
    let size = '40'
    if (len <= 24.5) size = '37'
    else if (len <= 25.0) size = '38'
    else if (len <= 25.5) size = '39'
    else if (len <= 26.5) size = '40'
    else if (len <= 27.5) size = '41'
    else if (len <= 28.0) size = '42'
    else if (len <= 29.0) size = '43'
    else size = '44'
    vfrResult.value = size
  }
}

function resetVfr() {
  vfrHeight.value = ''
  vfrWeight.value = ''
  vfrAge.value = ''
  vfrFit.value = 'regular'
  vfrResult.value = ''
  vfrFootLength.value = ''
}

// Jersey Personalization State & Logic
const customizationEnabled = ref(false)
const customizationName = ref('')
const customizationNumber = ref('')

const selectedVariation = computed(() => {
  if (!product.value || !product.value.variacoes) return null
  const hasColors = availableColors.value.length > 0
  return product.value.variacoes.find(v => {
    const matchSize = v.tamanho === selectedSize.value
    const matchColor = !hasColors || v.cor === selectedColor.value
    return matchSize && matchColor
  }) || null
})

const displayedPrice = computed(() => {
  if (!product.value) return 0
  let basePrice = product.value.tem_desconto ? product.value.preco_desconto : product.value.preco_venda
  if (selectedVariation.value) {
    basePrice = parseFloat(basePrice) + parseFloat(selectedVariation.value.preco_adicional || 0)
  }
  if (customizationEnabled.value) {
    basePrice = parseFloat(basePrice) + 70.0
  }
  return basePrice
})

const displayedOldPrice = computed(() => {
  if (!product.value) return 0
  let basePrice = product.value.preco_venda
  if (selectedVariation.value) {
    basePrice = parseFloat(basePrice) + parseFloat(selectedVariation.value.preco_adicional || 0)
  }
  if (customizationEnabled.value) {
    basePrice = parseFloat(basePrice) + 70.0
  }
  return basePrice
})

function handleNameInput(event) {
  let val = event.target.value.replace(/[^A-Za-zÀ-ÖØ-öø-ÿ\s]/g, '')
  if (val.length > 10) val = val.substring(0, 10)
  customizationName.value = val.toUpperCase()
}

function handleNumberInput(event) {
  let val = event.target.value.replace(/\D/g, '')
  if (val.length > 2) val = val.substring(0, 2)
  customizationNumber.value = val
}

// Formulário "Me Avise Quando Chegar"
const notifyForm = reactive({ nome: '', email: '' })
const notifyLoading = ref(false)
const notifySuccess = ref('')

async function submitNotifyMe() {
  if (!product.value) return
  notifyLoading.value = true
  notifySuccess.value = ''
  try {
    const res = await axios.post(`/api/products/${product.value.id}/notify`, notifyForm)
    notifySuccess.value = res.data.message || 'Solicitação enviada com sucesso!'
    notifyForm.nome = ''
    notifyForm.email = ''
  } catch (err) {
    alert(err.response?.data?.message || 'Erro ao enviar solicitação.')
  } finally {
    notifyLoading.value = false
  }
}

const allPhotos = computed(() => {
  if (!product.value) return [];
  const list = product.value.fotos || [];
  if (list.length === 0 && product.value.foto_capa) {
    return [product.value.foto_capa];
  }
  return list;
});

function navigatePhoto(direction) {
  const photos = allPhotos.value;
  if (photos.length <= 1) return;
  
  const currentIndex = photos.findIndex(p => p.url === mainImage.value);
  let nextIndex = currentIndex;
  
  if (direction === 'next') {
    nextIndex = (currentIndex + 1) % photos.length;
  } else {
    nextIndex = (currentIndex - 1 + photos.length) % photos.length;
  }
  
  if (photos[nextIndex] && photos[nextIndex].url) {
    mainImage.value = photos[nextIndex].url;
  }
}

const availableColors = computed(() => {
  if (!product.value || !product.value.variacoes) return [];
  const cores = product.value.variacoes.map(v => v.cor).filter(Boolean);
  return [...new Set(cores)];
});

const availableSizes = computed(() => {
  if (!product.value || !product.value.variacoes) return [];
  let vars = product.value.variacoes;
  if (selectedColor.value) {
    vars = vars.filter(v => v.cor === selectedColor.value);
  }
  const sizes = vars.map(v => v.tamanho).filter(Boolean);
  return [...new Set(sizes)];
});

const sizesToShow = computed(() => {
  if (!product.value || !product.value.variacoes) return [];
  const actualSizes = product.value.variacoes.map(v => v.tamanho).filter(Boolean);
  const isClothing = actualSizes.some(s => ['PP', 'P', 'M', 'G', 'GG', 'XGG', 'XG', 'XXG', 'GGG', 'G1', 'G2', 'G3'].includes(s.toUpperCase()));
  if (isClothing) {
    return ['PP', 'P', 'M', 'G', 'GG', 'XGG', 'G1', 'G2', 'G3'];
  }
  const isShoes = actualSizes.some(s => ['34','35','36','37','38','39','40','41','42','43','44','45'].includes(s));
  if (isShoes) {
    return ['37', '38', '39', '40', '41', '42', '43', '44'];
  }
  return [...new Set(actualSizes)];
});

function getColorCode(colorName) {
  const map = {
    'Preto': '#000',
    'Branco': '#fff',
    'Vermelho': '#e30613',
    'Amarelo': '#ffd700',
    'Azul': '#0000ff',
    'Verde': '#008000',
    'Cinza': '#808080',
    'Laranja': '#ffa500',
    'Rosa': '#ffc0cb',
    'Roxo': '#800080',
    'Marrom': '#8b4513'
  };
  return map[colorName] || '#ccc';
}

const cep = ref('')
const shippingResult = ref(false)
const shippingOptions = ref([])
const shippingLoading = ref(false)

useHead({
  title: () => `${product.value?.nome || 'Produto'} | 90+ Store`,
  meta: [
    { name: 'description', content: () => product.value?.descricao_curta || 'Compre agora na 90+ Store.' }
  ]
})

onMounted(() => {
  fetchProduct()
})

watch(() => route.params.slug, () => {
  fetchProduct()
})

async function fetchProduct() {
  loading.value = true
  try {
    const slug = route.params.slug
    const res = await axios.get(`/api/products/${slug}`)
    if (res.data.success && res.data.produto) {
      product.value = res.data.produto
      mainImage.value = product.value.foto_capa?.url || product.value.fotos?.[0]?.url || 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500'
      
      // Seleciona automaticamente se houver apenas 1 cor disponível
      if (availableColors.value.length === 1) {
        selectedColor.value = availableColors.value[0]
      }
      // Seleciona automaticamente se houver apenas 1 tamanho disponível
      if (availableSizes.value.length === 1) {
        selectedSize.value = availableSizes.value[0]
      }
      
      fetchRelated()
    } else {
      product.value = null
    }
  } catch (err) {
    console.error('Erro ao buscar produto', err)
    product.value = null
  } finally {
    loading.value = false
  }
}

async function fetchRelated() {
  try {
    const res = await axios.get(`/api/catalog?limit=4`)
    relatedProducts.value = res.data.produtos || []
  } catch (e) { }
}

function calculateDiscountPercent() {
  if (!product.value || !product.value.tem_desconto) return 0
  const off = ((product.value.preco_venda - product.value.preco_desconto) / product.value.preco_venda) * 100
  return Math.round(off)
}

function addToCart() {
  const hasColors = availableColors.value.length > 0
  if (!selectedSize.value || (hasColors && !selectedColor.value)) {
    alert(hasColors ? 'Selecione cor e tamanho para continuar.' : 'Selecione o tamanho para continuar.')
    return
  }

  const variation = product.value.variacoes.find(v => {
    const matchSize = v.tamanho === selectedSize.value
    const matchColor = !hasColors || v.cor === selectedColor.value
    return matchSize && matchColor
  })
  
  if (!variation) {
    alert('Esta combinação de cor e tamanho está indisponível.')
    return
  }

  // Personalization validation
  let customData = null
  if (customizationEnabled.value) {
    if (!customizationName.value.trim() || !customizationNumber.value.trim()) {
      alert('Por favor, informe o Nome e o Número para personalizar sua camisa.')
      return
    }
    customData = {
      personalizado: true,
      nome: customizationName.value.trim().toUpperCase(),
      numero: customizationNumber.value.trim(),
      preco_adicional: 70.0
    }
  }

  store.addToCart(product.value, variation, quantity.value, customData)
  window.dispatchEvent(new CustomEvent('open-cart-drawer'))
}

async function calculateShipping() {
  const cleanCep = cep.value.replace(/\D/g, '')
  if (cleanCep.length < 8) {
    alert('Digite um CEP válido com 8 dígitos.')
    return
  }

  shippingLoading.value = true
  shippingResult.value = false
  shippingOptions.value = []

  try {
    const res = await axios.post('/api/shipping/quote', {
      cep: cleanCep,
      peso_total: parseFloat(product.value.peso_kg || 0.3) * quantity.value
    })
    
    if (res.data.success || Array.isArray(res.data.opcoes)) {
      shippingOptions.value = res.data.opcoes || []
      shippingResult.value = true
    } else {
      alert('Não foi possível calcular o frete para este CEP.')
    }
  } catch (error) {
    console.error('Erro ao calcular frete:', error)
    alert('Erro ao calcular o frete. Tente novamente.')
  } finally {
    shippingLoading.value = false
  }
}

function formatMoney(val) {
  if (!val) return '0,00'
  return parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.product-view {
  padding: var(--spacing-6) var(--spacing-4);
}

.breadcrumb {
  font-size: 0.875rem;
  color: var(--color-gray);
  margin-bottom: var(--spacing-6);
}

.breadcrumb a {
  color: var(--color-gray);
}
.breadcrumb a:hover {
  color: var(--color-white);
}

/* Galeria */
.product-gallery {
  align-self: start;
  max-width: 500px;
  width: 100%;
}

.main-image-wrapper {
  background-color: var(--color-black-lighter);
  border-radius: var(--border-radius);
  overflow: hidden;
  aspect-ratio: 1/1;
}

.main-image {
  width: 100%;
  height: 100%;
  object-fit: contain;
  transition: transform 0.3s;
}

.main-image:hover {
  transform: scale(1.1); /* Simple zoom */
}

.thumbnails {
  display: flex;
  flex-wrap: nowrap;
  overflow-x: auto;
  gap: var(--spacing-3);
  scrollbar-width: none;
}

.thumbnails::-webkit-scrollbar {
  display: none;
}

.thumbnail {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: var(--border-radius-sm);
  cursor: pointer;
  border: 2px solid transparent;
  transition: var(--transition);
  flex-shrink: 0;
}

.thumbnail:hover, .thumbnail.active {
  border-color: var(--color-red);
}

/* Buy Box */
.product-info-col {
  display: flex;
  flex-direction: column;
}

.brand {
  font-size: 0.75rem;
  letter-spacing: 0.1em;
  color: var(--store-text-muted);
  text-transform: uppercase;
}

.gender-badge {
  background-color: var(--store-primary);
  color: white;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 0.65rem;
  font-weight: bold;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.product-title {
  font-family: var(--font-title);
  font-size: 2.5rem;
  line-height: 1.1;
  margin: var(--spacing-2) 0;
  color: var(--color-white);
}

.rating-box {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
}

.stars {
  color: #fbbf24;
  font-size: 1rem;
}

.rating-text {
  color: var(--color-gray);
  font-size: 0.875rem;
}

.price-old {
  color: var(--color-gray);
  text-decoration: line-through;
  font-size: 1.125rem;
}

.price-new {
  font-family: var(--font-title);
  font-size: 2.5rem;
  color: var(--color-red);
  display: flex;
  align-items: center;
  gap: var(--spacing-4);
}

.discount-badge {
  background-color: var(--color-red);
  color: var(--color-white);
  font-size: 1rem;
  padding: var(--spacing-1) var(--spacing-2);
  border-radius: var(--border-radius-sm);
}

.installments {
  color: var(--color-gray);
  font-size: 1rem;
}

.variation-group label {
  display: block;
  font-size: 0.875rem;
  color: var(--color-gray);
  margin-bottom: var(--spacing-2);
}

.variation-group strong {
  color: var(--color-white);
}

.color-options {
  display: flex;
  gap: var(--spacing-3);
}

.color-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid transparent;
  cursor: pointer;
  box-shadow: 0 0 0 1px var(--color-gray-dark);
}

.color-btn:hover, .color-btn.active {
  box-shadow: 0 0 0 2px var(--color-white);
}

.size-header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
}

.size-guide-btn {
  color: var(--color-gray);
  text-decoration: underline;
  font-size: 0.875rem;
}

.size-guide-btn:hover {
  color: var(--color-white);
}

.size-options {
  display: flex;
  gap: var(--spacing-2);
  flex-wrap: wrap;
}

.size-btn {
  min-width: 50px;
  height: 50px;
  border: 1px solid var(--color-gray-dark);
  background: transparent;
  color: var(--color-white);
  border-radius: var(--border-radius-sm);
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
}

.size-btn:hover {
  border-color: var(--color-white);
}

.size-btn.active {
  background-color: var(--color-white);
  color: var(--color-black);
  border-color: var(--color-white);
}

.size-btn.disabled {
  opacity: 0.4;
  cursor: not-allowed;
  background: linear-gradient(to top right, transparent calc(50% - 1px), var(--color-gray) 50%, transparent calc(50% + 1px)) !important;
  border-color: rgba(255, 255, 255, 0.15) !important;
  color: var(--color-gray) !important;
}

.size-btn.disabled:hover {
  border-color: rgba(255, 255, 255, 0.15) !important;
  color: var(--color-gray) !important;
}

.stock-warning {
  color: #f59e0b; /* yellow */
  font-size: 0.875rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
}

.action-section {
  display: flex;
  gap: var(--spacing-4);
}

.qty-selector {
  display: flex;
  align-items: center;
  border: 1px solid var(--color-black-lighter);
  border-radius: var(--border-radius-sm);
  background-color: var(--color-black-light);
}

.qty-selector button {
  width: 40px;
  height: 50px;
  color: var(--color-white);
  font-size: 1.25rem;
}

.qty-selector button:hover {
  color: var(--color-red);
}

.qty-selector input {
  width: 40px;
  height: 50px;
  background: transparent;
  border: none;
  color: var(--color-white);
  text-align: center;
  font-weight: 600;
  padding: 0;
  line-height: 50px;
  -moz-appearance: textfield;
}

.qty-selector input::-webkit-outer-spin-button,
.qty-selector input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.qty-selector input:focus {
  outline: none;
}

.add-to-cart-btn {
  flex: 1;
  height: 50px;
  font-size: 1.125rem;
}

.shipping-section {
  background-color: var(--color-black-light);
  padding: var(--spacing-4);
  border-radius: var(--border-radius-sm);
}

.shipping-section label {
  display: block;
  font-weight: 600;
  margin-bottom: var(--spacing-2);
}

.shipping-input-group {
  display: flex;
  gap: var(--spacing-2);
}

.shipping-input-group .input-field {
  flex: 1;
}

.shipping-option {
  display: flex;
  justify-content: space-between;
  padding: var(--spacing-2) 0;
  border-bottom: 1px solid var(--color-black-lighter);
  font-size: 0.875rem;
}
.shipping-option:last-child {
  border-bottom: none;
}

/* Sections */
.section-title {
  font-family: var(--font-title);
  font-size: 1.5rem;
  border-bottom: 2px solid var(--color-black-lighter);
  padding-bottom: var(--spacing-2);
  margin-bottom: var(--spacing-4);
}

.desc-content p {
  color: var(--color-gray);
  line-height: 1.8;
}

.review-item {
  background-color: var(--color-black-light);
  padding: var(--spacing-4);
  border-radius: var(--border-radius-sm);
  margin-bottom: var(--spacing-4);
}

.review-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-4);
  margin-bottom: var(--spacing-2);
}

.review-date {
  color: var(--color-gray);
  font-size: 0.875rem;
  margin-left: auto;
}

.review-text {
  color: var(--color-gray);
  line-height: 1.6;
}

/* Utils */
.mt-4 { margin-top: var(--spacing-4); }
.mt-6 { margin-top: var(--spacing-6); }
.mt-8 { margin-top: var(--spacing-8); }
.mt-12 { margin-top: var(--spacing-12); }
.mb-12 { margin-bottom: var(--spacing-12); }
.loading-state, .empty-state {
  text-align: center;
  padding: var(--spacing-16) 0;
}

@media (max-width: 768px) {
  .product-hero { grid-template-columns: 1fr; }
  .action-section { flex-direction: column; }
  .qty-selector { width: 100%; justify-content: space-between; }
  .qty-selector input { flex: 1; }
}

.favorite-btn {
  position: absolute;
  top: 1rem;
  right: 1rem;
  color: var(--color-gray);
  background-color: rgba(0, 0, 0, 0.6);
  border: none;
  border-radius: 50%;
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: var(--transition);
  z-index: 10;
}

.favorite-btn:hover {
  color: var(--color-red);
  background-color: var(--color-white);
  transform: scale(1.05);
}

.gallery-nav-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background-color: rgba(0, 0, 0, 0.6);
  color: var(--color-white);
  border: none;
  border-radius: 50%;
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 2rem;
  line-height: 1;
  transition: var(--transition);
  z-index: 5;
  user-select: none;
}

.gallery-nav-btn:hover {
  background-color: var(--color-red);
  color: var(--color-white);
}

.gallery-nav-btn.left {
  left: 1rem;
}

.gallery-nav-btn.right {
  right: 1rem;
}

/* Sizebay Modal */
.sizebay-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 1rem;
}

.sizebay-modal-content {
  background-color: #121214;
  border: 1px solid #29292e;
  border-radius: 12px;
  width: 100%;
  max-width: 900px;
  height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
}

.sizebay-modal-header-tabs {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 1.5rem;
  border-bottom: 1px solid #29292e;
  background-color: #1a1a1e;
}

.tabs-group {
  display: flex;
  gap: 0.5rem;
}

.modal-tab-btn {
  padding: 1.2rem 1rem;
  color: var(--color-gray);
  font-family: var(--font-title);
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: 1px;
  border: none;
  background: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  transition: var(--transition);
  font-size: 0.95rem;
}

.modal-tab-btn.active {
  color: var(--color-white);
  border-bottom-color: var(--color-red);
}

.modal-tab-btn:hover {
  color: var(--color-white);
}

/* VFR Styles */
.vfr-tab-wrapper {
  color: var(--color-white);
}

.vfr-form {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--spacing-4);
  margin-top: 1.5rem;
  margin-bottom: 1.5rem;
}

.vfr-field-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.vfr-field-group.full-width {
  grid-column: span 3;
}

.vfr-field-group label {
  font-weight: 600;
  font-size: 0.9rem;
  color: var(--color-white);
}

.field-hint {
  color: var(--color-gray);
  font-size: 0.8rem;
  margin-top: 0.2rem;
}

.fit-options {
  display: flex;
  gap: 1.5rem;
  margin-top: 0.5rem;
}

.fit-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  color: var(--color-gray);
  font-size: 0.95rem;
}

.fit-option input[type="radio"] {
  accent-color: var(--color-red);
  width: 16px;
  height: 16px;
}

.vfr-result-container {
  padding: 2rem 1.5rem;
}

.vfr-result-icon {
  font-size: 4rem;
  margin-bottom: 0.5rem;
}

.recommended-size-badge {
  font-family: var(--font-title);
  font-size: 4.5rem;
  color: var(--color-red);
  line-height: 1;
  margin: 1rem 0;
  font-weight: 800;
}

.vfr-result-desc {
  color: var(--color-gray);
  font-size: 1.1rem;
  max-width: 500px;
  margin: 0 auto;
  line-height: 1.6;
}

.vfr-result-actions {
  display: flex;
  justify-content: center;
  gap: 1rem;
}

.close-modal-btn {
  background: none;
  border: none;
  color: #a8a8b3;
  font-size: 2rem;
  line-height: 1;
  cursor: pointer;
  padding: 0;
  transition: color 0.2s;
}

.close-modal-btn:hover {
  color: var(--color-red);
}

.size-guide-content {
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
  color: var(--color-white);
  background-color: #121214;
}

.guide-intro {
  color: var(--color-gray);
  margin-bottom: 1.5rem;
  font-size: 1rem;
}

.size-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 2rem;
}

.size-table th, .size-table td {
  padding: 1rem;
  text-align: center;
  border-bottom: 1px solid var(--color-black-lighter);
}

.size-table th {
  background-color: var(--color-black-light);
  color: var(--color-red);
  font-family: var(--font-title);
  text-transform: uppercase;
  letter-spacing: 1px;
  font-size: 0.9rem;
}

.size-table td {
  font-size: 0.95rem;
}

.size-table tr:hover {
  background-color: rgba(255, 255, 255, 0.02);
}

.measurement-instructions {
  background-color: var(--color-black-light);
  padding: 1.5rem;
  border-radius: 8px;
  border: 1px solid var(--color-black-lighter);
}

.measurement-instructions p {
  color: var(--color-gray);
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  line-height: 1.6;
}

.measurement-instructions p strong {
  color: var(--color-white);
}

/* Personalization Section */
.personalization-container {
  background-color: var(--color-black-light);
  border: 1px solid var(--color-black-lighter);
  padding: 1.5rem;
  border-radius: var(--border-radius);
}

.personalization-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--color-white);
  margin-bottom: 0.25rem;
  font-family: var(--font-title);
  letter-spacing: 0.5px;
}

.personalization-subtitle {
  font-size: 0.85rem;
  color: var(--color-gray);
  margin-bottom: 1.25rem;
}

.personalization-options-pills {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}

.pill-btn {
  flex: 1;
  padding: 0.75rem var(--spacing-4);
  background-color: var(--color-black-lighter);
  border: 2px solid var(--color-black-lighter);
  border-radius: var(--border-radius);
  color: var(--color-gray);
  font-weight: 700;
  font-size: 0.85rem;
  text-align: center;
  cursor: pointer;
  font-family: var(--font-title);
  letter-spacing: 0.5px;
  text-transform: uppercase;
  transition: var(--transition);
}

.pill-btn:hover {
  color: var(--color-white);
  border-color: var(--color-gray-dark);
}

.pill-btn.active {
  background-color: rgba(227, 6, 19, 0.1);
  border-color: var(--color-red);
  color: var(--color-white);
  box-shadow: 0 0 10px rgba(227, 6, 19, 0.2);
}

.personalization-fields .field-row {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1rem;
}

.personalization-fields .field-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.personalization-fields .field-item label {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-gray);
}

.personalization-fields .field-item .input-field {
  padding: 0.75rem;
  background-color: var(--color-black-lighter);
  border: 1px solid var(--color-black-lighter);
  border-radius: var(--border-radius-sm);
  color: var(--color-white);
  font-family: inherit;
}

.personalization-fields .field-item .input-field:focus {
  border-color: var(--color-red);
  outline: none;
}

.personalization-disclaimer {
  display: block;
  font-size: 0.75rem;
  color: var(--color-red);
  margin-top: 1rem;
}

@media (max-width: 768px) {
  .personalization-fields .field-row {
    grid-template-columns: 1fr;
  }
  .sizebay-modal-content {
    height: 95vh;
    max-height: 95vh;
  }
}
</style>

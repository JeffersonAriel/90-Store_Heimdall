<template>
  <div v-if="loading" class="text-center py-6">
    <div class="spinner"></div>
    <p class="text-secondary mt-2">Carregando detalhes do produto...</p>
  </div>

  <div v-else-if="product" class="product-detail-container">
    <!-- Imagens/Galeria -->
    <div class="product-gallery">
      <img :src="activeImage" class="main-image" :alt="product.nome" />
      <div v-if="product.fotos?.length > 1" class="thumbnail-list mt-2">
        <img 
          v-for="foto in product.fotos" 
          :key="foto.id" 
          :src="foto.url" 
          class="thumbnail" 
          :class="{ active: activeImage === foto.url }" 
          @click="activeImage = foto.url"
        />
      </div>
    </div>

    <!-- Info e Compra -->
    <div class="product-info-panel">
      <span class="category-badge">{{ product.categoria?.nome }}</span>
      <h1 class="product-title mt-1">{{ product.nome }}</h1>
      
      <!-- Preço -->
      <div class="price-container mt-4">
        <template v-if="product.tem_desconto">
          <span class="price-old">R$ {{ formatMoney(product.preco_venda) }}</span>
          <span class="price-new">R$ {{ formatMoney(product.preco_desconto) }}</span>
        </template>
        <template v-else>
          <span class="price-new">R$ {{ formatMoney(product.preco_venda) }}</span>
        </template>
      </div>

      <!-- Variações: Tamanhos e Cores -->
      <div class="variations-selector mt-4">
        <div v-if="tamanhosDisponiveis.length" class="form-group mb-4">
          <label class="form-label">Tamanho Disponível</label>
          <div class="options-grid">
            <button 
              v-for="t in tamanhosDisponiveis" 
              :key="t" 
              class="option-btn" 
              :class="{ active: selectedTamanho === t }" 
              @click="selectedTamanho = t"
            >
              {{ t }}
            </button>
          </div>
        </div>

        <div v-if="coresDisponiveis.length" class="form-group mb-4">
          <label class="form-label">Cor / Modelo</label>
          <div class="options-grid">
            <button 
              v-for="c in coresDisponiveis" 
              :key="c" 
              class="option-btn" 
              :class="{ active: selectedCor === c }" 
              @click="selectedCor = c"
            >
              {{ c }}
            </button>
          </div>
        </div>
      </div>

      <!-- Compra / Carrinho -->
      <div class="purchase-box mt-6">
        <div class="quantity-selector flex items-center gap-2 mb-4">
          <button class="qty-btn" @click="quantity > 1 ? quantity-- : null">-</button>
          <span class="qty-num">{{ quantity }}</span>
          <button class="qty-btn" @click="quantity++">+</button>
        </div>

        <button class="store-btn store-btn-primary w-full" :disabled="!selectedVariation" @click="addToCart">
          {{ selectedVariation ? 'Adicionar ao Carrinho' : 'Selecione Tamanho/Cor' }}
        </button>
      </div>

      <!-- Atributos Customizados -->
      <div v-if="product.atributos_valores?.length" class="attributes-panel mt-6">
        <h3>Especificações Técnicas</h3>
        <table class="attributes-table mt-2">
          <tr v-for="atrib in product.atributos_valores" :key="atrib.id">
            <td class="attr-name">{{ atrib.atributo?.nome }}</td>
            <td class="attr-val">{{ atrib.opcao?.valor || atrib.valor_livre }}</td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useStore } from '@/store/main'
import { useHead } from '@vueuse/head'

const route = useRoute()
const router = useRouter()
const store = useStore()

const product = ref(null)
const loading = ref(true)
const activeImage = ref('')

const quantity = ref(1)
const selectedTamanho = ref(null)
const selectedCor = ref(null)

onMounted(() => {
  fetchProduct()
})

async function fetchProduct() {
  loading.value = true
  try {
    const res = await axios.get(`/api/products/${route.params.slug}`)
    product.value = res.data.produto
    activeImage.value = product.value.fotos[0]?.url || 'https://via.placeholder.com/600?text=Sem+Foto'
    
    // Auto-seleciona primeiro tamanho/cor se disponível
    if (tamanhosDisponiveis.value.length) selectedTamanho.value = tamanhosDisponiveis.value[0]
    if (coresDisponiveis.value.length) selectedCor.value = coresDisponiveis.value[0]
  } catch (err) {
    console.error('Produto não encontrado', err)
  } finally {
    loading.value = false
  }
}

// Configura SEO de forma reativa após o carregamento
watch(product, (newVal) => {
  if (newVal) {
    useHead({
      title: newVal.seo_title || `${newVal.nome} | Artigos Esportivos`,
      meta: [
        { name: 'description', content: newVal.seo_description || newVal.descricao_curta }
      ]
    })
  }
})

const tamanhosDisponiveis = computed(() => {
  if (!product.value?.variacoes) return []
  return [...new Set(product.value.variacoes.map(v => v.tamanho).filter(Boolean))]
})

const coresDisponiveis = computed(() => {
  if (!product.value?.variacoes) return []
  return [...new Set(product.value.variacoes.map(v => v.cor).filter(Boolean))]
})

const selectedVariation = computed(() => {
  if (!product.value?.variacoes) return null
  return product.value.variacoes.find(v => {
    const matchTam = !selectedTamanho.value || v.tamanho === selectedTamanho.value
    const matchCor = !selectedCor.value || v.cor === selectedCor.value
    return matchTam && matchCor
  })
})

function addToCart() {
  if (!selectedVariation.value) return
  store.addToCart(product.value, selectedVariation.value, quantity.value)
  router.push('/carrinho')
}

function formatMoney(val) {
  return parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.product-detail-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 3rem;
  margin-top: 2rem;
}

@media (max-width: 768px) {
  .product-detail-container {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
}

.main-image {
  width: 100%;
  aspect-ratio: 1;
  object-fit: cover;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
  background: var(--bg-card);
}

.thumbnail-list {
  display: flex;
  gap: 0.5rem;
  overflow-x: auto;
}

.thumbnail {
  width: 70px;
  height: 70px;
  object-fit: cover;
  border-radius: var(--radius-md);
  cursor: pointer;
  border: 2px solid transparent;
  background: var(--bg-card);
}

.thumbnail.active {
  border-color: var(--brand-primary);
}

.options-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.375rem;
}

.option-btn {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  color: var(--text-primary);
  padding: 0.5rem 1rem;
  border-radius: var(--radius-md);
  cursor: pointer;
  font-family: inherit;
  transition: var(--transition-smooth);
}

.option-btn:hover, .option-btn.active {
  border-color: var(--brand-primary);
  color: var(--brand-light);
  background: var(--brand-glow);
}

.qty-btn {
  width: 32px;
  height: 32px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  color: var(--text-primary);
  cursor: pointer;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
}

.qty-num {
  font-weight: 700;
  min-width: 24px;
  text-align: center;
}

.attributes-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.attributes-table td {
  padding: 0.5rem;
  border-bottom: 1px solid var(--border-color);
}

.attr-name {
  color: var(--text-secondary);
  font-weight: 600;
  width: 40%;
}

.attr-val {
  color: var(--text-primary);
}
</style>

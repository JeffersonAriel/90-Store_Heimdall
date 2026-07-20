<template>
  <AdminLayout title="Vitrine — Destaques">
    <template #breadcrumb>
      <span>Marketing & Vitrine / Destaques</span>
    </template>

    <div class="max-w-6xl mx-auto mb-10 grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- Painel de Ordenação (Destaques Atuais) -->
      <div class="card lg:col-span-2">
        <div class="card-body">
          <div class="flex justify-between items-center mb-6">
            <div>
              <h2 class="title-md m-0">⭐ Destaques da Rodada</h2>
              <p class="text-secondary text-sm m-0 mt-1">Arraste os itens para mudar a ordem na vitrine do e-commerce.</p>
            </div>
            <button 
              @click="saveOrder" 
              class="btn btn-primary shadow-md flex items-center gap-2"
              :disabled="saving"
            >
              {{ saving ? 'Salvando...' : '💾 Salvar Alterações' }}
            </button>
          </div>

          <div v-if="highlightedList.length === 0" class="empty-state">
            <span class="empty-icon">⭐</span>
            <p>Nenhum produto destacado. Adicione camisas da lista ao lado para começar.</p>
          </div>

          <div v-else class="drag-list">
            <div 
              v-for="(item, index) in highlightedList" 
              :key="item.id"
              class="drag-item"
              :class="{ 'is-dragging': dragIndex === index }"
              draggable="true"
              @dragstart="onDragStart(index)"
              @dragover.prevent="onDragOver(index)"
              @dragend="onDragEnd"
            >
              <!-- Handle -->
              <div class="drag-handle">☰</div>
              
              <!-- Capa -->
              <img 
                :src="item.foto_capa?.url || 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=100'" 
                class="item-thumb" 
                alt="Capa"
              />

              <!-- Detalhes -->
              <div class="item-info">
                <strong class="item-title">{{ item.nome }}</strong>
                <span class="item-meta">Marca: {{ item.marca }} · R$ {{ formatMoney(item.preco_venda) }}</span>
              </div>

              <!-- Ações -->
              <button 
                type="button" 
                @click="removeHighlight(index)" 
                class="remove-btn" 
                title="Remover dos destaques"
              >
                ✕
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Seletor (Adicionar Produtos) -->
      <div class="card">
        <div class="card-body">
          <h2 class="title-md mb-2">🛍️ Disponíveis</h2>
          <p class="text-secondary text-sm mb-4">Selecione camisas para destacar.</p>

          <!-- Busca -->
          <div class="mb-4">
            <input 
              v-model="searchTerm" 
              type="text" 
              placeholder="Buscar por nome ou marca..." 
              class="form-input"
            />
          </div>

          <div v-if="filteredAvailable.length === 0" class="text-center py-6 text-gray-500">
            Nenhum produto disponível encontrado.
          </div>

          <div v-else class="available-list">
            <div 
              v-for="(item, index) in filteredAvailable" 
              :key="item.id"
              class="available-item"
            >
              <img 
                :src="item.foto_capa?.url || 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=80'" 
                class="available-thumb" 
                alt="Capa"
              />
              <div class="available-info">
                <strong class="available-title">{{ item.nome }}</strong>
                <span class="available-meta">{{ item.marca }}</span>
              </div>
              <button 
                type="button" 
                @click="addHighlight(item)" 
                class="add-btn"
                title="Adicionar aos destaques"
              >
                +
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  highlighted: { type: Array, required: true },
  available: { type: Array, required: true }
})

const highlightedList = ref([...props.highlighted])
const availableList = ref([...props.available])

const searchTerm = ref('')
const saving = ref(false)
const dragIndex = ref(null)

const filteredAvailable = computed(() => {
  if (!searchTerm.value) return availableList.value
  const term = searchTerm.value.toLowerCase()
  return availableList.value.filter(p => 
    p.nome.toLowerCase().includes(term) || 
    (p.marca && p.marca.toLowerCase().includes(term))
  )
})

function addHighlight(item) {
  // Remove from available list
  availableList.value = availableList.value.filter(p => p.id !== item.id)
  // Add to highlighted list
  highlightedList.value.push(item)
}

function removeHighlight(index) {
  const item = highlightedList.value.splice(index, 1)[0]
  // Add back to available list and sort alphabetically by name
  availableList.value.push(item)
  availableList.value.sort((a, b) => a.nome.localeCompare(b.nome))
}

// Drag and drop sorting handlers
function onDragStart(index) {
  dragIndex.value = index
}

function onDragOver(index) {
  if (dragIndex.value === null || dragIndex.value === index) return
  
  const item = highlightedList.value.splice(dragIndex.value, 1)[0]
  highlightedList.value.splice(index, 0, item)
  dragIndex.value = index
}

function onDragEnd() {
  dragIndex.value = null
}

function saveOrder() {
  saving.value = true
  router.post(route('admin.marketing.highlights.update'), {
    ids: highlightedList.value.map(p => p.id)
  }, {
    onSuccess: () => {
      alert('Destaques da rodada salvos com sucesso!')
    },
    onError: () => {
      alert('Erro ao salvar destaques. Tente novamente.')
    },
    onFinish: () => {
      saving.value = false
    }
  })
}

function formatMoney(value) {
  if (!value) return '0,00'
  return parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1.5rem;
  background: rgba(255, 255, 255, 0.02);
  border: 2px dashed var(--color-border);
  border-radius: var(--border-radius-md, 8px);
  text-align: center;
  color: var(--color-gray, #9ca3af);
}
.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}
.drag-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.drag-item {
  display: flex;
  align-items: center;
  background: var(--color-bg-elevated, #1f2937);
  border: 1px solid var(--color-border, #374151);
  border-radius: 6px;
  padding: 0.75rem 1rem;
  cursor: grab;
  user-select: none;
  transition: all 0.2s ease;
}
.drag-item.is-dragging {
  opacity: 0.4;
  border-style: dashed;
  background: rgba(255, 255, 255, 0.05);
}
.drag-handle {
  font-size: 1.25rem;
  color: var(--color-gray, #9ca3af);
  margin-right: 1rem;
  cursor: grab;
}
.item-thumb {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border-radius: 4px;
  margin-right: 1rem;
  border: 1px solid var(--color-border);
}
.item-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}
.item-title {
  color: var(--color-white, #fff);
  font-size: 0.95rem;
}
.item-meta {
  color: var(--color-gray, #9ca3af);
  font-size: 0.8rem;
  margin-top: 2px;
}
.remove-btn {
  background: none;
  border: none;
  color: #ef4444;
  font-size: 1.1rem;
  cursor: pointer;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  transition: background 0.2s;
}
.remove-btn:hover {
  background: rgba(239, 68, 68, 0.1);
}

.available-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-height: 480px;
  overflow-y: auto;
  padding-right: 4px;
}
.available-item {
  display: flex;
  align-items: center;
  background: var(--color-bg-elevated, #1f2937);
  border: 1px solid var(--color-border, #374151);
  border-radius: 6px;
  padding: 0.5rem 0.75rem;
}
.available-thumb {
  width: 40px;
  height: 40px;
  object-fit: cover;
  border-radius: 4px;
  margin-right: 0.75rem;
  border: 1px solid var(--color-border);
}
.available-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}
.available-title {
  color: var(--color-white, #fff);
  font-size: 0.85rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 140px;
}
.available-meta {
  color: var(--color-gray, #9ca3af);
  font-size: 0.75rem;
}
.add-btn {
  background: var(--color-primary, #6366f1);
  border: none;
  color: #fff;
  width: 26px;
  height: 26px;
  border-radius: 40%;
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: transform 0.2s;
}
.add-btn:hover {
  transform: scale(1.1);
}
</style>

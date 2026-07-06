<template>
  <AdminLayout :title="isEdit ? 'Editar Produto' : 'Novo Produto'">
    <template #breadcrumb>
      <span><Link :href="route('admin.products.index')" class="text-indigo-600 hover:underline">Produtos</Link> / {{ isEdit ? 'Editar' : 'Novo' }}</span>
    </template>

    <div class="card max-w-5xl mx-auto">
      <div class="card-body">
        <form @submit.prevent="submitForm">
          
          <!-- 1. IDENTIFICAÇÃO -->
          <div class="card mb-6">
            <div class="card-header">
              <h3 class="card-title flex items-center gap-2 m-0">
                <span class="bg-indigo-600 text-white rounded-full w-6 h-6 inline-flex items-center justify-center text-sm font-bold">1</span> 
                Identificação do Produto
              </h3>
            </div>
            <div class="card-body">
              <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="col-span-1 md:col-span-2 form-group">
                  <label class="form-label">Nome do Produto *</label>
                  <input v-model="form.nome" @blur="generateBaseSku" type="text" class="form-input text-lg font-medium" placeholder="Ex: Camiseta Nike Vapor..." required />
                </div>
                
                <div class="col-span-1 form-group">
                  <label class="form-label">Marca</label>
                  <input v-model="form.marca" list="marcas_list" type="text" class="form-input text-lg font-medium" placeholder="Ex: Nike, Adidas..." />
                  <datalist id="marcas_list">
                    <option v-for="m in brandOptions" :key="m" :value="m" />
                  </datalist>
                </div>

                <div class="col-span-1 form-group">
                  <label class="form-label">Gênero *</label>
                  <select v-model="form.genero" class="form-select" required>
                  <option value="Unissex">Unissex</option>
                  <option value="Masculino">Masculino</option>
                  <option value="Feminino">Feminino</option>
                  <option value="Infantil">Infantil</option>
                </select>
              </div>

                <!-- SELEÇÃO EM CASCATA -->
                <div class="col-span-1 md:col-span-3 p-4 rounded-lg" style="background: var(--color-bg-elevated); border: 1px solid var(--color-border);">
                  <label class="form-label mb-3">Classificação (Categoria)</label>
                  
                  <div class="flex flex-wrap gap-4">
                    <!-- Nível 1 -->
                    <div class="flex-1 min-w-[200px]">
                      <select v-model="selectedCatLevel1" class="form-select" @change="onChangeLevel1">
                        <option value="" disabled>Selecione a Categoria Principal...</option>
                        <option v-for="cat in rootCategories" :key="cat.id" :value="cat.id">{{ cat.nome }}</option>
                      </select>
                    </div>
                    
                    <!-- Nível 2 -->
                    <div v-if="childrenLevel1.length > 0" class="flex-1 min-w-[200px] animate-fade-in">
                      <select v-model="selectedCatLevel2" class="form-select" @change="onChangeLevel2">
                        <option value="" disabled>Subcategoria...</option>
                        <option v-for="cat in childrenLevel1" :key="cat.id" :value="cat.id">{{ cat.nome }}</option>
                      </select>
                    </div>

                    <!-- Nível 3 -->
                    <div v-if="childrenLevel2.length > 0" class="flex-1 min-w-[200px] animate-fade-in">
                      <select v-model="selectedCatLevel3" class="form-select" @change="onChangeLevel3">
                        <option value="" disabled>Selecione...</option>
                        <option v-for="cat in childrenLevel2" :key="cat.id" :value="cat.id">{{ cat.nome }}</option>
                      </select>
                    </div>

                    <!-- Nível 4 -->
                    <div v-if="childrenLevel3.length > 0" class="flex-1 min-w-[200px] animate-fade-in">
                      <select v-model="selectedCatLevel4" class="form-select" @change="onChangeLevel4">
                        <option value="" disabled>Modelo/Time...</option>
                        <option v-for="cat in childrenLevel3" :key="cat.id" :value="cat.id">{{ cat.nome }}</option>
                      </select>
                    </div>
                  </div>
                  
                  <div v-if="form.categoria_id" class="mt-3 text-sm font-medium flex items-center gap-1" style="color: var(--color-success)">
                    ✓ Categoria final selecionada com sucesso.
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">SKU Base *</label>
                  <div class="flex">
                    <input v-model="form.sku_base" type="text" class="form-input font-mono" required placeholder="Geração automática..." />
                    <button type="button" @click="generateBaseSku(true)" class="btn btn-secondary ml-2 whitespace-nowrap" title="Regerar SKU aleatório">
                      🔄 Regerar
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 2. FORNECEDOR E DESCRIÇÃO -->
          <div class="card mb-6">
            <div class="card-header">
              <h3 class="card-title flex items-center gap-2 m-0">
                <span class="bg-indigo-600 text-white rounded-full w-6 h-6 inline-flex items-center justify-center text-sm font-bold">2</span> 
                Origem e Detalhes
              </h3>
            </div>
            <div class="card-body">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                <div class="form-group">
                  <label class="form-label">Fornecedor *</label>
                  <select v-model="form.fornecedor_id" class="form-select" required>
                    <option value="" disabled>Selecione um fornecedor</option>
                    <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">{{ sup.razao_social }}</option>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Descrição Completa</label>
                <textarea v-model="form.descricao" class="form-textarea" rows="4" placeholder="Detalhes do produto, tecido, tecnologia..."></textarea>
              </div>
            </div>
          </div>

          <!-- 3. PRECIFICAÇÃO E ESTOQUE GLOBAL -->
          <div class="card mb-6">
            <div class="card-header">
              <h3 class="card-title flex items-center gap-2 m-0">
                <span class="bg-indigo-600 text-white rounded-full w-6 h-6 inline-flex items-center justify-center text-sm font-bold">3</span> 
                Preços, Status e Metas de Estoque
              </h3>
            </div>
            <div class="card-body">
              <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="form-group">
                  <label class="form-label">Preço Custo (R$) *</label>
                  <input v-model.number="form.preco_custo" type="number" step="0.01" class="form-input font-medium" style="color: var(--color-danger)" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Preço Venda (R$) *</label>
                  <input v-model.number="form.preco_venda" type="number" step="0.01" class="form-input font-bold" style="color: var(--color-success)" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Promocional (R$)</label>
                  <input v-model.number="form.preco_desconto" @input="form.tem_desconto = form.preco_desconto > 0" type="number" step="0.01" class="form-input font-medium" style="color: var(--color-brand)" />
                </div>
                <div class="form-group">
                  <label class="form-label">Estoque Crítico (Total)</label>
                  <input v-model.number="form.estoque_critico" type="number" class="form-input" min="0" placeholder="Ex: 5" />
                </div>
              </div>

              <div class="mt-6 flex items-center">
                <input v-model="form.ativo" type="checkbox" id="ativo" class="w-5 h-5 rounded" style="accent-color: var(--color-brand)" />
                <label for="ativo" class="ml-2 form-label mb-0 font-bold cursor-pointer">Este produto está ativo e visível na vitrine</label>
              </div>

              <!-- OPÇÕES RETRÔ -->
              <div v-if="isTimeCategory" class="mt-6 p-4 rounded-lg" style="background: var(--color-warning-bg); border: 1px solid rgba(245, 158, 11, 0.2);">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                  <div class="flex items-center">
                    <input v-model="form.is_retro" type="checkbox" id="is_retro" class="w-5 h-5 rounded" style="accent-color: var(--color-warning)" />
                    <label for="is_retro" class="ml-2 form-label mb-0 font-bold cursor-pointer" style="color: var(--color-warning)">Camisa Retrô (Clássica/Histórica)</label>
                  </div>
                  <div class="form-group">
                    <label class="form-label font-bold" style="color: var(--color-warning)">Ano do Modelo / Edição</label>
                    <input v-model="form.retro_year" type="number" class="form-input" placeholder="Ex: 2026, 1998..." />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 4. VARIAÇÕES -->
          <template v-if="true">
            <div class="card mb-6">
              <div class="card-header">
                <h3 class="card-title flex items-center gap-2 m-0">
                  <span class="bg-indigo-600 text-white rounded-full w-6 h-6 inline-flex items-center justify-center text-sm font-bold">4</span> 
                  Grade de Variações e Estoque Inicial
                </h3>
                <button type="button" @click="addVariation" class="btn btn-secondary text-sm font-bold shadow-sm">
                  + Adicionar Variação
                </button>
              </div>
              <div class="card-body">
                <div class="table-wrapper">
                  <table class="w-full text-left border-collapse">
                    <thead>
                      <tr>
                        <th>Tamanho</th>
                        <th>Cor</th>
                        <th>SKU Gerado</th>
                        <th>Tipo Estoque</th>
                        <th class="text-center">Qtd Atual</th>
                        <th class="text-center">Ações</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(varItem, index) in form.variacoes" :key="index">
                        <td>
                          <select v-model="varItem.tamanho" @change="updateVariationSku(index)" class="form-select w-24 mx-auto" :disabled="!!varItem.id" required>
                            <option value="" disabled>Tam...</option>
                            <optgroup label="Roupas">
                              <option v-for="s in sizeOptions.roupas" :key="s" :value="s">{{ s }}</option>
                            </optgroup>
                            <optgroup label="Calçados">
                              <option v-for="s in sizeOptions.calcados" :key="s" :value="s">{{ s }}</option>
                            </optgroup>
                          </select>
                        </td>
                        <td>
                          <select v-model="varItem.cor" @change="updateVariationSku(index)" class="form-select w-32" :disabled="!!varItem.id" required>
                            <option value="" disabled>Cor...</option>
                            <option v-for="cor in colorOptions" :key="cor" :value="cor">{{ cor }}</option>
                          </select>
                        </td>
                        <td>
                          <input v-model="varItem.sku" type="text" class="form-input font-mono text-sm w-48" style="background: var(--color-bg-elevated)" :disabled="!!varItem.id" required />
                        </td>
                        <td>
                          <select v-model="varItem.tipo_estoque" class="form-select w-36">
                            <option value="proprio">Próprio</option>
                            <option value="dropshipping">Drop</option>
                          </select>
                        </td>
                        <td class="text-center">
                          <input v-if="varItem.tipo_estoque === 'proprio'" v-model.number="varItem.estoque_quantidade" type="number" class="form-input w-20 mx-auto text-center" min="0" required />
                          <span v-else class="text-muted text-sm">-</span>
                        </td>
                        <td class="text-center">
                          <button v-if="form.variacoes.length > 1" type="button" @click="removeVariation(index)" class="btn-icon" style="color: var(--color-danger)" title="Remover Variação">
                            🗑️
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </template>

          <!-- 5. IMAGENS POR COR -->
          <template v-if="uniqueColors.length > 0">
            <div class="card mb-6">
              <div class="card-header">
                <h3 class="card-title flex items-center gap-2 m-0">
                  <span class="bg-indigo-600 text-white rounded-full w-6 h-6 inline-flex items-center justify-center text-sm font-bold">5</span> 
                  Fotos por Cor
                </h3>
              </div>
              <div class="card-body">
                <p class="text-muted text-sm mb-4">Faça o upload das imagens correspondentes a cada cor selecionada na grade acima.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div v-for="cor in uniqueColors" :key="cor" class="p-4 rounded-lg" style="background: var(--color-bg-elevated); border: 1px solid var(--color-border);">
                    <label class="form-label font-bold" style="color: var(--color-text-primary)">
                      📸 Fotos da Cor: <span style="color: var(--color-brand)">{{ cor }}</span>
                    </label>
                    <div v-if="isEdit && existingPhotosByColor(cor).length > 0" class="flex flex-wrap gap-2 mt-2 mb-4">
                      <div v-for="foto in existingPhotosByColor(cor)" :key="foto.id" class="relative group" style="width: 64px; height: 64px; flex-shrink: 0;">
                        <img :src="foto.url" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px; border: 1px solid var(--color-border);" :class="{ 'opacity-50 grayscale': form.deleted_photos.includes(foto.id) }" />
                        <button type="button" @click="toggleDeletePhoto(foto.id)" class="absolute -top-2 -right-2 bg-white rounded-full text-red-500 shadow hover:text-red-700 flex items-center justify-center font-bold" style="width: 24px; height: 24px;">
                          {{ form.deleted_photos.includes(foto.id) ? '↺' : '×' }}
                        </button>
                      </div>
                    </div>
                    <div class="mt-3">
                      <label class="text-xs text-gray-500 font-bold mb-1 block">Opção 1: Arquivos locais</label>
                      <input type="file" @change="handleFileUpload($event, cor)" multiple accept="image/*" class="form-input text-sm" />
                      <p class="text-xs mt-1 text-muted">Selecione uma ou mais imagens do seu computador.</p>
                    </div>

                    <div class="mt-3">
                      <label class="text-xs text-gray-500 font-bold mb-1 block">Opção 2: Ou cole URLs externas</label>
                      <textarea v-model="form.fotos_url_por_cor[cor]" rows="2" class="form-textarea text-sm" placeholder="https://exemplo.com/imagem1.jpg&#10;https://exemplo.com/imagem2.png"></textarea>
                      <p class="text-xs mt-1 text-muted">Cole uma URL por linha.</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <!-- Ações -->
          <div class="flex justify-end gap-3 pt-2">
            <Link :href="route('admin.products.index')" class="btn btn-secondary px-6">Cancelar</Link>
            <button type="submit" class="btn btn-primary px-8 text-lg shadow-md" :disabled="form.processing || !form.categoria_id">
              {{ isEdit ? 'Salvar Alterações' : 'Concluir Cadastro' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  product: {
    type: Object,
    default: null
  },
  categories: {
    type: Array,
    default: () => []
  },
  suppliers: {
    type: Array,
    default: () => []
  }
});

const isEdit = computed(() => !!props.product);

// Cores Padronizadas
const colorOptions = [
  'Preto', 'Branco', 'Cinza', 'Chumbo', 'Azul Marinho', 'Azul Royal', 'Azul Celeste', 
  'Vermelho', 'Bordô', 'Vinho', 'Verde Escuro', 'Verde Musgo', 'Verde Limão', 
  'Amarelo', 'Laranja', 'Rosa', 'Roxo', 'Lilás', 'Marrom', 'Bege', 'Dourado', 
  'Prata', 'Bronze', 'Colorido/Estampado'
].sort();

// Marcas Pré-configuradas
const brandOptions = [
  'Nike', 'Adidas', 'Puma', 'Umbro', 'Kappa', 'Reebok', 'Fila', 'Under Armour', 
  'New Balance', 'Mizuno', 'Asics', 'Penalty', 'Topper', 'Castore', 'Macron', 
  'Joma', 'Le Coq Sportif', 'Hummel', 'Athleta'
].sort();

// Tamanhos Padronizados
const sizeOptions = {
  roupas: ['PP', 'P', 'M', 'G', 'GG', 'XG', 'XXG', '2', '4', '6', '8', '10', '12', '14', '16', '36', '38', '40', '42', '44', '46', '48', 'Único'],
  calcados: ['33', '34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48']
};

// Lógica de Cascata de Categorias
const selectedCatLevel1 = ref('');
const selectedCatLevel2 = ref('');
const selectedCatLevel3 = ref('');
const selectedCatLevel4 = ref('');

const rootCategories = computed(() => props.categories.filter(c => !c.parent_id));
const childrenLevel1 = computed(() => props.categories.filter(c => c.parent_id === selectedCatLevel1.value));
const childrenLevel2 = computed(() => props.categories.filter(c => c.parent_id === selectedCatLevel2.value));
const childrenLevel3 = computed(() => props.categories.filter(c => c.parent_id === selectedCatLevel3.value));

function onChangeLevel1() {
  selectedCatLevel2.value = '';
  selectedCatLevel3.value = '';
  selectedCatLevel4.value = '';
  updateFinalCategory();
}
function onChangeLevel2() {
  selectedCatLevel3.value = '';
  selectedCatLevel4.value = '';
  updateFinalCategory();
}
function onChangeLevel3() {
  selectedCatLevel4.value = '';
  updateFinalCategory();
}
function onChangeLevel4() {
  updateFinalCategory();
}

function updateFinalCategory() {
  form.categoria_id = selectedCatLevel4.value || selectedCatLevel3.value || selectedCatLevel2.value || selectedCatLevel1.value || '';
}



const isTimeCategory = computed(() => {
  const selectedIds = [selectedCatLevel1.value, selectedCatLevel2.value, selectedCatLevel3.value, selectedCatLevel4.value].filter(Boolean);
  for (const id of selectedIds) {
    const cat = props.categories.find(c => String(c.id) === String(id));
    if (cat && (
      cat.nome.toLowerCase().includes('time') || 
      cat.nome.toLowerCase().includes('seleção') || 
      cat.nome.toLowerCase().includes('selecao') ||
      cat.nome.toLowerCase().includes('nacional') ||
      cat.nome.toLowerCase().includes('internacional')
    )) {
      return true;
    }
  }
  return false;
});

const form = useForm({
  fornecedor_id: props.product?.fornecedor_id || '',
  categoria_id: props.product?.categoria_id || '',
  nome: props.product?.nome || '',
  marca: props.product?.marca || '',
  genero: props.product?.genero || 'Unissex',
  sku_base: props.product?.sku_base || '',
  descricao: props.product?.descricao || '',
  descricao_curta: props.product?.descricao_curta || '',
  preco_custo: props.product?.preco_custo || 0,
  preco_venda: props.product?.preco_venda || 0,
  tem_desconto: props.product?.tem_desconto || false,
  preco_desconto: props.product?.preco_desconto || 0,
  is_retro: props.product?.is_retro || false,
  retro_year: props.product?.retro_year || '',
  estoque_critico: props.product?.estoque_critico || 2,
  ativo: props.product ? props.product.ativo : true,
  is_destaque: props.product?.is_destaque || false,
  peso_kg: props.product?.peso_kg || 0,
  variacoes: props.product?.variacoes?.length ? [...props.product.variacoes] : [
    {
      sku: '',
      tamanho: '',
      cor: '',
      preco_adicional: 0,
      tipo_estoque: 'proprio',
      estoque_quantidade: 0,
    }
  ],
  fotos_por_cor: {},
  fotos_url_por_cor: {},
  deleted_photos: []
});

// Cores únicas
const uniqueColors = computed(() => {
  const cores = form.variacoes.map(v => v.cor).filter(c => c !== '');
  return [...new Set(cores)];
});

// Fotos existentes por cor
function existingPhotosByColor(cor) {
  if (!props.product || !props.product.fotos) return [];
  return props.product.fotos.filter(f => f.cor === cor);
}

// Toggle excluir foto
function toggleDeletePhoto(fotoId) {
  const index = form.deleted_photos.indexOf(fotoId);
  if (index > -1) {
    form.deleted_photos.splice(index, 1);
  } else {
    form.deleted_photos.push(fotoId);
  }
}

// Upload de imagens
function handleFileUpload(event, cor) {
  form.fotos_por_cor[cor] = Array.from(event.target.files);
}

// Adicionar Variação na Grade
function addVariation() {
  form.variacoes.push({
    sku: '',
    tamanho: '',
    cor: '',
    preco_adicional: 0,
    tipo_estoque: 'proprio',
    estoque_quantidade: 0,
  });
  if (form.sku_base) {
    updateVariationSku(form.variacoes.length - 1);
  }
}

// Remover Variação da Grade
function removeVariation(index) {
  form.variacoes.splice(index, 1);
}

// Geração de SKU Base Automático
function generateBaseSku(force = false) {
  if (!form.nome) return;
  if (form.sku_base && !force) return;

  const prefix = form.nome.substring(0, 3).toUpperCase().replace(/[^A-Z]/g, 'X') || 'PRO';
  const randomNum = Math.floor(1000 + Math.random() * 9000);
  form.sku_base = `${prefix}-${randomNum}`;
  
  if (!isEdit.value) {
    updateAllVariationSkus();
  }
}

// Atualização de SKU de Variação Individual
function updateVariationSku(index) {
  if (!form.sku_base) return;
  
  const varItem = form.variacoes[index];
  let varSuffix = [];
  
  if (varItem.tamanho) {
    varSuffix.push(varItem.tamanho.trim().toUpperCase().replace(/\s+/g, '-'));
  }
  
  if (varItem.cor) {
    varSuffix.push(varItem.cor.trim().toUpperCase().replace(/\s+/g, '-').substring(0, 3));
  }
  
  if (varSuffix.length > 0) {
    varItem.sku = `${form.sku_base}-${varSuffix.join('-')}`;
  } else {
    varItem.sku = `${form.sku_base}-V${index + 1}`;
  }
}

// Atualizar todos os SKUs ao mudar a base
function updateAllVariationSkus() {
  form.variacoes.forEach((_, idx) => updateVariationSku(idx));
}

// Observa mudanças no SKU base para refletir em todas as variações
watch(() => form.sku_base, () => {
  if (!isEdit.value) {
    updateAllVariationSkus();
  }
});

function submitForm() {
  if (!form.categoria_id) {
    alert("Por favor, selecione uma categoria.");
    return;
  }
  
  if (isEdit.value) {
    form.put(route('admin.products.update', props.product.id), {
      forceFormData: true, // Necessário para enviar arquivos via PUT
    });
  } else {
    form.post(route('admin.products.store'));
  }
}

onMounted(() => {
  if (isEdit.value && form.categoria_id) {
    let currentId = form.categoria_id;
    let path = [];
    
    while (currentId) {
      const cat = props.categories.find(c => c.id === currentId);
      if (cat) {
        path.unshift(cat.id);
        currentId = cat.parent_id;
      } else {
        break;
      }
    }
    
    if (path.length > 0) selectedCatLevel1.value = path[0];
    if (path.length > 1) selectedCatLevel2.value = path[1];
    if (path.length > 2) selectedCatLevel3.value = path[2];
    if (path.length > 3) selectedCatLevel4.value = path[3];
  }
});
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-out forwards;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-5px); }
  to { opacity: 1; transform: translateY(0); }
}

table th {
  letter-spacing: 0.05em;
}
</style>

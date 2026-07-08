<template>
  <AdminLayout :title="isEdit ? 'Editar Fornecedor' : 'Novo Fornecedor'">
    <template #breadcrumb>
      <span><Link :href="route('admin.suppliers.index')" class="text-brand hover:underline">Fornecedores</Link> / {{ isEdit ? 'Editar' : 'Novo' }}</span>
    </template>

    <div class="card max-w-4xl mx-auto">
      <div class="card-header">
        <h3 class="card-title">{{ isEdit ? 'Editar Fornecedor' : 'Cadastrar Fornecedor' }}</h3>
      </div>
      <div class="card-body">
        <form @submit.prevent="submitForm">
          
          <h4 class="font-bold text-lg mb-4 mt-2" style="color: var(--color-brand)">1. Dados Principais</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="form-group">
              <label class="form-label">Tipo de Pessoa *</label>
              <select v-model="form.tipo_pessoa" class="form-select" required>
                <option value="juridica">Pessoa Jurídica (CNPJ)</option>
                <option value="fisica">Pessoa Física (CPF)</option>
              </select>
            </div>
            <div class="form-group" v-if="form.tipo_pessoa === 'juridica'">
              <label class="form-label">CNPJ *</label>
              <input v-model="form.cnpj" type="text" class="form-input font-mono" placeholder="00.000.000/0000-00" required />
            </div>
            <div class="form-group" v-else>
              <label class="form-label">CPF *</label>
              <input v-model="form.cpf" type="text" class="form-input font-mono" placeholder="000.000.000-00" required />
            </div>

            <div class="form-group md:col-span-2">
              <label class="form-label">Nome / Razão Social *</label>
              <input v-model="form.razao_social" type="text" class="form-input text-lg" required placeholder="Nome oficial do fornecedor" />
            </div>
            
            <div class="form-group md:col-span-2">
              <label class="form-label">Nome Fantasia</label>
              <input v-model="form.nome_fantasia" type="text" class="form-input" placeholder="Como a empresa é conhecida" />
            </div>
          </div>

          <h4 class="font-bold text-lg mb-4 mt-8" style="color: var(--color-brand)">2. Contato</h4>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="form-group">
              <label class="form-label">E-mail</label>
              <input v-model="form.email" type="email" class="form-input" placeholder="contato@fornecedor.com" />
            </div>
            <div class="form-group">
              <label class="form-label">Telefone Fixo</label>
              <input v-model="form.telefone" type="text" class="form-input font-mono" placeholder="(00) 0000-0000" />
            </div>
            <div class="form-group">
              <label class="form-label">WhatsApp</label>
              <input v-model="form.whatsapp" type="text" class="form-input font-mono" placeholder="(00) 90000-0000" />
            </div>
            <div class="form-group md:col-span-3">
              <label class="form-label">Website</label>
              <input v-model="form.website" type="url" class="form-input" placeholder="https://..." />
            </div>
          </div>

          <h4 class="font-bold text-lg mb-4 mt-8" style="color: var(--color-brand)">3. Endereço</h4>
          <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-6">
            <div class="form-group md:col-span-4">
              <label class="form-label">CEP</label>
              <div style="position: relative;">
                <input
                  v-model="form.cep"
                  @blur="fetchCep"
                  @keydown.enter.prevent="fetchCep"
                  type="text"
                  class="form-input font-mono"
                  placeholder="00000-000"
                  maxlength="9"
                  :style="cepLoading ? 'padding-right: 2.5rem;' : ''"
                />
                <span v-if="cepLoading" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); font-size: 0.875rem; color: var(--color-brand);">⏳</span>
                <span v-if="cepError" style="display: block; color: var(--color-danger); font-size: 0.75rem; margin-top: 0.25rem;">{{ cepError }}</span>
                <span v-if="cepSuccess" style="display: block; color: var(--color-success); font-size: 0.75rem; margin-top: 0.25rem;">✓ Endereço encontrado!</span>
              </div>
            </div>
            <div class="form-group md:col-span-8">
              <label class="form-label">Logradouro (Rua, Av.)</label>
              <input v-model="form.logradouro" type="text" class="form-input" placeholder="Av. Principal" />
            </div>
            
            <div class="form-group md:col-span-3">
              <label class="form-label">Número</label>
              <input v-model="form.numero" type="text" class="form-input" placeholder="123" />
            </div>
            <div class="form-group md:col-span-4">
              <label class="form-label">Complemento</label>
              <input v-model="form.complemento" type="text" class="form-input" placeholder="Sala 4, Galpão B" />
            </div>
            <div class="form-group md:col-span-5">
              <label class="form-label">Bairro</label>
              <input v-model="form.bairro" type="text" class="form-input" placeholder="Centro" />
            </div>

            <div class="form-group md:col-span-8">
              <label class="form-label">Cidade</label>
              <input v-model="form.cidade" type="text" class="form-input" placeholder="São Paulo" />
            </div>
            <div class="form-group md:col-span-4">
              <label class="form-label">Estado (UF)</label>
              <input v-model="form.estado" type="text" class="form-input" placeholder="SP" maxlength="2" />
            </div>
          </div>

          <h4 class="font-bold text-lg mb-4 mt-8" style="color: var(--color-brand)">4. Informações Comerciais</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="form-group">
              <label class="form-label">Condição de Pagamento</label>
              <input v-model="form.condicao_pagamento" type="text" class="form-input" placeholder="Ex: Boleto 30/60/90, PIX Antecipado" />
            </div>
            <div class="form-group">
              <label class="form-label">Prazo Médio de Entrega (Dias)</label>
              <input v-model.number="form.prazo_medio_dias" type="number" min="0" class="form-input" placeholder="Ex: 15" />
            </div>

            <div class="form-group md:col-span-2">
              <label class="form-label">O que este fornecedor vende? (Categorias / Produtos)</label>
              <div class="flex gap-2">
                <input v-model="newCategoria" @keydown.enter.prevent="addCategoria" type="text" class="form-input flex-1" placeholder="Ex: Camisas de Time, Chuteiras, Bolas... (Pressione Enter para adicionar)" />
                <button type="button" @click="addCategoria" class="btn btn-secondary">Incluir</button>
              </div>
              <div class="flex flex-wrap gap-2 mt-3" v-if="form.categorias_fornecidas.length > 0">
                <span v-for="(cat, index) in form.categorias_fornecidas" :key="index" class="badge badge-primary flex items-center gap-1" style="font-size: 0.8125rem; padding: 0.375rem 0.75rem;">
                  {{ cat }}
                  <button type="button" @click="removeCategoria(index)" class="hover:text-red-200 ml-1 font-bold" title="Remover">&times;</button>
                </span>
              </div>
            </div>

            <div class="form-group md:col-span-2">
              <label class="form-label">Observações Internas</label>
              <textarea v-model="form.observacoes" class="form-textarea" rows="4" placeholder="Detalhes sobre frete, regras de pedido mínimo..."></textarea>
            </div>
          </div>

          <div class="mt-6 flex items-center p-4 rounded-lg" style="background: var(--color-bg-elevated); border: 1px solid var(--color-border);">
            <input v-model="form.ativo" type="checkbox" id="ativo" class="w-5 h-5 rounded" style="accent-color: var(--color-brand)" />
            <label for="ativo" class="ml-3 form-label mb-0 font-bold cursor-pointer text-lg">Fornecedor Ativo</label>
          </div>

          <!-- Ações -->
          <div class="flex justify-end gap-3 mt-8 pt-4 border-t" style="border-color: var(--color-border)">
            <Link :href="route('admin.suppliers.index')" class="btn btn-secondary px-6">Cancelar</Link>
            <button type="submit" class="btn btn-primary px-8 text-lg shadow-md" :disabled="form.processing">
              {{ isEdit ? 'Salvar Alterações' : 'Cadastrar Fornecedor' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  supplier: {
    type: Object,
    default: null
  }
});

const isEdit = computed(() => !!props.supplier);

const form = useForm({
  tipo_pessoa: props.supplier?.tipo_pessoa || 'juridica',
  razao_social: props.supplier?.razao_social || '',
  nome_fantasia: props.supplier?.nome_fantasia || '',
  cnpj: props.supplier?.cnpj || '',
  cpf: props.supplier?.cpf || '',
  email: props.supplier?.email || '',
  telefone: props.supplier?.telefone || '',
  whatsapp: props.supplier?.whatsapp || '',
  website: props.supplier?.website || '',
  cep: props.supplier?.cep || '',
  logradouro: props.supplier?.logradouro || '',
  numero: props.supplier?.numero || '',
  complemento: props.supplier?.complemento || '',
  bairro: props.supplier?.bairro || '',
  cidade: props.supplier?.cidade || '',
  estado: props.supplier?.estado || '',
  condicao_pagamento: props.supplier?.condicao_pagamento || '',
  prazo_medio_dias: props.supplier?.prazo_medio_dias || null,
  categorias_fornecidas: props.supplier?.categorias_fornecidas || [],
  observacoes: props.supplier?.observacoes || '',
  ativo: props.supplier !== null ? Boolean(props.supplier.ativo) : true,
});

const newCategoria = ref('');
const cepLoading = ref(false);
const cepError = ref('');
const cepSuccess = ref(false);

async function fetchCep() {
  const cep = form.cep.replace(/\D/g, '');
  if (cep.length !== 8) return;

  cepLoading.value = true;
  cepError.value = '';
  cepSuccess.value = false;

  try {
    const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
    const data = await res.json();

    if (data.erro) {
      cepError.value = 'CEP não encontrado. Verifique e tente novamente.';
    } else {
      form.logradouro = data.logradouro || '';
      form.bairro     = data.bairro || '';
      form.cidade     = data.localidade || '';
      form.estado     = data.uf || '';
      cepSuccess.value = true;
      setTimeout(() => { cepSuccess.value = false; }, 3000);
    }
  } catch (e) {
    cepError.value = 'Erro ao buscar CEP. Verifique sua conexão.';
  } finally {
    cepLoading.value = false;
  }
}

function addCategoria() {
  const val = newCategoria.value.trim();
  if (val && !form.categorias_fornecidas.includes(val)) {
    form.categorias_fornecidas.push(val);
  }
  newCategoria.value = '';
}

function removeCategoria(index) {
  form.categorias_fornecidas.splice(index, 1);
}

function submitForm() {
  if (isEdit.value) {
    form.put(route('admin.suppliers.update', props.supplier.id));
  } else {
    form.post(route('admin.suppliers.store'));
  }
}
</script>

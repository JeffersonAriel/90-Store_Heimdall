<template>
  <AdminLayout>
    <div class="mb-6 flex justify-between items-center">
      <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Gerenciar Banners (Carrossel Principal)</h1>
      <button @click="openModal()" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
        + Adicionar Banner
      </button>
    </div>

    <!-- Tabela de Banners -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Imagem</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo / Proporção</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Título / Link</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ordem</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          <tr v-for="banner in banners" :key="banner.id">
            <td class="px-6 py-4">
              <img :src="banner.image_path" class="h-16 w-32 object-cover rounded" style="width: 120px; height: 60px; object-fit: cover;" />
            </td>
            <td class="px-6 py-4">
              <div class="text-sm font-semibold capitalize text-gray-900 dark:text-white">{{ banner.type }}</div>
              <div class="text-xs text-gray-500">Proporção: {{ banner.aspect_ratio }}</div>
              <div v-if="banner.category" class="text-xs text-indigo-500 mt-1">Categoria: {{ banner.category.nome }}</div>
            </td>
            <td class="px-6 py-4">
              <div class="font-medium text-gray-900 dark:text-white">{{ banner.title || 'Sem título' }}</div>
              <div class="text-sm text-gray-500">{{ banner.link_url || 'Sem link' }}</div>
            </td>
            <td class="px-6 py-4">{{ banner.order }}</td>
            <td class="px-6 py-4">
              <span class="px-2 py-1 text-xs rounded" :class="banner.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                {{ banner.is_active ? 'Ativo' : 'Inativo' }}
              </span>
            </td>
            <td class="px-6 py-4 text-right">
              <button @click="openModal(banner)" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</button>
              <button @click="deleteBanner(banner.id)" class="text-red-600 hover:text-red-900">Excluir</button>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="!banners.length" class="p-6 text-center text-gray-500">Nenhum banner cadastrado.</div>
    </div>

    <!-- Modal Form -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; padding: 20px;">
      <div class="inline-block bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all" style="max-width: 550px; width: 100%; border-radius: 16px; border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);">
        <form @submit.prevent="submitForm">
          <div class="bg-white dark:bg-gray-800 px-6 pt-6 pb-4">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 pb-2" style="border-bottom: 1px solid rgba(255,255,255,0.08);">
              {{ form.id ? '⚡ Editar Banner' : '✨ Novo Banner' }}
            </h3>
            
            <div class="space-y-4">
              <!-- Preview de Imagem no topo do modal -->
              <div v-if="form.image_path" class="mb-4">
                <label class="block text-sm font-medium text-gray-400 mb-2">Pré-visualização</label>
                <div :style="{ 
                  aspectRatio: form.aspect_ratio.replace(':', '/'), 
                  backgroundImage: `url(${form.image_path})`, 
                  backgroundSize: 'cover',
                  backgroundPosition: 'center',
                  borderRadius: '12px',
                  border: '1px solid rgba(255,255,255,0.1)'
                }" style="width: 100%; max-height: 180px;"></div>
              </div>

              <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">URL da Imagem</label>
                <input type="text" v-model="form.image_path" required class="form-input" placeholder="https://exemplo.com/imagem.jpg">
                <p class="text-xs text-gray-500 mt-1">Insira a URL absoluta da imagem.</p>
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título</label>
                  <input type="text" v-model="form.title" class="form-input" placeholder="Ex: Cupom 10% OFF">
                </div>
                <div class="form-group">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subtítulo</label>
                  <input type="text" v-model="form.subtitle" class="form-input" placeholder="Ex: Use o cupom BEMVINDO">
                </div>
              </div>

              <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Link de destino (URL)</label>
                <input type="text" v-model="form.link_url" class="form-input" placeholder="Ex: /catalogo?categoria=camisetas">
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Banner</label>
                  <select v-model="form.type" required class="form-select">
                    <option value="vitrine">Vitrine (Carrossel Home)</option>
                    <option value="megamenu">Mega Menu</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Proporção da Imagem</label>
                  <select v-model="form.aspect_ratio" required class="form-select">
                    <option value="16:9">16:9 (Padrão Vitrine)</option>
                    <option value="4:3">4:3 (Padrão Mega Menu)</option>
                    <option value="1:1">1:1 (Quadrado)</option>
                  </select>
                </div>
              </div>

              <div v-if="form.type === 'megamenu'" class="form-group">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Categoria Principal Vinculada</label>
                <select v-model="form.category_id" required class="form-select">
                  <option :value="null">Selecione uma categoria principal...</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.nome }}</option>
                </select>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ordem (Exibição)</label>
                  <input type="number" v-model="form.order" class="form-input">
                </div>
                <div class="form-group">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                  <select v-model="form.is_active" class="form-select">
                    <option :value="true">Ativo</option>
                    <option :value="false">Inativo</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 flex justify-end">
            <button type="button" @click="closeModal" class="btn-cancel">
              Cancelar
            </button>
            <button type="submit" class="btn-save">
              Salvar
            </button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
.form-group label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 0.35rem;
  color: #9ca3af;
}
.form-input, .form-select {
  width: 100%;
  padding: 0.65rem 0.85rem;
  border-radius: 8px;
  background-color: #1f2937;
  border: 1px solid #4b5563;
  color: #ffffff;
  font-size: 0.9rem;
  box-sizing: border-box;
  margin-bottom: 0.5rem;
  transition: border-color 0.2s;
}
.form-input:focus, .form-select:focus {
  border-color: #6366f1;
  outline: none;
}
.btn-save {
  background-color: #4f46e5;
  color: white;
  padding: 0.65rem 1.5rem;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}
.btn-save:hover {
  background-color: #4338ca;
}
.btn-cancel {
  background-color: transparent;
  color: #d1d5db;
  padding: 0.65rem 1.5rem;
  border-radius: 8px;
  border: 1px solid #4b5563;
  font-weight: 600;
  cursor: pointer;
  margin-right: 0.75rem;
}
.btn-cancel:hover {
  background-color: #374151;
}
</style>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  banners: Array,
  categories: Array,
});

const isModalOpen = ref(false);

const form = useForm({
  id: null,
  title: '',
  subtitle: '',
  image_path: '',
  link_url: '',
  order: 0,
  is_active: true,
  type: 'vitrine',
  aspect_ratio: '16:9',
  category_id: null
});

const openModal = (banner = null) => {
  if (banner) {
    form.id = banner.id;
    form.title = banner.title;
    form.subtitle = banner.subtitle;
    form.image_path = banner.image_path;
    form.link_url = banner.link_url;
    form.order = banner.order;
    form.is_active = banner.is_active;
    form.type = banner.type || 'vitrine';
    form.aspect_ratio = banner.aspect_ratio || '16:9';
    form.category_id = banner.category_id || null;
  } else {
    form.reset();
  }
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  form.reset();
};

const submitForm = () => {
  if (form.id) {
    form.put(route('admin.banners.update', form.id), {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post(route('admin.banners.store'), {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteBanner = (id) => {
  if (confirm('Tem certeza que deseja excluir este banner?')) {
    router.delete(route('admin.banners.destroy', id));
  }
};
</script>

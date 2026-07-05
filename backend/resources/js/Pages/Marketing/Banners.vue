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
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Título / Link</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ordem</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          <tr v-for="banner in banners" :key="banner.id">
            <td class="px-6 py-4">
              <img :src="banner.image_path" class="h-16 w-32 object-cover rounded" />
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
    <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeModal"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <form @submit.prevent="submitForm">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
              <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4" id="modal-title">
                {{ form.id ? 'Editar Banner' : 'Novo Banner' }}
              </h3>
              
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">URL da Imagem</label>
                  <input type="text" v-model="form.image_path" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                  <p class="text-xs text-gray-500 mt-1">Insira a URL absoluta da imagem (ex: https://...). Upload será implementado depois.</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título</label>
                  <input type="text" v-model="form.title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subtítulo</label>
                  <input type="text" v-model="form.subtitle" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Link de destino (URL)</label>
                  <input type="text" v-model="form.link_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ordem (Exibição)</label>
                    <input type="number" v-model="form.order" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <select v-model="form.is_active" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                      <option :value="true">Ativo</option>
                      <option :value="false">Inativo</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
              <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                Salvar
              </button>
              <button type="button" @click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600">
                Cancelar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  banners: Array,
});

const isModalOpen = ref(false);

const form = useForm({
  id: null,
  title: '',
  subtitle: '',
  image_path: '',
  link_url: '',
  order: 0,
  is_active: true
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

<template>
  <div class="static-page container">
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
    </div>
    
    <div v-else-if="!pageContent" class="empty-state">
      <h2>Página não encontrada</h2>
    </div>

    <div v-else class="page-content-wrapper">
      <h1 class="title-lg mb-6">{{ pageContent.title }}</h1>
      <div class="content-html" v-html="pageContent.body"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useHead } from '@vueuse/head'

const route = useRoute()
const loading = ref(true)
const pageContent = ref(null)

useHead({
  title: () => `${pageContent.value?.title || 'Institucional'} | 90+ Store`
})

onMounted(() => {
  fetchPage()
})

watch(() => route.params.slug, () => {
  fetchPage()
})

function fetchPage() {
  loading.value = true
  const slug = route.params.slug
  
  // Simulação de busca na API do Heimdall
  setTimeout(() => {
    if (slug === 'sobre') {
      pageContent.value = {
        title: 'Quem Somos',
        body: '<p>A 90+ Store nasceu da paixão pelo esporte. Nosso objetivo é entregar os melhores equipamentos para atletas de alto rendimento.</p>'
      }
    } else if (slug === 'trocas') {
      pageContent.value = {
        title: 'Política de Trocas e Devoluções',
        body: '<p>Você tem até 30 dias para solicitar a troca do seu produto caso não sirva ou apresente defeito.</p>'
      }
    } else {
      pageContent.value = {
        title: 'Página Institucional',
        body: '<p>Conteúdo carregado dinamicamente via Heimdall API.</p>'
      }
    }
    loading.value = false
  }, 500)
}
</script>

<style scoped>
.static-page {
  padding: var(--spacing-8) var(--spacing-4);
  max-width: 800px;
  min-height: 50vh;
}

.page-content-wrapper {
  background-color: var(--color-black-light);
  padding: var(--spacing-8);
  border-radius: var(--border-radius-sm);
  border: 1px solid var(--color-black-lighter);
}

.content-html {
  color: var(--color-gray);
  line-height: 1.8;
}

.content-html :deep(h2) {
  color: var(--color-white);
  margin-top: var(--spacing-6);
  margin-bottom: var(--spacing-4);
  font-family: var(--font-title);
}

.content-html :deep(p) {
  margin-bottom: var(--spacing-4);
}

.mb-6 { margin-bottom: var(--spacing-6); }

.loading-state, .empty-state {
  text-align: center;
  padding: var(--spacing-16) 0;
}
.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--color-black-lighter);
  border-top-color: var(--color-red);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>

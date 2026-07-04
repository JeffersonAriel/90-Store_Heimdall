<template>
  <RouterView />
</template>

<script setup>
import { RouterView } from 'vue-router'
import { useHead } from '@vueuse/head'

useHead({
  titleTemplate: (title) => title ? `${title} — 90-Store` : '90-Store | Artigos Esportivos',
  meta: [
    { name: 'og:site_name', content: '90-Store' },
    { name: 'og:type', content: 'website' },
  ],
})
</script>

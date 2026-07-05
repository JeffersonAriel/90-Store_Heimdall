import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
    },
    {
      path: '/catalogo',
      name: 'catalog',
      component: () => import('@/views/CatalogView.vue'),
    },
    {
      path: '/produto/:slug',
      name: 'product.detail',
      component: () => import('@/views/ProductView.vue'),
    },
    {
      path: '/carrinho',
      name: 'cart',
      component: () => import('@/views/CartView.vue'),
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
    },
    {
      path: '/cadastro',
      name: 'register',
      component: () => import('@/views/RegisterView.vue'),
    },
    {
      path: '/checkout',
      name: 'checkout',
      component: () => import('@/views/CheckoutView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/minha-conta',
      name: 'account',
      component: () => import('@/views/AccountView.vue'),
    },
    {
      path: '/institucional/:slug',
      name: 'static.page',
      component: () => import('@/views/StaticPageView.vue'),
    }
  ],
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('store_auth_token')
  if (to.meta.requiresAuth && !token) {
    next({ name: 'login' })
  } else {
    next()
  }
})

export default router

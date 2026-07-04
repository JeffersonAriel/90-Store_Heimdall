import { defineStore } from 'pinia'
import axios from 'axios'

export const useStore = defineStore('main', {
  state: () => ({
    user: null,
    token: localStorage.getItem('store_auth_token') || null,
    cart: JSON.parse(localStorage.getItem('store_cart')) || [],
    shippingAddress: null,
    shippingQuote: null,
    activeGateway: 'mercadopago',
    appliedCoupon: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    
    cartSubtotal: (state) => {
      return state.cart.reduce((total, item) => {
        const preco = item.produto.tem_desconto ? item.produto.preco_desconto : item.produto.preco_venda
        const precoFinal = parseFloat(preco) + parseFloat(item.variacao.preco_adicional)
        return total + (precoFinal * item.quantidade)
      }, 0)
    },

    cartWeight: (state) => {
      return state.cart.reduce((total, item) => {
        const peso = parseFloat(item.produto.peso_kg || 0.3)
        return total + (peso * item.quantidade)
      }, 0)
    },

    cartDiscount: (state) => {
      if (!state.appliedCoupon) return 0
      const sub = state.cartSubtotal
      if (sub < state.appliedCoupon.valor_minimo_pedido) return 0

      if (state.appliedCoupon.tipo === 'percent') {
        return (sub * state.appliedCoupon.valor) / 100
      } else if (state.appliedCoupon.tipo === 'fixed') {
        return state.appliedCoupon.valor
      }
      return 0
    },

    cartTotal(state) {
      const sub = this.cartSubtotal
      const desc = this.cartDiscount
      const shipping = state.shippingQuote ? parseFloat(state.shippingQuote.valor) : 0
      return Math.max(0, (sub - desc) + shipping)
    }
  },

  actions: {
    async fetchUser() {
      if (!this.token) return
      try {
        const res = await axios.get('/api/profile', {
          headers: { Authorization: `Bearer ${this.token}` }
        })
        this.user = res.data.cliente
      } catch (err) {
        this.logout()
      }
    },

    addToCart(product, variation, quantity = 1) {
      const existing = this.cart.find(
        (item) => item.variacao.id === variation.id
      )

      if (existing) {
        existing.quantidade += quantity
      } else {
        this.cart.push({ produto: product, variacao: variation, quantidade: quantity })
      }

      this.saveCart()
    },

    removeFromCart(variationId) {
      this.cart = this.cart.filter((item) => item.variacao.id !== variationId)
      this.saveCart()
    },

    clearCart() {
      this.cart = []
      this.shippingQuote = null
      this.appliedCoupon = null
      this.saveCart()
    },

    saveCart() {
      localStorage.setItem('store_cart', JSON.stringify(this.cart))
    },

    setToken(token) {
      this.token = token
      localStorage.setItem('store_auth_token', token)
    },

    logout() {
      this.token = null
      this.user = null
      localStorage.removeItem('store_auth_token')
    }
  }
})

<template>
  <div class="account-view container">
    <div class="account-header">
      <h1 class="title-lg">MINHA CONTA</h1>
      <p class="subtitle">Bem-vindo(a) de volta{{ store.user ? ', ' + firstName : '' }}!</p>
    </div>

    <div class="account-layout">
      <!-- Sidebar -->
      <aside class="account-sidebar">
        <nav class="account-nav">
          <button class="nav-btn" :class="{ active: currentTab === 'dashboard' }" @click="setTab('dashboard')">Painel</button>
          <button class="nav-btn" :class="{ active: currentTab === 'pedidos' }"   @click="setTab('pedidos')">Meus Pedidos</button>
          <button class="nav-btn" :class="{ active: currentTab === 'enderecos' }" @click="setTab('enderecos')">Endereços</button>
          <button class="nav-btn" :class="{ active: currentTab === 'pontos' }"    @click="setTab('pontos')">Pontos & Fidelidade</button>
          <button class="nav-btn" :class="{ active: currentTab === 'cupons' }"    @click="setTab('cupons')">Meus Cupons</button>
          <button class="nav-btn text-red" @click="logout">Sair</button>
        </nav>
      </aside>

      <main class="account-content">

        <!-- ── DASHBOARD ────────────────────────────────── -->
        <div v-if="currentTab === 'dashboard'" class="tab-pane">
          <h2 class="title-md mb-6">Visão Geral</h2>

          <div class="grid grid-cols-3 gap-6">
            <!-- Pontos reais -->
            <div class="stat-card">
              <h3>Saldo de Pontos</h3>
              <p class="stat-value text-red">
                {{ profile?.pontos_saldo ?? 0 }} pts
              </p>
              <p class="stat-sub">≈ {{ formatCurrency((profile?.pontos_saldo ?? 0) * 0.10) }}</p>
              <button class="link-btn mt-2" @click="setTab('pontos')">Ver detalhes</button>
            </div>

            <!-- Último pedido real -->
            <div class="stat-card">
              <h3>Último Pedido</h3>
              <template v-if="lastOrder">
                <p class="stat-value">#{{ lastOrder.id }}</p>
                <span class="status-badge" :class="getStatusClass(lastOrder.status)">
                  {{ formatStatus(lastOrder.status) }}
                </span>
              </template>
              <p v-else class="stat-value text-gray" style="font-size:1rem">Nenhum pedido</p>
              <button class="link-btn mt-2" @click="setTab('pedidos')">Ver pedidos</button>
            </div>

            <!-- Indique e Ganhe com código real -->
            <div class="stat-card">
              <h3>Indique e Ganhe</h3>
              <p style="font-size:0.8rem;color:var(--color-gray);margin-bottom:8px">
                Compartilhe seu código e ganhe pontos quando um amigo comprar:
              </p>
              <div class="copy-box mt-2" @click="copyReferral" style="cursor:pointer" title="Clique para copiar">
                <code>{{ profile?.referral_code ?? '—' }}</code>
                <span class="copy-hint">{{ copied ? '✅ Copiado!' : '📋 Copiar' }}</span>
              </div>
            </div>
          </div>

          <!-- Dados Cadastrais editáveis -->
          <h3 class="title-sm mt-8 mb-4">Dados Cadastrais</h3>
          <form class="profile-form" @submit.prevent="saveProfile">
            <div class="grid grid-cols-2 gap-4" v-if="profile">
              <div class="input-group">
                <label class="input-label">Nome Completo</label>
                <input type="text" v-model="editForm.nome_completo" class="input-field" />
              </div>
              <div class="input-group">
                <label class="input-label">CPF <span class="text-gray" style="font-size:0.75rem">(não editável)</span></label>
                <input type="text" :value="profile.cpf" class="input-field" disabled />
              </div>
              <div class="input-group">
                <label class="input-label">E-mail</label>
                <input type="email" v-model="editForm.email" class="input-field" />
              </div>
              <div class="input-group">
                <label class="input-label">Telefone / WhatsApp</label>
                <input type="text" v-model="editForm.telefone" class="input-field" />
              </div>
              <div class="input-group">
                <label class="input-label">Nova Senha <span class="text-gray" style="font-size:0.75rem">(deixe em branco para não alterar)</span></label>
                <input type="password" v-model="editForm.password" class="input-field" placeholder="••••••••" />
              </div>
              <div class="input-group">
                <label class="input-label">Confirmar Nova Senha</label>
                <input type="password" v-model="editForm.password_confirmation" class="input-field" placeholder="••••••••" />
              </div>
            </div>
            <div v-if="profileMsg" class="profile-msg" :class="profileMsgType">{{ profileMsg }}</div>
            <button type="submit" class="btn btn-primary mt-4" :disabled="savingProfile">
              {{ savingProfile ? 'Salvando...' : 'Salvar Alterações' }}
            </button>
          </form>
        </div>

        <!-- ── PEDIDOS ───────────────────────────────────── -->
        <div v-if="currentTab === 'pedidos'" class="tab-pane">
          <h2 class="title-md mb-6">Histórico de Pedidos</h2>

          <div v-if="loadingOrders" class="text-center py-8">
            <span class="loading-spinner"></span>
            <p class="text-gray mt-2">Buscando seus pedidos...</p>
          </div>
          <div v-else-if="orders.length === 0" class="text-center py-8 text-gray">
            Nenhum pedido encontrado.
          </div>
          <div v-else class="order-list">
            <div v-for="order in orders" :key="order.id" class="order-card mb-4">
              <div class="order-header">
                <div>
                  <strong>Pedido #{{ order.id }}</strong>
                  <span class="order-date">{{ formatDate(order.created_at) }}</span>
                </div>
                <div class="order-total">{{ formatCurrency(order.total) }}</div>
              </div>
              <div class="order-body">
                <p>Status: <span class="status font-bold" :class="getStatusClass(order.status)">{{ formatStatus(order.status) }}</span></p>

                <div class="mt-4 border-t border-dark pt-4">
                  <div v-for="item in order.itens" :key="item.id" class="flex items-center mb-3">
                    <img
                      :src="item.produto?.foto_capa?.url || 'https://via.placeholder.com/60'"
                      class="w-12 h-12 object-cover rounded mr-4 bg-dark"
                      :alt="item.nome_snapshot"
                    />
                    <div class="flex-1">
                      <p class="font-bold text-sm">{{ item.nome_snapshot }}</p>
                      <p class="text-gray text-xs">
                        Tamanho: {{ item.variacao?.atributo_valor ?? item.variacao?.tamanho ?? '—' }}
                        | {{ item.quantidade }}x {{ formatCurrency(item.preco_venda_snapshot) }}
                      </p>
                    </div>
                  </div>
                </div>

                <div class="mt-4 flex gap-4">
                  <button v-if="order.status === 'aguardando_pagamento'" class="btn btn-primary" @click="payNow(order.id)">PAGAR AGORA</button>
                  <button class="btn btn-outline" @click="viewOrderDetails(order)">Ver Detalhes</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ── ENDEREÇOS ────────────────────────────────── -->
        <div v-if="currentTab === 'enderecos'" class="tab-pane">
          <div class="flex-between mb-6">
            <h2 class="title-md">Endereços Salvos</h2>
            <button class="btn btn-outline" @click="openAddressForm(null)">+ Novo Endereço</button>
          </div>

          <!-- Lista de endereços -->
          <div v-if="loadingAddresses" class="text-center py-4 text-gray">Carregando...</div>
          <div v-else-if="addresses.length === 0" class="text-center py-8 text-gray">
            Nenhum endereço cadastrado.
          </div>
          <div v-else class="addr-grid">
            <div v-for="addr in addresses" :key="addr.id" class="address-card" :class="{ 'addr-principal': addr.is_principal }">
              <div class="addr-top">
                <span class="badge badge-dark">{{ addr.apelido || 'Endereço' }}</span>
                <span v-if="addr.is_principal" class="badge-principal">★ Principal</span>
              </div>
              <p><strong>{{ addr.logradouro }}, {{ addr.numero }}</strong><span v-if="addr.complemento"> — {{ addr.complemento }}</span></p>
              <p>{{ addr.bairro }} — {{ addr.cidade }}/{{ addr.estado }}</p>
              <p>CEP: {{ addr.cep }}</p>
              <div class="address-actions mt-4">
                <button class="link-btn" @click="openAddressForm(addr)">Editar</button>
                <button v-if="!addr.is_principal" class="link-btn" @click="setPrincipalAddr(addr.id)">Tornar Principal</button>
                <button v-if="!addr.is_principal" class="link-btn text-red" @click="deleteAddress(addr.id)">Excluir</button>
              </div>
            </div>
          </div>

          <!-- Formulário de adicionar/editar endereço -->
          <div v-if="showAddressForm" class="addr-form-wrap mt-6">
            <h3 class="title-sm mb-4">{{ editingAddress ? 'Editar Endereço' : 'Novo Endereço' }}</h3>
            <form class="profile-form" @submit.prevent="saveAddress">
              <div class="grid grid-cols-2 gap-4">
                <div class="input-group">
                  <label class="input-label">Apelido (ex: Casa, Trabalho)</label>
                  <input type="text" v-model="addrForm.apelido" class="input-field" placeholder="Casa" />
                </div>
                <div class="input-group">
                  <label class="input-label">CEP</label>
                  <input type="text" v-model="addrForm.cep" class="input-field" placeholder="00000-000" maxlength="9"
                    @blur="lookupCep" @input="formatCepInput" />
                </div>
                <div class="input-group">
                  <label class="input-label">Logradouro</label>
                  <input type="text" v-model="addrForm.logradouro" class="input-field" required />
                </div>
                <div class="input-group">
                  <label class="input-label">Número</label>
                  <input type="text" v-model="addrForm.numero" class="input-field" required />
                </div>
                <div class="input-group">
                  <label class="input-label">Complemento</label>
                  <input type="text" v-model="addrForm.complemento" class="input-field" placeholder="Apto, Bloco..." />
                </div>
                <div class="input-group">
                  <label class="input-label">Bairro</label>
                  <input type="text" v-model="addrForm.bairro" class="input-field" required />
                </div>
                <div class="input-group">
                  <label class="input-label">Cidade</label>
                  <input type="text" v-model="addrForm.cidade" class="input-field" required />
                </div>
                <div class="input-group">
                  <label class="input-label">Estado (UF)</label>
                  <input type="text" v-model="addrForm.estado" class="input-field" maxlength="2" placeholder="SP" required />
                </div>
              </div>

              <div v-if="addrMsg" class="profile-msg" :class="addrMsgType">{{ addrMsg }}</div>

              <div class="flex gap-4 mt-4">
                <button type="submit" class="btn btn-primary" :disabled="savingAddress">
                  {{ savingAddress ? 'Salvando...' : (editingAddress ? 'Salvar Alterações' : 'Adicionar Endereço') }}
                </button>
                <button type="button" class="btn btn-outline" @click="closeAddressForm">Cancelar</button>
              </div>
            </form>
          </div>
        </div>

        <!-- ── PONTOS ────────────────────────────────────── -->
        <div v-if="currentTab === 'pontos'" class="tab-pane">
          <h2 class="title-md mb-6">Pontos & Fidelidade</h2>

          <div class="points-banner">
            <div class="points-circle">
              <strong>{{ profile?.pontos_saldo ?? 0 }}</strong>
              <span>Pts</span>
            </div>
            <div class="points-info">
              <h3>{{ pointsLevel }}</h3>
              <p>Seus pontos valem <strong>{{ formatCurrency((profile?.pontos_saldo ?? 0) * 0.10) }}</strong> de desconto na próxima compra.</p>
              <div class="points-rule mt-3">
                <span>📐 Regra:</span>
                <strong>A cada R$ 100 em compras = 10 pontos</strong><br>
                <span style="font-size:0.8rem;color:var(--color-gray)">1 ponto = R$ 0,10 de desconto</span>
              </div>
            </div>
          </div>

          <!-- Extrato real -->
          <h3 class="title-sm mt-8 mb-4">Extrato de Pontos</h3>
          <div v-if="loadingPoints" class="text-center py-4 text-gray">Carregando extrato...</div>
          <div v-else-if="pointsHistory.length === 0" class="text-center py-4 text-gray">
            Nenhuma movimentação ainda. Faça sua primeira compra para ganhar pontos! 🏆
          </div>
          <table v-else class="history-table">
            <thead>
              <tr>
                <th>Data</th>
                <th>Descrição</th>
                <th>Pontos</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in pointsHistory" :key="item.id">
                <td>{{ formatDate(item.created_at) }}</td>
                <td>{{ item.descricao }}</td>
                <td :class="item.tipo === 'credito' ? 'text-green' : 'text-red'">
                  {{ item.tipo === 'credito' ? '+' : '-' }} {{ item.pontos }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ── CUPONS ────────────────────────────────────── -->
        <div v-if="currentTab === 'cupons'" class="tab-pane">
          <h2 class="title-md mb-6">Meus Cupons</h2>

          <div v-if="loadingCoupons" class="text-center py-4 text-gray">Carregando cupons...</div>
          <div v-else-if="coupons.length === 0" class="text-center py-8 text-gray">
            Você não possui cupons ativos no momento.
          </div>
          <div v-else class="grid grid-cols-2 gap-6">
            <div v-for="c in coupons" :key="c.id" class="coupon-card">
              <div class="coupon-amount">
                {{ c.tipo === 'percent' ? c.valor + '% OFF' : 'R$ ' + c.valor }}
              </div>
              <div class="coupon-details">
                <h3>{{ c.nome }}</h3>
                <p v-if="c.valor_minimo_pedido > 0">Mínimo: {{ formatCurrency(c.valor_minimo_pedido) }}</p>
                <p v-if="c.data_expiracao">Válido até: {{ formatDate(c.data_expiracao) }}</p>
                <div class="coupon-code mt-2">{{ c.codigo }}</div>
              </div>
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useStore } from '@/store/main'
import axios from 'axios'
import { useHead } from '@vueuse/head'

useHead({ title: 'Minha Conta | 90+ Store' })

const router      = useRouter()
const route       = useRoute()
const store       = useStore()
const currentTab  = ref('dashboard')

// ── Profile ────────────────────────────────────────────────
const profile        = ref(null)
const savingProfile  = ref(false)
const profileMsg     = ref('')
const profileMsgType = ref('success')
const copied         = ref(false)

const editForm = ref({
  nome_completo: '',
  email: '',
  telefone: '',
  password: '',
  password_confirmation: '',
})

// ── Orders ─────────────────────────────────────────────────
const orders       = ref([])
const loadingOrders = ref(false)

// ── Points ─────────────────────────────────────────────────
const pointsHistory = ref([])
const loadingPoints = ref(false)

// ── Addresses ────────────────────────────────────

const addresses      = ref([])
const loadingAddresses = ref(false)
const showAddressForm  = ref(false)
const editingAddress   = ref(null)   // null = novo, obj = editar
const savingAddress    = ref(false)
const addrMsg          = ref('')
const addrMsgType      = ref('success')

const addrFormDefault = () => ({
  apelido: '', cep: '', logradouro: '', numero: '',
  complemento: '', bairro: '', cidade: '', estado: '',
})
const addrForm = ref(addrFormDefault())

async function fetchAddresses() {
  loadingAddresses.value = true
  try {
    const res = await axios.get('/api/addresses', authHeaders())
    addresses.value = res.data.enderecos ?? []
  } catch (e) {
    console.error(e)
  } finally {
    loadingAddresses.value = false
  }
}

function openAddressForm(addr) {
  editingAddress.value = addr
  addrForm.value = addr
    ? { apelido: addr.apelido ?? '', cep: addr.cep, logradouro: addr.logradouro,
        numero: addr.numero, complemento: addr.complemento ?? '',
        bairro: addr.bairro, cidade: addr.cidade, estado: addr.estado }
    : addrFormDefault()
  addrMsg.value    = ''
  showAddressForm.value = true
  // scroll suave até o formulário
  setTimeout(() => document.querySelector('.addr-form-wrap')?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100)
}

function closeAddressForm() {
  showAddressForm.value = false
  editingAddress.value  = null
  addrForm.value        = addrFormDefault()
}

async function saveAddress() {
  savingAddress.value = true
  addrMsg.value = ''
  try {
    if (editingAddress.value) {
      await axios.put(`/api/addresses/${editingAddress.value.id}`, addrForm.value, authHeaders())
    } else {
      await axios.post('/api/addresses', addrForm.value, authHeaders())
    }
    addrMsg.value    = '✅ Endereço salvo com sucesso!'
    addrMsgType.value = 'success'
    await fetchAddresses()
    setTimeout(closeAddressForm, 1200)
  } catch (e) {
    const errors = e.response?.data?.errors
    addrMsg.value    = errors ? Object.values(errors).flat().join(' | ') : (e.response?.data?.message ?? 'Erro ao salvar.')
    addrMsgType.value = 'error'
  } finally {
    savingAddress.value = false
  }
}

async function setPrincipalAddr(id) {
  try {
    await axios.patch(`/api/addresses/${id}/principal`, {}, authHeaders())
    await fetchAddresses()
  } catch (e) {
    alert(e.response?.data?.message ?? 'Erro ao definir endereço principal.')
  }
}

async function deleteAddress(id) {
  if (!confirm('Deseja realmente excluir este endereço?')) return
  try {
    await axios.delete(`/api/addresses/${id}`, authHeaders())
    await fetchAddresses()
  } catch (e) {
    alert(e.response?.data?.message ?? 'Erro ao excluir endereço.')
  }
}

async function lookupCep() {
  const cep = addrForm.value.cep.replace(/\D/g, '')
  if (cep.length !== 8) return
  try {
    const res = await axios.get(`/api/cep/${cep}`)
    if (res.data) {
      addrForm.value.logradouro = res.data.logradouro ?? addrForm.value.logradouro
      addrForm.value.bairro     = res.data.bairro     ?? addrForm.value.bairro
      addrForm.value.cidade     = res.data.localidade ?? addrForm.value.cidade
      addrForm.value.estado     = res.data.uf         ?? addrForm.value.estado
    }
  } catch { /* ignora erro de CEP */ }
}

function formatCepInput() {
  let v = addrForm.value.cep.replace(/\D/g, '')
  if (v.length > 5) v = v.slice(0, 5) + '-' + v.slice(5, 8)
  addrForm.value.cep = v
}

// ── Coupons ────────────────────────────────────────────────
const coupons       = ref([])
const loadingCoupons = ref(false)

// ── Computed ───────────────────────────────────────────────
const firstName = computed(() => store.user?.nome_completo?.split(' ')[0] ?? '')

const lastOrder = computed(() => {
  if (!orders.value.length) return null
  return [...orders.value].sort((a, b) => b.id - a.id)[0]
})

const pointsLevel = computed(() => {
  const pts = profile.value?.pontos_saldo ?? 0
  if (pts >= 500) return '🥇 Atleta Nível Ouro!'
  if (pts >= 200) return '🥈 Atleta Nível Prata!'
  if (pts > 0)   return '🥉 Atleta Nível Bronze!'
  return '🏁 Comece a colecionar pontos!'
})

// ── Lifecycle ──────────────────────────────────────────────
onMounted(async () => {
  if (!store.isAuthenticated) { router.push('/login'); return }
  await fetchProfile()
  await fetchOrders()   // carrega silenciosamente para o card de último pedido
  if (route.query.tab) setTab(route.query.tab)
})

// ── Methods ────────────────────────────────────────────────
function authHeaders() {
  return { headers: { Authorization: `Bearer ${store.token}` } }
}

async function fetchProfile() {
  try {
    const res = await axios.get('/api/profile', authHeaders())
    profile.value = res.data.cliente
    editForm.value.nome_completo = profile.value.nome_completo ?? ''
    editForm.value.email         = profile.value.email         ?? ''
    editForm.value.telefone      = profile.value.telefone      ?? ''
    // sincroniza com o store também
    store.user = profile.value
  } catch (e) {
    console.error('Erro ao carregar perfil', e)
  }
}

async function saveProfile() {
  savingProfile.value = true
  profileMsg.value    = ''
  try {
    const payload = {
      nome_completo: editForm.value.nome_completo,
      email:         editForm.value.email,
      telefone:      editForm.value.telefone,
    }
    if (editForm.value.password) {
      payload.password              = editForm.value.password
      payload.password_confirmation = editForm.value.password_confirmation
    }
    await axios.put('/api/profile', payload, authHeaders())
    profileMsg.value    = '✅ Dados atualizados com sucesso!'
    profileMsgType.value = 'success'
    await fetchProfile()
    editForm.value.password              = ''
    editForm.value.password_confirmation = ''
  } catch (e) {
    const errors = e.response?.data?.errors
    if (errors) {
      profileMsg.value = Object.values(errors).flat().join(' | ')
    } else {
      profileMsg.value = e.response?.data?.message ?? 'Erro ao salvar. Tente novamente.'
    }
    profileMsgType.value = 'error'
  } finally {
    savingProfile.value = false
    setTimeout(() => profileMsg.value = '', 5000)
  }
}

async function fetchOrders() {
  loadingOrders.value = true
  try {
    const res = await axios.get('/api/orders', authHeaders())
    orders.value = res.data.pedidos ?? []
  } catch (e) {
    console.error('Erro ao buscar pedidos', e)
  } finally {
    loadingOrders.value = false
  }
}

async function fetchPoints() {
  loadingPoints.value = true
  try {
    const res = await axios.get('/api/points/history', authHeaders())
    pointsHistory.value = res.data.historico ?? []
  } catch {
    pointsHistory.value = []
  } finally {
    loadingPoints.value = false
  }
}

async function fetchCoupons() {
  loadingCoupons.value = true
  try {
    const res = await axios.get('/api/coupons/mine', authHeaders())
    coupons.value = res.data.cupons ?? []
  } catch {
    coupons.value = []
  } finally {
    loadingCoupons.value = false
  }
}

function setTab(tabName) {
  currentTab.value = tabName
  if (tabName === 'pedidos'  && orders.value.length === 0) fetchOrders()
  if (tabName === 'enderecos') fetchAddresses()
  if (tabName === 'pontos')   fetchPoints()
  if (tabName === 'cupons')   fetchCoupons()
}

async function copyReferral() {
  const code = profile.value?.referral_code
  if (!code) return
  try {
    await navigator.clipboard.writeText(code)
    copied.value = true
    setTimeout(() => copied.value = false, 2000)
  } catch {
    alert('Seu código: ' + code)
  }
}

async function payNow(orderId) {
  try {
    const res   = await axios.get('/api/orders/pix-key', authHeaders())
    const chave = res.data.chave_pix
    if (chave) {
      alert(`Para pagar o Pedido #${orderId}, faça um PIX para a chave:\n\n${chave}\n\nApós o pagamento, o administrador confirmará seu pedido!`)
    } else {
      alert('No momento o pagamento deve ser combinado diretamente com a loja. Entre em contato.')
    }
  } catch {
    alert('Não foi possível carregar as opções de pagamento no momento.')
  }
}

function viewOrderDetails(order) {
  // Abre o modal de detalhes ou redireciona
  router.push(`/pagamento/sucesso?order_id=${order.id}`)
}

function logout() {
  store.logout()
  router.push('/login')
}

// ── Formatters ─────────────────────────────────────────────
function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(dateStr))
}

function formatCurrency(value) {
  return 'R$ ' + Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatStatus(status) {
  const labels = {
    aguardando_pagamento: 'Aguardando Pagamento',
    em_separacao:         'Em Separação',
    em_envio:             'Em Envio',
    enviado:              'Enviado',
    entregue:             'Entregue',
    cancelado:            'Cancelado',
    devolvido:            'Devolvido',
  }
  return labels[status] || status
}

function getStatusClass(status) {
  return {
    'text-red':    ['aguardando_pagamento', 'cancelado'].includes(status),
    'text-green':  ['em_separacao', 'em_envio', 'enviado', 'entregue'].includes(status),
    'text-yellow': status === 'em_separacao',
  }
}
</script>

<style scoped>
.account-view {
  padding: var(--spacing-8) var(--spacing-4);
  max-width: 1200px;
}

.account-header {
  margin-bottom: var(--spacing-8);
  border-bottom: 1px solid var(--color-black-lighter);
  padding-bottom: var(--spacing-6);
}

.subtitle { color: var(--color-gray); margin-top: var(--spacing-2); }

.account-layout {
  display: flex;
  gap: var(--spacing-8);
  align-items: flex-start;
}

.account-sidebar {
  width: 250px;
  flex-shrink: 0;
  background-color: var(--color-black-light);
  border-radius: var(--border-radius-sm);
  padding: var(--spacing-4);
}

.account-nav { display: flex; flex-direction: column; }

.nav-btn {
  text-align: left;
  padding: var(--spacing-3);
  color: var(--color-gray);
  font-weight: 500;
  border-radius: var(--border-radius-sm);
  margin-bottom: var(--spacing-1);
}
.nav-btn:hover { background-color: var(--color-black-lighter); color: var(--color-white); }
.nav-btn.active { background-color: var(--color-red); color: var(--color-white); }

.account-content { flex: 1; }

/* ── Stat cards ─────────────── */
.stat-card {
  background-color: var(--color-black-light);
  border: 1px solid var(--color-black-lighter);
  border-radius: var(--border-radius-sm);
  padding: var(--spacing-4);
}
.stat-card h3 {
  font-size: 0.875rem;
  color: var(--color-gray);
  margin-bottom: var(--spacing-2);
  text-transform: none;
}
.stat-value { font-family: var(--font-title); font-size: 1.5rem; }
.stat-sub { font-size: 0.8rem; color: var(--color-gray); margin-top: 2px; }

.status-badge {
  display: inline-block;
  font-size: 0.75rem;
  padding: 2px 8px;
  border-radius: 4px;
  margin-top: 4px;
  background: rgba(227,6,19,0.15);
}
.status-badge.text-green { background: rgba(16,185,129,0.15); }
.status-badge.text-yellow { background: rgba(234,179,8,0.15); }

.copy-box {
  background-color: var(--color-black);
  padding: var(--spacing-2) var(--spacing-3);
  border: 1px dashed var(--color-gray);
  border-radius: var(--border-radius-sm);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}
.copy-box code { color: var(--color-white); font-weight: bold; font-size: 1rem; letter-spacing: 0.05em; }
.copy-hint { font-size: 0.75rem; color: var(--color-gray); white-space: nowrap; }

.link-btn {
  color: var(--color-gray);
  text-decoration: underline;
  font-size: 0.875rem;
  padding: 0;
}
.link-btn:hover { color: var(--color-white); }

/* ── Profile form ─────────── */
.profile-form {
  background-color: var(--color-black-light);
  padding: var(--spacing-6);
  border-radius: var(--border-radius-sm);
}

.profile-msg {
  margin-top: var(--spacing-3);
  padding: var(--spacing-3);
  border-radius: var(--border-radius-sm);
  font-size: 0.875rem;
}
.profile-msg.success { background: rgba(16,185,129,0.12); color: #10b981; }
.profile-msg.error   { background: rgba(227,6,19,0.12);   color: var(--color-red); }

/* ── Orders ───────────────── */
.order-card {
  background-color: var(--color-black-light);
  border: 1px solid var(--color-black-lighter);
  border-radius: var(--border-radius-sm);
}
.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-4);
  background-color: var(--color-black-lighter);
  border-bottom: 1px solid var(--color-black-light);
}
.order-date { color: var(--color-gray); font-size: 0.875rem; margin-left: var(--spacing-4); }
.order-total { font-family: var(--font-title); font-size: 1.25rem; }
.order-body { padding: var(--spacing-4); }
.status { font-weight: 600; }

/* ── Address ──────────────── */
.flex-between { display: flex; justify-content: space-between; align-items: center; }
.address-card {
  background-color: var(--color-black-light);
  border: 1px solid var(--color-black-lighter);
  border-radius: var(--border-radius-sm);
  padding: var(--spacing-4);
}
.address-card p { color: var(--color-gray); font-size: 0.875rem; margin-bottom: 4px; }

/* ── Points ───────────────── */
.points-banner {
  display: flex;
  gap: var(--spacing-6);
  background-color: var(--color-black-light);
  padding: var(--spacing-6);
  border-radius: var(--border-radius-sm);
  align-items: center;
}
.points-circle {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background-color: var(--color-red);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--color-white);
  flex-shrink: 0;
}
.points-circle strong { font-family: var(--font-title); font-size: 1.5rem; }
.points-rule { font-size: 0.875rem; color: var(--color-gray); }
.points-rule span { color: var(--color-gray); }
.points-rule strong { color: var(--color-white); }

.history-table { width: 100%; border-collapse: collapse; }
.history-table th, .history-table td {
  padding: var(--spacing-3);
  text-align: left;
  border-bottom: 1px solid var(--color-black-lighter);
}
.history-table th { color: var(--color-gray); font-weight: 500; }

/* ── Coupons ──────────────── */
.coupon-card {
  display: flex;
  background-color: var(--color-black-light);
  border: 1px dashed var(--color-gray-dark);
  border-radius: var(--border-radius-sm);
  overflow: hidden;
}
.coupon-amount {
  background-color: var(--color-red);
  color: var(--color-white);
  padding: var(--spacing-4);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-title);
  font-size: 1.1rem;
  min-width: 90px;
  text-align: center;
}
.coupon-details { padding: var(--spacing-4); flex: 1; }
.coupon-details p { color: var(--color-gray); font-size: 0.875rem; margin-top: 4px; }
.coupon-code {
  display: inline-block;
  background-color: var(--color-black);
  padding: 4px 8px;
  border-radius: 4px;
  font-family: monospace;
  font-weight: bold;
}

/* ── Helpers ──────────────── */
.text-red    { color: var(--color-red); }
.text-green  { color: #10b981; }
.text-yellow { color: #f59e0b; }
.text-gray   { color: var(--color-gray); }
.mb-6  { margin-bottom: var(--spacing-6); }
.mt-8  { margin-top: var(--spacing-8); }
.mt-3  { margin-top: 0.75rem; }
.border-dark { border-color: var(--color-black-lighter); }

/* ── UI Endereços ───────────────── */
.addr-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: var(--spacing-6);
}
.address-card.addr-principal {
  border-color: #10b981;
  background-color: rgba(16, 185, 129, 0.03);
}
.addr-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-3);
}
.badge-principal {
  font-size: 0.75rem;
  color: #10b981;
  font-weight: bold;
}
.address-actions {
  display: flex;
  gap: var(--spacing-4);
  border-top: 1px solid var(--color-black-lighter);
  padding-top: var(--spacing-3);
}
.addr-form-wrap {
  background-color: var(--color-black-light);
  border: 1px solid var(--color-black-lighter);
  border-radius: var(--border-radius-sm);
  padding: var(--spacing-6);
}


@media (max-width: 768px) {
  .account-layout { flex-direction: column; }
  .account-sidebar { width: 100%; }
  .account-nav { flex-direction: row; overflow-x: auto; padding-bottom: 5px; }
  .nav-btn { white-space: nowrap; }
  .grid-cols-3, .grid-cols-2 { grid-template-columns: 1fr; }
  .points-banner { flex-direction: column; text-align: center; }
}
</style>

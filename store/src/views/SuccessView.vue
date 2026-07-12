<template>
  <div class="success-page" :class="{ 'paid': isPaid, 'pending': !isPaid && !loading }">

    <!-- Partículas de confetti esportivo -->
    <div class="confetti-wrap" aria-hidden="true" v-if="isPaid">
      <span v-for="i in 40" :key="i" class="confetti-piece" :style="confettiStyle(i)"></span>
    </div>

    <!-- Linhas de velocidade de fundo -->
    <div class="speed-lines" aria-hidden="true">
      <span v-for="i in 12" :key="i" class="speed-line" :style="{ '--i': i }"></span>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="sp-loading">
      <div class="sp-spinner">
        <span class="sp-spinner-inner">⚡</span>
      </div>
      <p>Confirmando seu pedido...</p>
    </div>

    <!-- Erro -->
    <div v-else-if="!order" class="sp-error">
      <div class="sp-error-icon">❌</div>
      <h2>Pedido não encontrado</h2>
      <p>Não foi possível carregar as informações do seu pedido.</p>
      <router-link to="/" class="sp-btn sp-btn-primary">Ir para Home</router-link>
    </div>

    <!-- Conteúdo principal -->
    <div v-else class="sp-content">

      <!-- Header de status -->
      <div class="sp-header" :class="isPaid ? 'sp-header--paid' : 'sp-header--pending'">
        <div class="sp-trophy-wrap" aria-hidden="true">
          <div class="sp-trophy">{{ isPaid ? '🏆' : '⏳' }}</div>
          <div class="sp-trophy-ring ring-a"></div>
          <div class="sp-trophy-ring ring-b"></div>
        </div>

        <div class="sp-status-badge" :class="isPaid ? 'badge--green' : 'badge--yellow'">
          {{ isPaid ? '✅ PAGAMENTO CONFIRMADO' : '🕐 AGUARDANDO PAGAMENTO' }}
        </div>

        <h1 class="sp-title" v-if="isPaid">
          Gol! Seu pagamento foi aprovado!
        </h1>
        <h1 class="sp-title" v-else>
          Pedido recebido!
        </h1>

        <p class="sp-subtitle" v-if="isPaid">
          Sua compra foi confirmada com sucesso. Já estamos separando os seus itens para envio. Prepare-se para jogar com estilo! 🔥
        </p>
        <p class="sp-subtitle" v-else>
          Aguardando a confirmação do pagamento. Você receberá uma atualização em breve.
        </p>
      </div>

      <!-- Próximos passos (só quando pago) -->
      <div class="sp-steps" v-if="isPaid">
        <h2 class="sp-section-title">⚡ Próximos Passos</h2>
        <div class="sp-steps-grid">
          <div class="sp-step" style="--s: 0">
            <div class="sp-step-icon">📦</div>
            <div>
              <strong>Em Separação</strong>
              <p>Estamos preparando seus itens com cuidado</p>
            </div>
          </div>
          <div class="sp-step-arrow" aria-hidden="true">→</div>
          <div class="sp-step" style="--s: 1">
            <div class="sp-step-icon">🚚</div>
            <div>
              <strong>Envio</strong>
              <p>Seu pedido será postado nos próximos dias úteis</p>
            </div>
          </div>
          <div class="sp-step-arrow" aria-hidden="true">→</div>
          <div class="sp-step" style="--s: 2">
            <div class="sp-step-icon">🏠</div>
            <div>
              <strong>Entrega</strong>
              <p>Seu produto chega no endereço cadastrado</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Resumo do Pedido -->
      <div class="sp-card">
        <h2 class="sp-section-title">🧾 Resumo do Pedido</h2>

        <div class="sp-info-row">
          <span>Número do Pedido</span>
          <strong class="sp-order-num">{{ order.order_nsu }}</strong>
        </div>
        <div class="sp-info-row">
          <span>Status</span>
          <span class="sp-status-tag" :class="'tag-' + order.status">{{ formatStatus(order.status) }}</span>
        </div>
        <div class="sp-info-row">
          <span>Forma de Pagamento</span>
          <strong>{{ paymentMethodLabel }}</strong>
        </div>
        <div class="sp-info-row" v-if="receiptUrl">
          <span>Comprovante</span>
          <a :href="receiptUrl" target="_blank" rel="noopener" class="sp-link">
            📄 Ver Comprovante
          </a>
        </div>
      </div>

      <!-- Produtos -->
      <div class="sp-card">
        <h2 class="sp-section-title">👟 Produtos Adquiridos</h2>
        <div class="sp-product" v-for="item in order.itens" :key="item.id">
          <div class="sp-product-img" aria-hidden="true">
            <img
              v-if="item.produto?.foto_capa?.caminho"
              :src="`/storage/${item.produto.foto_capa.caminho}`"
              :alt="item.nome_snapshot"
            />
            <span v-else>👕</span>
          </div>
          <div class="sp-product-info">
            <strong>{{ item.nome_snapshot }}</strong>
            <span v-if="item.variacao">{{ item.variacao.atributo_nome }}: {{ item.variacao.atributo_valor }}</span>
            <span>Qtd: {{ item.quantidade }}</span>
          </div>
          <div class="sp-product-price">
            {{ formatCurrency(item.preco_venda_snapshot * item.quantidade) }}
          </div>
        </div>
      </div>

      <!-- Totais -->
      <div class="sp-card">
        <h2 class="sp-section-title">💰 Valores</h2>
        <div class="sp-total-row">
          <span>Subtotal</span>
          <span>{{ formatCurrency(order.subtotal) }}</span>
        </div>
        <div class="sp-total-row sp-discount" v-if="order.desconto_cupom > 0">
          <span>Desconto (cupom)</span>
          <span>- {{ formatCurrency(order.desconto_cupom) }}</span>
        </div>
        <div class="sp-total-row sp-discount" v-if="order.desconto_pontos > 0">
          <span>Desconto (pontos)</span>
          <span>- {{ formatCurrency(order.desconto_pontos) }}</span>
        </div>
        <div class="sp-total-row">
          <span>Frete</span>
          <span>{{ order.valor_frete > 0 ? formatCurrency(order.valor_frete) : 'Grátis' }}</span>
        </div>
        <div class="sp-total-row sp-grand-total">
          <strong>Total Pago</strong>
          <strong class="sp-grand-value">{{ formatCurrency(order.total) }}</strong>
        </div>
      </div>

      <!-- Endereço de entrega -->
      <div class="sp-card" v-if="order.endereco">
        <h2 class="sp-section-title">📍 Endereço de Entrega</h2>
        <p class="sp-address">
          {{ order.endereco.logradouro }}, {{ order.endereco.numero }}
          <span v-if="order.endereco.complemento"> - {{ order.endereco.complemento }}</span><br>
          {{ order.endereco.bairro }} — {{ order.endereco.cidade }}/{{ order.endereco.estado }}<br>
          CEP: {{ order.endereco.cep }}
        </p>
      </div>

      <!-- WhatsApp -->
      <div class="sp-whatsapp-card">
        <div class="sp-wa-icon" aria-hidden="true">💬</div>
        <div class="sp-wa-text">
          <strong>Ficou com dúvidas sobre seu pedido?</strong>
          <p>Nossa equipe está pronta para te atender! Fale com a <strong>90+ Store</strong> pelo WhatsApp e tire todas as suas dúvidas rapidinho.</p>
        </div>
        <a
          :href="whatsappLink"
          target="_blank"
          rel="noopener"
          class="sp-btn sp-btn-whatsapp"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
          </svg>
          Falar no WhatsApp
        </a>
      </div>

      <!-- Botões de ação -->
      <div class="sp-actions">
        <router-link to="/" class="sp-btn sp-btn-primary">
          🏠 Voltar à Loja
        </router-link>
        <router-link to="/minha-conta" class="sp-btn sp-btn-outline">
          📋 Meus Pedidos
        </router-link>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useHead } from '@vueuse/head'
import axios from 'axios'

useHead({
  title: 'Pedido Confirmado | 90+ Store',
  meta: [{ name: 'description', content: 'Seu pedido foi confirmado com sucesso na 90+ Store.' }],
})

const route  = useRoute()
const order  = ref(null)
const loading = ref(true)

// WhatsApp fixo da loja (fallback) — será sobrescrito se vier das configurações
const storeWhatsApp = ref('5511940112438')

const isPaid = computed(() => {
  if (!order.value) return false
  const paid = ['em_separacao', 'em_envio', 'enviado', 'entregue']
  if (paid.includes(order.value.status)) return true
  // Verifica se tem pagamento aprovado
  return order.value.pagamentos?.some(p => p.status === 'aprovado') ?? false
})

const receiptUrl = computed(() => {
  if (!order.value?.pagamentos) return null
  const pago = order.value.pagamentos.find(p => p.status === 'aprovado')
  if (!pago) return null
  try {
    const parsed = typeof pago.payload_json === 'string'
      ? JSON.parse(pago.payload_json)
      : pago.payload_json
    return parsed?.receipt_url ?? null
  } catch { return null }
})

const paymentMethodLabel = computed(() => {
  const method = route.query.capture_method
  if (method === 'pix') return 'Pix'
  if (method === 'credit_card') return 'Cartão de Crédito'
  if (method === 'debit_card') return 'Cartão de Débito'
  return 'InfinitePay'
})

const whatsappLink = computed(() => {
  const num = storeWhatsApp.value.replace(/\D/g, '')
  const pedNum = order.value?.order_nsu ?? ''
  const msg = encodeURIComponent(
    `Olá, 90+ Store! Tenho uma dúvida sobre meu pedido *${pedNum}*. Pode me ajudar?`
  )
  return `https://wa.me/${num}?text=${msg}`
})

onMounted(async () => {
  const orderId = route.query.order_id
  if (!orderId) { loading.value = false; return }

  try {
    // Busca configurações da loja para pegar WhatsApp
    axios.get('/api/store-settings').then(r => {
      if (r.data?.whatsapp) storeWhatsApp.value = r.data.whatsapp
    }).catch(() => {})

    const res = await axios.get(`/api/orders/public/${orderId}`, { params: route.query })
    if (res.data.success) order.value = res.data.pedido
  } catch (err) {
    console.error('Erro ao buscar pedido', err)
  } finally {
    loading.value = false
  }
})

function formatStatus(status) {
  const map = {
    aguardando_pagamento: 'Aguardando Pagamento',
    em_separacao:         'Em Separação ✅',
    em_envio:             'Em Envio 🚚',
    enviado:              'Enviado 📬',
    entregue:             'Entregue 🏠',
    cancelado:            'Cancelado',
    devolvido:            'Devolvido',
  }
  return map[status] || status
}

function formatCurrency(value) {
  return 'R$ ' + Number(value || 0).toLocaleString('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

// Estilos aleatórios para confetti
const confettiColors = ['#ff2d2d','#ff9f43','#ffd32a','#0be881','#00d2ff','#c4b5fd','#f368e0','#ffffff']
function confettiStyle(i) {
  const seed = i * 137.508
  return {
    left:              `${(seed % 98) + 1}%`,
    width:             `${6 + (seed % 8)}px`,
    height:            `${10 + (seed % 14)}px`,
    background:        confettiColors[i % confettiColors.length],
    animationDuration: `${1.5 + (seed % 2.5)}s`,
    animationDelay:    `${(seed % 1.2)}s`,
    borderRadius:      i % 3 === 0 ? '50%' : '2px',
    transform:         `rotate(${seed % 360}deg)`,
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap');

/* ── Base ─────────────────────────────────────────────────── */
.success-page {
  min-height: 100vh;
  position: relative;
  overflow: hidden;
  background: #080c14;
  font-family: 'Inter', sans-serif;
  padding: 2rem 1rem 4rem;
}

/* ── Linhas de velocidade ────────────────────────────────── */
.speed-lines {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
  overflow: hidden;
}
.speed-line {
  position: absolute;
  top: 0;
  left: calc(var(--i) * 8% + 2%);
  width: 1px;
  height: 100%;
  background: linear-gradient(to bottom, transparent 0%, #e5000022 30%, #e5000008 70%, transparent 100%);
  animation: speedPulse 3s ease-in-out infinite;
  animation-delay: calc(var(--i) * 0.25s);
}
@keyframes speedPulse {
  0%, 100% { opacity: 0.3; transform: scaleY(1); }
  50%       { opacity: 0.8; transform: scaleY(1.05); }
}

/* ── Confetti ─────────────────────────────────────────────── */
.confetti-wrap {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 5;
}
.confetti-piece {
  position: absolute;
  top: -20px;
  animation: confettiFall linear forwards;
}
@keyframes confettiFall {
  0%   { transform: translateY(-10px) rotate(0deg); opacity: 1; }
  100% { transform: translateY(110vh) rotate(720deg); opacity: 0; }
}

/* ── Loading ──────────────────────────────────────────────── */
.sp-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  gap: 1.5rem;
  color: #94a3b8;
  position: relative;
  z-index: 10;
}
.sp-spinner {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  border: 3px solid #e5000022;
  border-top-color: #e50000;
  animation: spin 1s linear infinite;
  display: flex;
  align-items: center;
  justify-content: center;
}
@keyframes spin { to { transform: rotate(360deg); } }
.sp-spinner-inner { font-size: 1.5rem; }

/* ── Erro ─────────────────────────────────────────────────── */
.sp-error {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 4rem 1rem;
  text-align: center;
  color: #94a3b8;
  position: relative;
  z-index: 10;
}
.sp-error-icon { font-size: 3rem; }
.sp-error h2 { color: #e2e8f0; font-size: 1.5rem; }

/* ── Conteúdo ─────────────────────────────────────────────── */
.sp-content {
  position: relative;
  z-index: 10;
  max-width: 680px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  animation: fadeUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(30px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ── Header ───────────────────────────────────────────────── */
.sp-header {
  text-align: center;
  padding: 2.5rem 1.5rem 2rem;
  border-radius: 16px;
  border: 1px solid #1e293b;
  position: relative;
  overflow: hidden;
}
.sp-header--paid {
  background: linear-gradient(135deg, #0a1628 0%, #0d1f0a 100%);
  border-color: #16a34a44;
}
.sp-header--pending {
  background: linear-gradient(135deg, #0a1628 0%, #1a1200 100%);
  border-color: #ca8a0444;
}
.sp-header::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, #e50000, #ff6b6b, #e50000);
  background-size: 200% 100%;
  animation: shimmer 2s linear infinite;
}
.sp-header--paid::before {
  background: linear-gradient(90deg, #16a34a, #4ade80, #16a34a);
  background-size: 200% 100%;
}
@keyframes shimmer { to { background-position: -200% 0; } }

/* ── Troféu ───────────────────────────────────────────────── */
.sp-trophy-wrap {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
}
.sp-trophy {
  font-size: 3.5rem;
  animation: trophyBounce 2s ease-in-out infinite;
  filter: drop-shadow(0 0 20px #ffd32a88);
  position: relative;
  z-index: 2;
}
@keyframes trophyBounce {
  0%, 100% { transform: translateY(0) scale(1); }
  50%       { transform: translateY(-8px) scale(1.05); }
}
.sp-trophy-ring {
  position: absolute;
  border-radius: 50%;
  border: 2px solid;
  animation: ringExpand 2s ease-out infinite;
}
.ring-a { width: 80px;  height: 80px;  border-color: #ffd32a44; animation-delay: 0s; }
.ring-b { width: 110px; height: 110px; border-color: #ffd32a22; animation-delay: 0.5s; }
@keyframes ringExpand {
  0%   { opacity: 0.8; transform: scale(0.8); }
  100% { opacity: 0;   transform: scale(1.3); }
}

/* ── Status badge ─────────────────────────────────────────── */
.sp-status-badge {
  display: inline-block;
  padding: 0.35rem 1rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  margin-bottom: 1rem;
}
.badge--green { background: #16a34a22; color: #4ade80; border: 1px solid #16a34a44; }
.badge--yellow { background: #ca8a0422; color: #fbbf24; border: 1px solid #ca8a0444; }

.sp-title {
  font-family: 'Bebas Neue', sans-serif;
  font-size: clamp(1.8rem, 6vw, 3rem);
  color: #f1f5f9;
  letter-spacing: 0.04em;
  margin: 0 0 0.75rem;
  line-height: 1.1;
}
.sp-subtitle {
  color: #94a3b8;
  font-size: 0.97rem;
  line-height: 1.7;
  max-width: 480px;
  margin: 0 auto;
}

/* ── Próximos passos ──────────────────────────────────────── */
.sp-steps {
  background: #0d1117;
  border: 1px solid #1e293b;
  border-radius: 16px;
  padding: 1.5rem;
}
.sp-section-title {
  font-size: 0.9rem;
  font-weight: 700;
  color: #e2e8f0;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  margin: 0 0 1.25rem;
}
.sp-steps-grid {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.sp-step {
  flex: 1;
  min-width: 120px;
  background: #111827;
  border: 1px solid #1e293b;
  border-radius: 12px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 0.5rem;
  animation: stepIn 0.5s cubic-bezier(0.16,1,0.3,1) both;
  animation-delay: calc(var(--s) * 0.15s + 0.3s);
}
@keyframes stepIn {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}
.sp-step-icon { font-size: 1.75rem; }
.sp-step strong { font-size: 0.85rem; color: #e2e8f0; display: block; }
.sp-step p { font-size: 0.75rem; color: #64748b; margin: 0.25rem 0 0; }
.sp-step-arrow { color: #e50000; font-size: 1.25rem; font-weight: 700; flex-shrink: 0; }

/* ── Cards ────────────────────────────────────────────────── */
.sp-card {
  background: #0d1117;
  border: 1px solid #1e293b;
  border-radius: 16px;
  padding: 1.5rem;
}

/* ── Info rows ────────────────────────────────────────────── */
.sp-info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid #1e293b;
  font-size: 0.9rem;
  color: #94a3b8;
  gap: 1rem;
}
.sp-info-row:last-child { border-bottom: none; }
.sp-info-row strong, .sp-info-row span:last-child { color: #e2e8f0; text-align: right; }
.sp-order-num { font-family: 'Bebas Neue', sans-serif; font-size: 1.1rem; color: #e50000 !important; letter-spacing: 0.05em; }

/* ── Status tag ───────────────────────────────────────────── */
.sp-status-tag {
  padding: 0.25rem 0.75rem;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 600;
}
.tag-aguardando_pagamento { background: #ca8a0422; color: #fbbf24; }
.tag-em_separacao         { background: #16a34a22; color: #4ade80; }
.tag-em_envio             { background: #2563eb22; color: #60a5fa; }
.tag-enviado              { background: #7c3aed22; color: #a78bfa; }
.tag-entregue             { background: #16a34a44; color: #86efac; }
.tag-cancelado            { background: #dc262622; color: #f87171; }

.sp-link { color: #e50000; text-decoration: none; font-size: 0.9rem; transition: opacity 0.2s; }
.sp-link:hover { opacity: 0.8; }

/* ── Produtos ─────────────────────────────────────────────── */
.sp-product {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.85rem 0;
  border-bottom: 1px solid #1e293b;
}
.sp-product:last-child { border-bottom: none; }
.sp-product-img {
  width: 52px;
  height: 52px;
  border-radius: 8px;
  background: #111827;
  border: 1px solid #1e293b;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
  overflow: hidden;
}
.sp-product-img img { width: 100%; height: 100%; object-fit: cover; }
.sp-product-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  font-size: 0.875rem;
  color: #94a3b8;
}
.sp-product-info strong { color: #e2e8f0; font-size: 0.9rem; }
.sp-product-price { font-weight: 700; color: #e2e8f0; font-size: 0.95rem; white-space: nowrap; }

/* ── Totais ───────────────────────────────────────────────── */
.sp-total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.6rem 0;
  font-size: 0.9rem;
  color: #94a3b8;
  border-bottom: 1px solid #1e293b11;
}
.sp-discount { color: #4ade80; }
.sp-grand-total {
  border-top: 1px solid #1e293b;
  border-bottom: none;
  margin-top: 0.5rem;
  padding-top: 1rem;
}
.sp-grand-total strong { color: #e2e8f0; font-size: 1rem; }
.sp-grand-value { color: #e50000 !important; font-size: 1.3rem; }

/* ── Endereço ─────────────────────────────────────────────── */
.sp-address { font-size: 0.9rem; color: #94a3b8; line-height: 1.8; }

/* ── WhatsApp card ────────────────────────────────────────── */
.sp-whatsapp-card {
  background: linear-gradient(135deg, #052e16, #0a1628);
  border: 1px solid #16a34a44;
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}
.sp-wa-icon { font-size: 2.5rem; flex-shrink: 0; }
.sp-wa-text { flex: 1; min-width: 180px; }
.sp-wa-text strong { color: #e2e8f0; font-size: 0.95rem; display: block; margin-bottom: 0.35rem; }
.sp-wa-text p { color: #94a3b8; font-size: 0.85rem; line-height: 1.6; margin: 0; }
.sp-wa-text p strong { display: inline; color: #4ade80; }

/* ── Botões ───────────────────────────────────────────────── */
.sp-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}
.sp-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.8rem 1.5rem;
  border-radius: 10px;
  font-size: 0.9rem;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
  border: none;
  white-space: nowrap;
}
.sp-btn-primary {
  background: linear-gradient(135deg, #e50000, #b91c1c);
  color: #fff;
  flex: 1;
  min-width: 160px;
  box-shadow: 0 4px 20px #e5000033;
}
.sp-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 28px #e5000055; }
.sp-btn-outline {
  background: transparent;
  color: #94a3b8;
  border: 1px solid #1e293b;
  flex: 1;
  min-width: 140px;
}
.sp-btn-outline:hover { background: #1e293b; color: #e2e8f0; transform: translateY(-2px); }
.sp-btn-whatsapp {
  background: linear-gradient(135deg, #16a34a, #15803d);
  color: #fff;
  box-shadow: 0 4px 18px #16a34a44;
  flex-shrink: 0;
}
.sp-btn-whatsapp:hover { transform: translateY(-2px); box-shadow: 0 8px 28px #16a34a66; }

/* ── Responsivo ───────────────────────────────────────────── */
@media (max-width: 520px) {
  .sp-steps-grid { flex-direction: column; }
  .sp-step-arrow { transform: rotate(90deg); }
  .sp-whatsapp-card { flex-direction: column; text-align: center; }
  .sp-wa-icon { font-size: 2rem; }
  .sp-btn-whatsapp { width: 100%; }
  .sp-actions { flex-direction: column; }
}
</style>

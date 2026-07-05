<template>
  <div class="account-view container">
    <div class="account-header">
      <h1 class="title-lg">MINHA CONTA</h1>
      <p class="subtitle">Bem-vindo(a) de volta, João Teste!</p>
    </div>

    <div class="account-layout">
      <!-- Sidebar / Tabs -->
      <aside class="account-sidebar">
        <nav class="account-nav">
          <button class="nav-btn" :class="{ active: currentTab === 'dashboard' }" @click="currentTab = 'dashboard'">Painel</button>
          <button class="nav-btn" :class="{ active: currentTab === 'pedidos' }" @click="currentTab = 'pedidos'">Meus Pedidos</button>
          <button class="nav-btn" :class="{ active: currentTab === 'enderecos' }" @click="currentTab = 'enderecos'">Endereços</button>
          <button class="nav-btn" :class="{ active: currentTab === 'pontos' }" @click="currentTab = 'pontos'">Pontos & Fidelidade</button>
          <button class="nav-btn" :class="{ active: currentTab === 'cupons' }" @click="currentTab = 'cupons'">Meus Cupons</button>
          <button class="nav-btn text-red" @click="logout">Sair</button>
        </nav>
      </aside>

      <!-- Conteúdo da Tab -->
      <main class="account-content">
        
        <!-- Dashboard -->
        <div v-if="currentTab === 'dashboard'" class="tab-pane">
          <h2 class="title-md mb-6">Visão Geral</h2>
          <div class="grid grid-cols-3 gap-6">
            <div class="stat-card">
              <h3>Saldo de Pontos</h3>
              <p class="stat-value text-red">1.450 pts</p>
              <button class="link-btn mt-2" @click="currentTab = 'pontos'">Ver detalhes</button>
            </div>
            <div class="stat-card">
              <h3>Último Pedido</h3>
              <p class="stat-value">#99201</p>
              <p class="status-badge">Aguardando Pagamento</p>
              <button class="link-btn mt-2" @click="currentTab = 'pedidos'">Rastrear</button>
            </div>
            <div class="stat-card">
              <h3>Indique e Ganhe</h3>
              <p>Compartilhe seu código:</p>
              <div class="copy-box mt-2">
                <code>JOAO10OFF</code>
              </div>
            </div>
          </div>
          
          <h3 class="title-sm mt-8 mb-4">Dados Cadastrais</h3>
          <form class="profile-form">
            <div class="grid grid-cols-2 gap-4">
              <div class="input-group">
                <label class="input-label">Nome Completo</label>
                <input type="text" value="João Teste" class="input-field" />
              </div>
              <div class="input-group">
                <label class="input-label">CPF</label>
                <input type="text" value="123.***.***-00" class="input-field" disabled />
              </div>
              <div class="input-group">
                <label class="input-label">E-mail</label>
                <input type="email" value="teste@email.com" class="input-field" />
              </div>
              <div class="input-group">
                <label class="input-label">Telefone</label>
                <input type="text" value="(11) 99999-9999" class="input-field" />
              </div>
            </div>
            <button type="submit" class="btn btn-outline mt-4">Salvar Alterações</button>
          </form>
        </div>

        <!-- Pedidos -->
        <div v-if="currentTab === 'pedidos'" class="tab-pane">
          <h2 class="title-md mb-6">Histórico de Pedidos</h2>
          
          <div class="order-list">
            <div class="order-card">
              <div class="order-header">
                <div>
                  <strong>Pedido #99201</strong>
                  <span class="order-date">10/10/2025</span>
                </div>
                <div class="order-total">R$ 899,90</div>
              </div>
              <div class="order-body">
                <p>Status: <span class="status text-red">Aguardando Pagamento PIX</span></p>
                <div class="mt-4">
                  <button class="btn btn-primary" @click="$router.push('/checkout')">PAGAR AGORA</button>
                </div>
              </div>
            </div>

            <div class="order-card mt-4">
              <div class="order-header">
                <div>
                  <strong>Pedido #88120</strong>
                  <span class="order-date">01/09/2025</span>
                </div>
                <div class="order-total">R$ 349,90</div>
              </div>
              <div class="order-body">
                <p>Status: <span class="status text-green">Entregue</span></p>
                <div class="mt-4">
                  <button class="btn btn-outline">Ver Recibo</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Endereços -->
        <div v-if="currentTab === 'enderecos'" class="tab-pane">
          <div class="flex-between mb-6">
            <h2 class="title-md">Endereços Salvos</h2>
            <button class="btn btn-outline">+ Novo Endereço</button>
          </div>
          
          <div class="grid grid-cols-2 gap-6">
            <div class="address-card">
              <div class="badge badge-dark mb-2">Principal</div>
              <h3>Casa</h3>
              <p>Av. Paulista, 1000 - Apto 42</p>
              <p>Bela Vista - São Paulo, SP</p>
              <p>CEP: 01310-100</p>
              <div class="address-actions mt-4">
                <button class="link-btn">Editar</button>
                <button class="link-btn text-red">Excluir</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Pontos -->
        <div v-if="currentTab === 'pontos'" class="tab-pane">
          <h2 class="title-md mb-6">Pontos & Fidelidade</h2>
          <div class="points-banner">
            <div class="points-circle">
              <strong>1.450</strong>
              <span>Pts</span>
            </div>
            <div class="points-info">
              <h3>Você é um Atleta Nível Ouro!</h3>
              <p>Seus pontos valem <strong>R$ 14,50</strong> de desconto na próxima compra.</p>
              <button class="btn btn-primary mt-4">Resgatar Pontos</button>
            </div>
          </div>

          <h3 class="title-sm mt-8 mb-4">Extrato</h3>
          <table class="history-table">
            <thead>
              <tr>
                <th>Data</th>
                <th>Descrição</th>
                <th>Pontos</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>01/09/2025</td>
                <td>Compra Pedido #88120</td>
                <td class="text-green">+ 350</td>
              </tr>
              <tr>
                <td>15/08/2025</td>
                <td>Indicação (Amigo comprou)</td>
                <td class="text-green">+ 1100</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Cupons -->
        <div v-if="currentTab === 'cupons'" class="tab-pane">
          <h2 class="title-md mb-6">Meus Cupons</h2>
          
          <div class="grid grid-cols-2 gap-6">
            <div class="coupon-card">
              <div class="coupon-amount">10% OFF</div>
              <div class="coupon-details">
                <h3>Cupom de Boas Vindas</h3>
                <p>Válido para a primeira compra acima de R$ 200.</p>
                <div class="coupon-code mt-2">BEMVINDO10</div>
              </div>
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useHead } from '@vueuse/head'

useHead({ title: 'Minha Conta | 90+ Store' })

const router = useRouter()
const currentTab = ref('dashboard')

function logout() {
  alert('Você saiu da sua conta.')
  router.push('/login')
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

.subtitle {
  color: var(--color-gray);
  margin-top: var(--spacing-2);
}

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

.account-nav {
  display: flex;
  flex-direction: column;
}

.nav-btn {
  text-align: left;
  padding: var(--spacing-3);
  color: var(--color-gray);
  font-weight: 500;
  border-radius: var(--border-radius-sm);
  margin-bottom: var(--spacing-1);
}

.nav-btn:hover {
  background-color: var(--color-black-lighter);
  color: var(--color-white);
}

.nav-btn.active {
  background-color: var(--color-red);
  color: var(--color-white);
}

.account-content {
  flex: 1;
}

/* Dashboard */
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

.stat-value {
  font-family: var(--font-title);
  font-size: 1.5rem;
}

.status-badge {
  display: inline-block;
  font-size: 0.75rem;
  background-color: rgba(227, 6, 19, 0.2);
  color: var(--color-red);
  padding: 2px 6px;
  border-radius: 4px;
  margin-top: 4px;
}

.copy-box {
  background-color: var(--color-black);
  padding: var(--spacing-2);
  border: 1px dashed var(--color-gray);
  text-align: center;
  border-radius: var(--border-radius-sm);
}

.copy-box code {
  color: var(--color-white);
  font-weight: bold;
}

.link-btn {
  color: var(--color-gray);
  text-decoration: underline;
  font-size: 0.875rem;
  padding: 0;
}

.link-btn:hover {
  color: var(--color-white);
}

/* Form */
.profile-form {
  background-color: var(--color-black-light);
  padding: var(--spacing-6);
  border-radius: var(--border-radius-sm);
}

/* Orders */
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

.order-date {
  color: var(--color-gray);
  font-size: 0.875rem;
  margin-left: var(--spacing-4);
}

.order-total {
  font-family: var(--font-title);
  font-size: 1.25rem;
}

.order-body {
  padding: var(--spacing-4);
}

.status {
  font-weight: 600;
}

/* Address */
.flex-between {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.address-card {
  background-color: var(--color-black-light);
  border: 1px solid var(--color-black-lighter);
  border-radius: var(--border-radius-sm);
  padding: var(--spacing-4);
}

.address-card h3 {
  margin-bottom: var(--spacing-2);
}

.address-card p {
  color: var(--color-gray);
  font-size: 0.875rem;
  margin-bottom: 4px;
}

.address-actions {
  display: flex;
  gap: var(--spacing-4);
}

/* Points */
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
}

.points-circle strong {
  font-family: var(--font-title);
  font-size: 1.5rem;
}

.history-table {
  width: 100%;
  border-collapse: collapse;
}

.history-table th, .history-table td {
  padding: var(--spacing-3);
  text-align: left;
  border-bottom: 1px solid var(--color-black-lighter);
}

.history-table th {
  color: var(--color-gray);
  font-weight: 500;
}

/* Coupons */
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
  font-size: 1.25rem;
  width: 100px;
  text-align: center;
}

.coupon-details {
  padding: var(--spacing-4);
  flex: 1;
}

.coupon-details p {
  color: var(--color-gray);
  font-size: 0.875rem;
  margin-top: 4px;
}

.coupon-code {
  display: inline-block;
  background-color: var(--color-black);
  padding: 4px 8px;
  border-radius: 4px;
  font-family: monospace;
  font-weight: bold;
}

.text-red { color: var(--color-red); }
.text-green { color: #10b981; }

.mb-6 { margin-bottom: var(--spacing-6); }
.mt-8 { margin-top: var(--spacing-8); }

@media (max-width: 768px) {
  .account-layout { flex-direction: column; }
  .account-sidebar { width: 100%; }
  .account-nav { flex-direction: row; overflow-x: auto; padding-bottom: 5px; }
  .nav-btn { white-space: nowrap; }
  .grid-cols-3, .grid-cols-2 { grid-template-columns: 1fr; }
  .points-banner { flex-direction: column; text-align: center; }
}
</style>

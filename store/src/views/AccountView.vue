<template>
  <div class="card p-4">
    <h2>👤 Minha Conta</h2>
    
    <div v-if="loading" class="text-center py-6">Carregando dados da conta...</div>
    
    <div v-else-if="user" class="account-dashboard mt-4">
      <div class="grid-2">
        <div class="profile-summary">
          <h3>Dados Pessoais</h3>
          <p class="mt-2"><strong>Nome:</strong> {{ user.nome_completo }}</p>
          <p><strong>E-mail:</strong> {{ user.email }}</p>
          <p><strong>CPF:</strong> ***.***.***-** (Protegido por Criptografia)</p>
          <p><strong>Telefone:</strong> {{ user.telefone || 'N/A' }}</p>
          <p><strong>WhatsApp:</strong> {{ user.whatsapp || 'N/A' }}</p>
        </div>

        <div class="loyalty-summary text-center" style="background: var(--bg-input); padding: 1.5rem; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
          <h3>Fidelidade & Indicações</h3>
          <div class="points-circle mt-4">
            <span class="points-num">{{ user.pontos_saldo }}</span>
            <span class="points-lbl">pontos acumulados</span>
          </div>
          <div class="referral-box mt-4">
            <span class="text-secondary" style="font-size: 0.8125rem;">Seu código de indicação:</span>
            <input type="text" readonly :value="user.referral_code" class="store-input text-center mt-1" style="font-weight: 700; letter-spacing: 0.1em;" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useStore } from '@/store/main'
import axios from 'axios'

const store = useStore()
const user = ref(null)
const loading = ref(true)

onMounted(async () => {
  try {
    const res = await axios.get('/api/profile', {
      headers: { Authorization: `Bearer ${store.token}` }
    })
    user.value = res.data.cliente
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.points-circle {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: var(--brand-glow);
  border: 2px solid var(--brand-primary);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
}

.points-num {
  font-size: 2rem;
  font-weight: 900;
  color: var(--accent-cyan);
  line-height: 1;
}

.points-lbl {
  font-size: 0.6875rem;
  color: var(--text-secondary);
}
</style>

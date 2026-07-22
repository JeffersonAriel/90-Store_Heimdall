<template>
  <div class="login-universe">
    <!-- Animated Norse background -->
    <div class="bg-lightning"></div>
    <div class="bg-runes">
      <span class="rune" v-for="r in runes" :key="r.char" :style="r.style">{{ r.char }}</span>
    </div>
    <div class="bifrost-beam"></div>

    <div class="login-wrapper">
      <!-- Left panel: branding -->
      <div class="brand-panel">
        <div class="brand-content">
          <div class="brand-logo-wrap">
            <div class="brand-halo"></div>
            <img :src="logoUrl" class="brand-logo" alt="Heimdall" />
          </div>
          <h1 class="brand-title">HEIMDALL</h1>
          <p class="brand-subtitle">O Guardião dos Nove Mundos</p>
          <div class="brand-divider">
            <span class="rune-line">ᚺ ᛖ ᛁ ᛗ ᛞ ᚨ ᛚ ᛚ</span>
          </div>
          <p class="brand-desc">
            Visão além do horizonte.<br>
            Controle total do seu negócio.
          </p>
          <div class="brand-stats">
            <div class="stat-item">
              <span class="stat-icon">⚡</span>
              <span class="stat-text">Vigilância Total</span>
            </div>
            <div class="stat-item">
              <span class="stat-icon">🔱</span>
              <span class="stat-text">Poder Nórdico</span>
            </div>
            <div class="stat-item">
              <span class="stat-icon">🌌</span>
              <span class="stat-text">Controle Absoluto</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right panel: login form -->
      <div class="form-panel">
        <div class="login-card">
          <div class="card-top-bar"></div>

          <div class="login-header">
            <div class="mobile-logo">
              <img :src="logoUrl" class="mobile-logo-img" alt="Heimdall" />
            </div>
            <h2 class="card-title">Acesso ao Sistema</h2>
            <p class="card-subtitle">Apenas os dignos podem entrar</p>
          </div>

          <div v-if="errorMsg" class="alert-norse">
            <span class="alert-rune">ᚾ</span> {{ errorMsg }}
          </div>

          <form @submit.prevent="submit" class="login-form">
            <div class="field-group">
              <label class="field-label" for="email">
                <span class="field-rune">ᛖ</span> E-mail
              </label>
              <div class="field-wrap">
                <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                  <polyline points="22,6 12,13 2,6"/>
                </svg>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  class="field-input"
                  :class="{ 'field-error': errors.email }"
                  placeholder="seu@email.com"
                  required
                  autocomplete="username"
                />
              </div>
              <span v-if="errors.email" class="error-msg">{{ errors.email }}</span>
            </div>

            <div class="field-group">
              <label class="field-label" for="password">
                <span class="field-rune">ᛊ</span> Senha
              </label>
              <div class="field-wrap">
                <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPass ? 'text' : 'password'"
                  class="field-input"
                  :class="{ 'field-error': errors.password }"
                  placeholder="••••••••"
                  required
                  autocomplete="current-password"
                />
                <button type="button" class="toggle-pass" @click="showPass = !showPass">
                  <svg v-if="!showPass" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                    <line x1="1" y1="1" x2="23" y2="23"/>
                  </svg>
                </button>
              </div>
              <span v-if="errors.password" class="error-msg">{{ errors.password }}</span>
            </div>

            <div class="remember-row">
              <label class="remember-label">
                <input v-model="form.remember" type="checkbox" class="remember-check" id="remember" />
                <span class="remember-custom"></span>
                <span>Manter sessão ativa</span>
              </label>
            </div>

            <!-- Cloudflare Turnstile CAPTCHA -->
            <div v-if="siteKey" class="turnstile-wrapper">
              <div
                ref="turnstileContainer"
                class="cf-turnstile"
                :data-sitekey="siteKey"
                data-theme="dark"
                data-action="turnstile-spin-v2"
              ></div>
            </div>

            <button type="submit" class="btn-asgard" :disabled="form.processing">
              <span class="btn-lightning" v-if="!form.processing">⚡</span>
              <span class="btn-spinner" v-if="form.processing">
                <svg viewBox="0 0 24 24" class="spin-icon"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" stroke-dasharray="31.4" stroke-dashoffset="10"/></svg>
              </span>
              {{ form.processing ? 'Verificando...' : 'Entrar em Asgard' }}
            </button>
          </form>

          <div class="card-footer">
            <span class="footer-runes">ᚺ ᛖ ᛁ ᛗ ᛞ ᚨ ᛚ ᛚ</span>
            <p>Sistema de Gestão — 90-Store</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

const props = defineProps({
  turnstileSiteKey: {
    type: String,
    default: ''
  }
})

const page = usePage()
const errorMsg = computed(() => page.props.flash?.error || '')
const showPass = ref(false)
const turnstileContainer = ref(null)

const siteKey = computed(() => props.turnstileSiteKey || '0x4AAAAAAD7aAREUvmBJOxtu')

const form = useForm({
  email: '',
  password: '',
  remember: false,
  'cf-turnstile-response': '',
})

const errors = computed(() => form.errors)

onMounted(() => {
  if (typeof window !== 'undefined' && siteKey.value) {
    const checkTurnstile = setInterval(() => {
      if (window.turnstile && turnstileContainer.value) {
        clearInterval(checkTurnstile)
        try {
          window.turnstile.render(turnstileContainer.value, {
            sitekey: siteKey.value,
            theme: 'dark',
            callback: (token) => {
              form['cf-turnstile-response'] = token
            },
            'expired-callback': () => {
              form['cf-turnstile-response'] = ''
            }
          })
        } catch (e) {
          // Ignora se já estiver renderizado
        }
      }
    }, 200)

    setTimeout(() => clearInterval(checkTurnstile), 5000)
  }
})

function submit() {
  // Se o Turnstile tiver um input no DOM com a resposta
  const turnstileInput = document.querySelector('[name="cf-turnstile-response"]')
  if (turnstileInput && turnstileInput.value) {
    form['cf-turnstile-response'] = turnstileInput.value
  }

  form.post(route('admin.login.post'), {
    onFinish: () => form.reset('password'),
  })
}

const basePath = typeof window !== 'undefined' && window.location.pathname.includes('/~jeff2892') ? '/~jeff2892' : ''
const logoUrl = `${basePath}/logo-heimdall.png?v=4`

// Norse runes floating in background
const runeChars = ['ᚺ','ᛖ','ᛁ','ᛗ','ᛞ','ᚨ','ᛚ','ᚠ','ᚢ','ᚦ','ᚱ','ᚲ','ᚷ','ᚹ','ᚾ','ᛁ','ᛃ','ᛇ','ᛈ','ᛉ','ᛏ','ᛒ','ᛗ','ᛚ','ᛜ','ᛞ','ᛟ']
const runes = Array.from({ length: 20 }, (_, i) => ({
  char: runeChars[i % runeChars.length],
  style: {
    left: `${Math.random() * 100}%`,
    top: `${Math.random() * 100}%`,
    animationDelay: `${Math.random() * 8}s`,
    animationDuration: `${6 + Math.random() * 6}s`,
    fontSize: `${14 + Math.random() * 20}px`,
    opacity: 0.03 + Math.random() * 0.07,
  }
}))
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&display=swap');

/* ──── Universe Container ──────────────────────────── */
.login-universe {
  position: relative;
  min-height: 100vh;
  background: radial-gradient(ellipse at 20% 50%, #0a0a1f 0%, #04040d 60%, #0a0a1f 100%);
  overflow: hidden;
  display: flex;
  align-items: stretch;
}

/* ──── Lightning BG ────────────────────────────────── */
.bg-lightning {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse 60% 40% at 15% 30%, rgba(99,102,241,0.08) 0%, transparent 70%),
    radial-gradient(ellipse 40% 60% at 85% 70%, rgba(139,92,246,0.06) 0%, transparent 70%),
    radial-gradient(ellipse 80% 30% at 50% 0%, rgba(59,130,246,0.05) 0%, transparent 70%);
  pointer-events: none;
  animation: lightningPulse 8s ease-in-out infinite alternate;
}

@keyframes lightningPulse {
  0%  { opacity: 0.6; }
  50% { opacity: 1; }
  100% { opacity: 0.6; }
}

/* ──── Floating Runes ──────────────────────────────── */
.bg-runes {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 0;
}

.rune {
  position: absolute;
  color: #818cf8;
  font-family: serif;
  animation: floatRune linear infinite;
  user-select: none;
}

@keyframes floatRune {
  0%   { transform: translateY(0px) rotate(0deg); opacity: inherit; }
  50%  { transform: translateY(-20px) rotate(10deg); }
  100% { transform: translateY(0px) rotate(0deg); opacity: inherit; }
}

/* ──── Bifrost Beam ────────────────────────────────── */
.bifrost-beam {
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 2px;
  height: 100%;
  background: linear-gradient(180deg,
    transparent 0%,
    rgba(99,102,241,0.3) 20%,
    rgba(139,92,246,0.5) 50%,
    rgba(59,130,246,0.3) 80%,
    transparent 100%
  );
  pointer-events: none;
  animation: bifrostGlow 4s ease-in-out infinite alternate;
  display: none;
}

@media (min-width: 900px) {
  .bifrost-beam { display: block; }
}

@keyframes bifrostGlow {
  0%   { opacity: 0.3; filter: blur(2px); }
  100% { opacity: 0.8; filter: blur(1px); }
}

/* ──── Wrapper ─────────────────────────────────────── */
.login-wrapper {
  position: relative;
  z-index: 1;
  display: flex;
  width: 100%;
  min-height: 100vh;
}

/* ──── Brand Panel (left) ──────────────────────────── */
.brand-panel {
  display: none;
  flex: 1;
  align-items: center;
  justify-content: center;
  padding: 4rem 3rem;
  background: linear-gradient(135deg,
    rgba(13,13,30,0.95) 0%,
    rgba(20,20,50,0.85) 100%
  );
  border-right: 1px solid rgba(99,102,241,0.15);
  position: relative;
  overflow: hidden;
}

@media (min-width: 900px) {
  .brand-panel { display: flex; }
}

.brand-panel::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: conic-gradient(from 0deg at 50% 50%,
    transparent 0deg,
    rgba(99,102,241,0.03) 60deg,
    transparent 120deg,
    rgba(139,92,246,0.03) 180deg,
    transparent 240deg,
    rgba(59,130,246,0.03) 300deg,
    transparent 360deg
  );
  animation: conicSpin 30s linear infinite;
}

@keyframes conicSpin {
  to { transform: rotate(360deg); }
}

.brand-content {
  position: relative;
  z-index: 1;
  text-align: center;
  max-width: 380px;
}

.brand-logo-wrap {
  position: relative;
  display: inline-block;
  margin-bottom: 2rem;
}

.brand-halo {
  position: absolute;
  inset: -16px;
  border-radius: 50%;
  background: conic-gradient(from 0deg,
    rgba(99,102,241,0.6),
    rgba(139,92,246,0.4),
    rgba(59,130,246,0.6),
    rgba(99,102,241,0.6)
  );
  animation: haloSpin 4s linear infinite;
  filter: blur(8px);
}

@keyframes haloSpin {
  to { transform: rotate(360deg); }
}

.brand-logo {
  width: 120px;
  height: 120px;
  object-fit: contain;
  filter: drop-shadow(0 0 20px rgba(99,102,241,0.8)) drop-shadow(0 0 40px rgba(139,92,246,0.4));
  animation: logoFloat 6s ease-in-out infinite;
}

@keyframes logoFloat {
  0%, 100% { transform: translateY(0px); }
  50%       { transform: translateY(-12px); }
}

.brand-title {
  font-family: 'Cinzel', serif;
  font-size: 3rem;
  font-weight: 900;
  letter-spacing: 0.3em;
  background: linear-gradient(135deg, #e0e7ff 0%, #818cf8 40%, #c4b5fd 70%, #93c5fd 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin: 0 0 0.5rem;
  line-height: 1;
}

.brand-subtitle {
  font-family: 'Cinzel', serif;
  font-size: 0.9rem;
  color: rgba(199,210,254,0.7);
  letter-spacing: 0.2em;
  margin: 0 0 1.5rem;
}

.brand-divider {
  margin: 1.5rem 0;
  position: relative;
}

.brand-divider::before, .brand-divider::after {
  content: '';
  position: absolute;
  top: 50%;
  width: 25%;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(99,102,241,0.5));
}
.brand-divider::before { left: 0; }
.brand-divider::after  { right: 0; background: linear-gradient(90deg, rgba(99,102,241,0.5), transparent); }

.rune-line {
  font-size: 1.1rem;
  color: rgba(165,180,252,0.6);
  letter-spacing: 0.4em;
}

.brand-desc {
  color: rgba(199,210,254,0.6);
  font-size: 0.95rem;
  line-height: 1.8;
  margin: 0 0 2rem;
}

.brand-stats {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: rgba(99,102,241,0.06);
  border: 1px solid rgba(99,102,241,0.12);
  border-radius: 8px;
  padding: 0.6rem 1rem;
  transition: all 0.3s;
}

.stat-item:hover {
  background: rgba(99,102,241,0.12);
  border-color: rgba(99,102,241,0.3);
  transform: translateX(4px);
}

.stat-icon { font-size: 1.1rem; }
.stat-text {
  font-family: 'Cinzel', serif;
  font-size: 0.8rem;
  color: rgba(199,210,254,0.8);
  letter-spacing: 0.1em;
}

/* ──── Form Panel (right) ──────────────────────────── */
.form-panel {
  flex: 0 0 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1.5rem;
}

@media (min-width: 900px) {
  .form-panel { flex: 0 0 460px; }
}

/* ──── Login Card ──────────────────────────────────── */
.login-card {
  width: 100%;
  max-width: 420px;
  background: linear-gradient(145deg,
    rgba(18,18,40,0.95) 0%,
    rgba(12,12,28,0.98) 100%
  );
  border: 1px solid rgba(99,102,241,0.2);
  border-radius: 20px;
  overflow: hidden;
  box-shadow:
    0 0 0 1px rgba(255,255,255,0.03),
    0 20px 60px rgba(0,0,0,0.6),
    0 0 80px rgba(99,102,241,0.08);
  position: relative;
}

.card-top-bar {
  height: 3px;
  background: linear-gradient(90deg,
    #6366f1 0%, #8b5cf6 30%, #3b82f6 60%, #8b5cf6 80%, #6366f1 100%
  );
  background-size: 200% 100%;
  animation: barSlide 3s linear infinite;
}

@keyframes barSlide {
  0%   { background-position: 0% 0%; }
  100% { background-position: 200% 0%; }
}

/* ──── Header ──────────────────────────────────────── */
.login-header {
  text-align: center;
  padding: 2rem 2rem 0;
}

.mobile-logo {
  display: block;
  margin-bottom: 1rem;
}

@media (min-width: 900px) {
  .mobile-logo { display: none; }
}

.mobile-logo-img {
  width: 56px;
  height: 56px;
  object-fit: contain;
  margin: 0 auto;
  filter: drop-shadow(0 0 10px rgba(99,102,241,0.6));
}

.card-title {
  font-family: 'Cinzel', serif;
  font-size: 1.5rem;
  font-weight: 700;
  color: #e0e7ff;
  letter-spacing: 0.1em;
  margin: 0 0 0.4rem;
}

.card-subtitle {
  font-size: 0.85rem;
  color: rgba(165,180,252,0.6);
  margin: 0 0 1.5rem;
  font-style: italic;
}

/* ──── Alert ───────────────────────────────────────── */
.alert-norse {
  margin: 0 2rem 1rem;
  background: rgba(239,68,68,0.1);
  border: 1px solid rgba(239,68,68,0.3);
  border-radius: 8px;
  padding: 0.75rem 1rem;
  font-size: 0.85rem;
  color: #fca5a5;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.alert-rune {
  font-size: 1rem;
  color: #f87171;
}

/* ──── Form ────────────────────────────────────────── */
.login-form {
  padding: 1rem 2rem 0;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.field-label {
  font-size: 0.78rem;
  font-weight: 600;
  color: rgba(165,180,252,0.8);
  letter-spacing: 0.08em;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  text-transform: uppercase;
}

.field-rune {
  color: #818cf8;
  font-size: 0.9rem;
}

.field-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.field-icon {
  position: absolute;
  left: 0.875rem;
  width: 16px;
  height: 16px;
  color: rgba(129,140,248,0.5);
  pointer-events: none;
  transition: color 0.2s;
}

.field-input {
  width: 100%;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(99,102,241,0.2);
  border-radius: 10px;
  color: #e0e7ff;
  font-size: 0.9rem;
  padding: 0.75rem 2.75rem 0.75rem 2.75rem;
  outline: none;
  transition: all 0.25s;
  font-family: inherit;
}

.field-input::placeholder { color: rgba(165,180,252,0.3); }

.field-input:focus {
  border-color: rgba(99,102,241,0.6);
  background: rgba(99,102,241,0.05);
  box-shadow: 0 0 0 3px rgba(99,102,241,0.12), 0 0 20px rgba(99,102,241,0.08);
}

.field-input:focus + .field-icon,
.field-wrap:focus-within .field-icon {
  color: rgba(129,140,248,0.9);
}

.field-input.field-error {
  border-color: rgba(239,68,68,0.5);
}

.toggle-pass {
  position: absolute;
  right: 0.875rem;
  background: none;
  border: none;
  color: rgba(129,140,248,0.5);
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
  transition: color 0.2s;
}
.toggle-pass:hover { color: #818cf8; }
.toggle-pass svg { width: 16px; height: 16px; }

.error-msg {
  font-size: 0.75rem;
  color: #f87171;
  padding-left: 0.25rem;
}

/* ──── Remember Row ────────────────────────────────── */
.remember-row { margin-top: -0.25rem; }

.remember-label {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  cursor: pointer;
  font-size: 0.82rem;
  color: rgba(165,180,252,0.7);
  user-select: none;
  position: relative;
}

.remember-check {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.remember-custom {
  width: 16px;
  height: 16px;
  border: 1px solid rgba(99,102,241,0.4);
  border-radius: 4px;
  background: rgba(99,102,241,0.05);
  flex-shrink: 0;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.remember-check:checked ~ .remember-custom {
  background: #6366f1;
  border-color: #6366f1;
}

.remember-check:checked ~ .remember-custom::after {
  content: '✓';
  color: white;
  font-size: 11px;
  line-height: 1;
}

/* ──── Asgard Button ───────────────────────────────── */
.btn-asgard {
  position: relative;
  width: 100%;
  padding: 0.875rem 1.5rem;
  background: linear-gradient(135deg, #4f46e5 0%, #6366f1 40%, #7c3aed 100%);
  border: none;
  border-radius: 12px;
  color: white;
  font-size: 0.95rem;
  font-weight: 700;
  font-family: 'Cinzel', serif;
  letter-spacing: 0.12em;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  overflow: hidden;
  transition: all 0.3s;
  margin-top: 0.25rem;
  box-shadow:
    0 4px 20px rgba(99,102,241,0.4),
    inset 0 1px 0 rgba(255,255,255,0.1);
}

.btn-asgard::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
  transition: left 0.5s;
}

.btn-asgard:hover::before { left: 100%; }

.btn-asgard:hover {
  transform: translateY(-2px);
  box-shadow:
    0 8px 30px rgba(99,102,241,0.6),
    0 0 40px rgba(139,92,246,0.3),
    inset 0 1px 0 rgba(255,255,255,0.15);
}

.btn-asgard:active { transform: translateY(0); }

.btn-asgard:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.btn-lightning {
  font-size: 1rem;
  animation: zap 2s ease-in-out infinite;
}

@keyframes zap {
  0%, 90%, 100% { opacity: 1; transform: scale(1); }
  95%            { opacity: 0.5; transform: scale(1.3); }
}

.spin-icon {
  width: 18px;
  height: 18px;
  animation: spin 1s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* ──── Card Footer ─────────────────────────────────── */
.card-footer {
  text-align: center;
  padding: 1.5rem 2rem 2rem;
  border-top: 1px solid rgba(99,102,241,0.08);
  margin-top: 1.5rem;
}

.footer-runes {
  display: block;
  font-size: 0.75rem;
  color: rgba(99,102,241,0.4);
  letter-spacing: 0.4em;
  margin-bottom: 0.4rem;
}

.card-footer p {
  font-size: 0.75rem;
  color: rgba(165,180,252,0.3);
  margin: 0;
  letter-spacing: 0.05em;
}

/* ──── Turnstile CAPTCHA ────────────────────────────── */
.turnstile-wrapper {
  display: flex;
  justify-content: center;
  margin: 0.25rem 0 0.5rem;
  min-height: 65px;
}
</style>

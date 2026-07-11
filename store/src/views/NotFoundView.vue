<template>
  <div class="not-found">
    <!-- Partículas de neve / runas flutuantes -->
    <div class="particles" aria-hidden="true">
      <span v-for="i in 30" :key="i" class="particle" :style="particleStyle(i)">
        {{ runeSymbols[i % runeSymbols.length] }}
      </span>
    </div>

    <!-- Arco-íris nórdico (Bifrost) -->
    <div class="bifrost" aria-hidden="true">
      <div v-for="i in 6" :key="i" class="bifrost-band" :style="{ '--i': i }"></div>
    </div>

    <!-- Conteúdo central -->
    <div class="nf-content">
      <!-- Olho de Odin / Logo Heimdall -->
      <div class="odin-eye" aria-hidden="true">
        <div class="eye-outer">
          <div class="eye-pupil"></div>
          <div class="eye-glow"></div>
        </div>
        <div class="eye-rays">
          <span v-for="r in 8" :key="r" class="ray" :style="{ '--r': r }"></span>
        </div>
      </div>

      <!-- Número 404 em estilo rúnico -->
      <div class="rune-404" aria-label="Erro 404">
        <span class="digit" style="--d: 0">4</span>
        <span class="digit rune-zero" style="--d: 1">⊕</span>
        <span class="digit" style="--d: 2">4</span>
      </div>

      <h1 class="nf-title">As Brumas de Niflheim</h1>
      <p class="nf-subtitle">
        Heimdall vigiou as Nove Realidades, mas essa página<br>
        se perdeu além do Bifrost. O caminho não existe.
      </p>

      <!-- Divisor rúnico -->
      <div class="rune-divider" aria-hidden="true">
        <span>ᚠ</span><span class="divider-line"></span><span>ᚢ</span><span class="divider-line"></span><span>ᚦ</span>
      </div>

      <!-- Botões de ação -->
      <div class="nf-actions">
        <router-link to="/" class="btn-valhalla">
          <span class="btn-icon">⚡</span>
          Retornar ao Midgard
        </router-link>
        <router-link to="/catalogo" class="btn-secondary-norse">
          <span class="btn-icon">🛡</span>
          Ver Armaria
        </router-link>
      </div>

      <!-- Footer nórdico -->
      <p class="nf-footer" aria-hidden="true">
        ᚼᛖᛁᛗᛞᚨᛚᛚ · ᚷᚢᚨᚱᛞᛁᚨᚾ ᛟᚠ ᛏᚺᛖ ᚱᛖᚨᛚᛗᛊ
      </p>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'
import { useHead } from '@vueuse/head'

useHead({
  title: '404 - Página não encontrada | 90+ Store',
  meta: [{ name: 'description', content: 'A página que você procura se perdeu além do Bifrost.' }],
})

const runeSymbols = ['ᚠ', 'ᚢ', 'ᚦ', 'ᚨ', 'ᚱ', 'ᚲ', 'ᚷ', 'ᚹ', 'ᚺ', 'ᚾ', 'ᛁ', 'ᛃ', 'ᛇ', 'ᛈ', 'ᛉ', 'ᛊ', 'ᛏ', 'ᛒ', 'ᛖ', 'ᛗ', 'ᛚ', 'ᛜ', 'ᛞ', 'ᛟ']

function particleStyle(i) {
  const seed = i * 137.508
  const left = ((seed % 97) + 1.5)
  const duration = 8 + (seed % 12)
  const delay = -(seed % duration)
  const size = 0.7 + ((seed * 0.3) % 1.5)
  const opacity = 0.15 + ((seed * 0.07) % 0.5)
  return {
    left: `${left}%`,
    animationDuration: `${duration}s`,
    animationDelay: `${delay}s`,
    fontSize: `${size}rem`,
    opacity,
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;900&family=Inter:wght@300;400;500&display=swap');

/* ── Layout base ──────────────────────────────────────────── */
.not-found {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(ellipse at 50% 0%, #1a0a2e 0%, #0a0014 40%, #000508 100%);
  overflow: hidden;
  font-family: 'Inter', sans-serif;
}

/* ── Partículas rúnicas flutuantes ────────────────────────── */
.particles {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 1;
}
.particle {
  position: absolute;
  top: -2rem;
  color: #7c5cbf;
  animation: floatRune linear infinite;
  user-select: none;
}
@keyframes floatRune {
  0%   { transform: translateY(-10vh) rotate(0deg);   opacity: 0; }
  10%  { opacity: var(--opacity, 0.4); }
  90%  { opacity: var(--opacity, 0.4); }
  100% { transform: translateY(110vh) rotate(360deg); opacity: 0; }
}

/* ── Bifrost (arco animado) ───────────────────────────────── */
.bifrost {
  position: absolute;
  bottom: -60px;
  left: 50%;
  transform: translateX(-50%);
  width: 900px;
  height: 450px;
  pointer-events: none;
  z-index: 0;
}
.bifrost-band {
  position: absolute;
  inset: 0;
  border-radius: 50% 50% 0 0 / 100% 100% 0 0;
  border: 3px solid transparent;
  border-bottom: none;
  opacity: 0;
  animation: bifrostPulse 4s ease-in-out infinite;
  animation-delay: calc(var(--i) * 0.4s);
}
.bifrost-band:nth-child(1) { border-color: #ff6b6baa; transform: scale(calc(0.55 + var(--i) * 0.08)); }
.bifrost-band:nth-child(2) { border-color: #ff9f43aa; transform: scale(calc(0.55 + var(--i) * 0.08)); }
.bifrost-band:nth-child(3) { border-color: #ffd32aaa; transform: scale(calc(0.55 + var(--i) * 0.08)); }
.bifrost-band:nth-child(4) { border-color: #0be881aa; transform: scale(calc(0.55 + var(--i) * 0.08)); }
.bifrost-band:nth-child(5) { border-color: #00d2ffaa; transform: scale(calc(0.55 + var(--i) * 0.08)); }
.bifrost-band:nth-child(6) { border-color: #a29bfeaa; transform: scale(calc(0.55 + var(--i) * 0.08)); }
@keyframes bifrostPulse {
  0%, 100% { opacity: 0;    }
  50%       { opacity: 0.7; }
}

/* ── Conteúdo central ─────────────────────────────────────── */
.nf-content {
  position: relative;
  z-index: 10;
  text-align: center;
  padding: 2rem 1.5rem;
  max-width: 640px;
  animation: fadeSlideIn 0.9s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes fadeSlideIn {
  from { opacity: 0; transform: translateY(40px); }
  to   { opacity: 1; transform: translateY(0);    }
}

/* ── Olho de Odin ─────────────────────────────────────────── */
.odin-eye {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 2rem;
}
.eye-outer {
  position: relative;
  width: 90px;
  height: 90px;
  border-radius: 50%;
  background: radial-gradient(circle at 40% 35%, #4a2080, #1a0040);
  border: 2px solid #7c5cbf55;
  box-shadow: 0 0 40px #7c5cbf66, inset 0 0 20px #00000066;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: eyePulse 3s ease-in-out infinite;
}
@keyframes eyePulse {
  0%, 100% { box-shadow: 0 0 30px #7c5cbf55, inset 0 0 20px #00000066; }
  50%       { box-shadow: 0 0 70px #a78bfa99, inset 0 0 20px #00000066; }
}
.eye-pupil {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: radial-gradient(circle at 40% 35%, #c4b5fd, #6d28d9);
  animation: pupilMove 6s ease-in-out infinite;
  box-shadow: 0 0 15px #a78bfa;
}
@keyframes pupilMove {
  0%, 100% { transform: translate(0, 0); }
  25%       { transform: translate(6px, -4px); }
  50%       { transform: translate(-4px, 4px); }
  75%       { transform: translate(4px, 6px); }
}
.eye-glow {
  position: absolute;
  inset: -10px;
  border-radius: 50%;
  background: transparent;
  box-shadow: 0 0 25px #a78bfa44;
  animation: glowPulse 2s ease-in-out infinite;
}
@keyframes glowPulse {
  0%, 100% { opacity: 0.5; }
  50%       { opacity: 1; }
}
.eye-rays {
  position: absolute;
  inset: -20px;
  pointer-events: none;
}
.ray {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 55px;
  height: 2px;
  background: linear-gradient(to right, #a78bfa44, transparent);
  transform-origin: 0 50%;
  transform: rotate(calc(var(--r) * 45deg));
  animation: rayShimmer 3s ease-in-out infinite;
  animation-delay: calc(var(--r) * 0.3s);
}
@keyframes rayShimmer {
  0%, 100% { opacity: 0.2; width: 55px; }
  50%       { opacity: 0.8; width: 70px; }
}

/* ── Número 404 rúnico ────────────────────────────────────── */
.rune-404 {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.2rem;
  margin-bottom: 1.25rem;
}
.digit {
  font-family: 'Cinzel', serif;
  font-size: clamp(5rem, 18vw, 10rem);
  font-weight: 900;
  line-height: 1;
  background: linear-gradient(135deg, #c4b5fd 0%, #7c3aed 50%, #4f46e5 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  filter: drop-shadow(0 0 20px #7c3aed88);
  animation: digitFloat 4s ease-in-out infinite;
  animation-delay: calc(var(--d) * 0.2s);
}
.rune-zero {
  font-size: clamp(4rem, 14vw, 8rem);
  background: linear-gradient(135deg, #34d399 0%, #059669 50%, #065f46 100%);
  -webkit-background-clip: text;
  background-clip: text;
  filter: drop-shadow(0 0 20px #10b98188);
  font-family: 'Cinzel', serif;
}
@keyframes digitFloat {
  0%, 100% { transform: translateY(0);    }
  50%       { transform: translateY(-10px); }
}

/* ── Textos ───────────────────────────────────────────────── */
.nf-title {
  font-family: 'Cinzel', serif;
  font-size: clamp(1.4rem, 4vw, 2.2rem);
  font-weight: 600;
  color: #e2d9f3;
  margin-bottom: 1rem;
  letter-spacing: 0.06em;
  text-shadow: 0 0 30px #7c3aed44;
}
.nf-subtitle {
  font-size: 1rem;
  color: #94a3b8;
  line-height: 1.7;
  margin-bottom: 2rem;
}

/* ── Divisor rúnico ───────────────────────────────────────── */
.rune-divider {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  margin-bottom: 2.25rem;
  color: #6d28d9;
  font-size: 1.1rem;
}
.divider-line {
  height: 1px;
  width: 60px;
  background: linear-gradient(to right, transparent, #6d28d9, transparent);
}

/* ── Botões ───────────────────────────────────────────────── */
.nf-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
  margin-bottom: 2.5rem;
}
.btn-valhalla {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.8rem 1.8rem;
  background: linear-gradient(135deg, #7c3aed, #4f46e5);
  color: #fff;
  font-family: 'Cinzel', serif;
  font-weight: 600;
  font-size: 0.9rem;
  letter-spacing: 0.04em;
  border-radius: 8px;
  text-decoration: none;
  border: 1px solid #a78bfa44;
  box-shadow: 0 4px 20px #7c3aed44;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.btn-valhalla:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 30px #7c3aed77;
  background: linear-gradient(135deg, #8b5cf6, #6366f1);
}
.btn-secondary-norse {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.8rem 1.8rem;
  background: transparent;
  color: #c4b5fd;
  font-family: 'Cinzel', serif;
  font-weight: 600;
  font-size: 0.9rem;
  letter-spacing: 0.04em;
  border-radius: 8px;
  text-decoration: none;
  border: 1px solid #7c3aed66;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.btn-secondary-norse:hover {
  background: #7c3aed18;
  border-color: #a78bfaaa;
  transform: translateY(-3px);
  box-shadow: 0 6px 20px #7c3aed33;
}
.btn-icon {
  font-size: 1rem;
}

/* ── Rodapé rúnico ────────────────────────────────────────── */
.nf-footer {
  font-size: 0.7rem;
  color: #4b3a6e;
  letter-spacing: 0.15em;
  animation: runeGlow 4s ease-in-out infinite;
}
@keyframes runeGlow {
  0%, 100% { color: #4b3a6e; }
  50%       { color: #7c5cbf; }
}

/* ── Responsivo ───────────────────────────────────────────── */
@media (max-width: 480px) {
  .bifrost { width: 100vw; }
  .nf-actions { flex-direction: column; align-items: center; }
  .btn-valhalla, .btn-secondary-norse { width: 100%; justify-content: center; }
}
</style>

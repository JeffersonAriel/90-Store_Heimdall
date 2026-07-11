<template>
  <div class="error-404">
    <!-- Runas flutuantes de fundo -->
    <div class="rune-bg" aria-hidden="true">
      <span v-for="i in 25" :key="i" class="rune-float" :style="runeStyle(i)">
        {{ runes[i % runes.length] }}
      </span>
    </div>

    <!-- Bifrost arco-íris -->
    <div class="bifrost-wrap" aria-hidden="true">
      <div v-for="b in 6" :key="b" class="bifrost-arc" :style="{ '--b': b }"></div>
    </div>

    <!-- Conteúdo -->
    <div class="err-body">
      <!-- Olho de Odin -->
      <div class="odin-container" aria-hidden="true">
        <div class="odin-eye">
          <div class="eye-inner">
            <div class="pupil"></div>
          </div>
          <div class="eye-ring ring-1"></div>
          <div class="eye-ring ring-2"></div>
          <div v-for="r in 8" :key="r" class="eye-ray" :style="{ '--r': r }"></div>
        </div>
        <div class="heimdall-label">HEIMDALL</div>
      </div>

      <!-- Número 404 -->
      <div class="code-404" aria-label="Erro 404">
        <span class="c4 c4-l">4</span>
        <span class="c0">⊕</span>
        <span class="c4 c4-r">4</span>
      </div>

      <!-- Textos -->
      <h1 class="err-title">Portal Inexistente</h1>
      <p class="err-desc">
        Heimdall vigia as Nove Realidades, mas esta página<br class="d-br">
        se perdeu nas névoas de Niflheim.
      </p>

      <!-- Divisor rúnico -->
      <div class="divider" aria-hidden="true">
        <span>ᚠ</span>
        <span class="d-line"></span>
        <span>ᚺ</span>
        <span class="d-line"></span>
        <span>ᛟ</span>
      </div>

      <!-- Ações -->
      <div class="err-actions">
        <a href="/heimdall/dashboard" class="btn-primary-norse">
          <span>⚡</span> Voltar ao Painel
        </a>
        <a href="/heimdall/login" class="btn-outline-norse">
          <span>🛡</span> Ir para Login
        </a>
      </div>

      <!-- Rodapé em runas -->
      <p class="rune-footer" aria-hidden="true">ᚼᛖᛁᛗᛞᚨᛚᛚ · ᚷᚢᚨᚱᛞᛁᚨᚾ ᛟᚠ ᛏᚺᛖ ᚱᛖᚨᛚᛗᛊ</p>
    </div>
  </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'

const runes = ['ᚠ','ᚢ','ᚦ','ᚨ','ᚱ','ᚲ','ᚷ','ᚹ','ᚺ','ᚾ','ᛁ','ᛃ','ᛇ','ᛈ','ᛉ','ᛊ','ᛏ','ᛒ','ᛖ','ᛗ','ᛚ','ᛜ','ᛞ','ᛟ']

function runeStyle(i) {
  const seed = i * 137.508
  return {
    left: `${(seed % 97) + 1.5}%`,
    animationDuration: `${8 + (seed % 12)}s`,
    animationDelay: `${-(seed % 10)}s`,
    fontSize: `${0.7 + ((seed * 0.3) % 1.5)}rem`,
    opacity: 0.12 + ((seed * 0.05) % 0.35),
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;900&family=Inter:wght@300;400;500&display=swap');

/* ── Base ──────────────────────────────────────────────────── */
.error-404 {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(ellipse at 50% 0%, #1a0a2e 0%, #0a0014 45%, #000508 100%);
  overflow: hidden;
  font-family: 'Inter', sans-serif;
}

/* ── Runas flutuantes ─────────────────────────────────────── */
.rune-bg { position: absolute; inset: 0; pointer-events: none; z-index: 1; }
.rune-float {
  position: absolute;
  top: -2rem;
  color: #7c5cbf;
  animation: floatRune linear infinite;
  user-select: none;
}
@keyframes floatRune {
  0%   { transform: translateY(-5vh) rotate(0deg);   opacity: 0; }
  10%  { opacity: 1; }
  90%  { opacity: 1; }
  100% { transform: translateY(105vh) rotate(360deg); opacity: 0; }
}

/* ── Bifrost ──────────────────────────────────────────────── */
.bifrost-wrap {
  position: absolute;
  bottom: -80px;
  left: 50%;
  transform: translateX(-50%);
  width: 960px;
  height: 480px;
  pointer-events: none;
  z-index: 0;
}
.bifrost-arc {
  position: absolute;
  inset: 0;
  border-radius: 50% 50% 0 0 / 100% 100% 0 0;
  border: 2px solid transparent;
  border-bottom: none;
  animation: arcPulse 4s ease-in-out infinite;
  animation-delay: calc(var(--b) * 0.45s);
}
.bifrost-arc:nth-child(1) { border-color: #ff6b6b88; transform: scale(0.62); }
.bifrost-arc:nth-child(2) { border-color: #ff9f4388; transform: scale(0.70); }
.bifrost-arc:nth-child(3) { border-color: #ffd32a88; transform: scale(0.78); }
.bifrost-arc:nth-child(4) { border-color: #0be88188; transform: scale(0.86); }
.bifrost-arc:nth-child(5) { border-color: #00d2ff88; transform: scale(0.94); }
.bifrost-arc:nth-child(6) { border-color: #a29bfe88; transform: scale(1.02); }
@keyframes arcPulse {
  0%, 100% { opacity: 0; }
  50%       { opacity: 0.75; }
}

/* ── Body central ─────────────────────────────────────────── */
.err-body {
  position: relative;
  z-index: 10;
  text-align: center;
  padding: 2rem 1.5rem;
  max-width: 600px;
  animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(36px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ── Olho de Odin ─────────────────────────────────────────── */
.odin-container { margin-bottom: 2rem; display: flex; flex-direction: column; align-items: center; gap: 0.75rem; }
.odin-eye {
  position: relative;
  width: 100px;
  height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.eye-inner {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: radial-gradient(circle at 40% 35%, #4a2080, #1a0040);
  border: 2px solid #7c5cbf44;
  box-shadow: 0 0 40px #7c5cbf55, inset 0 0 20px #00000066;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: eyePulse 3s ease-in-out infinite;
  z-index: 2;
}
@keyframes eyePulse {
  0%, 100% { box-shadow: 0 0 30px #7c5cbf55; }
  50%       { box-shadow: 0 0 60px #a78bfa99; }
}
.pupil {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: radial-gradient(circle at 38% 35%, #c4b5fd, #6d28d9);
  box-shadow: 0 0 12px #a78bfa;
  animation: pupilMove 6s ease-in-out infinite;
}
@keyframes pupilMove {
  0%, 100% { transform: translate(0, 0); }
  25%  { transform: translate(6px, -4px); }
  50%  { transform: translate(-4px, 4px); }
  75%  { transform: translate(4px, 6px); }
}
.eye-ring {
  position: absolute;
  border-radius: 50%;
  border: 1px solid #7c5cbf33;
  animation: ringPulse 3s ease-in-out infinite;
}
.ring-1 { width: 90px;  height: 90px;  animation-delay: 0s; }
.ring-2 { width: 108px; height: 108px; animation-delay: 0.5s; }
@keyframes ringPulse {
  0%, 100% { opacity: 0.3; transform: scale(1); }
  50%       { opacity: 0.8; transform: scale(1.05); }
}
.eye-ray {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 60px;
  height: 1.5px;
  background: linear-gradient(to right, #a78bfa33, transparent);
  transform-origin: 0 50%;
  transform: rotate(calc(var(--r) * 45deg));
  animation: rayShimmer 3s ease-in-out infinite;
  animation-delay: calc(var(--r) * 0.25s);
}
@keyframes rayShimmer {
  0%, 100% { opacity: 0.2; }
  50%       { opacity: 0.8; }
}
.heimdall-label {
  font-family: 'Cinzel', serif;
  font-size: 0.6rem;
  letter-spacing: 0.35em;
  color: #7c5cbf;
  animation: labelGlow 3s ease-in-out infinite;
}
@keyframes labelGlow {
  0%, 100% { color: #5b3ea0; }
  50%       { color: #c4b5fd; }
}

/* ── 404 ──────────────────────────────────────────────────── */
.code-404 {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.1rem;
  margin-bottom: 1.25rem;
}
.c4 {
  font-family: 'Cinzel', serif;
  font-size: clamp(5rem, 17vw, 9.5rem);
  font-weight: 900;
  line-height: 1;
  background: linear-gradient(135deg, #c4b5fd 0%, #7c3aed 55%, #4f46e5 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  filter: drop-shadow(0 0 18px #7c3aed77);
  animation: digit4Float 4s ease-in-out infinite;
}
.c4-l { animation-delay: 0s; }
.c4-r { animation-delay: 0.2s; }
@keyframes digit4Float {
  0%, 100% { transform: translateY(0); }
  50%       { transform: translateY(-10px); }
}
.c0 {
  font-family: 'Cinzel', serif;
  font-size: clamp(4rem, 13vw, 8rem);
  font-weight: 900;
  line-height: 1;
  background: linear-gradient(135deg, #34d399 0%, #059669 55%, #065f46 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  filter: drop-shadow(0 0 18px #10b98177);
  animation: digit4Float 4s ease-in-out infinite;
  animation-delay: 0.1s;
}

/* ── Textos ───────────────────────────────────────────────── */
.err-title {
  font-family: 'Cinzel', serif;
  font-size: clamp(1.3rem, 4vw, 2rem);
  font-weight: 600;
  color: #e2d9f3;
  margin-bottom: 0.85rem;
  letter-spacing: 0.05em;
  text-shadow: 0 0 25px #7c3aed33;
}
.err-desc {
  font-size: 0.97rem;
  color: #94a3b8;
  line-height: 1.75;
  margin-bottom: 1.75rem;
}
.d-br { display: block; }

/* ── Divisor ──────────────────────────────────────────────── */
.divider {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.7rem;
  margin-bottom: 2rem;
  color: #6d28d9;
  font-size: 1rem;
}
.d-line {
  height: 1px;
  width: 55px;
  background: linear-gradient(to right, transparent, #6d28d9, transparent);
}

/* ── Botões ───────────────────────────────────────────────── */
.err-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
  margin-bottom: 2.5rem;
}
.btn-primary-norse, .btn-outline-norse {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.75rem 1.65rem;
  border-radius: 8px;
  font-family: 'Cinzel', serif;
  font-size: 0.875rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-decoration: none;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
}
.btn-primary-norse {
  background: linear-gradient(135deg, #7c3aed, #4f46e5);
  color: #fff;
  border: 1px solid #a78bfa33;
  box-shadow: 0 4px 18px #7c3aed33;
}
.btn-primary-norse:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 28px #7c3aed66;
  background: linear-gradient(135deg, #8b5cf6, #6366f1);
}
.btn-outline-norse {
  background: transparent;
  color: #c4b5fd;
  border: 1px solid #7c3aed55;
}
.btn-outline-norse:hover {
  background: #7c3aed18;
  border-color: #a78bfaaa;
  transform: translateY(-3px);
}

/* ── Rodapé rúnico ────────────────────────────────────────── */
.rune-footer {
  font-size: 0.65rem;
  letter-spacing: 0.14em;
  color: #4b3a6e;
  animation: runeGlow 4s ease-in-out infinite;
}
@keyframes runeGlow {
  0%, 100% { color: #4b3a6e; }
  50%       { color: #7c5cbf; }
}

@media (max-width: 480px) {
  .bifrost-wrap { width: 100vw; }
  .err-actions { flex-direction: column; align-items: center; }
  .btn-primary-norse, .btn-outline-norse { width: 100%; justify-content: center; }
  .d-br { display: inline; }
}
</style>

<template>
  <div class="alerta" :class="`alerta--${alerta.prioridade}`">
    <div class="alerta__dot" :class="`alerta__dot--${alerta.prioridade}`"></div>
    <div class="alerta__body">
      <div class="alerta__titulo">{{ alerta.titulo }}</div>
      <div v-if="alerta.cliente" class="alerta__sub">
        👤 {{ alerta.cliente.nome_completo }}
      </div>
    </div>
    <div class="alerta__meta">
      <span class="alerta__tempo">{{ relTime(alerta.created_at) }}</span>
      <span class="alerta__prio" :class="`alerta__prio--${alerta.prioridade}`">
        {{ labelPrioridade(alerta.prioridade) }}
      </span>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({ alerta: Object })

function relTime(dt) {
  if (!dt) return ''
  const diff = Math.floor((Date.now() - new Date(dt)) / 60000)
  if (diff < 1)  return 'agora'
  if (diff < 60) return `${diff}m`
  if (diff < 1440) return `${Math.floor(diff/60)}h`
  return `${Math.floor(diff/1440)}d`
}

function labelPrioridade(p) {
  const m = { urgente: '🔴 Urgente', alta: '🟠 Alta', media: '🟡 Média', baixa: '🟢 Baixa' }
  return m[p] ?? p
}
</script>

<style scoped>
.alerta {
  display: flex;
  align-items: center;
  gap: .75rem;
  background: rgba(255,255,255,.04);
  border-radius: 10px;
  padding: .65rem .9rem;
  border-left: 3px solid transparent;
  transition: background .15s;
}
.alerta:hover { background: rgba(255,255,255,.07); }
.alerta--urgente { border-left-color: #ef4444; }
.alerta--alta    { border-left-color: #f97316; }
.alerta--media   { border-left-color: #f59e0b; }
.alerta--baixa   { border-left-color: #10b981; }
.alerta__dot {
  width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
  animation: pulse 2s infinite;
}
.alerta__dot--urgente { background: #ef4444; }
.alerta__dot--alta    { background: #f97316; }
.alerta__dot--media   { background: #f59e0b; }
.alerta__dot--baixa   { background: #10b981; animation: none; }
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: .4; }
}
.alerta__body { flex: 1; min-width: 0; }
.alerta__titulo { font-size: .85rem; color: #e2e8f0; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.alerta__sub { font-size: .75rem; color: #64748b; margin-top: .15rem; }
.alerta__meta { display: flex; flex-direction: column; align-items: flex-end; gap: .2rem; flex-shrink: 0; }
.alerta__tempo { font-size: .72rem; color: #475569; }
.alerta__prio { font-size: .68rem; font-weight: 600; }
.alerta__prio--urgente { color: #f87171; }
.alerta__prio--alta    { color: #fb923c; }
.alerta__prio--media   { color: #fbbf24; }
.alerta__prio--baixa   { color: #34d399; }
</style>

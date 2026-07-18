<template>
  <div class="kpi-card" :style="{ '--kpi-color': color }">
    <div class="kpi-icon">{{ icon }}</div>
    <div class="kpi-value">{{ value }}</div>
    <div class="kpi-label">{{ label }}</div>
    <div class="kpi-glow"></div>
  </div>
</template>

<script setup>
defineProps({
  label: String,
  value: [String, Number],
  icon:  String,
  color: { type: String, default: '#6366f1' },
})
</script>

<style scoped>
.kpi-card {
  position: relative;
  background: linear-gradient(135deg, rgba(30,27,75,.9), rgba(17,24,39,.95));
  border: 1px solid rgba(var(--kpi-color-rgb, 99,102,241), .25);
  border-radius: 16px;
  padding: 1.25rem 1rem;
  text-align: center;
  overflow: hidden;
  transition: all .25s;
  cursor: default;
  border-color: rgba(99,102,241,.2);
}
.kpi-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 35px rgba(0,0,0,.3);
  border-color: v-bind(color);
}
.kpi-glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 50% 0%, v-bind(color) 0%, transparent 65%);
  opacity: .06;
  pointer-events: none;
  transition: opacity .25s;
}
.kpi-card:hover .kpi-glow { opacity: .12; }
.kpi-icon  { font-size: 1.8rem; margin-bottom: .35rem; }
.kpi-value { font-size: 1.4rem; font-weight: 800; color: #f1f5f9; line-height: 1.1; margin-bottom: .3rem; }
.kpi-label { font-size: .72rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
</style>

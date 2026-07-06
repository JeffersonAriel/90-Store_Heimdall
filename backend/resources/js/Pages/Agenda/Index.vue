<template>
  <AdminLayout title="Agenda & Compromissos">
    <template #breadcrumb>
      <span>Operações / Agenda</span>
    </template>

    <div class="page-header mb-6">
      <div>
        <h1 class="page-title">📅 Agenda & Calendário</h1>
        <p class="text-secondary mt-1">Organize compromissos, reuniões e tarefas de funcionários ou clientes.</p>
      </div>
      <div>
        <button class="btn btn-primary" @click="openCreateModal(new Date())">
          ➕ Novo Compromisso
        </button>
      </div>
    </div>

    <!-- Calendário Principal -->
    <div class="grid-4 gap-6 align-start">
      
      <!-- Lateral: Lista de Compromissos do Dia Selecionado -->
      <div class="card col-span-1">
        <div class="card-header">
          <h3 class="card-title">📌 Dia {{ selectedDayFormatted }}</h3>
        </div>
        <div class="card-body">
          <div v-if="selectedDayEvents.length === 0" class="text-center py-6 text-muted">
            Sem compromissos marcados para este dia.
          </div>
          <div v-else class="event-mini-list">
            <div v-for="ev in selectedDayEvents" :key="ev.id" class="event-mini-card" :style="`border-left-color: ${ev.cor}`">
              <div class="flex justify-between items-start">
                <strong class="event-title">{{ ev.titulo }}</strong>
                <button class="text-danger hover-opacity" style="font-size:0.875rem;" @click="deleteEvent(ev.id)">✕</button>
              </div>
              <p v-if="ev.descricao" class="event-desc mt-1 text-secondary">{{ ev.descricao }}</p>
              
              <div class="event-time mt-2 text-muted">
                ⏰ {{ formatTime(ev.data_inicio) }} - {{ formatTime(ev.data_fim) }}
              </div>
              
              <div class="event-meta mt-2 pt-2 border-t flex flex-col gap-1 text-secondary" style="font-size:0.75rem;">
                <span v-if="ev.funcionario">👤 Funcionário: <strong>{{ ev.funcionario.nome }}</strong></span>
                <span v-if="ev.cliente">🛍️ Cliente: <strong>{{ ev.cliente.nome_completo }}</strong></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Calendário Mensal -->
      <div class="card col-span-3">
        <div class="card-header flex justify-between items-center">
          <div class="flex items-center gap-4">
            <h2 class="font-bold font-title text-xl" style="text-transform: capitalize;">
              {{ currentMonthLabel }}
            </h2>
          </div>
          <div class="flex gap-2">
            <button class="btn btn-secondary btn-sm" @click="changeMonth(-1)">◀ Voltar</button>
            <button class="btn btn-secondary btn-sm" @click="goToday">Hoje</button>
            <button class="btn btn-secondary btn-sm" @click="changeMonth(1)">Avançar ▶</button>
          </div>
        </div>
        <div class="card-body" style="padding: 0;">
          <div class="calendar-grid">
            
            <!-- Dias da Semana -->
            <div class="calendar-week-header" v-for="day in weekDays" :key="day">
              {{ day }}
            </div>

            <!-- Dias do Mês -->
            <div 
              v-for="(day, idx) in calendarDays" 
              :key="idx" 
              class="calendar-day-cell"
              :class="{
                'different-month': !day.isCurrentMonth,
                'today': day.isToday,
                'selected': isSelected(day.date)
              }"
              @click="selectDay(day.date)"
              @dblclick="openCreateModal(day.date)"
            >
              <div class="day-number">{{ day.date.getDate() }}</div>
              
              <!-- Lista de Eventos no Dia -->
              <div class="day-events">
                <div 
                  v-for="ev in day.events" 
                  :key="ev.id" 
                  class="day-event-pill" 
                  :style="`background-color: ${ev.cor}`"
                  :title="ev.titulo"
                >
                  {{ ev.titulo }}
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

    </div>

    <!-- Modal de Criação / Edição de Compromisso -->
    <div v-if="showModal" class="modal-backdrop">
      <div class="modal-container" style="max-width: 500px;">
        <div class="modal-header">
          <h3>📅 Novo Compromisso</h3>
          <button @click="showModal = false" class="modal-close-btn">✕</button>
        </div>
        <form @submit.prevent="submitForm">
          <div class="modal-body">
            
            <div class="form-group mb-4">
              <label class="form-label">Título do Compromisso</label>
              <input v-model="modalForm.titulo" type="text" class="form-control" placeholder="Reunião, entrega, atendimento..." required />
            </div>

            <div class="form-group mb-4">
              <label class="form-label">Descrição / Observações</label>
              <textarea v-model="modalForm.descricao" class="form-control" rows="3" placeholder="Detalhes adicionais..."></textarea>
            </div>

            <div class="grid-2 gap-4 mb-4">
              <div class="form-group">
                <label class="form-label">Início</label>
                <input v-model="modalForm.data_inicio" type="datetime-local" class="form-control" required />
              </div>
              <div class="form-group">
                <label class="form-label">Fim</label>
                <input v-model="modalForm.data_fim" type="datetime-local" class="form-control" required />
              </div>
            </div>

            <div class="form-group mb-4">
              <label class="form-label">Responsável / Funcionário</label>
              <select v-model="modalForm.funcionario_id" class="form-control">
                <option :value="null">Nenhum responsável</option>
                <option v-for="f in funcionarios" :key="f.id" :value="f.id">{{ f.nome }}</option>
              </select>
            </div>

            <div class="form-group mb-4">
              <label class="form-label">Cliente Associado <span class="text-secondary">(Opcional)</span></label>
              <select v-model="modalForm.cliente_id" class="form-control">
                <option :value="null">Nenhum cliente</option>
                <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.nome_completo }}</option>
              </select>
            </div>

            <div class="form-group mb-2">
              <label class="form-label">Cor de Identificação</label>
              <div class="flex gap-2">
                <button 
                  v-for="color in colorPresets" 
                  :key="color" 
                  type="button" 
                  class="color-dot" 
                  :style="`background-color: ${color}`"
                  :class="{ active: modalForm.cor === color }"
                  @click="modalForm.cor = color"
                ></button>
              </div>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showModal = false">Cancelar</button>
            <button type="submit" class="btn btn-primary" :disabled="submitting">
              {{ submitting ? 'Salvando...' : 'Confirmar Agendamento' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  events: { type: Array, required: true },
  funcionarios: { type: Array, required: true },
  clientes: { type: Array, required: true },
})

const weekDays = ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB']
const colorPresets = ['#3b82f6', '#10b981', '#ef4444', '#f59e0b', '#8b5cf6', '#6b7280']

const currentYear = ref(new Date().getFullYear())
const currentMonth = ref(new Date().getMonth()) // 0-indexed
const selectedDay = ref(new Date())

const showModal = ref(false)
const submitting = ref(false)
const modalForm = ref({
  id: null,
  titulo: '',
  descricao: '',
  data_inicio: '',
  data_fim: '',
  cor: '#3b82f6',
  funcionario_id: null,
  cliente_id: null,
})

// Rótulo do Mês/Ano Corrente
const currentMonthLabel = computed(() => {
  const date = new Date(currentYear.value, currentMonth.value, 1)
  return date.toLocaleString('pt-BR', { month: 'long', year: 'numeric' })
})

// Dia selecionado formatado
const selectedDayFormatted = computed(() => {
  return selectedDay.value.toLocaleDateString('pt-BR', { dateStyle: 'long' })
})

// Eventos do dia selecionado
const selectedDayEvents = computed(() => {
  return props.events.filter(ev => {
    const evDate = new Date(ev.data_inicio)
    return evDate.getFullYear() === selectedDay.value.getFullYear() &&
           evDate.getMonth() === selectedDay.value.getMonth() &&
           evDate.getDate() === selectedDay.value.getDate()
  })
})

// Estrutura os dias para renderizar a grade do calendário
const calendarDays = computed(() => {
  const year = currentYear.value
  const month = currentMonth.value

  const firstDayIndex = new Date(year, month, 1).getDay()
  const totalDays = new Date(year, month + 1, 0).getDate()
  const totalPrevDays = new Date(year, month, 0).getDate()

  const today = new Date()
  const list = []

  // Dias do mês anterior para preencher offset
  for (let i = firstDayIndex - 1; i >= 0; i--) {
    const d = new Date(year, month - 1, totalPrevDays - i)
    list.push(createDayObj(d, false, today))
  }

  // Dias do mês atual
  for (let i = 1; i <= totalDays; i++) {
    const d = new Date(year, month, i)
    list.push(createDayObj(d, true, today))
  }

  // Dias do próximo mês para fechar a grade (múltiplo de 7)
  const remaining = 42 - list.length
  for (let i = 1; i <= remaining; i++) {
    const d = new Date(year, month + 1, i)
    list.push(createDayObj(d, false, today))
  }

  return list
})

function createDayObj(date, isCurrentMonth, today) {
  // Filtra eventos correspondentes a esta data
  const dayEvents = props.events.filter(ev => {
    const evDate = new Date(ev.data_inicio)
    return evDate.getFullYear() === date.getFullYear() &&
           evDate.getMonth() === date.getMonth() &&
           evDate.getDate() === date.getDate()
  })

  const isToday = date.getFullYear() === today.getFullYear() &&
                  date.getMonth() === today.getMonth() &&
                  date.getDate() === today.getDate()

  return {
    date,
    isCurrentMonth,
    isToday,
    events: dayEvents
  }
}

function changeMonth(step) {
  currentMonth.value += step
  if (currentMonth.value < 0) {
    currentMonth.value = 11
    currentYear.value -= 1
  } else if (currentMonth.value > 11) {
    currentMonth.value = 0
    currentYear.value += 1
  }
}

function goToday() {
  const today = new Date()
  currentYear.value = today.getFullYear()
  currentMonth.value = today.getMonth()
  selectedDay.value = today
}

function selectDay(date) {
  selectedDay.value = date
}

function isSelected(date) {
  return date.getFullYear() === selectedDay.value.getFullYear() &&
         date.getMonth() === selectedDay.value.getMonth() &&
         date.getDate() === selectedDay.value.getDate()
}

function openCreateModal(date) {
  const localDate = new Date(date)
  localDate.setHours(9, 0, 0, 0)
  
  // Format to YYYY-MM-DDTHH:mm
  const offset = localDate.getTimezoneOffset()
  localDate.setMinutes(localDate.getMinutes() - offset)
  const formattedStart = localDate.toISOString().slice(0, 16)
  
  localDate.setHours(10, 0, 0, 0)
  const formattedEnd = localDate.toISOString().slice(0, 16)

  modalForm.value = {
    id: null,
    titulo: '',
    descricao: '',
    data_inicio: formattedStart,
    data_fim: formattedEnd,
    cor: '#3b82f6',
    funcionario_id: null,
    cliente_id: null,
  }
  showModal.value = true
}

function submitForm() {
  submitting.value = true
  router.post(route('admin.agenda.store'), modalForm.value, {
    onSuccess: () => {
      showModal.value = false
      submitting.value = false
    },
    onError: () => {
      submitting.value = false
    }
  })
}

function deleteEvent(id) {
  if (confirm('Deseja realmente remover este compromisso da agenda?')) {
    router.delete(route('admin.agenda.destroy', id))
  }
}

function formatTime(dateStr) {
  const date = new Date(dateStr)
  return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
}
</script>

<style scoped>
.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  border-left: 1px solid var(--color-border);
  border-top: 1px solid var(--color-border);
}

.calendar-week-header {
  background-color: var(--color-bg-alt);
  text-align: center;
  padding: 10px;
  font-weight: bold;
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  border-right: 1px solid var(--color-border);
  border-bottom: 1px solid var(--color-border);
}

.calendar-day-cell {
  min-height: 100px;
  background-color: var(--color-bg);
  padding: 8px;
  border-right: 1px solid var(--color-border);
  border-bottom: 1px solid var(--color-border);
  cursor: pointer;
  display: flex;
  flex-direction: column;
  transition: all 0.2s;
}

.calendar-day-cell:hover {
  background-color: var(--color-bg-alt);
}

.calendar-day-cell.different-month {
  background-color: var(--color-bg-alt);
  opacity: 0.4;
}

.calendar-day-cell.today {
  background-color: rgba(59, 130, 246, 0.08);
}

.calendar-day-cell.today .day-number {
  background-color: var(--color-brand);
  color: white;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.calendar-day-cell.selected {
  border: 2px solid var(--color-brand);
}

.day-number {
  font-weight: 600;
  font-size: 0.875rem;
  margin-bottom: 6px;
}

.day-events {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
  overflow: hidden;
}

.day-event-pill {
  font-size: 0.7rem;
  color: white;
  padding: 2px 6px;
  border-radius: 3px;
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
}

/* Event mini list */
.event-mini-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.event-mini-card {
  background-color: var(--color-bg-alt);
  padding: 12px;
  border-radius: var(--border-radius-sm);
  border-left: 4px solid var(--color-brand);
}

.event-title {
  font-size: 0.95rem;
}

.event-desc {
  font-size: 0.8125rem;
}

.event-time {
  font-size: 0.75rem;
  font-weight: bold;
}

/* Color Dot Presets */
.color-dot {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: 2px solid transparent;
  cursor: pointer;
  padding: 0;
}

.color-dot.active {
  border-color: var(--color-white);
  box-shadow: 0 0 0 2px var(--color-brand);
}
</style>

<template>
  <AdminLayout title="Importação & Exportação">
    <template #breadcrumb>
      <span>Ferramentas / Importação e Exportação</span>
    </template>

    <div class="page-header mb-6">
      <div>
        <h1 class="page-title">📁 Importação & Exportação em Lote</h1>
        <p class="text-secondary mt-1">Gerencie cadastros massivos de Produtos e Fornecedores via planilhas Excel (.xlsx) com validação em tempo real.</p>
      </div>
    </div>

    <!-- Módulos de Operação -->
    <div class="grid-2 gap-6 mb-6">
      <!-- Painel de Upload -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">📥 Upload de Nova Planilha</h3>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">Tipo de Cadastro</label>
            <select v-model="uploadForm.tipo" class="form-control">
              <option value="produtos">📦 Produtos e Variações</option>
              <option value="fornecedores">🏭 Fornecedores</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Planilha (.xlsx)</label>
            <input type="file" @change="handleFileChange" accept=".xlsx, .xls" class="form-control" />
          </div>

          <div class="flex gap-4 mt-6">
            <a :href="route('admin.import-export.template', uploadForm.tipo)" target="_blank" class="btn btn-secondary flex-1 text-center">
              Baixar Modelo (.xlsx)
            </a>
            <button @click="uploadFile" :disabled="!selectedFile || loading" class="btn btn-primary flex-1">
              {{ loading ? 'Processando...' : 'Validar Planilha' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Ajuda e Instruções -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">💡 Instruções Importantes</h3>
        </div>
        <div class="card-body">
          <ul class="text-secondary" style="font-size: 0.875rem; padding-left: 1.25rem; line-height: 1.6;">
            <li>Baixe sempre o modelo correto antes de preencher os dados.</li>
            <li><strong>Produtos:</strong> SKU Base deve ser único para o produto. SKU Variação deve ser único para a cor/tamanho.</li>
            <li><strong>Fornecedores:</strong> Tipo de pessoa deve ser 'juridica' ou 'fisica'. CNPJ ou CPF devem ser válidos.</li>
            <li>Nenhuma alteração é gravada no banco antes de você visualizar a prévia e clicar em <strong>Confirmar Importação</strong>.</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Pré-visualização da validação -->
    <div v-if="preview" class="card mb-6">
      <div class="card-header flex justify-between items-center">
        <h3 class="card-title">🔍 Pré-visualização e Validação do Lote</h3>
        <span class="badge badge-secondary">{{ preview.total }} linhas encontradas</span>
      </div>
      <div class="card-body">
        <div class="grid-4 gap-4 mb-4">
          <div class="kpi-card" style="padding: 1rem;">
            <div class="kpi-value text-success">{{ preview.criar }}</div>
            <div class="kpi-label">Serão criados</div>
          </div>
          <div class="kpi-card" style="padding: 1rem;">
            <div class="kpi-value text-brand">{{ preview.atualizar }}</div>
            <div class="kpi-label">Serão atualizados</div>
          </div>
          <div class="kpi-card" style="padding: 1rem;">
            <div class="kpi-value text-danger">{{ preview.erros }}</div>
            <div class="kpi-label">Linhas com erro (serão puladas)</div>
          </div>
        </div>

        <div class="table-wrapper mb-4" style="max-height: 350px; overflow-y: auto;">
          <table>
            <thead>
              <tr>
                <th style="width: 60px;">Linha</th>
                <th>Ação</th>
                <th>Chave / SKU / Nome</th>
                <th>Detalhes / Erros</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in preview.data" :key="item.linha" :style="item.status === 'erro' ? 'background: rgba(var(--color-danger-rgb), 0.05);' : ''">
                <td class="font-mono text-center">{{ item.linha }}</td>
                <td>
                  <span class="badge" :class="item.status === 'criar' ? 'badge-success' : item.status === 'atualizar' ? 'badge-primary' : 'badge-danger'">
                    {{ item.status === 'criar' ? 'Criar' : item.status === 'atualizar' ? 'Atualizar' : 'Erro' }}
                  </span>
                </td>
                <td>
                  <strong>{{ item.chave }}</strong>
                </td>
                <td :class="item.status === 'erro' ? 'text-danger font-bold' : 'text-secondary'">
                  {{ item.info }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex gap-3 justify-end">
          <button @click="preview = null" class="btn btn-secondary">Descartar</button>
          <button @click="confirmImport" :disabled="loading || (preview.criar === 0 && preview.atualizar === 0)" class="btn btn-primary">
            Confirmar e Importar
          </button>
        </div>
      </div>
    </div>

    <!-- Histórico de Importações -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">📜 Histórico de Importações Recentes</h3>
      </div>
      <div class="card-body" style="padding: 0;">
        <div v-if="history.length === 0" class="alert alert-secondary" style="margin: 1.5rem;">
          Nenhuma importação realizada no lote ainda.
        </div>
        <div v-else class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Data</th>
                <th>Funcionário</th>
                <th>Tipo</th>
                <th>Arquivo</th>
                <th>Criados</th>
                <th>Atualizados</th>
                <th>Erros</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="h in history" :key="h.id">
                <td style="font-size: 0.8125rem;">{{ formatDate(h.created_at) }}</td>
                <td><strong>{{ h.funcionario_nome }}</strong></td>
                <td>
                  <span class="badge badge-secondary">{{ h.tipo }}</span>
                </td>
                <td class="text-secondary font-mono" style="font-size: 0.8125rem;">{{ h.arquivo_original }}</td>
                <td class="text-success font-bold">+{{ h.criados }}</td>
                <td class="text-brand font-bold">{{ h.atualizados }}</td>
                <td class="text-danger font-bold">{{ h.erros }}</td>
                <td>
                  <span class="badge" :class="h.status === 'concluido' ? 'badge-success' : 'badge-warning'">
                    {{ h.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  history: { type: Array, required: true }
})

const uploadForm = ref({
  tipo: 'produtos'
})

const selectedFile = ref(null)
const preview = ref(null)
const loading = ref(false)
const importId = ref(null)

function handleFileChange(e) {
  selectedFile.value = e.target.files[0]
}

async function uploadFile() {
  if (!selectedFile.value) return
  loading.value = true
  
  const formData = new FormData()
  formData.append('tipo', uploadForm.value.tipo)
  formData.append('file', selectedFile.value)

  try {
    const response = await fetch(route('admin.import-export.upload'), {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })
    const data = await response.json()
    if (data.success) {
      preview.value = data.preview
      importId.value = data.import_id
    } else {
      alert(data.message || 'Erro ao processar a planilha.')
    }
  } catch (error) {
    alert('Erro de conexão ou tamanho de arquivo excedido.')
  } finally {
    loading.value = false
  }
}

async function confirmImport() {
  if (!importId.value) return
  loading.value = true

  try {
    const response = await fetch(route('admin.import-export.confirm'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({ import_id: importId.value })
    })
    const data = await response.json()
    if (data.success) {
      alert(data.message)
      preview.value = null
      router.reload()
    } else {
      alert(data.message)
    }
  } catch (error) {
    alert('Erro ao confirmar a importação.')
  } finally {
    loading.value = false
  }
}

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleString('pt-BR')
}
</script>

<style scoped>
.disabled {
  pointer-events: none;
  opacity: 0.5;
}
</style>

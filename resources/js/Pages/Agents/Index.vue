<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, usePage, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

/* ====== PROPS DARI BACKEND ====== */
const page = usePage()
const agents  = computed(() => page.props.agents || [])
const counts  = computed(() => page.props.counts || {})
const filters = computed(() => page.props.filters || { status: 'all' })

/* ====== FILTER ====== */
const filterStatus = ref(filters.value.status ?? 'all')

function changeFilter(v) {
  router.get('/agents', { status: v }, {
    preserveState: true,
    replace: true,
  })
}

/* ====== MODALS & STATE ====== */
const dialog        = ref(false)
const editDialog    = ref(false)
const deleteDialog  = ref(false)
const successDialog = ref(false)
const selectedAgent = ref(null)

/* ====== DATA UNTUK POPUP PASSWORD ====== */
const newAgent = ref({
  email: '',
  password: '',
})

/* ====== FORM ====== */
const form = useForm({
  name: '',
  email: '',
  role: 'Agent',
})

/* ====== OPEN MODAL ====== */
function openAdd() {
  selectedAgent.value = null
  form.reset()
  form.role = 'Agent'
  dialog.value = true
}

function openEdit(agent) {
  selectedAgent.value = agent
  form.name  = agent.name
  form.email = agent.email
  form.role  = agent.role
  editDialog.value = true
}

function openDelete(agent) {
  selectedAgent.value = agent
  deleteDialog.value = true
}

/* ====== CREATE / UPDATE ====== */
function saveAgent() {
  // CREATE
  if (!selectedAgent.value) {
    form.post('/agents', {
      preserveState: true,
      onSuccess: (pageResponse) => {
        dialog.value = false

        // Ambil flash dari response terbaru
        const flash = pageResponse.props.flash || {}

        if (flash.newAgent) {
          newAgent.value = flash.newAgent
          successDialog.value = true
        }
      },
    })
    return
  }

  // UPDATE
  form.put(`/agents/${selectedAgent.value.id}`, {
    preserveState: true,
    onSuccess: () => {
      editDialog.value = false
    },
  })
}

/* ====== DELETE ====== */
function deleteAgent() {
  router.delete(`/agents/${selectedAgent.value.id}`, {
    preserveState: true,
    onSuccess: () => {
      deleteDialog.value = false
    },
  })
}

/* ====== BADGE STATUS ====== */
const badgeColor = (status) => {
  if (status === 'online') return 'green'
  if (status === 'offline') return 'grey'
  return 'orange'
}

/* ====== FILTER VIEW ====== */
const filteredAgents = computed(() => {
  if (filterStatus.value === 'all') return agents.value
  return agents.value.filter(a => a.status === filterStatus.value)
})
</script>

<template>
  <Head title="Agents" />

  <AdminLayout>
    <template #title>Agents</template>

    <v-card elevation="2" class="pa-4">
      <!-- Header -->
      <v-row align="center" class="mb-4">
        <v-col>
          <h3 class="text-h6 font-weight-bold">Agents Management</h3>
          <p class="text-body-2 text-grey-darken-1">
            Kelola daftar agent yang bertanggung jawab menangani pesan pelanggan.
          </p>
        </v-col>
        <v-col cols="auto">
          <v-btn color="primary" prepend-icon="mdi-account-plus" @click="openAdd">
            Add Agent
          </v-btn>
        </v-col>
      </v-row>

      <!-- FILTER -->
      <v-chip-group v-model="filterStatus" mandatory @update:modelValue="changeFilter">
        <v-chip value="all" filter>All ({{ counts.all }})</v-chip>
        <v-chip value="online" filter color="green">Online ({{ counts.online }})</v-chip>
        <v-chip value="offline" filter color="grey">Offline ({{ counts.offline }})</v-chip>
        <v-chip value="pending" filter color="orange">Pending ({{ counts.pending }})</v-chip>
      </v-chip-group>

      <v-divider class="my-4" />

      <!-- TABLE -->
      <v-table density="comfortable" hover>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>

        <tbody>
          <tr v-if="!filteredAgents.length">
            <td colspan="5" class="text-center py-6">No agents found</td>
          </tr>

          <tr v-for="a in filteredAgents" :key="a.id">
            <td>
              <div class="d-flex align-center">
                <v-avatar color="blue" size="32" class="mr-2">
                  {{ a.name.charAt(0) }}
                </v-avatar>
                {{ a.name }}
              </div>
            </td>
            <td>{{ a.email }}</td>
            <td>
              <v-chip size="small">{{ a.role }}</v-chip>
            </td>
            <td>
              <v-chip :color="badgeColor(a.status)" dark size="small">
                {{ a.status }}
              </v-chip>
            </td>
            <td>
              <v-btn icon variant="text" color="primary" @click="openEdit(a)">
                <v-icon>mdi-pencil</v-icon>
              </v-btn>
              <v-btn icon variant="text" color="red" @click="openDelete(a)">
                <v-icon>mdi-delete</v-icon>
              </v-btn>
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-card>

    <!-- ADD AGENT -->
    <v-dialog v-model="dialog" width="450">
      <v-card class="pa-4">
        <h3 class="text-h6 mb-4">Add New Agent</h3>
        <v-text-field v-model="form.name" label="Full Name" />
        <v-text-field v-model="form.email" label="Email" class="mt-3" />
        <v-select
          v-model="form.role"
          class="mt-3"
          :items="['Admin', 'Supervisor', 'Agent']"
          label="Role"
        />
        <v-divider class="my-4" />
        <div class="d-flex justify-end gap-2">
          <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="saveAgent" :loading="form.processing">
            Save
          </v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- EDIT AGENT -->
    <v-dialog v-model="editDialog" width="450">
      <v-card class="pa-4">
        <h3 class="text-h6 mb-4">Edit Agent</h3>
        <v-text-field v-model="form.name" label="Full Name" />
        <v-text-field v-model="form.email" class="mt-3" label="Email" />
        <v-select
          v-model="form.role"
          class="mt-3"
          :items="['Admin', 'Supervisor', 'Agent']"
          label="Role"
        />
        <v-divider class="my-4" />
        <div class="d-flex justify-end gap-2">
          <v-btn variant="text" @click="editDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="saveAgent" :loading="form.processing">
            Update
          </v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- DELETE AGENT -->
    <v-dialog v-model="deleteDialog" width="400">
      <v-card class="pa-4 text-center">
        <v-icon size="48" color="red" class="mb-2">mdi-alert-circle</v-icon>
        <h3 class="text-h6 mb-2">Delete Agent?</h3>
        <p class="text-body-2 mb-4">
          Are you sure want to delete <strong>{{ selectedAgent?.name }}</strong>?
        </p>
        <div class="d-flex justify-center gap-2">
          <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
          <v-btn color="red" @click="deleteAgent">Delete</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- SUCCESS PASSWORD POPUP -->
    <v-dialog v-model="successDialog" width="420">
      <v-card class="pa-4 text-center">
        <v-icon size="48" color="green" class="mb-2">mdi-check-circle</v-icon>
        <h3 class="text-h6 mb-2">Agent Created Successfully!</h3>

        <p class="mb-1">
          <strong>Email:</strong> {{ newAgent.email }}
        </p>

        <v-text-field
          v-model="newAgent.password"
          label="Temporary Password"
          type="text"
          readonly
          density="comfortable"
          variant="outlined"
          class="mb-3"
          prepend-inner-icon="mdi-lock"
        />

        <v-btn
          color="primary"
          block
          class="mb-3"
          @click="navigator.clipboard.writeText(newAgent.password)"
        >
          <v-icon left>mdi-content-copy</v-icon>
          Copy Password
        </v-btn>

        <v-btn variant="text" block @click="successDialog = false">
          Close
        </v-btn>
      </v-card>
    </v-dialog>
  </AdminLayout>
</template>

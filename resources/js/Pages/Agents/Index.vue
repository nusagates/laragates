<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

// Dummy Data (Nanti pakai dari BE)
const agents = ref([
  { id: 1, name: "Renaldi", email: "renaldi@demo.com", role: "Admin", status: "online" },
  { id: 2, name: "Jonathan", email: "jonathan@demo.com", role: "Agent", status: "offline" },
  { id: 3, name: "Michelle", email: "michelle@demo.com", role: "Supervisor", status: "online" },
  { id: 4, name: "Aldi", email: "aldi@demo.com", role: "Agent", status: "pending" },
]);

// Filters & Modal
const filterStatus = ref('all');
const dialog = ref(false);
const editDialog = ref(false);
const deleteDialog = ref(false);
const selectedAgent = ref(null);

// Form Add/Edit Agent
const form = ref({
  name: '',
  email: '',
  role: 'Agent',
});

// Filter Function
const filteredAgents = computed(() => {
  if (filterStatus.value === 'all') return agents.value;
  return agents.value.filter(a => a.status === filterStatus.value);
});

// Status styling
const badgeColor = (status) => {
  if (status === 'online') return 'green';
  if (status === 'offline') return 'grey';
  return 'orange';
};

// Open Edit
function openEdit(agent) {
  selectedAgent.value = agent;
  form.value = { ...agent };
  editDialog.value = true;
}

// Delete
function openDelete(agent) {
  selectedAgent.value = agent;
  deleteDialog.value = true;
}

// Save Add/Edit
function saveAgent() {
  console.log('SAVE:', form.value);
  dialog.value = false;
  editDialog.value = false;
}

// Confirm Delete
function deleteAgent() {
  console.log('DELETE:', selectedAgent.value);
  deleteDialog.value = false;
}
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
          <v-btn color="primary" prepend-icon="mdi-account-plus" @click="dialog = true">
            Add Agent
          </v-btn>
        </v-col>
      </v-row>

      <!-- FILTER -->
      <v-chip-group v-model="filterStatus" mandatory>
        <v-chip value="all" filter>All</v-chip>
        <v-chip value="online" filter color="green">Online</v-chip>
        <v-chip value="offline" filter color="grey">Offline</v-chip>
        <v-chip value="pending" filter color="orange">Pending</v-chip>
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
            <td><v-chip size="small">{{ a.role }}</v-chip></td>
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

    <!-- ===================== MODALS ===================== -->

    <!-- ADD AGENT -->
    <v-dialog v-model="dialog" width="450">
      <v-card class="pa-4">
        <h3 class="text-h6 mb-4">Add New Agent</h3>

        <v-text-field v-model="form.name" label="Full Name" class="mb-3" />
        <v-text-field v-model="form.email" label="Email" class="mb-3" />
        <v-select
          v-model="form.role"
          :items="['Admin','Supervisor','Agent']"
          label="Role"
        />

        <v-divider class="my-4" />

        <div class="d-flex justify-end gap-2">
          <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="saveAgent">Save</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- EDIT AGENT -->
    <v-dialog v-model="editDialog" width="450">
      <v-card class="pa-4">
        <h3 class="text-h6 mb-4">Edit Agent</h3>

        <v-text-field v-model="form.name" label="Full Name" class="mb-3" />
        <v-text-field v-model="form.email" label="Email" class="mb-3" />
        <v-select
          v-model="form.role"
          :items="['Admin','Supervisor','Agent']"
          label="Role"
        />

        <v-divider class="my-4" />

        <div class="d-flex justify-end gap-2">
          <v-btn variant="text" @click="editDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="saveAgent">Update</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- DELETE -->
    <v-dialog v-model="deleteDialog" width="400">
      <v-card class="pa-4 text-center">
        <v-icon size="48" color="red" class="mb-2">mdi-alert-circle</v-icon>
        <h3 class="text-h6 mb-2">Delete Agent?</h3>
        <p class="text-body-2 text-grey-darken-1 mb-4">
          Are you sure want to delete <strong>{{ selectedAgent?.name }}</strong>?
        </p>

        <div class="d-flex justify-center gap-2">
          <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
          <v-btn color="red" @click="deleteAgent">Delete</v-btn>
        </div>
      </v-card>
    </v-dialog>
  </AdminLayout>
</template>

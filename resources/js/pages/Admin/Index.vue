<template>
  <div class="admin-container">
    <AdminNavbar 
      title="Admin" 
      :showBackButton="true"
      :backUrl="getRoute('index')"
    />

    <div class="admin-menu">
      <a :href="getRoute('admin.apps.index')" class="admin-menu-card">
        <font-awesome-icon icon="fa-solid fa-window-restore" class="admin-menu-icon" />
        <h2 class="admin-menu-title">Manajemen Aplikasi</h2>
        <p class="admin-menu-description">Kelola aplikasi dan integrasi antar aplikasi</p>
      </a>

      <a :href="getRoute('admin.technology.index')" class="admin-menu-card">
        <font-awesome-icon icon="fa-solid fa-microchip" class="admin-menu-icon" />
        <h2 class="admin-menu-title">Manajemen Teknologi</h2>
        <p class="admin-menu-description">Kelola komponen teknologi seperti database, framework, dll</p>
      </a>

       <a :href="getRoute('admin.integrations.index')" class="admin-menu-card">
        <font-awesome-icon icon="fa-solid fa-network-wired" class="admin-menu-icon" />
        <h2 class="admin-menu-title">Manajemen Koneksi</h2>
        <p class="admin-menu-description">Kelola hubungan dan integrasi antar aplikasi secara detail</p>
      </a>

      <a :href="getRoute('admin.contracts.index')" class="admin-menu-card">
        <font-awesome-icon icon="fa-solid fa-file-contract" class="admin-menu-icon" />
        <h2 class="admin-menu-title">Manajemen Kontrak</h2>
        <p class="admin-menu-description">Kelola kontrak aplikasi dan periode pembayaran</p>
      </a>

      <a
        :href="firstAllowedStream ? getRoute('admin.diagrams.show', { streamName: firstAllowedStream }) : getRoute('admin.streams.index')"
        class="admin-menu-card"
        :class="{ disabled: !firstAllowedStream }"
        :title="!firstAllowedStream ? 'Belum ada stream yang diizinkan untuk diagram. Atur di Manajemen Stream.' : undefined"
      >
        <font-awesome-icon icon="fa-solid fa-project-diagram" class="admin-menu-icon" />
        <h2 class="admin-menu-title">Manajemen Diagram</h2>
        <p class="admin-menu-description">Kelola tata letak dan tampilan diagram integrasi</p>
      </a>

      <a :href="getRoute('admin.streams.index')" class="admin-menu-card">
        <font-awesome-icon icon="fa-solid fa-sitemap" class="admin-menu-icon" />
        <h2 class="admin-menu-title">Manajemen Stream</h2>
        <p class="admin-menu-description">Kelola stream aplikasi, izin diagram, dan prioritas</p>
      </a>

      <a :href="getRoute('admin.connection-types.index')" class="admin-menu-card">
        <font-awesome-icon icon="fa-solid fa-plug" class="admin-menu-icon" />
        <h2 class="admin-menu-title">Manajemen Tipe Koneksi</h2>
        <p class="admin-menu-description">Kelola tipe koneksi dan warna</p>
      </a>
    </div>
  </div>
</template>

<script setup lang="ts">
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import AdminNavbar from '@/components/Admin/AdminNavbar.vue';
import { useRoutes } from '@/composables/useRoutes';

const { getRoute } = useRoutes();

// Receive first allowed stream from server (sorted by priority)
const props = defineProps<{ firstAllowedStream?: string | null }>();
const firstAllowedStream = props.firstAllowedStream ?? null;
</script>

<style scoped>
@import '@/../css/admin.css';

.admin-menu {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  margin-top: 2rem;
}

.admin-menu-card {
  background-color: white;
  border-radius: var(--radius-lg);
  padding: 2rem;
  text-align: center;
  transition: all var(--transition-fast);
  border: 1px solid var(--border-color);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  text-decoration: none;
  position: relative;
  overflow: hidden;
}

.admin-menu-card:not(.disabled):hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
  border-color: var(--primary-color);
}

.admin-menu-card.disabled {
  opacity: 0.7;
  cursor: not-allowed;
  background-color: #f8fafc;
}

.admin-menu-icon {
  font-size: 2.5rem;
  color: var(--primary-color);
}

.admin-menu-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-color);
  margin: 0;
}

.admin-menu-description {
  color: var(--text-muted);
  font-size: 0.875rem;
  line-height: 1.5;
  margin: 0;
}
</style> 
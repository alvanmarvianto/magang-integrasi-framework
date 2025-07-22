<template>
  <div class="admin-container">
    <AdminNavbar 
      title="Back Office" 
      :showBackButton="true"
      backUrl="/"
    />

    <div class="admin-menu">
      <a href="/admin/apps" class="admin-menu-card">
        <font-awesome-icon icon="fa-solid fa-window-restore" class="admin-menu-icon" />
        <h2 class="admin-menu-title">Manajemen Aplikasi</h2>
        <p class="admin-menu-description">Kelola aplikasi dan integrasi antar aplikasi</p>
      </a>

      <a href="/admin/technology" class="admin-menu-card">
        <font-awesome-icon icon="fa-solid fa-microchip" class="admin-menu-icon" />
        <h2 class="admin-menu-title">Manajemen Teknologi</h2>
        <p class="admin-menu-description">Kelola komponen teknologi seperti database, framework, dll</p>
      </a>

      <a href="/admin/stream/sp" class="admin-menu-card">
        <font-awesome-icon icon="fa-solid fa-project-diagram" class="admin-menu-icon" />
        <h2 class="admin-menu-title">Manajemen Diagram</h2>
        <p class="admin-menu-description">Kelola tata letak dan tampilan diagram integrasi</p>
      </a>

      <a href="/admin/integrations" class="admin-menu-card">
        <font-awesome-icon icon="fa-solid fa-network-wired" class="admin-menu-icon" />
        <h2 class="admin-menu-title">Manajemen Koneksi</h2>
        <p class="admin-menu-description">Kelola hubungan dan integrasi antar aplikasi secara detail</p>
      </a>
    </div>
  </div>
</template>

<script setup lang="ts">
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import AdminNavbar from '@/components/Admin/AdminNavbar.vue';
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
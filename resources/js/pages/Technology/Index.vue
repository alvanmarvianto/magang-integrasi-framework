<template>
    <div id="container">
        <Sidebar title="Teknologi" :title-style="{ fontSize: '1.75em' }" icon="fa-solid fa-microchip">
            <SidebarNavigation :links="navigationLinks" />
        </Sidebar>

        <main id="main-content">
            <div id="menu-toggle" v-show="!visible" :class="{ active: visible }"
                @click.stop="toggleSidebar">
                <FontAwesomeIcon icon="fa-solid fa-bars" />
            </div>
            <div id="technology-container">
                <!-- Header -->
                <div class="index-header">
                    <div class="header-content">
                        <h1>
                            <FontAwesomeIcon icon="fa-solid fa-microchip" />
                            Daftar Teknologi
                        </h1>
                    </div>
                </div>

                <div class="tech-categories-grid">
                    <!-- Vendors -->
                    <div class="tech-category-card">
                        <div class="tech-category-header">
                            <FontAwesomeIcon icon="fa-solid fa-building" class="category-icon" />
                            <h3>Vendor</h3>
                        </div>
                        <div class="tech-items-grid">
                            <a v-for="vendor in technologies.vendors" :key="vendor"
                                :href="getRoute('technology.vendor', { vendor_name: vendor })" class="tech-item">
                                {{ vendor }}
                            </a>
                        </div>
                    </div>

                    <!-- App Types -->
                    <div class="tech-category-card">
                        <div class="tech-category-header">
                            <FontAwesomeIcon icon="fa-solid fa-cube" class="category-icon" />
                            <h3>Jenis Aplikasi</h3>
                        </div>
                        <div class="tech-items-grid">
                            <a v-for="appType in technologies.appTypes" :key="appType"
                                :href="getRoute('technology.app_type', { app_type: appType })" class="tech-item">
                                {{ formatTechName(appType) }}
                            </a>
                        </div>
                    </div>

                    <!-- Stratifications -->
                    <div class="tech-category-card">
                        <div class="tech-category-header">
                            <FontAwesomeIcon icon="fa-solid fa-layer-group" class="category-icon" />
                            <h3>Stratifikasi</h3>
                        </div>
                        <div class="tech-items-grid">
                            <a v-for="stratification in technologies.stratifications" :key="stratification"
                                :href="getRoute('technology.stratification', { stratification })" class="tech-item">
                                {{ formatTechName(stratification) }}
                            </a>
                        </div>
                    </div>



                    <!-- Operating Systems -->
                    <div class="tech-category-card">
                        <div class="tech-category-header">
                            <FontAwesomeIcon icon="fa-solid fa-desktop" class="category-icon" />
                            <h3>Sistem Operasi</h3>
                        </div>
                        <div class="tech-items-grid">
                            <a v-for="os in technologies.operatingSystems" :key="os"
                                :href="getRoute('technology.os', { os_name: os })" class="tech-item">
                                {{ os }}
                            </a>
                        </div>
                    </div>

                    <!-- Databases -->
                    <div class="tech-category-card">
                        <div class="tech-category-header">
                            <FontAwesomeIcon icon="fa-solid fa-database" class="category-icon" />
                            <h3>Database</h3>
                        </div>
                        <div class="tech-items-grid">
                            <a v-for="database in technologies.databases" :key="database"
                                :href="getRoute('technology.database', { database_name: database })" class="tech-item">
                                {{ database }}
                            </a>
                        </div>
                    </div>

                    <!-- Programming Languages -->
                    <div class="tech-category-card">
                        <div class="tech-category-header">
                            <FontAwesomeIcon icon="fa-solid fa-code" class="category-icon" />
                            <h3>Bahasa Pemrograman</h3>
                        </div>
                        <div class="tech-items-grid">
                            <a v-for="language in technologies.languages" :key="language"
                                :href="getRoute('technology.language', { language_name: language })" class="tech-item">
                                {{ language }}
                            </a>
                        </div>
                    </div>
                    
                    <!-- Third Party -->
                    <div class="tech-category-card">
                        <div class="tech-category-header">
                            <FontAwesomeIcon icon="fa-solid fa-plug" class="category-icon" />
                            <h3>Third Party</h3>
                        </div>
                        <div class="tech-items-grid">
                            <a v-for="thirdParty in technologies.thirdParties" :key="thirdParty"
                                :href="getRoute('technology.third_party', { third_party_name: thirdParty })" class="tech-item">
                                {{ thirdParty }}
                            </a>
                        </div>
                    </div>

                    <!-- Middleware -->
                    <div class="tech-category-card">
                        <div class="tech-category-header">
                            <FontAwesomeIcon icon="fa-solid fa-exchange-alt" class="category-icon" />
                            <h3>Middleware</h3>
                        </div>
                        <div class="tech-items-grid">
                            <a v-for="middleware in technologies.middlewares" :key="middleware"
                                :href="getRoute('technology.middleware', { middleware_name: middleware })" class="tech-item">
                                {{ middleware }}
                            </a>
                        </div>
                    </div>

                    <!-- Frameworks -->
                    <div class="tech-category-card">
                        <div class="tech-category-header">
                            <FontAwesomeIcon icon="fa-solid fa-tools" class="category-icon" />
                            <h3>Framework</h3>
                        </div>
                        <div class="tech-items-grid">
                            <a v-for="framework in technologies.frameworks" :key="framework"
                                :href="getRoute('technology.framework', { framework_name: framework })" class="tech-item">
                                {{ framework }}
                            </a>
                        </div>
                    </div>

                    <!-- Platforms -->
                    <div class="tech-category-card">
                        <div class="tech-category-header">
                            <FontAwesomeIcon icon="fa-solid fa-cloud" class="category-icon" />
                            <h3>Platform</h3>
                        </div>
                        <div class="tech-items-grid">
                            <a v-for="platform in technologies.platforms" :key="platform"
                                :href="getRoute('technology.platform', { platform_name: platform })" class="tech-item">
                                {{ platform }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { useSidebar } from '../../composables/useSidebar';
import { useRoutes } from '../../composables/useRoutes';
import { router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import Sidebar from '../../components/Sidebar/Sidebar.vue';
import SidebarNavigation from '../../components/Sidebar/SidebarNavigation.vue';

interface Technologies {
    appTypes: string[];
    stratifications: string[];
    vendors: string[];
    operatingSystems: string[];
    databases: string[];
    languages: string[];
    frameworks: string[];
    middlewares: string[];
    thirdParties: string[];
    platforms: string[];
}

interface Props {
    technologies: Technologies;
}

const props = defineProps<Props>();

const { visible, isMobile, toggleSidebar } = useSidebar();
const { visitRoute, getRoute } = useRoutes();

const navigationLinks = [
    {
        icon: 'fa-solid fa-home',
        text: 'Halaman Utama',
        onClick: () => visitRoute('index'),
    },
    {
    icon: 'fa-solid fa-file-contract',
    text: 'Daftar Kontrak',
    onClick: () => visitRoute('contract.index'),
  },
];

function formatTechName(name: string): string {
    return name.split('_').map(word =>
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
}
</script>

<style scoped>
@import '../../../css/app.css';
@import '../../../css/technology.css';
@import '../../../css/index-shared.css';

/* Categories Grid Layout */
.tech-categories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 2rem;
  padding: 1rem;
}

/* Technology-specific category card styling */
.tech-category-card {
  background: var(--card-bg, white);
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
  height: 400px;
  display: flex;
  flex-direction: column;
}

.tech-category-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

/* Category Header */
.tech-category-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border-color, #e5e7eb);
}

.category-icon {
  font-size: 1.5rem;
  color: var(--primary-color, #007AFF);
}

.tech-category-header h3 {
  flex: 1;
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary, #1f2937);
}

.category-count {
  font-size: 0.875rem;
  color: var(--text-secondary, #6b7280);
  background: var(--bg-alt, #f9fafb);
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-weight: 500;
}

/* Technology Items Grid */
.tech-items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 0.75rem;
  overflow-y: auto;
  max-height: calc(400px - 120px);
}

/* Technology Items Styling */
.tech-item {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.75rem 1rem;
  background: rgba(255, 255, 255, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  text-decoration: none;
  color: var(--text-primary, #1f2937);
  font-size: 0.875rem;
  font-weight: 500;
  text-align: center;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
  min-height: 44px;
}

.tech-item:hover {
  background: rgba(255, 255, 255, 0.5);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  color: var(--primary-color, #007AFF);
  border-color: var(--primary-color, #007AFF);
}

/* Empty State for Technology Cards */
.tech-category-card:has(.tech-items-grid:empty) {
  opacity: 0.6;
}

.tech-category-card:has(.tech-items-grid:empty) .tech-category-header {
  border-bottom-color: transparent;
}

.tech-category-card:has(.tech-items-grid:empty)::after {
  content: 'Belum ada data tersedia';
  display: block;
  text-align: center;
  color: var(--text-secondary, #6b7280);
  font-style: italic;
  padding: 2rem;
}

/* Responsive design for Technology Index */
@media (max-width: 768px) {
  .tech-categories-grid {
    grid-template-columns: 1fr;
    padding: 0.5rem;
  }

  .tech-category-card {
    padding: 1rem;
    height: 350px;
  }

  .tech-items-grid {
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 0.5rem;
    max-height: calc(350px - 100px);
  }

  .tech-item {
    padding: 0.5rem 0.75rem;
    font-size: 0.8rem;
  }
}
</style>

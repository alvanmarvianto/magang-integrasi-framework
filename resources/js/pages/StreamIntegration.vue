<template>
    <div id="container">
        <aside id="sidebar">
            <button id="sidebar-close" @click="closeSidebar">
                <i class="fas fa-times"></i>
            </button>
            <header>
                <h1>
                    <i class="fas fa-stream"></i>
                    Integrasi Stream {{ streamName }}
                </h1>
            </header>
            <div class="sidebar-content">
                <div class="navigation">
                    <a @click.prevent="$inertia.visit('/')" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Halaman Utama</span>
                    </a>
                </div>

                <div class="legend">
                    <h3>Node Legend</h3>
                    <ul>
                        <li><span class="legend-key circle sp"></span> Aplikasi SP</li>
                        <li><span class="legend-key circle mi"></span> Aplikasi MI</li>
                        <li><span class="legend-key circle ssk-mon"></span> Aplikasi SSK & Moneter</li>
                        <li><span class="legend-key circle market"></span> Aplikasi Market</li>
                        <li><span class="legend-key circle internal"></span> Aplikasi Internal BI di luar DLDS</li>
                        <li>
                            <span class="legend-key circle external"></span>
                            Aplikasi Eksternal BI
                        </li>
                        <li><span class="legend-key circle middleware"></span> Middleware</li>
                    </ul>
                </div>

                <div class="legend">
                    <h3>Link Legend</h3>
                    <ul>
                        <li><span class="legend-key line direct"></span> Direct</li>
                        <li><span class="legend-key line soa"></span> SOA</li>
                        <li><span class="legend-key line sftp"></span> SFTP</li>
                    </ul>
                </div>
            </div>
        </aside>

        <main id="main-content">
            <div id="menu-toggle" v-show="isMobile && !visible" :class="{ active: visible }"
                @click.stop="toggleSidebar">
                <i class="fas fa-bars"></i>
            </div>
            <div id="body"></div>
        </main>
    </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { useSidebar } from '../composables/useSidebar';
import { useD3ForceStreamIntegration } from '../composables/useD3ForceStreamIntegration';

const props = defineProps<{
    streamName: string;
    graphData: {
        nodes: { id: number; name: string; lingkup: string }[];
        links: { source: number; target: number; type: string }[];
    };
}>();

const { visible, isMobile, toggleSidebar, closeSidebar } = useSidebar();
useD3ForceStreamIntegration(props.graphData, props.streamName);

</script>

<style scoped>
@import '../../css/app.css';

:deep(.link) {
    stroke-opacity: 0.6;
    stroke-width: 1.5px;
    transition: d 0.1s ease-out;
}

:deep(.node text) {
    fill: var(--text-color);
    font-size: 12px;
    pointer-events: auto;
    font-family: sans-serif;
}

:deep(.node circle) {
    transition: all 0.2s ease;
}

:deep(.node circle:hover) {
    stroke-width: 4px;
    filter: brightness(1.1);
}

:deep(.node text:hover) {
    font-weight: bold;
}

:deep(.node-border) {
    stroke-opacity: 0.6;
    stroke-width: 3px;
}
</style>

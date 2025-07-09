<template>
    <div id="container">
        <aside id="sidebar">
            <header>
                <h1>Integrasi Stream {{ streamName }}</h1>
            </header>
            <div class="sidebar-content">
                <div class="notes">
                    <h3> About </h3>
                    <p>
                        Grafik ini menampilkan interkoneksi antar aplikasi
                        di dalam stream {{ streamName }}.
                    </p>
                    <a @click.prevent="router.visit('/index')" class="sidebar-link" style="cursor: pointer;">
                        <i class="fas fa-arrow-left"></i> Kembali ke Halaman Utama
                    </a>
                </div>

                <div class="legend">
                    <h3>Node Legend</h3>
                    <ul>
                        <li><span class="legend-key circle sp"></span> Aplikasi SP</li>
                        <li><span class="legend-key circle mi"></span> Aplikasi MI</li>
                        <li>
                            <span class="legend-key circle ssk-mon"></span> Aplikasi SSK &
                            Moneter
                        </li>
                        <li>
                            <span class="legend-key circle market"></span> Aplikasi Market
                        </li>
                        <li>
                            <span class="legend-key circle internal"></span> Aplikasi
                            Internal BI di luar DLDS
                        </li>
                        <li>
                            <span class="legend-key circle external"></span>
                            Aplikasi Eksternal BI
                        </li>
                        <li>
                            <span class="legend-key circle middleware"></span> Middleware
                        </li>
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
            <div id="menu-toggle" @click.stop="toggleSidebar">
                <i class="fas fa-bars"></i>
            </div>
            <div id="body"></div>
        </main>
    </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { onMounted } from 'vue';
import * as d3 from 'd3';

const props = defineProps<{
    streamName: string;
    graphData: {
        nodes: { id: number; name: string; lingkup: string }[];
        links: { source: number; target: number; type: string }[];
    };
}>();

function goBack() {
    window.history.back();
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar?.classList.toggle('visible');
}

onMounted(() => {
    const container = document.getElementById('body');
    if (!container || !props.graphData.nodes.length) return;

    const width = container.clientWidth;
    const height = container.clientHeight;
    const centerX = width / 2;
    const centerY = height / 2;
    const mainRadius = Math.min(width, height) / 3;

    const { nodes, links } = props.graphData;

    const linkCount = {};
    links.forEach(link => {
        const sourceId = typeof link.source === 'object' ? link.source.id : link.source;
        const targetId = typeof link.target === 'object' ? link.target.id : link.target;
        const key = sourceId < targetId ? `${sourceId}-${targetId}` : `${targetId}-${sourceId}`;
        linkCount[key] = 1;
    });

    const linkNum = {};
    links.forEach(link => {
        const sourceId = typeof link.source === 'object' ? link.source.id : link.source;
        const targetId = typeof link.target === 'object' ? link.target.id : link.target;
        const key = sourceId < targetId ? `${sourceId}-${targetId}` : `${targetId}-${sourceId}`;
        link.linknum = linkNum[key] = 1;
        link.linktotal = linkCount[key];
    });


    const svg = d3
        .select(container)
        .append('svg')
        .attr('width', width)
        .attr('height', height);

    const simulation = d3.forceSimulation(nodes)
        .force('link', d3.forceLink(links).id(d => d.id).distance(150))
        .force('charge', d3.forceManyBody().strength(-150))
        .force('collide', d3.forceCollide().radius(30).iterations(2))
        .force('x', d3.forceX(centerX).strength(d => (d.lingkup === props.streamName ? 0.15 : 0)))
        .force('y', d3.forceY(centerY).strength(d => (d.lingkup === props.streamName ? 0.15 : 0)))
        .force('radial', d3.forceRadial(mainRadius, centerX, centerY).strength(d => (d.lingkup !== props.streamName ? 0.8 : 0)));

    const link = svg
        .append('g')
        .attr('fill', 'none')
        .selectAll('path')
        .data(links)
        .enter()
        .append('path')
        .attr('class', (d) => `link ${d.type}`);

    const node = svg
        .append('g')
        .selectAll('g')
        .data(nodes)
        .enter()
        .append('g')
        .attr('class', 'node');

    node
        .append('circle')
        .attr('r', 10)
        .attr('class', (d) => `node-border ${d.lingkup}`)
        .attr('fill', '#fff');

    node
        .append('text')
        .attr('dy', -15)
        .attr('text-anchor', 'middle')
        .text((d) => d.name);

    const drag = d3.drag()
        .on('start', function (event) {
            const d = d3.select(this).datum();
            if (!event.active) simulation.alphaTarget(0.3).restart();
            d.fx = d.x;
            d.fy = d.y;
        })
        .on('drag', function (event) {
            const d = d3.select(this).datum();
            d.fx = event.x;
            d.fy = event.y;
        })
        .on('end', function (event) {
            const d = d3.select(this).datum();
            if (!event.active) simulation.alphaTarget(0);
            d.fx = null;
            d.fy = null;
        });

    node.call(drag);
    simulation.on('tick', () => {
        link.attr('d', d => {
            if (d.linktotal === 1) {
                return `M${d.source.x},${d.source.y} L${d.target.x},${d.target.y}`;
            }

            // For multiple links, calculate a curve
            const dx = d.target.x - d.source.x;
            const dy = d.target.y - d.source.y;
            const dr = Math.sqrt(dx * dx + dy * dy);

            const sweepFlag = d.linknum % 2 === 0 ? 1 : 0;

            const arcFactor = 1.5 + (d.linknum / 2);

            return `M${d.source.x},${d.source.y} A${dr * arcFactor},${dr * arcFactor} 0 0,${sweepFlag} ${d.target.x},${d.target.y}`;
        });

        node.attr('transform', d => `translate(${d.x},${d.y})`);
    });
});
</script>

<style scoped>
@import '../../css/app.css';


:deep(.link) {
    stroke-opacity: 0.6;
    stroke-width: 1.5px;
}

:deep(.node text) {
    fill: var(--text-color);
    font-size: 12px;
    pointer-events: none;
    font-family: sans-serif;
}

:deep(.node-border) {
    stroke-width: 3px;
}
</style>
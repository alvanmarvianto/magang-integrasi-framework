<template>
    <div id="container">
        <aside id="sidebar">
            <header>
                <h1>Integrasi Stream {{ streamName }}</h1>
            </header>
            <div class="sidebar-content">
                <div class="navigation">
                    <a @click.prevent="$inertia.visit('/')" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Halaman Utama</span>
                    </a>
                </div>
                <div class="notes">
                    <h3>About</h3>
                    <p>Grafik ini menampilkan interkoneksi antar aplikasi di dalam stream {{ streamName }}.</p>
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
            <div id="menu-toggle" @click.stop="toggleSidebar">
                <i class="fas fa-bars"></i>
            </div>
            <div id="body"></div>
        </main>
    </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import * as d3 from 'd3';
import { onMounted } from 'vue';

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
    links.forEach((link) => {
        const sourceId = typeof link.source === 'object' ? link.source.id : link.source;
        const targetId = typeof link.target === 'object' ? link.target.id : link.target;
        const key = sourceId < targetId ? `${sourceId}-${targetId}` : `${targetId}-${sourceId}`;
        linkCount[key] = 1;
    });

    const linkNum = {};
    links.forEach((link) => {
        const sourceId = typeof link.source === 'object' ? link.source.id : link.source;
        const targetId = typeof link.target === 'object' ? link.target.id : link.target;
        const key = sourceId < targetId ? `${sourceId}-${targetId}` : `${targetId}-${sourceId}`;
        link.linknum = linkNum[key] = 1;
        link.linktotal = linkCount[key];
    });

    const svg = d3.select(container).append('svg').attr('width', width).attr('height', height);

    const simulation = d3
        .forceSimulation(nodes)
        .force(
            'link',
            d3
                .forceLink(links)
                .id((d) => d.id)
                .distance(150),
        )
        .force('charge', d3.forceManyBody().strength(-150))
        .force('collide', d3.forceCollide().radius(30).iterations(2))
        .force(
            'x',
            d3.forceX(centerX).strength((d) => (d.lingkup === props.streamName ? 0.15 : 0)),
        )
        .force(
            'y',
            d3.forceY(centerY).strength((d) => (d.lingkup === props.streamName ? 0.15 : 0)),
        )
        .force(
            'radial',
            d3.forceRadial(mainRadius, centerX, centerY).strength((d) => (d.lingkup !== props.streamName ? 0.8 : 0)),
        );

    const link = svg
        .append('g')
        .attr('fill', 'none')
        .selectAll('path')
        .data(links)
        .enter()
        .append('path')
        .attr('class', (d) => `link ${d.type}`);

    const node = svg.append('g').selectAll('g').data(nodes).enter().append('g').attr('class', 'node');

    node.append('circle')
        .attr('r', 10)
        .attr('class', (d) => `node-border ${d.lingkup}`)
        .attr('fill', '#fff');

    node.append('text')
        .attr('class', 'node-label')
        .attr('dy', '0.35em')
        .attr('text-anchor', 'middle')
        .text((d) => d.name);

    const drag = d3
        .drag()
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

    function lineIntersectsCircle(x1, y1, x2, y2, cx, cy, r) {
        const dx = x2 - x1;
        const dy = y2 - y1;
        const fx = x1 - cx;
        const fy = y1 - cy;

        const a = dx * dx + dy * dy;
        const b = 2 * (fx * dx + fy * dy);
        const c = fx * fx + fy * fy - r * r;

        const discriminant = b * b - 4 * a * c;

        if (discriminant < 0) return false;

        const discriminantSqrt = Math.sqrt(discriminant);
        const t1 = (-b - discriminantSqrt) / (2 * a);
        const t2 = (-b + discriminantSqrt) / (2 * a);

        return (t1 >= 0 && t1 <= 1) || (t2 >= 0 && t2 <= 1) || (t1 < 0 && t2 > 1);
    }

    function findOptimalPath(source, target, nodes, nodeRadius = 20) {
        const dx = target.x - source.x;
        const dy = target.y - source.y;
        const distance = Math.sqrt(dx * dx + dy * dy);

        if (distance < 50) {
            return `M${source.x},${source.y} L${target.x},${target.y}`;
        }

        const perpX = -dy / distance;
        const perpY = dx / distance;

        const attempts = [
            { offset: 0, type: 'straight' },
            { offset: distance * 0.15, type: 'curve' },
            { offset: -distance * 0.15, type: 'curve' },
            { offset: distance * 0.3, type: 'curve' },
            { offset: -distance * 0.3, type: 'curve' },
            { offset: distance * 0.5, type: 'curve' },
            { offset: -distance * 0.5, type: 'curve' },
        ];

        for (let attempt of attempts) {
            let pathClear = true;

            if (attempt.type === 'straight') {
                for (let node of nodes) {
                    if (node.id === source.id || node.id === target.id) continue;

                    if (lineIntersectsCircle(source.x, source.y, target.x, target.y, node.x, node.y, nodeRadius)) {
                        pathClear = false;
                        break;
                    }
                }

                if (pathClear) {
                    return `M${source.x},${source.y} L${target.x},${target.y}`;
                }
            } else {
                const midX = (source.x + target.x) / 2;
                const midY = (source.y + target.y) / 2;
                const controlX = midX + perpX * attempt.offset;
                const controlY = midY + perpY * attempt.offset;

                for (let t = 0; t <= 1; t += 0.1) {
                    const curveX = (1 - t) * (1 - t) * source.x + 2 * (1 - t) * t * controlX + t * t * target.x;
                    const curveY = (1 - t) * (1 - t) * source.y + 2 * (1 - t) * t * controlY + t * t * target.y;

                    for (let node of nodes) {
                        if (node.id === source.id || node.id === target.id) continue;

                        const distToNode = Math.sqrt((curveX - node.x) ** 2 + (curveY - node.y) ** 2);
                        if (distToNode < nodeRadius) {
                            pathClear = false;
                            break;
                        }
                    }

                    if (!pathClear) break;
                }

                if (pathClear) {
                    return `M${source.x},${source.y} Q${controlX},${controlY} ${target.x},${target.y}`;
                }
            }
        }

        const midX = (source.x + target.x) / 2;
        const midY = (source.y + target.y) / 2;
        const fallbackControlX = midX + perpX * distance * 0.6;
        const fallbackControlY = midY + perpY * distance * 0.6;

        return `M${source.x},${source.y} Q${fallbackControlX},${fallbackControlY} ${target.x},${target.y}`;
    }

    node.call(drag);
    simulation.on('tick', () => {
        link.attr('d', (d) => {
            return findOptimalPath(d.source, d.target, nodes, 20);
        });

        svg.selectAll('.node-label')
            .attr('x', function (d) {
                if (d.x > centerX) {
                    return 16;
                }
                return -16;
            })
            .attr('text-anchor', function (d) {
                if (d.x > centerX) {
                    return 'start';
                }

                return 'end';
            });

        node.attr('transform', (d) => `translate(${d.x},${d.y})`);
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
    stroke-opacity: 0.6;
    stroke-width: 3px;
}
</style>

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
            <div id="menu-toggle" v-show="isMobile && !visible" :class="{ active: visible }" @click.stop="toggleSidebar">
                <i class="fas fa-bars"></i>
            </div>
            <div id="body"></div>
        </main>
    </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import * as d3 from 'd3';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps<{
    streamName: string;
    graphData: {
        nodes: { id: number; name: string; lingkup: string }[];
        links: { source: number; target: number; type: string }[];
    };
}>();

const visible = ref(false)
const isMobile = ref(false)

function checkScreenSize() {
    isMobile.value = window.innerWidth <= 768
    if (!isMobile.value) {
        visible.value = false
        const sidebar = document.getElementById('sidebar')
        sidebar?.classList.remove('visible')
    }
}

function goBack() {
    window.history.back();
}

function toggleSidebar() {
    visible.value = !visible.value
    const sidebar = document.getElementById('sidebar');
    sidebar?.classList.toggle('visible');
}

function closeSidebar() {
    visible.value = false
    const sidebar = document.getElementById('sidebar');
    sidebar?.classList.remove('visible');
}

function handleClickOutside(event: Event) {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menu-toggle');
    
    if (sidebar && menuToggle && !sidebar.contains(event.target as Node) && !menuToggle.contains(event.target as Node)) {
        visible.value = false
        sidebar.classList.remove('visible');
    }
}

function handleEscapeKey(event: KeyboardEvent) {
    if (event.key === 'Escape') {
        visible.value = false
        const sidebar = document.getElementById('sidebar');
        sidebar?.classList.remove('visible');
    }
}

onMounted(() => {
    const container = document.getElementById('body');
    if (!container || !props.graphData.nodes.length) return;

    checkScreenSize()
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleEscapeKey);
    window.addEventListener('resize', checkScreenSize);

    let svg: any;
    let simulation: any;
    let node: any;
    let link: any;

    function initializeGraph() {
        d3.select(container).select('svg').remove();

        const width = container.clientWidth;
        const height = container.clientHeight;
        const centerX = width / 2;
        const centerY = height / 2;
        const mainRadius = Math.min(width, height) / 3;

        const { nodes, links: originalLinks } = props.graphData;

        const linkMap = new Map();
        const links = originalLinks.filter((link) => {
            const sourceId = typeof link.source === 'object' ? link.source.id : link.source;
            const targetId = typeof link.target === 'object' ? link.target.id : link.target;
            const key = sourceId < targetId ? `${sourceId}-${targetId}` : `${targetId}-${sourceId}`;
            
            if (!linkMap.has(key)) {
                linkMap.set(key, link);
                return true;
            }
            return false;
        });

        svg = d3.select(container).append('svg').attr('width', width).attr('height', height);

        simulation = d3
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

        link = svg
            .append('g')
            .attr('fill', 'none')
            .selectAll('path')
            .data(links)
            .enter()
            .append('path')
            .attr('class', (d) => `link ${d.type}`);

        node = svg.append('g').selectAll('g').data(nodes).enter().append('g').attr('class', 'node');

        node.append('circle')
            .attr('r', 10)
            .attr('class', (d) => `node-border ${d.lingkup}`)
            .attr('fill', '#fff')
            .style('cursor', 'pointer')
            .on('click', function(event, d) {
                if (d.id && (d.lingkup === 'sp' || d.lingkup === 'mi' || d.lingkup === 'ssk' || d.lingkup === 'market' || d.lingkup === 'moneter')) {
                    event.stopPropagation();
                    window.location.href = `/integration/app/${d.id}`;
                }
            });

        node.append('text')
            .attr('class', 'node-label')
            .attr('dy', '0.35em')
            .attr('text-anchor', 'middle')
            .style('cursor', 'pointer')
            .text((d) => d.name)
            .on('click', function(event, d) {
                if (d.id && (d.lingkup === 'sp' || d.lingkup === 'mi' || d.lingkup === 'ssk' || d.lingkup === 'market' || d.lingkup === 'moneter')) {
                    event.stopPropagation();
                    window.location.href = `/integration/app/${d.id}`;
                }
            });

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

        const linkPathCache = new Map();
        
        function findOptimalPath(link, nodes, nodeRadius = 20) {
            const source = link.source;
            const target = link.target;
            const linkId = `${source.id}-${target.id}`;
            
            const cached = linkPathCache.get(linkId);
            if (cached) {
                const dx = Math.abs(source.x - cached.sourceX) + Math.abs(target.x - cached.targetX);
                const dy = Math.abs(source.y - cached.sourceY) + Math.abs(target.y - cached.targetY);
                if (dx + dy < 10) {
                    if (cached.type === 'straight') {
                        return `M${source.x},${source.y} L${target.x},${target.y}`;
                    } else {
                        const midX = (source.x + target.x) / 2;
                        const midY = (source.y + target.y) / 2;
                        const dx = target.x - source.x;
                        const dy = target.y - source.y;
                        const distance = Math.sqrt(dx * dx + dy * dy);
                        const perpX = -dy / distance;
                        const perpY = dx / distance;
                        const controlX = midX + perpX * cached.offset;
                        const controlY = midY + perpY * cached.offset;
                        return `M${source.x},${source.y} Q${controlX},${controlY} ${target.x},${target.y}`;
                    }
                }
            }

            const dx = target.x - source.x;
            const dy = target.y - source.y;
            const distance = Math.sqrt(dx * dx + dy * dy);

            if (distance < 50) {
                const pathData = {
                    sourceX: source.x,
                    sourceY: source.y,
                    targetX: target.x,
                    targetY: target.y,
                    type: 'straight',
                    offset: 0
                };
                linkPathCache.set(linkId, pathData);
                return `M${source.x},${source.y} L${target.x},${target.y}`;
            }

            const perpX = -dy / distance;
            const perpY = dx / distance;

            let attempts = [
                { offset: 0, type: 'straight' },
                { offset: distance * 0.15, type: 'curve' },
                { offset: -distance * 0.15, type: 'curve' },
                { offset: distance * 0.3, type: 'curve' },
                { offset: -distance * 0.3, type: 'curve' },
                { offset: distance * 0.5, type: 'curve' },
                { offset: -distance * 0.5, type: 'curve' },
            ];

            if (cached && cached.type === 'curve') {
                attempts.unshift({ offset: cached.offset, type: 'curve' });
            }

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
                        const pathData = {
                            sourceX: source.x,
                            sourceY: source.y,
                            targetX: target.x,
                            targetY: target.y,
                            type: 'straight',
                            offset: 0
                        };
                        linkPathCache.set(linkId, pathData);
                        return `M${source.x},${source.y} L${target.x},${target.y}`;
                    }
                } else {
                    const midX = (source.x + target.x) / 2;
                    const midY = (source.y + target.y) / 2;
                    const controlX = midX + perpX * attempt.offset;
                    const controlY = midY + perpY * attempt.offset;

                    for (let t = 0; t <= 1; t += 0.2) {
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
                        const pathData = {
                            sourceX: source.x,
                            sourceY: source.y,
                            targetX: target.x,
                            targetY: target.y,
                            type: 'curve',
                            offset: attempt.offset
                        };
                        linkPathCache.set(linkId, pathData);
                        return `M${source.x},${source.y} Q${controlX},${controlY} ${target.x},${target.y}`;
                    }
                }
            }
            const midX = (source.x + target.x) / 2;
            const midY = (source.y + target.y) / 2;
            const fallbackOffset = distance * 0.6;
            const fallbackControlX = midX + perpX * fallbackOffset;
            const fallbackControlY = midY + perpY * fallbackOffset;

            const pathData = {
                sourceX: source.x,
                sourceY: source.y,
                targetX: target.x,
                targetY: target.y,
                type: 'curve',
                offset: fallbackOffset
            };
            linkPathCache.set(linkId, pathData);

            return `M${source.x},${source.y} Q${fallbackControlX},${fallbackControlY} ${target.x},${target.y}`;
        }

        node.call(drag);
        simulation.on('tick', () => {
            link.attr('d', (d) => {
                return findOptimalPath(d, nodes, 20);
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
    }

    let resizeHandler = () => {
        initializeGraph();
    };

    initializeGraph();

    window.addEventListener('resize', resizeHandler);

    (window as any).__streamIntegrationResizeHandler = resizeHandler;
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleEscapeKey);
    window.removeEventListener('resize', checkScreenSize);
    
    const resizeHandler = (window as any).__streamIntegrationResizeHandler;
    if (resizeHandler) {
        window.removeEventListener('resize', resizeHandler);
        delete (window as any).__streamIntegrationResizeHandler;
    }
});
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

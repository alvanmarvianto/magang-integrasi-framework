<template>
  <div id="container">
    <aside id="sidebar">
      <button id="sidebar-close" @click="closeSidebar">
        <i class="fas fa-times"></i>
      </button>
      <header>
        <h1>
          <i class="fas fa-project-diagram"></i>
          Integrasi Aplikasi {{ appName }}
        </h1>
      </header>
      <div class="sidebar-content">
        <div class="navigation">
          <a @click.prevent="$inertia.visit('/')" class="nav-link">
            <i class="fas fa-home"></i>
            <span>Halaman Utama</span>
          </a>
          <a @click.prevent="$inertia.visit(`/technology/${parentAppId}`)" class="nav-link">
            <i class="fas fa-project-diagram"></i>
            <span>Halaman Teknologi</span>
          </a>
        </div>
        <div class="notes">
          <h3>About</h3>
          <p>
            Halaman ini menampilkan integrasi untuk aplikasi {{ appName }}.
          </p>
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
      <!-- <div id="loader" vif="loading"></div> -->
      <div id="body"></div>
      <p id="error-message" style="display: none"></p>
    </main>
  </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { onMounted, onUnmounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';

declare const d3: any;

interface NodeData {
  id?: number;
  app_id?: number;
  name: string;
  description?: string;
  url?: string;
  stream?: string;
  children?: NodeData[];
  _children?: NodeData[];
  parent?: NodeData;
  x0?: number;
  y0?: number;
  x?: number;
  y?: number;
  depth?: number;
  lingkup?: string;
  link?: string;
}

const props = defineProps<{
  integrationData: any;
  appName: string;
  streamName: string;
  parentAppId: number;
}>();

const loading = ref(true);
let root: NodeData | null = null;
let i = 0;
const duration = 750;
let tree: any, vis: any;

function goBack() {
  window.history.back();
}

function collapse(d: NodeData) {
  if (d.children) {
    d._children = d.children;
    d._children.forEach(collapse);
    d.children = undefined;
  }
}

function update(source: NodeData) {
  const container = d3.select('#body').node();
  const width = container.clientWidth;
  const height = container.clientHeight;
  const radius = Math.min(width, height) / 2 - 150;

  tree.size([360, radius]);
  const nodes = tree.nodes(root).reverse();
  const links = tree.links(nodes);

  nodes.forEach((d: NodeData) => (d.y = d.depth * 180));

  const node = vis.selectAll('g.node').data(nodes, (d: NodeData) => d.id || (d.id = ++i));

  const nodeEnter = node
    .enter()
    .append('g')
    .attr('class', 'node')
    .attr('transform', () => `rotate(${source.x0 - 90})translate(${source.y0})`)
    .on('click', (d: NodeData) => {
      toggle(d);
      update(d);
    });

  nodeEnter
    .append('circle')
    .attr('r', 1e-6)
    .attr('class', (d: NodeData) => `node-border ${d.lingkup || 'external'}`)
    .on('click', function (d) {
      if(d.depth === 0){
        d3.event.stopPropagation()
        window.location.href = `/technology/${d.app_id}`
      } else if (d.id && d.lingkup === 'sp' || d.lingkup === 'mi' || d.lingkup === 'ssk' || d.lingkup === 'market' || d.lingkup === 'moneter') {
        d3.event.stopPropagation()
        window.location.href = `/integration/app/${d.app_id}`
      }
    })

  nodeEnter
    .append('text')
    .attr('x', 0)
    .attr('dy', (d: NodeData) => {
      if (d.depth === 0) return '-1.5em';
      return d.x >= 90 && d.x < 270 ? '1.4em' : '-0.8em';
    })
    .attr('text-anchor', 'middle')
    .attr('transform', (d: NodeData) => `rotate(${-(d.x - 90)})`)
    .text((d: NodeData) => d.name)
    .style('fill-opacity', 1e-6)
    .style('font-weight', (d: NodeData) => (d.depth === 0 ? 'bold' : 'normal'))
    .style('font-size', (d: NodeData) => (d.depth === 0 ? '16px' : '12px'));


  const nodeUpdate = node
    .transition()
    .duration(duration)
    .attr('transform', (d: NodeData) => `rotate(${d.x - 90})translate(${d.y})`);

  nodeUpdate
    .select('circle')
    .attr('r', (d: NodeData) => (d.depth === 0 ? 8 : 6))
    .attr('class', (d: NodeData) => `node-border ${d.lingkup || 'external'}`)
    .style('fill', (d: NodeData) => {
      if (d.depth === 0) return `var(--${d.lingkup || 'eksternal'})`;
      return '#fff';
    });

  nodeUpdate
    .select('text')
    .style('fill-opacity', 1)
    .attr('dy', (d: NodeData) => {
      if (d.depth === 0) return '-1.5em';
      return d.x >= 90 && d.x < 270 ? '1.4em' : '-0.8em';
    })
    .attr('transform', (d: NodeData) => `rotate(${-(d.x - 90)})`);


  const nodeExit = node
    .exit()
    .transition()
    .duration(duration)
    .attr('transform', () => `rotate(${source.x - 90})translate(${source.y})`)
    .remove();

  nodeExit.select('circle').attr('r', 1e-6);
  nodeExit.select('text').style('fill-opacity', 1e-6);

  const link = vis.selectAll('path.link').data(links, (d: any) => d.target.id);
  const radialLink = d3.svg.line
    .radial()
    .interpolate("linear")
    .angle((d: NodeData) => (d.x * Math.PI) / 180)
    .radius((d: NodeData) => d.y);

  link
    .enter()
    .insert('path', 'g')
    .attr('class', (d: any) => `link ${d.target.link || ''}`)
    .attr('d', () => {
      const o = { x: source.x0, y: source.y0, parent: { x: source.x0, y: source.y0 } };
      return radialLink([o, o]);
    });


  link.transition().duration(duration).attr('d', (d: any) => radialLink([d.source, d.target]));

  link
    .exit()
    .transition()
    .duration(duration)
    .attr('d', () => {
      const o = { x: source.x, y: source.y, parent: { x: source.x, y: source.y } };
      return radialLink([o, o]);
    })
    .remove();

  nodes.forEach((d: NodeData) => {
    d.x0 = d.x;
    d.y0 = d.y;
  });
}

function toggle(d: NodeData) {
  if (d.children) {
    d._children = d.children;
    d.children = undefined;
  } else {
    d.children = d._children;
    d._children = undefined;
  }
}

function redraw() {
  d3.select('#body svg').remove();
  const container = d3.select('#body').node();
  const width = container.clientWidth;
  const height = container.clientHeight;
  const radius = Math.min(width, height) / 2 - 150;


  tree = d3.layout.tree().size([360, radius]).separation((a, b) => (a.parent == b.parent ? 1 : 2) / 1 + a.depth);

  vis = d3
    .select('#body')
    .append('svg')
    .attr('width', width)
    .attr('height', height)
    .append('g')
    .attr('transform', `translate(${width / 2},${height / 2})`);

  update(root);
}

function deepCloneWithoutParent(obj) {
  return JSON.parse(JSON.stringify(obj));
}

function toggleSidebar() {
  const sidebar = document.getElementById('sidebar')
  sidebar?.classList.toggle('visible')
}

function closeSidebar() {
  const sidebar = document.getElementById('sidebar')
  sidebar?.classList.remove('visible')
}

// Close sidebar when clicking outside on mobile
function handleClickOutside(event: Event) {
  const sidebar = document.getElementById('sidebar')
  const menuToggle = document.getElementById('menu-toggle')
  
  if (sidebar && menuToggle && !sidebar.contains(event.target as Node) && !menuToggle.contains(event.target as Node)) {
    sidebar.classList.remove('visible')
  }
}

// Close sidebar on escape key
function handleEscapeKey(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    const sidebar = document.getElementById('sidebar')
    sidebar?.classList.remove('visible')
  }
}

onMounted(() => {
  root = deepCloneWithoutParent(props.integrationData)
  root.x0 = 0
  root.y0 = 0
  if (root.children) root.children.forEach(collapse)
  redraw()
  
  // Add mobile sidebar event listeners
  document.addEventListener('click', handleClickOutside)
  document.addEventListener('keydown', handleEscapeKey)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  document.removeEventListener('keydown', handleEscapeKey)
})
</script>

<style scoped>
@import '../../css/app.css';
</style>
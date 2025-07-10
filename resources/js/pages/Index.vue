<template>
  <div id="container">
    <aside id="sidebar">
      <button id="sidebar-close" @click="closeSidebar">
        <i class="fas fa-times"></i>
      </button>
      <header>
        <h1>
          <i class="fas fa-sitemap"></i>
          Aplikasi BI
        </h1>
      </header>
      <div class="sidebar-content">
        <div id="controls">
          <div class="control-group">
            <label for="search">Search:</label>
            <div class="search-wrapper">
              <input v-model="searchTerm" @input="onSearchInput" type="text" id="search" list="search-suggestions"
                placeholder="Cari Aplikasi..." />
              <i class="fas fa-times-circle" id="clear-search" @click="clearSearch"></i>
            </div>
            <datalist id="search-suggestions">
              <option v-for="name in uniqueNodeNames" :key="name" :value="name" />
            </datalist>
          </div>
        </div>
        <div class="stream-links">
          <h3>Intergrasi dalam Stream</h3>
            <p>Navigasi ke halaman integrasi stream tertentu:</p>
          <div class="stream-buttons">
            <a href="/integration/stream/ssk" class="stream-link">
              <i class="fas fa-project-diagram"></i>
              <span>SSK Stream</span>
            </a>
            <a href="/integration/stream/moneter" class="stream-link">
              <i class="fas fa-coins"></i>
              <span>Moneter Stream</span>
            </a>
            <a href="/integration/stream/mi" class="stream-link">
              <i class="fas fa-chart-line"></i>
              <span>MI Stream</span>
            </a>
            <a href="/integration/stream/sp" class="stream-link">
              <i class="fas fa-shield-alt"></i>
              <span>SP Stream</span>
            </a>
            <a href="/integration/stream/market" class="stream-link">
              <i class="fas fa-store"></i>
              <span>Market Stream</span>
            </a>
          </div>
        </div>
        
        <div class="notes">
          <h3>Notes</h3>
          <p>Website ini berisi kumpulan aplikasi milik Bank Indonesia beserta integrasinya.</p>
        </div>
      </div>
    </aside>

    <main id="main-content">
      <div id="menu-toggle" v-show="isMobile && !visible" :class="{ active: visible }" @click.stop="toggleSidebar">
        <i class="fas fa-bars"></i>
      </div>
      <div id="loader" v-if="loading"></div>
      <div id="body"></div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import * as d3 from 'd3'

let i = 0
const duration = 750

const props = defineProps<{ appData: any }>()
const loading = ref(true)
const searchTerm = ref('')
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

let root: any = null
const allNodes: any[] = []

const uniqueNodeNames = ref<string[]>([])

const margin = [20, 120, 20, 140]
let tree: any, diagonal: any, vis: any

function collapse(d: any) {
  if (d.children) {
    d._children = d.children
    d._children.forEach((c: any) => collapse(c))
    d.children = null
  }
}

function processData(node: any, parent: any) {
  node.parent = parent
  allNodes.push(node)
  const children = node.children || node._children || []
  children.forEach((child: any) => processData(child, node))
}

function redraw() {
  d3.select('#body svg').remove()
  const container = d3.select('#body').node() as HTMLElement
  if (!container) return
  
  const width = container.clientWidth
  const height = container.clientHeight

  tree = d3.tree<any>().size([height - margin[0] - margin[2], width - margin[3] - margin[1]])
  diagonal = d3.linkHorizontal<any, any>().x((d: any) => d.y).y((d: any) => d.x)

  vis = d3.select('#body')
    .append('svg')
    .attr('width', width)
    .attr('height', height)
    .append('g')
    .attr('transform', `translate(${margin[3]},${margin[0]})`)

  update(root)
}

function update(source: any) {
  const container = d3.select('#body').node() as HTMLElement
  if (!container) return
  
  const containerWidth = container.clientWidth
  const containerHeight = container.clientHeight

  tree.size([containerHeight - margin[0] - margin[2], containerWidth - margin[3] - margin[1]])
  
  const treeData = tree(d3.hierarchy(root))
  const nodes = treeData.descendants()
  const links = treeData.links()

  let maxDepth = 0
  nodes.forEach((d: any) => { if (d.depth > maxDepth) maxDepth = d.depth })

  const availableWidth = containerWidth - margin[1] - margin[3]
  const nodeSpacing = maxDepth > 0 ? Math.min(240, availableWidth / maxDepth) : 240
  
  d3.select('#body svg').attr('width', containerWidth).attr('height', containerHeight)

  nodes.forEach((d: any) => d.y = d.depth * nodeSpacing)

  const sourceNode = source === root ? treeData : nodes.find((n: any) => n.data === source) || treeData

  const node = vis.selectAll('g.node').data(nodes, (d: any) => d.data.id || (d.data.id = ++i))

  const nodeEnter = node.enter().append('g')
    .attr('class', 'node')
    .attr('transform', (d: any) => `translate(${sourceNode.y0 || sourceNode.y || 0},${sourceNode.x0 || sourceNode.x || 0})`)
    .on('click', (event: any, d: any) => { toggle(d); update(d.data) })

  nodeEnter.append('circle')
    .attr('r', 1e-6)
    .style('fill', (d: any) => d.data._children ? 'var(--primary-color)' : '#fff')
    .style('stroke', 'var(--primary-color)')
    .on('click', function (event: any, d: any) {
      if (d.data.url) {
        event.stopPropagation()
        window.location.href = `${d.data.url}`
      }
    })

  nodeEnter.append('text')
    .attr('x', (d: any) => d.data.children || d.data._children ? -10 : 10)
    .attr('dy', '.35em')
    .attr('text-anchor', (d: any) => d.data.children || d.data._children ? 'end' : 'start')
    .text((d: any) => d.data.name)
    .style('fill-opacity', 1e-6)
    .on('click', function (event: any, d: any) {
      if (d.data.url) {
        event.stopPropagation()
        window.location.href = `${d.data.url}`
      }
    })

  nodeEnter.append('title').text((d: any) => d.data.description || '')

  const nodeUpdate = node.merge(nodeEnter).transition().duration(duration)
    .attr('transform', (d: any) => `translate(${d.y},${d.x})`)

  nodeUpdate.select('circle').attr('r', 6)
  nodeUpdate.select('text').style('fill-opacity', 1)

  const nodeExit = node.exit().transition().duration(duration)
    .attr('transform', (d: any) => `translate(${sourceNode.y},${sourceNode.x})`).remove()
  nodeExit.select('circle').attr('r', 1e-6)
  nodeExit.select('text').style('fill-opacity', 1e-6)

  const link = vis.selectAll('path.link').data(links, (d: any) => d.target.data.id)

  link.enter().insert('path', 'g')
    .attr('class', 'link')
    .attr('d', (d: any) => {
      const o = { x: sourceNode.x0 || sourceNode.x || 0, y: sourceNode.y0 || sourceNode.y || 0 }
      return diagonal({ source: o, target: o })
    })
    .transition().duration(duration)
    .attr('d', diagonal)

  link.transition().duration(duration).attr('d', diagonal)

  link.exit().transition().duration(duration)
    .attr('d', (d: any) => {
      const o = { x: sourceNode.x, y: sourceNode.y }
      return diagonal({ source: o, target: o })
    }).remove()

  nodes.forEach((d: any) => {
    d.x0 = d.x
    d.y0 = d.y
  })
}

function toggle(d: any) {
  if (d.data.children) {
    d.data._children = d.data.children
    d.data.children = null
  } else {
    d.data.children = d.data._children
    d.data._children = null
  }
}

function onSearchInput() {
  if (searchTerm.value.length > 2) {
    search(searchTerm.value)
  } else {
    clearSearch()
  }
}

function search(searchTerm: string) {
    const lowerCaseSearchTerm = searchTerm.toLowerCase();
    
    const matchedNodes = allNodes.filter(function(d: any) {
        return d.name.toLowerCase().includes(lowerCaseSearchTerm) || 
               (d.description && d.description.toLowerCase().includes(lowerCaseSearchTerm));
    });

    const nodesToExpand = new Set();
    matchedNodes.forEach(function(d: any) {
        let current = d.parent;
        while(current) {
            nodesToExpand.add(current);
            current = current.parent;
        }
    });

    nodesToExpand.forEach(function(originalNode: any) {
        if (originalNode._children) {
            originalNode.children = originalNode._children;
            originalNode._children = null;
        }
    });
    
    update(root);

    const nodesToHighlight = new Set(matchedNodes);
    matchedNodes.forEach(function(d: any){
        let current = d;
        while(current){
            nodesToHighlight.add(current);
            current = current.parent;
        }
    });

    vis.selectAll("g.node")
        .style("display", (d: any) => nodesToHighlight.has(d.data) ? "block" : "none");
        
    vis.selectAll("path.link")
        .style("display", (d: any) => (nodesToHighlight.has(d.source.data) && nodesToHighlight.has(d.target.data)) ? "block" : "none");
}

function clearSearch() {
  vis.selectAll('g.node').style('display', 'block')
  vis.selectAll('path.link').style('display', 'block')
  if (root.children) root.children.forEach((c: any) => collapse(c))
  update(root)
}

function toggleSidebar() {
  visible.value = !visible.value
  const sidebar = document.getElementById('sidebar')
  sidebar?.classList.toggle('visible')
}

function closeSidebar() {
  visible.value = false
  const sidebar = document.getElementById('sidebar')
  sidebar?.classList.remove('visible')
}

function handleClickOutside(event: Event) {
  const sidebar = document.getElementById('sidebar')
  const menuToggle = document.getElementById('menu-toggle')
  
  if (sidebar && menuToggle && !sidebar.contains(event.target as Node) && !menuToggle.contains(event.target as Node)) {
    visible.value = false
    sidebar.classList.remove('visible')
  }
}

function handleEscapeKey(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    visible.value = false
    const sidebar = document.getElementById('sidebar')
    sidebar?.classList.remove('visible')
  }
}

onMounted(() => {
  checkScreenSize()
  root = props.appData
  root.x0 = 0
  root.y0 = 0
  processData(root, null)
  uniqueNodeNames.value = Array.from(new Set(allNodes.map((n: any) => n.name)))
  if (root.children) root.children.forEach((c: any) => collapse(c))
  redraw()
  loading.value = false
  window.addEventListener('resize', redraw)
  window.addEventListener('resize', checkScreenSize)

  document.addEventListener('click', handleClickOutside)
  document.addEventListener('keydown', handleEscapeKey)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  document.removeEventListener('keydown', handleEscapeKey)
  window.removeEventListener('resize', redraw)
  window.removeEventListener('resize', checkScreenSize)
})
</script>


<style scoped src="../../css/app.css"></style>
<template>
  <div id="container">
    <aside id="sidebar">
      <header>
        <h1>Integrasi Aplikasi DLDS</h1>
      </header>
      <div class="sidebar-content">
        <div id="controls">
          <div class="control-group">
            <label for="search">Search:</label>
            <div class="search-wrapper">
              <input v-model="searchTerm" @input="onSearchInput" type="text" id="search" list="search-suggestions"
                placeholder="Filter nodes..." />
              <i class="fas fa-times-circle" id="clear-search" @click="clearSearch"></i>
            </div>
            <datalist id="search-suggestions">
              <option v-for="name in uniqueNodeNames" :key="name" :value="name" />
            </datalist>
          </div>
        </div>
        <div class="notes">
          <h3>Notes</h3>
          <p>Website ini berisi kumpulan aplikasi milik Bank Indonesia beserta integrasinya.</p>
        </div>
      </div>
    </aside>

    <main id="main-content">
      <div id="menu-toggle" @click.stop="toggleSidebar">
        <i class="fas fa-bars"></i>
      </div>
      <div id="loader" v-if="loading"></div>
      <div id="body"></div>
    </main>
  </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { ref, onMounted, computed } from 'vue'

declare const d3: any

let i = 0
const duration = 750

const props = defineProps<{ appData: any }>()
const loading = ref(true)
const searchTerm = ref('')
let root: any = null
const allNodes: any[] = []

const uniqueNodeNames = computed(() => Array.from(new Set(allNodes.map(n => n.name))))

const margin = [20, 120, 20, 140]
let tree, diagonal, vis

function collapse(d: any) {
  if (d.children) {
    d._children = d.children
    d._children.forEach(c => collapse(c))
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
  const container = d3.select('#body').node()
  const width = container.clientWidth
  const height = container.clientHeight

  tree = d3.layout.tree().size([height - margin[0] - margin[2], width - margin[3] - margin[1]])
  diagonal = d3.svg.diagonal().projection(d => [d.y, d.x])

  vis = d3.select('#body')
    .append('svg')
    .attr('width', width)
    .attr('height', height)
    .append('g')
    .attr('transform', `translate(${margin[3]},${margin[0]})`)

  update(root)
}

function update(source: any) {
  const container = d3.select('#body').node()
  const height = container.clientHeight

  tree.size([height - margin[0] - margin[2], container.clientWidth - margin[3] - margin[1]])
  const nodes = tree.nodes(root).reverse()
  const links = tree.links(nodes)

  let maxDepth = 0
  nodes.forEach(d => { if (d.depth > maxDepth) maxDepth = d.depth })

  const requiredWidth = (maxDepth * 240) + margin[1] + margin[3] + 300
  const newWidth = Math.max(container.clientWidth, requiredWidth)
  d3.select('#body svg').attr('width', newWidth)

  nodes.forEach(d => d.y = d.depth * 240)

  const node = vis.selectAll('g.node').data(nodes, d => d.id || (d.id = ++i))

  const nodeEnter = node.enter().append('g')
    .attr('class', 'node')
    .attr('transform', d => `translate(${source.y0 || 0},${source.x0 || 0})`)
    .on('click', d => { toggle(d); update(d) })

  nodeEnter.append('circle')
    .attr('r', 1e-6)
    .style('fill', d => d._children ? 'var(--primary-color)' : '#fff')
    .style('stroke', 'var(--primary-color)')
    .on('click', function (d) {
      if (d.url) {
        d3.event.stopPropagation()
        window.location.href = `${d.url}`
      }
    })

  nodeEnter.append('text')
    .attr('x', d => d.children || d._children ? -10 : 10)
    .attr('dy', '.35em')
    .attr('text-anchor', d => d.children || d._children ? 'end' : 'start')
    .text(d => d.name)
    .style('fill-opacity', 1e-6)
    .on('click', function (d) {
      if (d.url) {
        d3.event.stopPropagation()
        window.location.href = `${d.url}`
      }
    })

  nodeEnter.append('title').text(d => d.description || '')

  const nodeUpdate = node.transition().duration(duration)
    .attr('transform', d => `translate(${d.y},${d.x})`)

  nodeUpdate.select('circle').attr('r', 6)
  nodeUpdate.select('text').style('fill-opacity', 1)

  const nodeExit = node.exit().transition().duration(duration)
    .attr('transform', d => `translate(${source.y},${source.x})`).remove()
  nodeExit.select('circle').attr('r', 1e-6)
  nodeExit.select('text').style('fill-opacity', 1e-6)

  const link = vis.selectAll('path.link').data(links, d => d.target.id)

  link.enter().insert('path', 'g')
    .attr('class', 'link')
    .attr('d', d => {
      const o = { x: source.x0 || 0, y: source.y0 || 0 }
      return diagonal({ source: o, target: o })
    })
    .transition().duration(duration)
    .attr('d', diagonal)

  link.transition().duration(duration).attr('d', diagonal)

  link.exit().transition().duration(duration)
    .attr('d', d => {
      const o = { x: source.x, y: source.y }
      return diagonal({ source: o, target: o })
    }).remove()

  nodes.forEach(d => {
    d.x0 = d.x
    d.y0 = d.y
  })
}

function toggle(d: any) {
  if (d.children) {
    d._children = d.children
    d.children = null
  } else {
    d.children = d._children
    d._children = null
  }
}

function onSearchInput() {
  if (searchTerm.value.length > 2) {
    search(searchTerm.value)
  } else {
    clearSearch()
  }
}

function search(term: string) {
  const lower = term.toLowerCase()
  const matched = allNodes.filter(
    d => d.name?.toLowerCase().includes(lower) || d.description?.toLowerCase().includes(lower)
  )
  // TODO: implement highlight logic
}

function clearSearch() {
  vis.selectAll('g.node').style('display', 'block')
  vis.selectAll('path.link').style('display', 'block')
  if (root.children) root.children.forEach(c => collapse(c))
  update(root)
}

function toggleSidebar() {
  const sidebar = document.getElementById('sidebar')
  sidebar?.classList.toggle('visible')
}

onMounted(() => {
  root = props.appData
  root.x0 = 0
  root.y0 = 0
  processData(root, null)
  if (root.children) root.children.forEach(c => collapse(c))
  redraw()
  loading.value = false
  window.addEventListener('resize', redraw)
})
</script>


<style scoped src="../../css/app.css"></style>
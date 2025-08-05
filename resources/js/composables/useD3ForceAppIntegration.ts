// @ts-nocheck
import { onMounted, onUnmounted } from 'vue';
import * as d3 from 'd3';

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

export function useD3ForceAppIntegration(integrationData: any) {
  let root: NodeData | null = null;
  let i = 0;
  let simulation: any, vis: any;
  let nodes: any[] = [];
  let links: any[] = [];

  function collapse(d: NodeData) {
    if (d.children) {
      d._children = d.children;
      d._children.forEach(collapse);
      d.children = undefined;
    }
  }

  function flattenHierarchy(node: NodeData): { nodes: NodeData[], links: any[] } {
    const allNodes: NodeData[] = [];
    const allLinks: any[] = [];
    
    function traverse(current: NodeData, parentNode: NodeData | null) {
      allNodes.push(current);
      
      if (parentNode) {
        allLinks.push({
          source: parentNode,
          target: current,
          type: current.link || 'default'
        });
      }
      
      if (current.children) {
        current.children.forEach(child => {
          traverse(child, current);
        });
      }
    }
    
    traverse(node, null);
    return { nodes: allNodes, links: allLinks };
  }

  function update(source: NodeData) {
    const container = d3.select('#body').node();
    if (!container) return;
    const width = container.clientWidth;
    const height = container.clientHeight;
    const centerX = width / 2;
    const centerY = height / 2;

    const { nodes: flatNodes, links: flatLinks } = flattenHierarchy(root);
    
    nodes = flatNodes;
    links = flatLinks;

    if (simulation) {
      simulation.stop();
    }

    simulation = d3.forceSimulation(nodes)
      .force('link', d3.forceLink(links)
        .id((d: any) => d.id || d.name)
        .distance(100)
        .strength(0.5)
      )
      .force('charge', d3.forceManyBody().strength(-300))
      .force('center', d3.forceCenter(centerX, centerY))
      .force('collision', d3.forceCollide().radius(30));

    const node = vis.selectAll('g.node')
      .data(nodes, (d: any) => d.id || d.name);

    const nodeEnter = node.enter()
      .append('g')
      .attr('class', 'node')
      .style('cursor', (d: any) => {
        // Check if node is clickable based on allowed streams
        if (d.name === root.name) {
          return 'pointer'; // Root node is always clickable
        }
        // Check if the node is in allowed streams
        const isAllowed = d.app_id && (d.lingkup === 'sp' || d.lingkup === 'mi' || d.lingkup === 'ssk' || d.lingkup === 'market' || d.lingkup === 'moneter');
        return isAllowed ? 'pointer' : 'not-allowed';
      })
      .on('click', (event, d: any) => {
        if (d.children) {
          d._children = d.children;
          d.children = undefined;
        } else if (d._children) {
          d.children = d._children;
          d._children = undefined;
        }
        update(source);
      });

    nodeEnter.append('circle')
      .attr('r', (d: any) => d.name === root.name ? 12 : 8)
      .attr('class', (d: any) => `node-border ${d.lingkup || 'external'}`)
      .style('fill', (d: any) => {
        if (d.name === root.name) return `var(--${d.lingkup || 'external'})`;
        return '#fff';
      })
      .style('stroke-width', 2)
      .on('click', function (event, d) {
        if (d.name === root.name) {
          event.stopPropagation();
          window.location.href = `/technology/${d.app_id}`;
        } else if (d.app_id && (d.lingkup === 'sp' || d.lingkup === 'mi' || d.lingkup === 'ssk' || d.lingkup === 'market' || d.lingkup === 'moneter')) {
          event.stopPropagation();
          window.location.href = `/integration/app/${d.app_id}`;
        }
      });

    nodeEnter.append('text')
      .attr('dy', (d: any) => d.name === root.name ? '-1.8em' : '-1.5em')
      .attr('text-anchor', 'middle')
      .style('font-size', (d: any) => d.name === root.name ? '14px' : '12px')
      .style('font-weight', (d: any) => d.name === root.name ? 'bold' : 'normal')
      .style('fill', '#333')
      .text((d: any) => d.name);

    nodeEnter.append('title')
      .text((d: any) => d.description || d.name);

    node.exit().remove();

    const link = vis.selectAll('path.link')
      .data(links, (d: any) => `${d.source.id || d.source.name}-${d.target.id || d.target.name}`);

    link.enter()
      .insert('path', 'g')
      .attr('class', (d: any) => `link ${d.type || 'default'}`)
      .style('stroke-width', 1.5)
      .style('fill', 'none');

    link.exit().remove();

    const drag = d3.drag()
      .on('start', function(event, d: any) {
        if (!event.active) simulation.alphaTarget(0.3).restart();
        d.fx = d.x;
        d.fy = d.y;
      })
      .on('drag', function(event, d: any) {
        d.fx = event.x;
        d.fy = event.y;
      })
      .on('end', function(event, d: any) {
        if (!event.active) simulation.alphaTarget(0);
        d.fx = null;
        d.fy = null;
      });

    vis.selectAll('g.node').call(drag);

    simulation.on('tick', () => {
      vis.selectAll('path.link')
        .attr('d', (d: any) => `M${d.source.x},${d.source.y}L${d.target.x},${d.target.y}`);

      vis.selectAll('g.node')
        .attr('transform', (d: any) => `translate(${d.x},${d.y})`);
    });

    setTimeout(() => {
      if (simulation) {
        simulation.stop();
      }
    }, 3000);
  }

  function redraw() {
    d3.select('#body svg').remove();
    const container = d3.select('#body').node();
    if (!container) return;
    const width = container.clientWidth;
    const height = container.clientHeight;

    vis = d3
      .select('#body')
      .append('svg')
      .attr('width', width)
      .attr('height', height)
      .append('g');

    if (root) {
      update(root);
    }
  }

  function deepCloneWithoutParent(obj) {
    return JSON.parse(JSON.stringify(obj));
  }

  onMounted(() => {
    root = deepCloneWithoutParent(integrationData);
    root.x0 = 0;
    root.y0 = 0;
    if (root.children) root.children.forEach(collapse);
    redraw();
    
    let resizeHandler = () => redraw();
    window.addEventListener('resize', resizeHandler);
    (window as any).__appIntegrationResizeHandler = resizeHandler;
  });

  onUnmounted(() => {
    if (simulation) {
      simulation.stop();
    }
    d3.select('#body svg').remove();
    const resizeHandler = (window as any).__appIntegrationResizeHandler;
    if (resizeHandler) {
      window.removeEventListener('resize', resizeHandler);
      delete (window as any).__appIntegrationResizeHandler;
    }
    simulation = null;
    vis = null;
    nodes = [];
    links = [];
    root = null;
  });

  return {
    
  };
}

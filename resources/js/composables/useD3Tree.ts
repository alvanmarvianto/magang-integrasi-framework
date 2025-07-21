// @ts-nocheck
import { ref, onMounted, onUnmounted } from 'vue';
import * as d3 from 'd3';

export function useD3Tree(appData) {
  let i = 0;
  const duration = 750;
  const loading = ref(true);
  const searchTerm = ref('');
  
  let root: any = null;
  const allNodes: any[] = [];
  const uniqueNodeNames = ref<string[]>([]);

  let tree: any, diagonal: any, vis: any, svg: any, zoom: any;

  function collapse(d: any) {
    if (d.children) {
      d._children = d.children;
      d._children.forEach((c: any) => collapse(c));
      d.children = null;
    }
  }

  function processData(node: any, parent: any) {
    node.parent = parent;
    allNodes.push(node);
    const children = node.children || node._children || [];
    children.forEach((child: any) => processData(child, node));
  }

  function redraw() {
    d3.select('#body svg').remove();
    const container = d3.select('#body').node() as HTMLElement;
    if (!container) return;
    
    const width = container.clientWidth;
    const height = container.clientHeight;

    tree = d3.tree<any>().nodeSize([40, 180]);
    diagonal = d3.linkHorizontal<any, any>().x((d: any) => d.y).y((d: any) => d.x);

    // Create zoom behavior with custom controls
    zoom = d3.zoom()
      .scaleExtent([0.2, 2])
      .wheelDelta((event: any) => {
        return -event.deltaY * (event.deltaMode === 1 ? 0.05 : event.deltaMode ? 1 : 0.002);
      })
      .filter((event: any) => {
        // Allow zoom only with Ctrl + scroll or programmatic calls
        if (event.type === 'wheel') {
          return event.ctrlKey;
        }
        // Allow drag for panning
        return event.type !== 'wheel';
      })
      .on('zoom', (event: any) => {
        vis.attr('transform', event.transform);
      });

    svg = d3.select('#body')
      .append('svg')
      .attr('width', width)
      .attr('height', height)
      .style('touch-action', 'none')
      .call(zoom)
      .on('contextmenu', (event: any) => event.preventDefault())
      .on('wheel', (event: any) => {
        event.preventDefault();
        
        if (!event.ctrlKey) {
          const currentTransform = d3.zoomTransform(svg.node());
          
          if (event.shiftKey) {
            // Shift + scroll for horizontal movement
            const deltaX = event.deltaY * 0.5; // Adjust scroll sensitivity
            const newTransform = currentTransform.translate(-deltaX, 0);
            svg.call(zoom.transform, newTransform);
          } else {
            // Regular scroll for vertical movement
            const deltaY = event.deltaY * 0.5; // Adjust scroll sensitivity
            const newTransform = currentTransform.translate(0, -deltaY);
            svg.call(zoom.transform, newTransform);
          }
        }
        // Ctrl + scroll is handled by zoom behavior automatically
      });

    vis = svg.append('g');

    svg.call(zoom.transform, d3.zoomIdentity.translate(width / 4.25, height / 2));

    update(root);
  }

  function update(source: any) {
    const container = d3.select('#body').node() as HTMLElement;
    if (!container) return;

    const treeData = tree(d3.hierarchy(root));
    const nodes = treeData.descendants();
    const links = treeData.links();

    nodes.forEach((d: any) => {
      d.y = d.depth * 180;
    });

    nodes.forEach((d: any) => {
      if (d.x0 === undefined || d.y0 === undefined) {
        d.x0 = d.x;
        d.y0 = d.y;
      }
    });

    const node = vis.selectAll('g.node').data(nodes, (d: any) => d.data.id || (d.data.id = ++i));

    const nodeEnter = node.enter().append('g')
      .attr('class', 'node')
      .attr('transform', (d: any) => `translate(${source.y0},${source.x0})`)
      .on('click', (event: any, d: any) => { toggle(d); update(d); });

    nodeEnter.append('circle')
      .attr('r', 1e-6)
      .style('fill', (d: any) => d.data._children ? 'var(--primary-color)' : '#fff')
      .style('stroke', 'var(--primary-color)')
      .on('click', function (event: any, d: any) {
        if (d.data.url) {
          event.stopPropagation();
          window.location.href = `${d.data.url}`;
        }
      });

    nodeEnter.append('text')
      .attr('x', (d: any) => d.children || d.data._children || !d.parent ? -10 : 10)
      .attr('dy', '.35em')
      .attr('text-anchor', (d: any) => d.children || d.data._children || !d.parent ? 'end' : 'start')
      .text((d: any) => d.data.name)
      .style('fill-opacity', 1e-6)
      .on('click', function (event: any, d: any) {
        if (d.data.url) {
          event.stopPropagation();
          window.location.href = `${d.data.url}`;
        }
      });

    nodeEnter.append('title').text((d: any) => d.data.description || '');

    const nodeUpdate = node.merge(nodeEnter).transition().duration(duration)
      .attr('transform', (d: any) => `translate(${d.y},${d.x})`);

    nodeUpdate.select('circle').attr('r', 6).style('fill', (d: any) => d.data._children ? 'var(--primary-color)' : '#fff');
    nodeUpdate.select('text').style('fill-opacity', 1);

    const nodeExit = node.exit().transition().duration(duration)
      .attr('transform', (d: any) => `translate(${source.y},${source.x})`).remove();
    nodeExit.select('circle').attr('r', 1e-6);
    nodeExit.select('text').style('fill-opacity', 1e-6);

    const link = vis.selectAll('path.link').data(links, (d: any) => d.target.data.id);

    const linkEnter = link.enter().insert('path', 'g')
      .attr('class', 'link')
      .attr('d', (d: any) => {
        const o = { x: source.x0, y: source.y0 };
        return diagonal({ source: o, target: o });
      });

    link.merge(linkEnter).transition().duration(duration).attr('d', diagonal);

    link.exit().transition().duration(duration)
      .attr('d', (d: any) => {
        const o = { x: source.x, y: source.y };
        return diagonal({ source: o, target: o });
      }).remove();

    nodes.forEach((d: any) => {
      d.x0 = d.x;
      d.y0 = d.y;
    });
  }

  function toggle(d: any) {
    if (d.data.children) {
      d.data._children = d.data.children;
      d.data.children = null;
    } else {
      d.data.children = d.data._children;
      d.data._children = null;
    }
  }

  function search(term: string) {
    const lowerCaseSearchTerm = term.toLowerCase();
    
    const matchedNodes = allNodes.filter((d: any) => 
        d.name.toLowerCase().includes(lowerCaseSearchTerm) || 
        (d.description && d.description.toLowerCase().includes(lowerCaseSearchTerm))
    );

    const nodesToExpand = new Set();
    matchedNodes.forEach((d: any) => {
        let current = d.parent;
        while(current) {
            nodesToExpand.add(current);
            current = current.parent;
        }
    });

    allNodes.forEach(node => {
        if (!nodesToExpand.has(node) && node.children) {
            node._children = node.children;
            node.children = null;
        }
    });

    nodesToExpand.forEach((originalNode: any) => {
        if (originalNode._children) {
            originalNode.children = originalNode._children;
            originalNode._children = null;
        }
    });
    
    update(root);

    const nodesToHighlight = new Set(matchedNodes);
    matchedNodes.forEach((d: any) => {
        let current = d;
        while(current){
            nodesToHighlight.add(current);
            current = current.parent;
        }
    });

    vis.selectAll("g.node")
        .style("opacity", (d: any) => nodesToHighlight.has(d.data) ? 1 : 0.2);
        
    vis.selectAll("path.link")
        .style("opacity", (d: any) => (nodesToHighlight.has(d.source.data) && nodesToHighlight.has(d.target.data)) ? 1 : 0.2);
  }

  function clearSearch() {
    vis.selectAll('g.node').style('opacity', 1);
    vis.selectAll('path.link').style('opacity', 1);
    if (root.children) root.children.forEach(collapse);
    update(root);
  }

  function onSearchInput() {
    if (searchTerm.value.length > 2) {
      search(searchTerm.value);
    } else {
      clearSearch();
    }
  }

  onMounted(() => {
    root = appData;
    root.x0 = 0;
    root.y0 = 0;
    processData(root, null);
    uniqueNodeNames.value = Array.from(new Set(allNodes.map((n: any) => n.name)));
    if (root.children) root.children.forEach(collapse);
    redraw();
    loading.value = false;
    window.addEventListener('resize', redraw);
  });

  onUnmounted(() => {
    window.removeEventListener('resize', redraw);
  });

  return {
    loading,
    searchTerm,
    uniqueNodeNames,
    onSearchInput,
    clearSearch,
  };
}

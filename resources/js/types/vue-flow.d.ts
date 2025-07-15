// Type declarations to prevent deep instantiation issues
declare module '@vue-flow/core' {
  import { Component } from 'vue'
  
  export interface NodeProps<T = any> {
    id: string
    type?: string
    data: T
    selected?: boolean
    connectable?: boolean
    position: { x: number; y: number }
    dimensions?: { width: number; height: number }
    isValidTargetPos?: boolean
    isValidSourcePos?: boolean
    parentNode?: string
    dragging?: boolean
    zIndex?: number
    targetPosition?: Position
    sourcePosition?: Position
    style?: Record<string, any>
    class?: string
    extent?: 'parent' | [[number, number], [number, number]]
    draggable?: boolean
    selectable?: boolean
  }

  export interface Node<T = any> {
    id: string
    type?: string
    data: T
    position: { x: number; y: number }
    style?: Record<string, any>
    parentNode?: string
    extent?: 'parent' | [[number, number], [number, number]]
    draggable?: boolean
    selectable?: boolean
    [key: string]: any // Allow additional properties
  }

  export interface Edge<T = any> {
    id: string
    source: string
    target: string
    type?: string
    data?: T
    style?: Record<string, any>
    animated?: boolean
    hidden?: boolean
    deletable?: boolean
    updatable?: boolean
    selectable?: boolean
    label?: string
    labelStyle?: Record<string, any>
    labelBgStyle?: Record<string, any>
    labelBgPadding?: [number, number]
    labelBgBorderRadius?: number
    markerStart?: string
    markerEnd?: string
    sourceHandle?: string
    targetHandle?: string
  }

  export interface NodeMouseEvent {
    event: MouseEvent
    node: Node
  }

  export enum Position {
    Left = 'left',
    Top = 'top',
    Right = 'right',
    Bottom = 'bottom'
  }

  export enum PanOnScrollMode {
    Free = 'free',
    Vertical = 'vertical',
    Horizontal = 'horizontal'
  }

  export const VueFlow: Component
  export const Handle: Component
  export function useVueFlow(): any
}

declare module '@vue-flow/background' {
  import { Component } from 'vue'
  
  export const Background: Component
  
  export enum BackgroundVariant {
    Lines = 'lines',
    Dots = 'dots',
    Cross = 'cross'
  }
}

declare module '@vue-flow/controls' {
  import { Component } from 'vue'
  
  export const Controls: Component
}

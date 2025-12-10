declare module 'qrcode.react' {
  import * as React from 'react'

  export interface QRCodeProps {
    value: string
    size?: number
    includeMargin?: boolean
    renderAs?: 'svg' | 'canvas'
    bgColor?: string
    fgColor?: string
    level?: 'L' | 'M' | 'Q' | 'H'
    imageSettings?: {
      src: string
      x?: number
      y?: number
      height?: number
      width?: number
      excavate?: boolean
    }
    className?: string
  }

  export default class QRCode extends React.Component<QRCodeProps> {}
}


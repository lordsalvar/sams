import { ReactNode } from 'react'
import { AppSidebar } from './AppSidebar'
import {
  SidebarInset,
  SidebarProvider,
  SidebarTrigger,
} from './ui/sidebar'
import { Separator } from './ui/separator'
import { ThemeToggle } from './ThemeToggle'

interface DashboardLayoutProps {
  children: ReactNode
  userRole: string
  username: string
  onLogout: () => void
  title?: string
}

export function DashboardLayout({
  children,
  userRole,
  username,
  onLogout,
  title = 'Dashboard',
}: DashboardLayoutProps) {
  return (
    <SidebarProvider>
      <AppSidebar userRole={userRole} username={username} onLogout={onLogout} />
      <SidebarInset>
        <header className="flex h-16 shrink-0 items-center gap-2 border-b px-4 lg:px-6">
          <SidebarTrigger className="-ml-1" />
          <Separator orientation="vertical" className="mr-2 h-4" />
          <h1 className="text-base font-medium">{title}</h1>
          <div className="ml-auto">
            <ThemeToggle />
          </div>
        </header>
        <div className="flex flex-1 flex-col gap-4 p-4 lg:p-6">
          {children}
        </div>
      </SidebarInset>
    </SidebarProvider>
  )
}

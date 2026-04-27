'use client';

import { ReactNode, useState } from 'react';
import Sidebar from '@/components/admin/shared/Sidebar';
import Topbar from '@/components/admin/shared/Topbar';

interface AdminLayoutProps {
  title: string;
  children: ReactNode;
}

export default function AdminLayout({ title, children }: AdminLayoutProps) {
  const [sidebarOpen, setSidebarOpen] = useState(false);

  return (
    <div className="adm-shell">
      {/* Sidebar with open state class applied directly to it */}
      <Sidebar 
        isOpen={sidebarOpen} 
        onClose={() => setSidebarOpen(false)} 
      />

      {/* Overlay for mobile */}
      <div 
        className={`adm-sidebar-overlay ${sidebarOpen ? 'adm-sidebar-overlay--visible' : ''}`}
        onClick={() => setSidebarOpen(false)}
      />

      <div className="adm-content">
        <Topbar title={title} onMenuClick={() => setSidebarOpen(true)} />
        <main className="adm-main">{children}</main>
      </div>
    </div>
  );
}

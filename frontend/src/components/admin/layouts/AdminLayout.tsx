'use client';

import { ReactNode } from 'react';
import Sidebar from '@/components/admin/shared/Sidebar';
import Topbar from '@/components/admin/shared/Topbar';

interface AdminLayoutProps {
  title: string;
  children: ReactNode;
}

export default function AdminLayout({ title, children }: AdminLayoutProps) {
  return (
    <div className="adm-shell">
      <Sidebar />

      <div className="adm-content">
        <Topbar title={title} />
        <main className="adm-main">{children}</main>
      </div>
    </div>
  );
}

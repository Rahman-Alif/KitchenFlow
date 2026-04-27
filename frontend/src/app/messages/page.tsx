'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { getUser, AuthUser } from '@/lib/auth';
import AdminLayout from '@/components/admin/layouts/AdminLayout';
import KitchenLayout from '@/components/kitchen/layouts/KitchenLayout';
import MessagesPortal from '@/components/admin/ui/MessagesPortal';
import '@/components/admin/layouts/admin-layout.css';
import './messages.css';

export default function MessagesPage() {
  const router = useRouter();
  const [user, setUser] = useState<AuthUser | null>(null);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    const u = getUser();
    if (!u) {
      router.push('/login');
      return;
    }
    setUser(u);
    setReady(true);
  }, [router]);

  if (!ready || !user) return null;

  if (user.role === 'admin') {
    return (
      <AdminLayout title="Messages">
        <div className="adm-shell">
          <MessagesPortal isAdmin={true} />
        </div>
      </AdminLayout>
    );
  }

  if (user.role === 'kitchen_staff') {
    return (
      <KitchenLayout>
        <div className="adm-shell kitchen-theme">
          <MessagesPortal isAdmin={false} />
        </div>
      </KitchenLayout>
    );
  }

  return null;
}


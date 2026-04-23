'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { apiRequest } from '@/lib/api';
import { clearAuth, getUser } from '@/lib/auth';

interface TopbarProps {
  title: string;
}

export default function Topbar({ title }: TopbarProps) {
  const router = useRouter();
  const user = getUser();
  const [loading, setLoading] = useState(false);

  async function handleLogout() {
    if (loading) return;
    setLoading(true);

    await apiRequest('/auth/logout', { method: 'POST' });
    clearAuth();
    router.push('/login');
  }

  return (
    <header className="adm-topbar">
      <h1>{title}</h1>

      <div className="adm-user-area">
        <span>{user?.name ?? user?.email ?? 'Admin'}</span>
        <button type="button" className="adm-logout-btn" onClick={handleLogout} disabled={loading}>
          {loading ? 'Signing out...' : 'Logout'}
        </button>
      </div>
    </header>
  );
}

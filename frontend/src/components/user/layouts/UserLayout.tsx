'use client';

import { useEffect, useState } from 'react';
import { useRouter, usePathname } from 'next/navigation';
import { getUser, clearAuth, AuthUser } from '@/lib/auth';
import { apiRequest } from '@/lib/api';
import Link from 'next/link';

export default function UserLayout({ children }: { children: React.ReactNode }) {
  const router   = useRouter();
  const pathname = usePathname();
  const [user, setUser] = useState<AuthUser | null>(null);

  useEffect(() => {
    const u = getUser();
    if (!u) {
      router.push('/login');
      return;
    }
    setUser(u);
  }, [router]);

  async function handleLogout() {
    await apiRequest('/auth/logout', { method: 'POST' });
    clearAuth();
    router.push('/login');
  }

  const navItems = [
    { href: '/menu',   label: 'Menu'   },
    { href: '/orders', label: 'Orders' },
    { href: '/profile', label: 'Profile' },
  ];

  return (
    <div className="min-h-screen bg-zinc-950 text-white">

      {/* Topbar */}
      <header className="bg-zinc-900 border-b border-zinc-800 px-6 py-4 flex items-center justify-between">
        <div className="flex items-center gap-8">
          <h1 className="text-lg font-bold text-white">
            Kitchen<span className="text-orange-500">Flow</span>
          </h1>
          <nav className="flex items-center gap-1">
            {navItems.map((item) => (
              <Link
                key={item.href}
                href={item.href}
                className={`px-4 py-2 rounded-lg text-sm font-medium transition ${
                  pathname.startsWith(item.href)
                    ? 'bg-orange-500 text-white'
                    : 'text-zinc-400 hover:text-white hover:bg-zinc-800'
                }`}
              >
                {item.label}
              </Link>
            ))}
          </nav>
        </div>

        <div className="flex items-center gap-4">
          <span className="text-sm text-zinc-400">{user?.name}</span>
          <button
            onClick={handleLogout}
            className="text-sm text-zinc-400 hover:text-white transition"
          >
            Logout
          </button>
        </div>
      </header>

      {/* Page content */}
      <main className="p-6">
        {children}
      </main>

    </div>
  );
}

'use client';

import { useEffect, useState } from 'react';
import { useRouter, usePathname } from 'next/navigation';
import { getUser, clearAuth, AuthUser } from '@/lib/auth';
import { apiRequest } from '@/lib/api';
import Link from 'next/link';

export default function KitchenLayout({ children }: { children: React.ReactNode }) {
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
    { href: '/orders', label: 'Orders' },
    { href: '/menu',   label: 'Menu'   },
    { href: '/messages', label: 'Messages' },
    { href: '/profile', label: 'Profile' },
  ];


  return (
    <div className="min-h-screen bg-slate-50 text-slate-900">

      {/* Topbar */}
      <header className="sticky top-0 z-50 backdrop-blur-md bg-white/80 border-b border-slate-200 px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row items-center justify-between gap-4 transition-all duration-300 shadow-sm">
        <div className="flex items-center justify-between w-full sm:w-auto gap-8">
          <Link href={navItems[0].href} className="flex items-center gap-2 group">
            <div className="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center shadow-lg shadow-orange-500/20 group-hover:shadow-orange-500/40 transition-all duration-300 group-hover:scale-105">
              <svg className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <h1 className="text-xl font-black text-slate-900 tracking-tight">
              Kitchen<span className="text-orange-500">Flow</span>
            </h1>
          </Link>
          
          <div className="flex sm:hidden items-center gap-3">
             <div className="w-8 h-8 rounded-full bg-gradient-to-tr from-orange-500 to-rose-500 flex items-center justify-center text-sm font-bold text-white shadow-sm">
                {user?.name?.charAt(0).toUpperCase()}
             </div>
             <button
              onClick={handleLogout}
              className="p-2 text-slate-500 hover:text-slate-900 bg-slate-100 hover:bg-red-50 hover:text-red-600 rounded-lg transition-all duration-300"
            >
              <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
            </button>
          </div>
        </div>

        <nav className="flex items-center justify-between sm:justify-start gap-1 sm:gap-2 p-1 bg-slate-100/80 border border-slate-200/80 rounded-xl w-full sm:w-auto overflow-x-auto scrollbar-hide">
          {navItems.map((item) => {
            const isActive = pathname.startsWith(item.href);
            return (
              <Link
                key={item.href}
                href={item.href}
                className={`flex-1 sm:flex-none text-center px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300 ${
                  isActive
                    ? 'bg-white text-slate-900 shadow-sm border border-slate-200'
                    : 'text-slate-500 hover:text-slate-900 hover:bg-slate-200/50'
                }`}
              >
                {item.label}
              </Link>
            );
          })}
        </nav>

        <div className="hidden sm:flex items-center gap-4">
          <div className="flex items-center gap-3 px-3 py-1.5 bg-white border border-slate-200 rounded-full cursor-default">
            <div className="w-6 h-6 rounded-full bg-gradient-to-tr from-orange-500 to-rose-500 flex items-center justify-center text-xs font-bold text-white shadow-sm">
              {user?.name?.charAt(0).toUpperCase()}
            </div>
            <span className="text-sm font-medium text-slate-700">{user?.name}</span>
          </div>
          <button
            onClick={handleLogout}
            className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all duration-300 group"
          >
            <span>Logout</span>
            <svg className="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
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
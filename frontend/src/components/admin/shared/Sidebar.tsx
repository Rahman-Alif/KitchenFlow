'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';


const navItems = [
  { href: '/dashboard', label: 'Dashboard', icon: '▦' },
  { href: '/users', label: 'Users', icon: '◉' },
  { href: '/categories', label: 'Categories', icon: '⊞' },
  { href: '/menu', label: 'Menu Items', icon: '◈' },
  { href: '/orders-history', label: 'Order History', icon: '◷' },
  { href: '/messages', label: 'Messages', icon: '◻' },
  { href: '/profile', label: 'Profile', icon: '◉' },
];

export default function Sidebar() {
  const pathname = usePathname();

  return (
    <aside className="adm-sidebar">
      <Link href="/dashboard" className="adm-brand group">
        <div className="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center shadow-lg shadow-orange-500/20 group-hover:shadow-orange-500/40 transition-all duration-300 group-hover:scale-105">
          <svg className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
        </div>
        <span className="text-xl font-black text-white tracking-tight">
          Kitchen<span className="text-orange-500">Flow</span>
        </span>
      </Link>

      <nav className="adm-nav" aria-label="Admin navigation">
        <div className="adm-nav-section">Main Menu</div>
        {navItems.map((item) => {
          const isActive = pathname === item.href || pathname.startsWith(`${item.href}/`);
          return (
            <Link
              key={item.href}
              href={item.href}
              className={`adm-nav-item${isActive ? ' adm-nav-item--active' : ''}`}
            >
              <span style={{ fontSize: '14px', opacity: 0.8, width: '18px', textAlign: 'center', flexShrink: 0 }}>
                {item.icon}
              </span>
              {item.label}
            </Link>
          );
        })}
      </nav>


    </aside>
  );
}

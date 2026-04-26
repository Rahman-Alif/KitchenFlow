'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';


const navItems = [
  { href: '/dashboard',      label: 'Dashboard',     icon: '▦' },
  { href: '/users',          label: 'Users',          icon: '◉' },
  { href: '/categories',     label: 'Categories',     icon: '⊞' },
  { href: '/menu',           label: 'Menu Items',     icon: '◈' },
  { href: '/orders-history', label: 'Order History',  icon: '◷' },
  { href: '/messages',       label: 'Messages',       icon: '◻' },
];

export default function Sidebar() {
  const pathname = usePathname();

  return (
    <aside className="adm-sidebar">
      <div className="adm-brand">KitchenFlow</div>

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

'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';

const navItems = [
  { href: '/dashboard', label: 'Dashboard' },
  { href: '/users', label: 'Users' },
  { href: '/categories', label: 'Categories' },
  { href: '/menu', label: 'Menu Items' },
];

export default function Sidebar() {
  const pathname = usePathname();

  return (
    <aside className="adm-sidebar">
      <div className="adm-brand">KitchenFlow</div>

      <nav className="adm-nav" aria-label="Admin navigation">
        {navItems.map((item) => {
          const isActive = pathname === item.href || pathname.startsWith(`${item.href}/`);
          const className = isActive ? 'adm-nav-item adm-nav-item--active' : 'adm-nav-item';

          return (
            <Link key={item.href} href={item.href} className={className}>
              {item.label}
            </Link>
          );
        })}
      </nav>
    </aside>
  );
}

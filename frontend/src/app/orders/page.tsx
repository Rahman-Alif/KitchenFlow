'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { getUser, AuthUser } from '@/lib/auth';
import KitchenLayout from '@/components/kitchen/layouts/KitchenLayout';
import OrderQueueView from '@/components/kitchen/OrderQueueView';
import UserLayout from '@/components/user/layouts/UserLayout';
import UserOrderHistory from '@/components/user/UserOrderHistory';

export default function OrdersPage() {
  const router = useRouter();
  const [user, setUser]   = useState<AuthUser | null>(null);
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

  if (user.role === 'kitchen_staff') {
    return (
      <KitchenLayout>
        <OrderQueueView />
      </KitchenLayout>
    );
  }

  if (user.role === 'user') {
    return (
      <UserLayout>
        <UserOrderHistory />
      </UserLayout>
    );
  }

  return null;
}

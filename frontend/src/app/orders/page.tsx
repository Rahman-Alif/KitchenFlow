'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import KitchenLayout from '@/components/kitchen/layouts/KitchenLayout';
import { getOrderQueue, Order } from '@/lib/services/orders';
import { getUser } from '@/lib/auth';

const STATUS_COLORS: Record<string, string> = {
  pending:   'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
  preparing: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
  ready:     'bg-green-500/10 text-green-400 border-green-500/20',
};

const STATUS_LABELS: Record<string, string> = {
  pending:   'Pending',
  preparing: 'Preparing',
  ready:     'Ready',
};

function timeAgo(dateStr: string): string {
  const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
  if (diff < 60)  return `${diff}s ago`;
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  return `${Math.floor(diff / 3600)}h ago`;
}

export default function OrderQueuePage() {
  const router = useRouter();
  const [orders, setOrders]   = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError]     = useState('');

  useEffect(() => {
    const user = getUser();
    if (!user || user.role !== 'kitchen_staff') {
      router.push('/login');
      return;
    }
    fetchOrders();

    // Auto-refresh every 30 seconds
    const interval = setInterval(fetchOrders, 30000);
    return () => clearInterval(interval);
  }, []);

  async function fetchOrders() {
    const { data, error } = await getOrderQueue();
    if (error) {
      setError(error);
    } else {
      setOrders(data?.data ?? []);
    }
    setLoading(false);
  }

  const grouped = {
    pending:   orders.filter((o) => o.status === 'pending'),
    preparing: orders.filter((o) => o.status === 'preparing'),
    ready:     orders.filter((o) => o.status === 'ready'),
  };

  return (
    <KitchenLayout>
      <div className="max-w-7xl mx-auto">

        {/* Header */}
        <div className="flex items-center justify-between mb-6">
          <div>
            <h2 className="text-2xl font-bold text-white">Order Queue</h2>
            <p className="text-zinc-400 text-sm mt-1">
              {orders.length} active {orders.length === 1 ? 'order' : 'orders'}
            </p>
          </div>
          <button
            onClick={fetchOrders}
            className="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-sm rounded-lg transition"
          >
            Refresh
          </button>
        </div>

        {/* Error */}
        {error && (
          <div className="bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-lg px-4 py-3 mb-6">
            {error}
          </div>
        )}

        {/* Loading */}
        {loading ? (
          <div className="text-zinc-500 text-sm">Loading orders...</div>
        ) : orders.length === 0 ? (
          <div className="text-center py-20">
            <p className="text-zinc-500 text-lg">No active orders</p>
            <p className="text-zinc-600 text-sm mt-1">New orders will appear here automatically</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {(['pending', 'preparing', 'ready'] as const).map((status) => (
              <div key={status}>

                {/* Column header */}
                <div className="flex items-center gap-2 mb-4">
                  <span className={`px-3 py-1 rounded-full text-xs font-semibold border ${STATUS_COLORS[status]}`}>
                    {STATUS_LABELS[status]}
                  </span>
                  <span className="text-zinc-500 text-sm">
                    {grouped[status].length}
                  </span>
                </div>

                {/* Order cards */}
                <div className="space-y-3">
                  {grouped[status].length === 0 ? (
                    <div className="bg-zinc-900 border border-zinc-800 rounded-xl p-4 text-center text-zinc-600 text-sm">
                      No orders
                    </div>
                  ) : (
                    grouped[status].map((order) => (
                      <button
                        key={order.id}
                        onClick={() => router.push(`/orders/${order.id}`)}
                        className="w-full text-left bg-zinc-900 border border-zinc-800 hover:border-zinc-600 rounded-xl p-4 transition"
                      >
                        <div className="flex items-center justify-between mb-3">
                          <span className="text-white font-semibold">
                            Order #{order.id}
                          </span>
                          <span className="text-zinc-500 text-xs">
                            {timeAgo(order.created_at)}
                          </span>
                        </div>

                        <p className="text-zinc-400 text-sm mb-3">
                          {order.placed_by.name}
                        </p>

                        <div className="space-y-1 mb-3">
                          {order.items.map((item) => (
                            <div key={item.id} className="flex justify-between text-sm">
                              <span className="text-zinc-300">{item.name}</span>
                              <span className="text-zinc-500">x{item.quantity}</span>
                            </div>
                          ))}
                        </div>

                        {order.notes && (
                          <div className="bg-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-400 mb-3">
                            📝 {order.notes}
                          </div>
                        )}

                        <div className="flex items-center justify-between pt-3 border-t border-zinc-800">
                          <span className="text-zinc-400 text-sm">Total</span>
                          <span className="text-white font-semibold">
                            ৳{order.total_amount}
                          </span>
                        </div>
                      </button>
                    ))
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </KitchenLayout>
  );
}
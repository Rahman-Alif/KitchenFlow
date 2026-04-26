'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAutoAnimate } from '@formkit/auto-animate/react';
import { getOrderQueue, updateOrderStatus, Order } from '@/lib/services/orders';

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

export default function OrderQueueView() {
  const router = useRouter();
  const [orders, setOrders]   = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError]     = useState('');
  const [updating, setUpdating] = useState<number | null>(null);

  const [pendingRef]   = useAutoAnimate<HTMLDivElement>();
  const [preparingRef] = useAutoAnimate<HTMLDivElement>();
  const [readyRef]     = useAutoAnimate<HTMLDivElement>();
  const refs: Record<string, React.RefObject<HTMLDivElement> | any> = {
    pending: pendingRef,
    preparing: preparingRef,
    ready: readyRef,
  };

  useEffect(() => {
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

  async function handleQuickAction(e: React.MouseEvent, order: Order, newStatus: string) {
    e.stopPropagation(); // prevent card click
    setUpdating(order.id);
    const { data, error: apiError } = await updateOrderStatus(order.id, newStatus);
    if (apiError) {
      setError(apiError);
    } else {
      // update local state
      setOrders(prev => prev.map(o => o.id === order.id ? { ...o, status: newStatus as Order['status'] } : o));
    }
    setUpdating(null);
  }

  const grouped = {
    pending:   orders.filter((o) => o.status === 'pending'),
    preparing: orders.filter((o) => o.status === 'preparing'),
    ready:     orders.filter((o) => o.status === 'ready'),
  };

  return (
    <div className="max-w-7xl mx-auto">

      {/* ── Staff Hero ── */}
      <div className="relative mb-6 rounded-3xl overflow-hidden bg-white border border-slate-200 shadow-sm">

        {/* Decorative glow — blue for staff */}
        <div className="absolute -top-16 -right-16 w-64 h-64 bg-blue-500/8 rounded-full blur-3xl pointer-events-none" />
        <div className="absolute -bottom-10 -left-10 w-48 h-48 bg-blue-600/5 rounded-full blur-2xl pointer-events-none" />

        <div className="relative px-8 py-6 flex items-center justify-between gap-6 flex-wrap">

          {/* Left: badge + title */}
          <div>
            <div className="inline-flex items-center gap-2 bg-blue-500/10 border border-blue-500/20 rounded-full px-3 py-1 mb-3">
              <span className="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse" />
              <span className="text-blue-400 text-xs font-semibold uppercase tracking-widest">Kitchen Staff · Live Queue</span>
            </div>
            <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
              Order <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-blue-600">Queue</span>
            </h1>
            <p className="text-slate-500 text-sm mt-1">Manage incoming orders and update their status in real time.</p>
          </div>

          {/* Right: stats + refresh */}
          <div className="flex items-center gap-5">
            <div className="flex items-center gap-6">
              <div className="text-center">
                <p className="text-2xl font-bold text-yellow-500">{grouped.pending.length}</p>
                <p className="text-slate-500 text-xs mt-0.5">Pending</p>
              </div>
              <div className="w-px h-10 bg-slate-200" />
              <div className="text-center">
                <p className="text-2xl font-bold text-blue-500">{grouped.preparing.length}</p>
                <p className="text-slate-500 text-xs mt-0.5">Preparing</p>
              </div>
              <div className="w-px h-10 bg-slate-200" />
              <div className="text-center">
                <p className="text-2xl font-bold text-emerald-500">{grouped.ready.length}</p>
                <p className="text-slate-500 text-xs mt-0.5">Ready</p>
              </div>
            </div>
            <button
              onClick={fetchOrders}
              className="flex items-center gap-2 px-4 py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-600 border border-blue-200 text-sm font-semibold rounded-xl transition"
            >
              ↻ Refresh
            </button>
          </div>

        </div>
      </div>

      {/* Error */}
      {error && (
        <div className="bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-lg px-4 py-3 mb-6">
          {error}
        </div>
      )}

      {/* Loading */}
      {loading ? (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {(['pending', 'preparing', 'ready'] as const).map((status) => (
            <div key={status} className="bg-slate-50 rounded-3xl p-5 border border-slate-200 flex flex-col h-[calc(100vh-12rem)]">
              <div className="flex items-center justify-between mb-5 px-1">
                 <div className="h-8 w-24 bg-slate-200 rounded-full animate-pulse"></div>
              </div>
              <div className="space-y-4">
                {[1, 2, 3].map(i => (
                  <div key={i} className="bg-white rounded-2xl p-5 border border-slate-200 flex flex-col animate-pulse shadow-sm">
                    <div className="flex justify-between mb-4">
                      <div className="h-6 w-16 bg-slate-200 rounded-md"></div>
                      <div className="h-6 w-16 bg-slate-200 rounded-md"></div>
                    </div>
                    <div className="h-4 w-32 bg-slate-200 rounded-md mb-4"></div>
                    <div className="space-y-2 mb-4">
                       <div className="h-4 w-full bg-slate-200 rounded-md"></div>
                       <div className="h-4 w-2/3 bg-slate-200 rounded-md"></div>
                    </div>
                    <div className="mt-2 pt-4 border-t border-slate-200 flex justify-between">
                       <div className="h-6 w-16 bg-slate-200 rounded-md"></div>
                       <div className="h-8 w-24 bg-slate-200 rounded-xl"></div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      ) : orders.length === 0 ? (
        <div className="text-center py-20 bg-white border border-slate-200 rounded-3xl shadow-sm">
          <p className="text-slate-500 text-lg">No active orders</p>
          <p className="text-slate-400 text-sm mt-1">New orders will appear here automatically</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {(['pending', 'preparing', 'ready'] as const).map((status) => (
            <div key={status} className="bg-slate-50 rounded-3xl p-5 border border-slate-200 flex flex-col h-[calc(100vh-12rem)]">
              {/* Column header */}
              <div className="flex items-center justify-between mb-5 px-1">
                <div className="flex items-center gap-3">
                  <span className={`px-4 py-1.5 rounded-full text-sm font-bold border shadow-sm ${STATUS_COLORS[status]}`}>
                    {STATUS_LABELS[status]}
                  </span>
                  <span className="text-slate-500 font-bold bg-white px-2 py-1 rounded-lg border border-slate-200">
                    {grouped[status].length}
                  </span>
                </div>
              </div>

              {/* Order cards */}
              <div ref={refs[status]} className="space-y-4 overflow-y-auto pr-2 pb-4 scrollbar-thin scrollbar-thumb-slate-300 scrollbar-track-transparent flex-1">
                {grouped[status].length === 0 ? (
                  <div className="h-full flex flex-col items-center justify-center text-center text-slate-400 p-8">
                    <div className="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                      <svg className="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                      </svg>
                    </div>
                    <p>No orders in this queue</p>
                  </div>
                ) : (
                  grouped[status].map((order) => (
                    <div
                      key={order.id}
                      className="bg-white border border-slate-200 hover:border-slate-300 rounded-2xl overflow-hidden transition-all duration-300 shadow-sm hover:shadow-md hover:shadow-slate-200 group flex flex-col"
                    >
                      <div 
                        className="p-5 cursor-pointer flex-1"
                        onClick={() => router.push(`/orders/${order.id}`)}
                      >
                        <div className="flex items-center justify-between mb-4">
                          <span className="text-slate-900 font-bold text-lg drop-shadow-sm">
                            #{order.id}
                          </span>
                          <span className="text-slate-500 font-medium text-sm bg-slate-100 px-2.5 py-1 rounded-lg">
                            {timeAgo(order.created_at)}
                          </span>
                        </div>

                        <p className="text-slate-600 text-sm font-medium mb-4 flex items-center gap-2">
                          <svg className="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                          {order.placed_by.name}
                        </p>

                        <div className="space-y-2 mb-4 bg-slate-50 p-3 rounded-xl border border-slate-200">
                          {order.items.map((item) => (
                            <div key={item.id} className="flex justify-between items-start text-sm">
                              <span className="text-slate-700 font-medium pr-4">{item.name}</span>
                              <span className="text-slate-500 font-bold bg-slate-200/60 px-2 py-0.5 rounded-md">x{item.quantity}</span>
                            </div>
                          ))}
                        </div>

                        {order.notes && (
                          <div className="bg-yellow-50 border border-yellow-200 rounded-xl p-3 text-sm text-yellow-700 mb-2 flex gap-2 items-start">
                            <span>📝</span>
                            <p className="leading-snug">{order.notes}</p>
                          </div>
                        )}
                      </div>

                      {/* Quick Actions Footer */}
                      <div className="px-5 pb-5 pt-2 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between">
                        <span className="text-slate-900 font-bold tracking-tight">
                          ৳{order.total_amount}
                        </span>
                        
                        <div className="flex items-center">
                          {status === 'pending' && (
                            <button
                              onClick={(e) => handleQuickAction(e, order, 'preparing')}
                              disabled={updating === order.id}
                              className="px-4 py-2 bg-blue-500/20 hover:bg-blue-500 text-blue-400 hover:text-white border border-blue-500/30 font-bold text-sm rounded-xl transition-all duration-200 active:scale-95 disabled:opacity-50 flex items-center gap-2"
                            >
                              {updating === order.id ? 'Updating...' : 'Start Prep'}
                            </button>
                          )}
                          {status === 'preparing' && (
                            <button
                              onClick={(e) => handleQuickAction(e, order, 'ready')}
                              disabled={updating === order.id}
                              className="px-4 py-2 bg-green-500/20 hover:bg-green-500 text-green-400 hover:text-white border border-green-500/30 font-bold text-sm rounded-xl transition-all duration-200 active:scale-95 disabled:opacity-50 flex items-center gap-2"
                            >
                              {updating === order.id ? 'Updating...' : 'Mark Ready'}
                            </button>
                          )}
                          {status === 'ready' && (
                            <button
                              onClick={(e) => handleQuickAction(e, order, 'served')}
                              disabled={updating === order.id}
                              className="px-4 py-2 bg-purple-500/20 hover:bg-purple-500 text-purple-400 hover:text-white border border-purple-500/30 font-bold text-sm rounded-xl transition-all duration-200 active:scale-95 disabled:opacity-50 flex items-center gap-2"
                            >
                              {updating === order.id ? 'Updating...' : 'Mark Served'}
                            </button>
                          )}
                        </div>
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
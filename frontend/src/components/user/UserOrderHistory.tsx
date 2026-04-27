'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAutoAnimate } from '@formkit/auto-animate/react';
import { toast } from 'sonner';
import { getUserOrders, updateUserOrder, cancelUserOrder, Order, OrderItem } from '@/lib/services/orders';


const STATUS_COLORS: Record<string, string> = {
  pending:   'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
  preparing: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
  ready:     'bg-green-500/10 text-green-400 border-green-500/20',
  served:    'bg-slate-100 text-slate-500 border-slate-200',
  canceled:  'bg-red-50 text-red-600 border-red-200',
};

const STATUS_LABELS: Record<string, string> = {
  pending:   'Pending',
  preparing: 'Preparing',
  ready:     'Ready for Pickup',
  served:    'Served',
  canceled:  'Canceled',
};

function formatDate(dateStr: string): string {
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-GB', {
    day:   'numeric',
    month: 'short',
    year:  'numeric',
  }) + ' · ' + date.toLocaleTimeString('en-GB', {
    hour:   '2-digit',
    minute: '2-digit',
  });
}

interface EditState {
  orderId:  number;
  items:    { order_item_id: number; name: string; unit_price: string; quantity: number }[];
  notes:    string;
}

export default function UserOrderHistory() {
  const router = useRouter();
  const [orders, setOrders]     = useState<Order[]>([]);
  const [loading, setLoading]   = useState(true);
  const [error, setError]       = useState('');
  const [filter, setFilter]     = useState<string>('all');
  const [editing, setEditing]   = useState<EditState | null>(null);
  const [saving, setSaving]     = useState(false);
  const [editError, setEditError] = useState('');
  const [canceling, setCanceling] = useState<number | null>(null);
  const [ordersRef] = useAutoAnimate<HTMLDivElement>();

  useEffect(() => {
    fetchOrders();
    const interval = setInterval(fetchOrders, 5000);
    return () => clearInterval(interval);
  }, []);

  async function fetchOrders() {
    const { data, error } = await getUserOrders();
    if (error) {
      setError(error);
    } else {
      setOrders(data?.data ?? []);
    }
    setLoading(false);
  }

  function startEdit(order: Order) {
    setEditError('');
    setEditing({
      orderId: order.id,
      notes:   order.notes ?? '',
      items:   order.items.map((item) => ({
        order_item_id: item.id,
        name:          item.name,
        unit_price:    item.unit_price,
        quantity:      item.quantity,
      })),
    });
  }

  function updateEditQuantity(order_item_id: number, quantity: number) {
    if (!editing) return;
    setEditing({
      ...editing,
      items: editing.items.map((i) =>
        i.order_item_id === order_item_id ? { ...i, quantity } : i
      ),
    });
  }

  async function handleSaveEdit() {
    if (!editing) return;
    setEditError('');
    setSaving(true);

    const { data, error } = await updateUserOrder(
      editing.orderId,
      editing.items.map((i) => ({
        order_item_id: i.order_item_id,
        quantity:      i.quantity,
      })),
      editing.notes || undefined
    );

    if (error) {
      setEditError(error);
      toast.error('Failed to update order');
      setSaving(false);
      return;
    }

    // Update order in list
    setOrders((prev) =>
      prev.map((o) => (o.id === editing.orderId ? data!.data! : o))
    );
    toast.success('Order updated successfully!');
    setEditing(null);
    setSaving(false);
  }

  async function handleCancel(orderId: number) {
  if (!confirm('Are you sure you want to cancel this order?')) return;
  setCanceling(orderId);
  const { data, error } = await cancelUserOrder(orderId);
  if (error) {
    setError(error);
    toast.error('Failed to cancel order');
  } else {
    setOrders((prev) =>
      prev.map((o) => (o.id === orderId ? data!.data! : o))
    );
    toast.success('Order cancelled successfully!');
  }
  setCanceling(null);
}

  const filteredOrders = filter === 'all'
    ? orders
    : orders.filter((o) => o.status === filter);

  const activeOrders = orders.filter((o) =>
    ['pending', 'preparing', 'ready'].includes(o.status)
  );

  const editTotal = editing
    ? editing.items.reduce(
        (sum, i) => sum + parseFloat(i.unit_price) * i.quantity,
        0
      )
    : 0;

  return (
    <div className="max-w-6xl mx-auto">
      {/* ── Hero Section ── */}
      <div className="relative mb-8 rounded-3xl overflow-hidden bg-white/70 backdrop-blur-md border border-slate-200 shadow-sm">

        {/* Decorative glow */}
        <div className="absolute -top-16 -right-16 w-64 h-64 bg-orange-500/10 rounded-full blur-3xl pointer-events-none" />
        <div className="absolute -bottom-10 -left-10 w-48 h-48 bg-orange-600/5 rounded-full blur-2xl pointer-events-none" />

        <div className="relative px-8 pt-10 pb-8">

          {/* Badge */}
          <div className="inline-flex items-center gap-2 bg-orange-500/10 border border-orange-500/20 rounded-full px-3 py-1 mb-5">
            <span className="w-1.5 h-1.5 rounded-full bg-orange-400 animate-pulse" />
            <span className="text-orange-400 text-xs font-semibold uppercase tracking-widest">Betopia Kitchen · Order History</span>
          </div>

          {/* Title + Stats */}
          <div className="flex items-end justify-between gap-6 flex-wrap">
            <div>
              <h1 className="text-4xl font-extrabold text-slate-900 tracking-tight leading-tight mb-2">
                Your
                <span className="block text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-orange-600">
                  Orders
                </span>
              </h1>
              <p className="text-slate-500 text-sm max-w-sm leading-relaxed">
                Track the status of your current and past orders all in one place.
              </p>
            </div>

            {/* Stats */}
            <div className="flex items-center gap-6 shrink-0">
              <div className="text-center">
                <p className="text-2xl font-bold text-slate-900">{orders.length}</p>
                <p className="text-slate-500 text-xs mt-0.5">Total Orders</p>
              </div>
              <div className="w-px h-10 bg-slate-200" />
              <div className="text-center">
                <p className="text-2xl font-bold text-orange-500">{activeOrders.length}</p>
                <p className="text-slate-500 text-xs mt-0.5">Active Now</p>
              </div>
              <div className="w-px h-10 bg-slate-200" />
              <div className="text-center">
                <p className="text-2xl font-bold text-emerald-500">
                  {orders.filter(o => o.status === 'served').length}
                </p>
                <p className="text-slate-500 text-xs mt-0.5">Completed</p>
              </div>
            </div>
          </div>

          {/* Active order alert */}
          {activeOrders.length > 0 && (
            <div className="mt-6 flex items-center gap-3 bg-orange-50 border border-orange-200 rounded-2xl px-4 py-3 shadow-sm">
              <span className="text-xl">🔥</span>
              <div>
                <p className="text-orange-600 font-semibold text-sm">
                  {activeOrders.length} order{activeOrders.length > 1 ? 's' : ''} currently being processed
                </p>
                <p className="text-orange-400/80 text-xs mt-0.5">The kitchen is working on it — we'll have it ready soon!</p>
              </div>
              <button
                onClick={fetchOrders}
                className="ml-auto flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-orange-100 text-orange-500 text-xs font-semibold rounded-xl border border-orange-200 transition shadow-sm"
              >
                ↻ Refresh
              </button>
            </div>
          )}

          {/* Filter pills */}
          <div className="flex gap-2 mt-6 flex-wrap">
            {['all', 'pending', 'preparing', 'ready', 'served', 'canceled'].map((status) => (
              <button
                key={status}
                onClick={() => setFilter(status)}
                className={`px-4 py-1.5 rounded-full text-xs font-semibold border transition-all capitalize ${
                  filter === status
                    ? 'bg-orange-500 border-orange-500 text-white shadow-md shadow-orange-500/20'
                    : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300 hover:text-slate-700 shadow-sm'
                }`}
              >
                {status === 'all' ? `All (${orders.length})` : STATUS_LABELS[status]}
              </button>
            ))}
          </div>

        </div>
      </div>

      {error && (
        <div className="bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-lg px-4 py-3 mb-6">
          {error}
        </div>
      )}

      {loading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {[1, 2, 3].map(i => (
            <div key={i} className="bg-white border border-slate-200 rounded-2xl p-5 animate-pulse shadow-sm">
              <div className="flex items-center justify-between mb-4">
                <div>
                  <div className="h-5 w-28 bg-slate-200 rounded-md mb-2"></div>
                  <div className="h-3 w-40 bg-slate-200 rounded-md"></div>
                </div>
                <div className="h-6 w-20 bg-slate-200 rounded-full"></div>
              </div>
              <div className="space-y-2 mb-4">
                <div className="h-4 w-full bg-slate-200 rounded-md"></div>
                <div className="h-4 w-3/4 bg-slate-200 rounded-md"></div>
              </div>
              <div className="flex justify-between pt-4 border-t border-slate-200">
                <div className="h-4 w-12 bg-slate-200 rounded-md"></div>
                <div className="h-4 w-16 bg-slate-200 rounded-md"></div>
              </div>
            </div>
          ))}
        </div>
      ) : filteredOrders.length === 0 ? (
        <div className="text-center py-24 bg-white/70 backdrop-blur-md border border-slate-200 rounded-3xl shadow-sm flex flex-col items-center">
          <svg className="w-24 h-24 text-slate-200 mb-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <p className="text-slate-500 text-lg font-medium">No orders yet</p>
          <p className="text-slate-400 text-sm mt-1">
            {filter === 'all' ? 'Browse the menu to place your first order.' : 'No orders with this status.'}
          </p>
          {filter === 'all' && (
            <button
              onClick={() => router.push('/menu')}
              className="mt-6 px-6 py-2.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-xl transition shadow-sm"
            >
              Browse Menu
            </button>
          )}
        </div>
      ) : (
        <div ref={ordersRef} className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {filteredOrders.map((order) => (
            <div
              key={order.id}
              className="bg-white/80 backdrop-blur-md border border-slate-200 rounded-2xl p-5 shadow-sm flex flex-col h-full"
            >
              {/* Order header */}
              <div className="flex items-center justify-between mb-4">
                <div>
                  <p className="text-slate-900 font-semibold">Order #{order.id}</p>
                  <p className="text-slate-500 text-xs mt-0.5">{formatDate(order.created_at)}</p>
                </div>
                <div className="flex items-center gap-2">
                  {order.status === 'pending' && editing?.orderId !== order.id && (
                    <>
                      <button
                        onClick={() => startEdit(order)}
                        className="px-3 py-1 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-600 text-xs rounded-lg transition"
                      >
                        ✏️ Edit
                      </button>
                      <button
                        onClick={() => handleCancel(order.id)}
                        disabled={canceling === order.id}
                        className="px-3 py-1 bg-red-50 hover:bg-red-100 border border-red-200 text-red-600 text-xs rounded-lg transition disabled:opacity-50"
                      >
                        {canceling === order.id ? 'Canceling...' : '✕ Cancel'}
                      </button>
                    </>
                  )}
                  <span className={`px-3 py-1 rounded-full text-xs font-semibold border ${STATUS_COLORS[order.status]}`}>
                    {STATUS_LABELS[order.status]}
                  </span>
                </div>
              </div>

              {/* Edit mode */}
              {editing?.orderId === order.id ? (
                <div>
                  <p className="text-slate-500 text-xs mb-3">
                    Adjust quantities. Set to 0 to remove an item.
                  </p>

                  <div className="space-y-3 mb-4">
                    {editing.items.map((item) => (
                      <div key={item.order_item_id} className="flex items-center justify-between">
                        <div>
                          <p className={`text-sm font-medium ${item.quantity === 0 ? 'text-slate-400 line-through' : 'text-slate-900'}`}>
                            {item.name}
                          </p>
                          <p className="text-slate-500 text-xs">৳{item.unit_price} each</p>
                        </div>
                        <div className="flex items-center gap-3">
                          <button
                            onClick={() => updateEditQuantity(item.order_item_id, Math.max(0, item.quantity - 1))}
                            className="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-600 flex items-center justify-center transition"
                          >
                            −
                          </button>
                          <span className={`text-sm font-semibold w-4 text-center ${item.quantity === 0 ? 'text-slate-400' : 'text-slate-900'}`}>
                            {item.quantity}
                          </span>
                          <button
                            onClick={() => updateEditQuantity(item.order_item_id, item.quantity + 1)}
                            className="w-7 h-7 rounded-full bg-orange-500 hover:bg-orange-600 text-white flex items-center justify-center transition shadow-sm"
                          >
                            +
                          </button>
                        </div>
 </div>
                    ))}
                  </div>

                  {/* Notes */}
                  <div className="mb-4">
                    <label className="block text-xs font-medium text-slate-500 mb-1.5">
                      Notes
                    </label>
                    <textarea
                      value={editing.notes}
                      onChange={(e) => setEditing({ ...editing, notes: e.target.value })}
                      placeholder="e.g. Less spicy please"
                      rows={2}
                      className="w-full bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-500 transition resize-none"
                    />
                  </div>

                  {/* New total */}
                  <div className="flex justify-between text-sm mb-4 bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 mt-auto">
                    <span className="text-slate-500">New Total</span>
                    <span className="text-slate-900 font-bold">৳{editTotal.toFixed(2)}</span>
                  </div>

                  {editError && (
                    <div className="bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-lg px-3 py-2 mb-4">
                      {editError}
                    </div>
                  )}

                  <div className="flex gap-3">
                    <button
                      onClick={handleSaveEdit}
                      disabled={saving}
                      className="flex-1 bg-orange-500 hover:bg-orange-600 disabled:bg-orange-500/50 text-white font-semibold rounded-lg px-4 py-2.5 text-sm transition"
                    >
                      {saving ? 'Saving...' : 'Save Changes'}
                    </button>
                    <button
                      onClick={() => setEditing(null)}
                      className="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 text-sm rounded-lg transition"
                    >
                      Cancel
                    </button>
                  </div>
                </div>
              ) : (
                <>
                  {/* Normal view */}
                  <div className="space-y-2 mb-4">
                    {order.items.map((item) => (
                      <div key={item.id} className="flex justify-between text-sm">
                        <span className="text-slate-700">
                          {item.name}
                          <span className="text-slate-500 ml-1 font-medium">x{item.quantity}</span>
                        </span>
                        <span className="text-slate-600 font-medium">
                          ৳{(parseFloat(item.unit_price) * item.quantity).toFixed(2)}
                        </span>
                      </div>
                    ))}
                  </div>

                  {order.notes && (
                    <div className="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-500 mb-4">
                      📝 {order.notes}
                    </div>
                  )}

                  <div className="flex items-center justify-between pt-4 border-t border-slate-200 mt-auto">
                    <span className="text-slate-500 text-sm font-medium">Total</span>
                    <span className="text-slate-900 font-bold">৳{order.total_amount}</span>
                  </div>

                  {order.status === 'ready' && (
                    <div className="mt-4 bg-green-500/10 border border-green-500/20 rounded-lg px-4 py-3 text-sm text-green-400 font-medium text-center">
                      ✅ Your order is ready — please collect at the counter
                    </div>
                  )}
                </>
              )}
            </div>
          ))}
        </div>
      )}

    </div>
  );
}
'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { getUserOrders, updateUserOrder, cancelUserOrder, Order, OrderItem } from '@/lib/services/orders';


const STATUS_COLORS: Record<string, string> = {
  pending:   'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
  preparing: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
  ready:     'bg-green-500/10 text-green-400 border-green-500/20',
  served:    'bg-zinc-500/10 text-zinc-400 border-zinc-500/20',
  canceled:  'bg-red-500/10 text-red-400 border-red-500/20',
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

  useEffect(() => {
    fetchOrders();
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
      setSaving(false);
      return;
    }

    // Update order in list
    setOrders((prev) =>
      prev.map((o) => (o.id === editing.orderId ? data!.data! : o))
    );
    setEditing(null);
    setSaving(false);
  }

  async function handleCancel(orderId: number) {
  if (!confirm('Are you sure you want to cancel this order?')) return;
  setCanceling(orderId);
  const { data, error } = await cancelUserOrder(orderId);
  if (error) {
    setError(error);
  } else {
    setOrders((prev) =>
      prev.map((o) => (o.id === orderId ? data!.data! : o))
    );
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
    <div className="max-w-3xl mx-auto">

      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-2xl font-bold text-white">My Orders</h2>
          <p className="text-zinc-400 text-sm mt-1">
            {orders.length} {orders.length === 1 ? 'order' : 'orders'} total
          </p>
        </div>
        <button
          onClick={fetchOrders}
          className="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-sm rounded-lg transition"
        >
          Refresh
        </button>
      </div>

      {/* Active orders banner */}
      {activeOrders.length > 0 && (
        <div className="bg-orange-500/10 border border-orange-500/20 rounded-xl px-5 py-4 mb-6">
          <p className="text-orange-400 font-semibold text-sm">
            🔥 {activeOrders.length} active {activeOrders.length === 1 ? 'order' : 'orders'}
          </p>
          <p className="text-zinc-400 text-xs mt-0.5">
            Your order is being processed by the kitchen
          </p>
        </div>
      )}

      {/* Filter tabs */}
      <div className="flex gap-2 mb-6 flex-wrap">
        {['all', 'pending', 'preparing', 'ready', 'served', 'canceled'].map((status) => (
          <button
            key={status}
            onClick={() => setFilter(status)}
            className={`px-4 py-1.5 rounded-full text-xs font-semibold border transition capitalize ${
              filter === status
                ? 'bg-orange-500 border-orange-500 text-white'
                : 'border-zinc-700 text-zinc-400 hover:border-zinc-500 hover:text-white'
            }`}
          >
            {status === 'all' ? `All (${orders.length})` : STATUS_LABELS[status]}
          </button>
        ))}
      </div>

      {error && (
        <div className="bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-lg px-4 py-3 mb-6">
          {error}
        </div>
      )}

      {loading ? (
        <div className="text-zinc-500 text-sm">Loading orders...</div>
      ) : filteredOrders.length === 0 ? (
        <div className="text-center py-20">
          <p className="text-zinc-500 text-lg">No orders found</p>
          <p className="text-zinc-600 text-sm mt-1">
            {filter === 'all' ? 'Place your first order from the menu.' : 'No orders with this status.'}
          </p>
          {filter === 'all' && (
            <button
              onClick={() => router.push('/menu')}
              className="mt-4 px-5 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition"
            >
              Browse Menu
            </button>
          )}
        </div>
      ) : (
        <div className="space-y-4">
          {filteredOrders.map((order) => (
            <div
              key={order.id}
              className="bg-zinc-900 border border-zinc-800 rounded-2xl p-5"
            >
              {/* Order header */}
              <div className="flex items-center justify-between mb-4">
                <div>
                  <p className="text-white font-semibold">Order #{order.id}</p>
                  <p className="text-zinc-500 text-xs mt-0.5">{formatDate(order.created_at)}</p>
                </div>
                <div className="flex items-center gap-2">
                  {order.status === 'pending' && editing?.orderId !== order.id && (
                    <>
                      <button
                        onClick={() => startEdit(order)}
                        className="px-3 py-1 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs rounded-lg transition"
                      >
                        ✏️ Edit
                      </button>
                      <button
                        onClick={() => handleCancel(order.id)}
                        disabled={canceling === order.id}
                        className="px-3 py-1 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 text-red-400 text-xs rounded-lg transition disabled:opacity-50"
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
                  <p className="text-zinc-400 text-xs mb-3">
                    Adjust quantities. Set to 0 to remove an item.
                  </p>

                  <div className="space-y-3 mb-4">
                    {editing.items.map((item) => (
                      <div key={item.order_item_id} className="flex items-center justify-between">
                        <div>
                          <p className={`text-sm font-medium ${item.quantity === 0 ? 'text-zinc-600 line-through' : 'text-white'}`}>
                            {item.name}
                          </p>
                          <p className="text-zinc-500 text-xs">৳{item.unit_price} each</p>
                        </div>
                        <div className="flex items-center gap-3">
                          <button
                            onClick={() => updateEditQuantity(item.order_item_id, Math.max(0, item.quantity - 1))}
                            className="w-7 h-7 rounded-full bg-zinc-800 hover:bg-zinc-700 text-white flex items-center justify-center transition"
                          >
                            −
                          </button>
                          <span className={`text-sm font-semibold w-4 text-center ${item.quantity === 0 ? 'text-zinc-600' : 'text-white'}`}>
                            {item.quantity}
                          </span>
                          <button
                            onClick={() => updateEditQuantity(item.order_item_id, item.quantity + 1)}
                            className="w-7 h-7 rounded-full bg-orange-500 hover:bg-orange-600 text-white flex items-center justify-center transition"
                          >
                            +
                          </button>
                        </div>
 </div>
                    ))}
                  </div>

                  {/* Notes */}
                  <div className="mb-4">
                    <label className="block text-xs font-medium text-zinc-400 mb-1.5">
                      Notes
                    </label>
                    <textarea
                      value={editing.notes}
                      onChange={(e) => setEditing({ ...editing, notes: e.target.value })}
                      placeholder="e.g. Less spicy please"
                      rows={2}
                      className="w-full bg-zinc-800 border border-zinc-700 text-white placeholder-zinc-500 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-500 transition resize-none"
                    />
                  </div>

                  {/* New total */}
                  <div className="flex justify-between text-sm mb-4 bg-zinc-800 rounded-lg px-4 py-3">
                    <span className="text-zinc-400">New Total</span>
                    <span className="text-white font-bold">৳{editTotal.toFixed(2)}</span>
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
                      className="px-4 py-2.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-sm rounded-lg transition"
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
                        <span className="text-zinc-300">
                          {item.name}
                          <span className="text-zinc-500 ml-1">x{item.quantity}</span>
                        </span>
                        <span className="text-zinc-400">
                          ৳{(parseFloat(item.unit_price) * item.quantity).toFixed(2)}
                        </span>
                      </div>
                    ))}
                  </div>

                  {order.notes && (
                    <div className="bg-zinc-800 rounded-lg px-3 py-2 text-xs text-zinc-400 mb-4">
                      📝 {order.notes}
                    </div>
                  )}

                  <div className="flex items-center justify-between pt-4 border-t border-zinc-800">
                    <span className="text-zinc-400 text-sm font-medium">Total</span>
                    <span className="text-white font-bold">৳{order.total_amount}</span>
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
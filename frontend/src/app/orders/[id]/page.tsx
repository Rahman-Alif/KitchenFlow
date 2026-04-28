'use client';

import { useEffect, useState } from 'react';
import { useRouter, useParams } from 'next/navigation';
import KitchenLayout from '@/components/kitchen/layouts/KitchenLayout';
import {
  getOrderDetail,
  updateOrderStatus,
  recordTransaction,
  Order,
} from '@/lib/services/orders';
import { getUser } from '@/lib/auth';

const STATUS_COLORS: Record<string, string> = {
  pending:   'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
  preparing: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
  ready:     'bg-green-500/10 text-green-400 border-green-500/20',
  served:    'bg-slate-100 text-slate-500 border-slate-200',
  canceled:  'bg-red-50 text-red-600 border-red-200',
};

const NEXT_STATUS: Record<string, string> = {
  pending:   'preparing',
  preparing: 'ready',
  ready:     'served',
};

const NEXT_STATUS_LABEL: Record<string, string> = {
  pending:   'Accept & Record Payment',
  preparing: 'Mark as Ready',
  ready:     'Complete & Serve',
};

export default function OrderDetailPage() {
  const router = useRouter();
  const params = useParams();
  const id     = Number(params.id);

  const [order, setOrder]               = useState<Order | null>(null);
  const [loading, setLoading]           = useState(true);
  const [error, setError]               = useState('');
  const [actionLoading, setActionLoading] = useState(false);

  // Transaction form
  const [showPayment, setShowPayment]     = useState(false);
  const [tenderedAmount, setTenderedAmount] = useState('');
  const [paymentError, setPaymentError]   = useState('');

  useEffect(() => {
    const user = getUser();
    if (!user || user.role !== 'kitchen_staff') {
      router.push('/login');
      return;
    }
    fetchOrder();
  }, [id]);

  async function fetchOrder() {
    setLoading(true);
    const { data, error } = await getOrderDetail(id);
    if (error) {
      setError(error);
    } else {
      setOrder(data?.data ?? null);
    }
    setLoading(false);
  }

  async function handleStatusUpdate() {
    if (!order) return;
    const next = NEXT_STATUS[order.status];
    if (!next) return;

    if (next === 'preparing') {
      setShowPayment(true);
      return;
    }

    setActionLoading(true);
    const { data, error } = await updateOrderStatus(id, next);
    if (error) {
      setError(error);
    } else {
      setOrder(data?.data ?? null);
    }
    setActionLoading(false);
  }

  async function handleCancel() {
    if (!order) return;
    setActionLoading(true);
    const { data, error } = await updateOrderStatus(id, 'canceled');
    if (error) {
      setError(error);
    } else {
      setOrder(data?.data ?? null);
    }
    setActionLoading(false);
  }

  async function handlePaymentSubmit() {
    setPaymentError('');
    const amount = parseFloat(tenderedAmount);

    if (!tenderedAmount || isNaN(amount)) {
      setPaymentError('Please enter a valid amount.');
      return;
    }

    if (amount < parseFloat(order?.total_amount ?? '0')) {
      setPaymentError('Tendered amount cannot be less than the order total.');
      return;
    }

    setActionLoading(true);
    const { data, error } = await recordTransaction(id, amount);
    if (error) {
      setPaymentError(error);
      setActionLoading(false);
      return;
    }

    // After transaction — status is already advanced to preparing by backend
    await fetchOrder();
    setShowPayment(false);
    setActionLoading(false);
  }

  if (loading) {
    return (
      <KitchenLayout>
        <div className="text-slate-500 text-sm">Loading order...</div>
      </KitchenLayout>
    );
  }

  if (error || !order) {
    return (
      <KitchenLayout>
        <div className="bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-lg px-4 py-3">
          {error || 'Order not found.'}
        </div>
      </KitchenLayout>
    );
  }

  const change = showPayment && tenderedAmount
    ? (parseFloat(tenderedAmount) - parseFloat(order.total_amount)).toFixed(2)
    : null;

  return (
    <KitchenLayout>
      <div className="max-w-2xl mx-auto">

        {/* Back */}
        <button
          onClick={() => router.push('/orders')}
          className="text-slate-500 hover:text-slate-900 text-sm mb-6 flex items-center gap-2 transition"
        >
          ← Back to Queue
        </button>

        {/* Header */}
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-2xl font-bold text-slate-900">Order #{order.id}</h2>
          <span className={`px-3 py-1 rounded-full text-xs font-semibold border capitalize ${STATUS_COLORS[order.status]}`}>
            {order.status}
          </span>
        </div>

        {/* Order Info */}
        <div className="bg-white border border-slate-200 shadow-sm rounded-2xl p-6 mb-4">
          <div className="flex justify-between text-sm mb-4">
            <span className="text-slate-500">Placed by</span>
            <span className="text-slate-900 font-medium">{order.placed_by.name}</span>
          </div>
          <div className="flex justify-between text-sm">
            <span className="text-slate-500">Placed at</span>
            <span className="text-slate-900">{new Date(order.created_at).toLocaleTimeString()}</span>
          </div>
          {order.notes && (
            <div className="mt-4 bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm text-slate-500">
              📝 {order.notes}
            </div>
          )}
        </div>

        {/* Items */}
        <div className="bg-white border border-slate-200 shadow-sm rounded-2xl p-6 mb-4">
          <h3 className="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-4">
            Items
          </h3>
          <div className="space-y-3">
            {order.items.map((item) => (
              <div key={item.id} className="flex gap-4 p-3 bg-slate-50 rounded-xl border border-slate-200">
                {/* Image */}
                <div className="w-16 h-16 rounded-lg bg-slate-100 shrink-0 overflow-hidden border border-slate-200">
                  {item.image_url ? (
                    <img src={item.image_url} alt={item.name} className="w-full h-full object-cover" />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center text-slate-400 text-xs">No img</div>
                  )}
                </div>

                {/* Details */}
                <div className="flex-1 flex flex-col justify-center">
                  <div className="flex justify-between items-start mb-1">
                    <p className="text-slate-900 text-sm font-bold">{item.name}</p>
                    <p className="text-slate-700 text-sm font-bold bg-white border border-slate-200 px-2 py-0.5 rounded-md">x{item.quantity}</p>
                  </div>
                  
                  {item.description && (
                    <p className="text-slate-500 text-xs line-clamp-2 mb-2 pr-4">{item.description}</p>
                  )}
                  
                  <div className="flex justify-between items-center mt-auto">
                    <p className="text-slate-500 text-xs">৳{item.unit_price} each</p>
                    <p className="text-orange-500 text-sm font-bold">
                      ৳{(parseFloat(item.unit_price) * item.quantity).toFixed(2)}
                    </p>
                  </div>
                </div>
              </div>
            ))}
          </div>
          <div className="border-t border-slate-200 mt-4 pt-4 flex justify-between">
            <span className="text-slate-500 font-medium">Total</span>
            <span className="text-slate-900 font-bold text-lg">৳{order.total_amount}</span>
          </div>
        </div>

        {order.transaction && (
          <div className="bg-green-50 border border-green-200 rounded-2xl p-6 mb-4">
            <h3 className="text-sm font-semibold text-green-600 uppercase tracking-wider mb-4">
              Payment Recorded
            </h3>
            <div className="space-y-2 text-sm">
              <div className="flex justify-between">
                <span className="text-green-700/70">Tendered</span>
                <span className="text-green-800 font-medium">৳{order.transaction.tendered_amount}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-green-700/70">Change</span>
                <span className="text-green-800 font-medium">৳{order.transaction.change_returned}</span>
              </div>
            </div>
          </div>
        )}

        {/* Payment Form */}
        {showPayment && (
          <div className="bg-white border border-slate-200 shadow-sm rounded-2xl p-6 mb-4">
            <h3 className="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-4">
              Record Payment
            </h3>

            {paymentError && (
              <div className="bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg px-4 py-3 mb-4">
                {paymentError}
              </div>
            )}

            <div className="mb-4">
              <label className="block text-sm font-medium text-slate-700 mb-1.5">
                Order Total
              </label>
              <p className="text-slate-900 font-bold text-xl">৳{order.total_amount}</p>
            </div>

            <div className="mb-4">
              <label className="block text-sm font-medium text-slate-700 mb-1.5">
                Tendered Amount
              </label>
              <input
                type="number"
                value={tenderedAmount}
                onChange={(e) => setTenderedAmount(e.target.value)}
                placeholder="0.00"
                min={order.total_amount}
                className="w-full bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition shadow-sm"
              />
            </div>

            {change !== null && parseFloat(change) >= 0 && (
              <div className="bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 mb-4 flex justify-between text-sm">
                <span className="text-slate-500">Change to return</span>
                <span className="text-slate-900 font-semibold">৳{change}</span>
              </div>
            )}

            <div className="flex gap-3">
              <button
                onClick={handlePaymentSubmit}
                disabled={actionLoading}
                className="flex-1 bg-green-600 hover:bg-green-700 disabled:bg-green-600/50 text-white font-semibold rounded-lg px-4 py-2.5 text-sm transition"
              >
                {actionLoading ? 'Processing...' : 'Confirm Payment & Serve'}
              </button>
              <button
                onClick={() => setShowPayment(false)}
                className="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm rounded-lg transition border border-slate-200"
              >
                Cancel
              </button>
            </div>
          </div>
        )}

        {/* Actions */}
        {!showPayment && order.status !== 'served' && order.status !== 'canceled' && (
          <div className="flex gap-3">
            <button
              onClick={handleStatusUpdate}
              disabled={actionLoading}
              className="flex-1 bg-orange-500 hover:bg-orange-600 disabled:bg-orange-500/50 text-white font-semibold rounded-lg px-4 py-3 text-sm transition"
            >
              {actionLoading ? 'Updating...' : NEXT_STATUS_LABEL[order.status]}
            </button>
            {order.status === 'pending' && (
              <button
                onClick={handleCancel}
                disabled={actionLoading}
                className="px-4 py-3 bg-white hover:bg-red-50 border border-slate-200 hover:border-red-200 hover:text-red-600 text-slate-600 text-sm rounded-lg transition shadow-sm"
              >
                Cancel Order
              </button>
            )}
          </div>
        )}

      </div>
    </KitchenLayout>
  );
}
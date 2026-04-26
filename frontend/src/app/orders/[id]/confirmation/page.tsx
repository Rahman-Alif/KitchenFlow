'use client';

import { useEffect, useState } from 'react';
import { useRouter, useParams } from 'next/navigation';
import UserLayout from '@/components/user/layouts/UserLayout';
import { getUserOrder, Order } from '@/lib/services/orders';
import { getUser } from '@/lib/auth';

export default function OrderConfirmationPage() {
  const router = useRouter();
  const params = useParams();
  const id     = Number(params.id);

  const [order, setOrder]   = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError]   = useState('');

  useEffect(() => {
    const user = getUser();
    if (!user || user.role !== 'user') {
      router.push('/login');
      return;
    }
    fetchOrder();
  }, [id]);

  async function fetchOrder() {
    const { data, error } = await getUserOrder(id);
    if (error) {
      setError(error);
    } else {
      setOrder(data?.data ?? null);
    }
    setLoading(false);
  }

  if (loading) {
    return (
      <UserLayout>
        <div className="text-slate-500 text-sm">Loading...</div>
      </UserLayout>
    );
  }

  if (error || !order) {
    return (
      <UserLayout>
        <div className="bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg px-4 py-3">
          {error || 'Order not found.'}
        </div>
      </UserLayout>
    );
  }

  return (
    <UserLayout>
      <div className="max-w-lg mx-auto">

        {/* Success header */}
        <div className="text-center mb-8">
          <div className="w-16 h-16 bg-green-50 border border-green-200 text-green-500 rounded-full flex items-center justify-center mx-auto mb-4">
            <span className="text-3xl">✓</span>
          </div>
          <h2 className="text-2xl font-bold text-slate-900">Order Placed!</h2>
          <p className="text-slate-500 text-sm mt-2">
            Your order has been sent to the kitchen.
          </p>
        </div>

        {/* Order summary */}
        <div className="bg-white border border-slate-200 shadow-sm rounded-2xl p-6 mb-4">
          <div className="flex justify-between items-center mb-4">
            <h3 className="text-slate-900 font-semibold">Order #{order.id}</h3>
            <span className="px-3 py-1 bg-slate-50 border border-slate-200 text-slate-600 text-xs font-semibold rounded-full capitalize">
              {order.status}
            </span>
          </div>

          <div className="space-y-3 mb-4">
            {order.items.map((item) => (
              <div key={item.id} className="flex justify-between items-center">
                <div>
                  <p className="text-slate-900 text-sm font-medium">{item.name}</p>
                  <p className="text-slate-500 text-xs">৳{item.unit_price} x {item.quantity}</p>
                </div>
                <p className="text-slate-900 text-sm font-semibold">
                  ৳{(parseFloat(item.unit_price) * item.quantity).toFixed(2)}
                </p>
              </div>
            ))}
          </div>

          {order.notes && (
            <div className="bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm text-slate-500 mb-4">
              📝 {order.notes}
            </div>
          )}

          <div className="border-t border-slate-200 pt-4 flex justify-between">
            <span className="text-slate-500 font-medium">Total</span>
            <span className="text-slate-900 font-bold text-lg">৳{order.total_amount}</span>
          </div>
        </div>

        {/* Info */}
        <div className="bg-slate-50 border border-slate-200 rounded-2xl p-5 mb-6 text-sm text-slate-600">
          Please wait at the counter. Kitchen staff will call your name when your order is ready.
        </div>

        {/* Actions */}
        <button
          onClick={() => router.push('/menu')}
          className="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-lg px-4 py-3 text-sm transition"
        >
          Order More
        </button>

      </div>
    </UserLayout>
  );
}
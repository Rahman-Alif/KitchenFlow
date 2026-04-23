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
        <div className="text-zinc-500 text-sm">Loading...</div>
      </UserLayout>
    );
  }

  if (error || !order) {
    return (
      <UserLayout>
        <div className="bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-lg px-4 py-3">
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
          <div className="w-16 h-16 bg-green-500/10 border border-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
            <span className="text-3xl">✓</span>
          </div>
          <h2 className="text-2xl font-bold text-white">Order Placed!</h2>
          <p className="text-zinc-400 text-sm mt-2">
            Your order has been sent to the kitchen.
          </p>
        </div>

        {/* Order summary */}
        <div className="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 mb-4">
          <div className="flex justify-between items-center mb-4">
            <h3 className="text-white font-semibold">Order #{order.id}</h3>
            <span className="px-3 py-1 bg-yellow-500/10 border border-yellow-500/20 text-yellow-400 text-xs font-semibold rounded-full capitalize">
              {order.status}
            </span>
          </div>

          <div className="space-y-3 mb-4">
            {order.items.map((item) => (
              <div key={item.id} className="flex justify-between items-center">
                <div>
                  <p className="text-white text-sm font-medium">{item.name}</p>
                  <p className="text-zinc-500 text-xs">৳{item.unit_price} x {item.quantity}</p>
                </div>
                <p className="text-white text-sm font-semibold">
                  ৳{(parseFloat(item.unit_price) * item.quantity).toFixed(2)}
                </p>
              </div>
            ))}
          </div>

          {order.notes && (
            <div className="bg-zinc-800 rounded-lg px-4 py-3 text-sm text-zinc-300 mb-4">
              📝 {order.notes}
            </div>
          )}

          <div className="border-t border-zinc-800 pt-4 flex justify-between">
            <span className="text-zinc-400 font-medium">Total</span>
            <span className="text-white font-bold text-lg">৳{order.total_amount}</span>
          </div>
        </div>

        {/* Info */}
        <div className="bg-zinc-900 border border-zinc-800 rounded-2xl p-5 mb-6 text-sm text-zinc-400">
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
'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import {
  getUserMenu,
  placeOrder,
  MenuCategory,
  CartItem,
} from '@/lib/services/orders';

export default function UserMenuView() {
  const router = useRouter();
  const [categories, setCategories] = useState<MenuCategory[]>([]);
  const [loading, setLoading]       = useState(true);
  const [error, setError]           = useState('');
  const [cart, setCart]             = useState<CartItem[]>([]);
  const [notes, setNotes]           = useState('');
  const [placing, setPlacing]       = useState(false);
  const [orderError, setOrderError] = useState('');

  useEffect(() => {
    fetchMenu();
  }, []);

  async function fetchMenu() {
    const { data, error } = await getUserMenu();
    if (error) {
      setError(error);
    } else {
      setCategories(data?.data ?? []);
    }
    setLoading(false);
  }

  function addToCart(item: { id: number; name: string; price: string }) {
    setCart((prev) => {
      const existing = prev.find((c) => c.menu_item_id === item.id);
      if (existing) {
        return prev.map((c) =>
          c.menu_item_id === item.id
            ? { ...c, quantity: c.quantity + 1 }
            : c
        );
      }
      return [...prev, {
        menu_item_id: item.id,
        name:         item.name,
        price:        item.price,
        quantity:     1,
      }];
    });
  }

  function removeFromCart(menu_item_id: number) {
    setCart((prev) => {
      const existing = prev.find((c) => c.menu_item_id === menu_item_id);
      if (existing && existing.quantity > 1) {
        return prev.map((c) =>
          c.menu_item_id === menu_item_id
            ? { ...c, quantity: c.quantity - 1 }
            : c
        );
      }
      return prev.filter((c) => c.menu_item_id !== menu_item_id);
    });
  }

  function getQuantity(menu_item_id: number): number {
    return cart.find((c) => c.menu_item_id === menu_item_id)?.quantity ?? 0;
  }

  const cartTotal = cart.reduce(
    (sum, item) => sum + parseFloat(item.price) * item.quantity,
    0
  );

  async function handlePlaceOrder() {
    if (cart.length === 0) return;
    setOrderError('');
    setPlacing(true);

    const items = cart.map((c) => ({
      menu_item_id: c.menu_item_id,
      quantity:     c.quantity,
    }));

    const { data, error } = await placeOrder(items, notes || undefined);

    if (error) {
      setOrderError(error);
      setPlacing(false);
      return;
    }

    const orderId = data?.data?.id;
    router.push(`/orders/${orderId}/confirmation`);
  }

  return (
    <div className="max-w-6xl mx-auto">
      <div className="flex gap-6">

        {/* Menu */}
        <div className="flex-1">
          <h2 className="text-2xl font-bold text-white mb-6">Menu</h2>

          {error && (
            <div className="bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-lg px-4 py-3 mb-6">
              {error}
            </div>
          )}

          {loading ? (
            <div className="text-zinc-500 text-sm">Loading menu...</div>
          ) : categories.length === 0 ? (
            <div className="text-zinc-500 text-sm">No items available.</div>
          ) : (
            <div className="space-y-8">
              {categories.map((cat) => (
                <div key={cat.category_id}>
                  <h3 className="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-3">
                    {cat.category_name}
                  </h3>
                  <div className="space-y-2">
                    {cat.items.map((item) => {
                      const qty = getQuantity(item.id);
                      return (
                        <div
                          key={item.id}
                          className="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden flex"
                        >
                          {/* Image */}
                          {item.image_path && (
                            <div className="w-24 h-24 shrink-0">
                              <img
                                src={item.image_path}
                                alt={item.name}
                                className="w-full h-full object-cover"
                              />
                            </div>
                          )}

                          {/* Details */}
                          <div className="flex-1 p-4 flex items-center justify-between">
                            <div className="flex-1 mr-4">
                              <p className="text-white font-medium">{item.name}</p>
                              {item.description && (
                                <p className="text-zinc-500 text-xs mt-0.5 line-clamp-2">
                                  {item.description}
                                </p>
                              )}
                              <p className="text-orange-400 font-semibold text-sm mt-1">
                                ৳{item.price}
                              </p>
                            </div>

                            {/* Quantity control */}
                            {qty === 0 ? (
                              <button
                                onClick={() => addToCart(item)}
                                className="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition"
                              >
                                Add
                              </button>
                            ) : (
                              <div className="flex items-center gap-3">
                                <button
                                  onClick={() => removeFromCart(item.id)}
                                  className="w-8 h-8 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-white text-lg flex items-center justify-center transition"
                                >
                                  −
                                </button>
                                <span className="text-white font-semibold w-4 text-center">
                                  {qty}
                                </span>
                                <button
                                  onClick={() => addToCart(item)}
                                  className="w-8 h-8 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-lg flex items-center justify-center transition"
                                >
                                  +
                                </button>
                              </div>
                            )}
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Cart */}
        <div className="w-80 shrink-0">
          <div className="bg-zinc-900 border border-zinc-800 rounded-2xl p-5 sticky top-6">
            <h3 className="text-white font-bold text-lg mb-4">Your Order</h3>

            {cart.length === 0 ? (
              <p className="text-zinc-500 text-sm">No items added yet.</p>
            ) : (
              <>
                <div className="space-y-3 mb-4">
                  {cart.map((item) => (
                    <div key={item.menu_item_id} className="flex justify-between items-center">
                      <div>
                        <p className="text-white text-sm font-medium">{item.name}</p>
                        <p className="text-zinc-500 text-xs">
                          ৳{item.price} x {item.quantity}
                        </p>
                      </div>
                      <p className="text-white text-sm font-semibold">
                        ৳{(parseFloat(item.price) * item.quantity).toFixed(2)}
                      </p>
                    </div>
                  ))}
                </div>

                <div className="border-t border-zinc-800 pt-4 mb-4">
                  <div className="flex justify-between">
                    <span className="text-zinc-400 text-sm">Total</span>
                    <span className="text-white font-bold">৳{cartTotal.toFixed(2)}</span>
                  </div>
                </div>

                {/* Notes */}
                <div className="mb-4">
                  <label className="block text-sm font-medium text-zinc-300 mb-1.5">
                    Notes (optional)
                  </label>
                  <textarea
                    value={notes}
                    onChange={(e) => setNotes(e.target.value)}
                    placeholder="e.g. Less spicy please"
                    rows={2}
                    className="w-full bg-zinc-800 border border-zinc-700 text-white placeholder-zinc-500 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition resize-none"
                  />
                </div>

                {orderError && (
                  <div className="bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-lg px-3 py-2 mb-4">
                    {orderError}
                  </div>
                )}

                <button
                  onClick={handlePlaceOrder}
                  disabled={placing}
                  className="w-full bg-orange-500 hover:bg-orange-600 disabled:bg-orange-500/50 text-white font-semibold rounded-lg px-4 py-2.5 text-sm transition"
                >
                  {placing ? 'Placing order...' : 'Place Order'}
                </button>
              </>
            )}
          </div>
        </div>

      </div>
    </div>
  );
}
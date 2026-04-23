import { apiRequest } from '@/lib/api';

// ─── Types ────────────────────────────────────────────────

export interface OrderItem {
  id:         number;
  name:       string;
  quantity:   number;
  unit_price: string;
}

export interface Transaction {
  id:              number;
  tendered_amount: string;
  change_returned: string;
  recorded_at:     string;
}

export interface Order {
  id:           number;
  status:       'pending' | 'preparing' | 'ready' | 'served' | 'canceled';
  total_amount: string;
  notes:        string | null;
  placed_by:    { id: number; name: string };
  items:        OrderItem[];
  created_at:   string;
  transaction?: Transaction | null;
}

export interface KitchenMenuItem {
  id:                  number;
  name:                string;
  description:         string | null;
  image_path:          string | null;
  category:            { id: number; name: string };
  price:               string;
  stock_quantity:      number;
  low_stock_threshold: number;
  is_available:        boolean;
  is_low_stock:        boolean;
  needs_restock:       boolean;
  requested_restock_quantity: number | null;
}

export interface UserMenuItem {
  id:          number;
  name:        string;
  description: string | null;
  image_path:  string | null;
  price:       string;
  category:    { id: number; name: string };
}

export interface MenuCategory {
  category_id:   number;
  category_name: string;
  items:         UserMenuItem[];
}

export interface CartItem {
  menu_item_id: number;
  name:         string;
  price:        string;
  quantity:     number;
}

// ─── Kitchen Staff — Orders ───────────────────────────────

export async function getOrderQueue() {
  return apiRequest<{ data: Order[] }>('/kitchen/orders');
}

export async function getOrderDetail(id: number) {
  return apiRequest<{ data: Order }>(`/kitchen/orders/${id}`);
}

export async function updateOrderStatus(id: number, status: string) {
  return apiRequest<{ data: Order }>(`/kitchen/orders/${id}/status`, {
    method: 'PATCH',
    body:   { status },
  });
}

export async function recordTransaction(id: number, tendered_amount: number) {
  return apiRequest<{ data: Transaction }>(`/kitchen/orders/${id}/transaction`, {
    method: 'POST',
    body:   { tendered_amount },
  });
}

// ─── Kitchen Staff — Menu ─────────────────────────────────

export async function getKitchenMenu() {
  return apiRequest<{ data: KitchenMenuItem[] }>('/kitchen/menu');
}

export async function updateAvailability(id: number, is_available: boolean) {
  return apiRequest<{ data: KitchenMenuItem }>(`/kitchen/menu/${id}/availability`, {
    method: 'PATCH',
    body:   { is_available },
  });
}

export async function requestRestock(id: number, quantity?: number) {
  return apiRequest<{ data: KitchenMenuItem }>(`/kitchen/menu/${id}/request-restock`, {
    method: 'POST',
    body: quantity ? { quantity } : undefined,
  });
}

// ─── User — Menu & Orders ─────────────────────────────────

export async function getUserMenu() {
  return apiRequest<{ data: MenuCategory[] }>('/user/menu');
}

export async function placeOrder(items: { menu_item_id: number; quantity: number }[], notes?: string) {
  return apiRequest<{ data: Order }>('/user/orders', {
    method: 'POST',
    body:   { items, notes },
  });
}

export async function getUserOrder(id: number) {
  return apiRequest<{ data: Order }>(`/user/orders/${id}`);
}
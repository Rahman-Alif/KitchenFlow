export interface User {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'kitchen_staff' | 'user';
}

export interface Category {
  id: number;
  name: string;
}

export interface MenuItem {
  id: number;
  name: string;
  description: string | null;
  image_path: string | null;
  price: string;
  category: Category;
}

export interface KitchenMenuItem {
  id: number;
  name: string;
  category: Category;
  price: string;
  stock_quantity: number;
  low_stock_threshold: number;
  is_available: boolean;
  is_low_stock: boolean;
}

export interface OrderItem {
  id: number;
  name: string;
  quantity: number;
  unit_price: string;
}

export interface Transaction {
  id: number;
  tendered_amount: string;
  change_returned: string;
  recorded_at: string;
}

export interface Order {
  id: number;
  status: 'pending' | 'preparing' | 'ready' | 'served' | 'canceled';
  total_amount: string;
  notes: string | null;
  placed_by: {
    id: number;
    name: string;
  };
  items: OrderItem[];
  created_at: string;
  transaction?: Transaction | null;
}

export interface MenuCategory {
  category_id: number;
  category_name: string;
  items: MenuItem[];
}

export interface CartItem {
  menu_item_id: number;
  name: string;
  price: string;
  quantity: number;
}
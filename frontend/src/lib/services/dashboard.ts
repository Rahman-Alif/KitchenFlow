import { apiRequest } from '@/lib/api';

export interface DashboardStatusRow {
  status: string;
  count: number;
}

export interface DashboardTopItem {
  name: string;
  quantity: number;
}

export interface DashboardData {
  totalOrders: number;
  totalRevenue: number;
  ordersByStatus: DashboardStatusRow[];
  topItems: DashboardTopItem[];
}

export interface RevenueWeekRow {
  date: string;
  revenue: number;
}

interface DashboardApiPayload {
  data?: {
    total_orders?: number;
    total_revenue?: number;
    orders_by_status?: Array<{ status?: string; count?: number }>;
    top_items?: Array<{ name?: string; quantity?: number }>;
  };
  total_orders?: number;
  total_revenue?: number;
  orders_by_status?: Array<{ status?: string; count?: number }>;
  top_items?: Array<{ name?: string; quantity?: number }>;
}

interface RevenueWeekApiPayload {
  data?: Array<{
    date?: string;
    revenue?: string | number;
  }>;
}

export async function getDashboardData(date: string): Promise<{
  data: DashboardData | null;
  error: string | null;
}> {
  const response = await apiRequest<DashboardApiPayload>(`/admin/dashboard?date=${date}`);

  if (response.error || !response.data) {
    return { data: null, error: response.error ?? 'Failed to load dashboard data.' };
  }

  const source = response.data.data ?? response.data;

  const ordersByStatus = (source.orders_by_status ?? []).map((row) => ({
    status: row.status ?? 'unknown',
    count: Number(row.count ?? 0),
  }));

  const topItems = (source.top_items ?? []).slice(0, 5).map((item) => ({
    name: item.name ?? 'Unnamed item',
    quantity: Number(item.quantity ?? 0),
  }));

  return {
    data: {
      totalOrders: Number(source.total_orders ?? 0),
      totalRevenue: Number(source.total_revenue ?? 0),
      ordersByStatus,
      topItems,
    },
    error: null,
  };
}

export async function getRevenueWeekData(): Promise<{
  data: RevenueWeekRow[] | null;
  error: string | null;
}> {
  const response = await apiRequest<RevenueWeekApiPayload>('/admin/dashboard/revenue-week');

  if (response.error || !response.data?.data) {
    return { data: null, error: response.error ?? 'Failed to load weekly revenue.' };
  }

  return {
    data: response.data.data.map((row) => ({
      date: row.date ?? '',
      revenue: Number(row.revenue ?? 0),
    })),
    error: null,
  };
}

// All AI feature calls — points to FastAPI at NEXT_PUBLIC_AI_URL
import { apiRequest } from '@/lib/api';

const AI_BASE = process.env.NEXT_PUBLIC_AI_URL ?? 'http://localhost:8080';

async function aiPost<T>(endpoint: string, body: Record<string, unknown>): Promise<{ data: T | null; error: string | null }> {
  try {
    const res = await fetch(`${AI_BASE}${endpoint}`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(body),
    });

    const json = await res.json();

    if (!res.ok) {
      return { data: null, error: json?.detail ?? 'AI service error.' };
    }

    return { data: json as T, error: null };
  } catch {
    return { data: null, error: 'Could not reach AI service. Is it running?' };
  }
}

// ── Revenue ───────────────────────────────────────────────────
// `period` format from backend: "2026-04 W1" | "2026-04 W2"
export interface RevenueHistorical { period: string; revenue: number; }
export interface RevenueForecast   { period: string; revenue: number; pct_change: number; }
export interface RevenueForecastResult {
  historical: RevenueHistorical[];
  forecast:   RevenueForecast[];   // always 2 items — current bi-week + next bi-week
}

export const trainRevenue    = (tenant_id: number) =>
  aiPost<{ message: string }>('/revenue/train', { tenant_id });

export const forecastRevenue = (tenant_id: number) =>
  aiPost<RevenueForecastResult>('/revenue/forecast', { tenant_id });

// ── Demand ────────────────────────────────────────────────────
export interface DemandHistorical { month: string; qty: number; }
export interface DemandItem {
  name:                  string;
  historical:            DemandHistorical[];
  next_month_label:      string;
  next_month_prediction: number;
}
export interface DemandForecastResult { items: DemandItem[]; }

export const trainDemand    = (tenant_id: number) =>
  aiPost<{ message: string }>('/demand/train', { tenant_id });

export const forecastDemand = (tenant_id: number) =>
  aiPost<DemandForecastResult>('/demand/forecast', { tenant_id });

// ── Affinity ─────────────────────────────────────────────────
export interface AffinityCompanion { name: string; count: number; }
export interface AffinityAnchor    { anchor: string; companions: AffinityCompanion[]; total: number; }
export interface AffinityResult    { anchors: AffinityAnchor[]; }

export const getAffinity = (tenant_id: number) =>
  aiPost<AffinityResult>('/affinity', { tenant_id });

// ── Predictive Affinity (Laravel-based) ─────────────────────
export interface AffinityPrediction {
  id: number;
  name: string;
  rate: number;
  count: number;
}
export interface PredictiveAffinityResult {
  anchor: { id: number; name: string };
  predictions: AffinityPrediction[];
  period_days: number;
  total_anchor_orders: number;
}

export const predictItemAffinity = (itemId: number) =>
  apiRequest<PredictiveAffinityResult>(`/admin/ai/affinity/${itemId}`);

export const searchMenuItems = (query: string) =>
  apiRequest<{ id: number; name: string }[]>(`/admin/ai/items/search?q=${encodeURIComponent(query)}`);

// ── Stock Recommendation ──────────────────────────────────────
export interface StockItem   { name: string; predicted_qty: number; }
export interface StockSlot   { label: string; items: StockItem[]; }
export interface StockResult { slots: StockSlot[]; }

export const trainStock     = (tenant_id: number) =>
  aiPost<{ message: string }>('/stock/train', { tenant_id });

export const recommendStock = (tenant_id: number) =>
  aiPost<StockResult>('/stock/recommend', { tenant_id });

// ── Description Autofill ──────────────────────────────────────
export const generateDescription = (category_name: string, item_name: string) =>
  aiPost<{ description: string }>('/description/generate', { category_name, item_name });

// ── Message Autofill ──────────────────────────────────────────
export interface MessageAutofill { title: string; tag: string; priority: string; }

export const autofillMessage = (content: string) =>
  aiPost<MessageAutofill>('/messaging/autofill', { content });

export interface InsightResult {
  insight: string;
  context: {
    day: string;
    time_slot: string;
    today_orders: number;
    today_revenue: number;
    orders_vs_yesterday: number | null;
    revenue_vs_yesterday: number | null;
  };
}

export const getInsight = (tenant_id: number) =>
  aiPost<InsightResult>('/insight', { tenant_id });
'use client';

import { useEffect, useState } from 'react';
import { getUser } from '@/lib/auth';
import {
  trainRevenue, forecastRevenue, RevenueForecastResult,
  trainDemand, forecastDemand, DemandForecastResult,
  getAffinity, AffinityResult,
  trainStock, recommendStock, StockResult,
} from '@/lib/services/ai';

import RevenueChart           from './RevenueChart';
import DemandChart            from './DemandChart';
import AffinityPanel          from './AffinityPanel';
import StockRecommendationPanel from './StockRecommendationPanel';

type SectionState<T> = { status: 'idle' | 'training' | 'loading' | 'ready' | 'error'; data: T | null; error: string | null };

function initState<T>(): SectionState<T> {
  return { status: 'idle', data: null, error: null };
}

export default function AnalyticsDashboard() {
  const [tenantId, setTenantId] = useState<number>(0);
  const [revenue,  setRevenue]  = useState<SectionState<RevenueForecastResult>>(initState());
  const [demand,   setDemand]   = useState<SectionState<DemandForecastResult>>(initState());
  const [affinity, setAffinity] = useState<SectionState<AffinityResult>>(initState());
  const [stock,    setStock]    = useState<SectionState<StockResult>>(initState());

  // ── Get tenantId from user on mount (client-side only) ────
  useEffect(() => {
    const user = getUser();
    setTenantId(user?.tenant_id ?? 0);
  }, []);

  // ── Auto-load all on mount ────────────────────────────────
  useEffect(() => {
    if (!tenantId) return;
    loadRevenue();
    loadDemand();
    loadAffinity();
    loadStock();
  }, [tenantId]);

  // ── Revenue ───────────────────────────────────────────────
  async function loadRevenue() {
    setRevenue(s => ({ ...s, status: 'loading', error: null }));
    const { data, error } = await forecastRevenue(tenantId);
    if (error) { setRevenue({ status: 'error', data: null, error }); return; }
    setRevenue({ status: 'ready', data, error: null });
  }

  async function handleTrainRevenue() {
    setRevenue(s => ({ ...s, status: 'training', error: null }));
    const { error } = await trainRevenue(tenantId);
    if (error) { setRevenue({ status: 'error', data: null, error }); return; }
    await loadRevenue();
  }

  // ── Demand ────────────────────────────────────────────────
  async function loadDemand() {
    setDemand(s => ({ ...s, status: 'loading', error: null }));
    const { data, error } = await forecastDemand(tenantId);
    if (error) { setDemand({ status: 'error', data: null, error }); return; }
    setDemand({ status: 'ready', data, error: null });
  }

  async function handleTrainDemand() {
    setDemand(s => ({ ...s, status: 'training', error: null }));
    const { error } = await trainDemand(tenantId);
    if (error) { setDemand({ status: 'error', data: null, error }); return; }
    await loadDemand();
  }

  // ── Affinity (no training needed) ────────────────────────
  async function loadAffinity() {
    setAffinity(s => ({ ...s, status: 'loading', error: null }));
    const { data, error } = await getAffinity(tenantId);
    if (error) { setAffinity({ status: 'error', data: null, error }); return; }
    setAffinity({ status: 'ready', data, error: null });
  }

  // ── Stock ─────────────────────────────────────────────────
  async function loadStock() {
    setStock(s => ({ ...s, status: 'loading', error: null }));
    const { data, error } = await recommendStock(tenantId);
    if (error) { setStock({ status: 'error', data: null, error }); return; }
    setStock({ status: 'ready', data, error: null });
  }

  async function handleTrainStock() {
    setStock(s => ({ ...s, status: 'training', error: null }));
    const { error } = await trainStock(tenantId);
    if (error) { setStock({ status: 'error', data: null, error }); return; }
    await loadStock();
  }

  function TrainBtn({ label, onClick, loading }: { label: string; onClick: () => void; loading: boolean }) {
    return (
      <button className="ai-train-btn" onClick={onClick} disabled={loading}>
        {loading ? '⏳ Training…' : `⚡ ${label}`}
      </button>
    );
  }

  return (
    <div className="ai-page">

      <div className="ai-dashboard-top">
        {/* ── Admin1: Revenue Forecast ─────────────────────── */}
        <section className="ai-section">
          <div className="ai-section-head">
            <span className="ai-section-title">📈 Revenue Forecast</span>
            <TrainBtn
              label="Train Revenue Model"
              onClick={handleTrainRevenue}
              loading={revenue.status === 'training'}
            />
          </div>
          <div className="ai-section-body">
            {revenue.status === 'loading' || revenue.status === 'training'
              ? <div className="ai-state">Loading…</div>
              : revenue.status === 'error'
              ? <div className="ai-error">{revenue.error}</div>
              : revenue.data
              ? <RevenueChart data={revenue.data} />
              : <div className="ai-state">Train the model to see revenue forecast.</div>
            }
          </div>
        </section>

        {/* ── Admin2: Item Demand ──────────────────────────── */}
        <section className="ai-section">
          <div className="ai-section-head">
            <span className="ai-section-title">🍽 Item Demand Forecast</span>
            <TrainBtn
              label="Train Demand Model"
              onClick={handleTrainDemand}
              loading={demand.status === 'training'}
            />
          </div>
          <div className="ai-section-body">
            {demand.status === 'loading' || demand.status === 'training'
              ? <div className="ai-state">Loading…</div>
              : demand.status === 'error'
              ? <div className="ai-error">{demand.error}</div>
              : demand.data
              ? <DemandChart data={demand.data} />
              : <div className="ai-state">Train the model to see item demand forecast.</div>
            }
          </div>
        </section>
      </div>

      {/* ── Admin3: Affinity Analysis ────────────────────── */}
      <section className="ai-section">
        <div className="ai-section-head">
          <span className="ai-section-title">🔗 Item Affinity Analysis</span>
          <button className="ai-train-btn" onClick={loadAffinity} disabled={affinity.status === 'loading'}>
            {affinity.status === 'loading' ? '⏳ Loading…' : '🔄 Refresh'}
          </button>
        </div>
        <div className="ai-section-body">
          {affinity.status === 'loading'
            ? <div className="ai-state">Analysing order patterns…</div>
            : affinity.status === 'error'
            ? <div className="ai-error">{affinity.error}</div>
            : affinity.data
            ? <AffinityPanel data={affinity.data} />
            : <div className="ai-state">No affinity data available.</div>
          }
        </div>
      </section>

      {/* ── A&S1: Stock Recommendation ───────────────────── */}
      <section className="ai-section">
        <div className="ai-section-head">
          <span className="ai-section-title">📦 Today's Stock Recommendation</span>
          <TrainBtn
            label="Train Stock Model"
            onClick={handleTrainStock}
            loading={stock.status === 'training'}
          />
        </div>
        <div className="ai-section-body">
          {stock.status === 'loading' || stock.status === 'training'
            ? <div className="ai-state">Loading…</div>
            : stock.status === 'error'
            ? <div className="ai-error">{stock.error}</div>
            : stock.data
            ? <StockRecommendationPanel data={stock.data} />
            : <div className="ai-state">Train the model to see stock recommendations.</div>
          }
        </div>
      </section>

    </div>
  );
}
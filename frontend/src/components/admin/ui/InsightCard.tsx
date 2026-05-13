'use client';

import { useState } from 'react';
import { getUser } from '@/lib/auth';
import { getInsight, InsightResult } from '@/lib/services/ai';

export default function InsightCard() {
  const user     = getUser();
  const tenantId = user?.tenant_id ?? 0;

  const [insight,  setInsight]  = useState<InsightResult | null>(null);
  const [loading,  setLoading]  = useState(false);
  const [error,    setError]    = useState<string | null>(null);
  const [loaded,   setLoaded]   = useState(false);

  async function load() {
    setLoading(true);
    setError(null);
    const { data, error } = await getInsight(tenantId);
    if (error) { setError(error); }
    else        { setInsight(data); setLoaded(true); }
    setLoading(false);
  }

  const pctLabel = (val: number | null | undefined) => {
    if (val == null) return null;
    const sign = val >= 0 ? '▲' : '▼';
    const color = val >= 0 ? '#16a34a' : '#dc2626';
    return <span style={{ color, fontWeight: 700, fontSize: '0.8rem' }}>{sign} {Math.abs(val)}% vs yesterday</span>;
  };

  return (
    <div style={{
      background: 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)',
      borderRadius: '1rem',
      padding: '1.25rem 1.5rem',
      color: '#fff',
      display: 'flex',
      flexDirection: 'column',
      gap: insight ? '1rem' : '0.5rem',
    }}>
      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '0.6rem' }}>
          <span style={{ fontSize: '1.2rem' }}>🤖</span>
          <span style={{ fontWeight: 700, fontSize: '0.95rem', color: '#f8fafc' }}>
            AI Operations Insight
          </span>
          {insight && (
            <span style={{ fontSize: '0.72rem', color: '#64748b', marginLeft: '0.25rem' }}>
              {insight.context.day} · {insight.context.time_slot}
            </span>
          )}
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
          {!loaded && !loading && !error && (
            <span style={{ color: '#94a3b8', fontSize: '0.85rem', fontWeight: 500 }}>
              Click generate for AI-powered operations summary.
            </span>
          )}
          <button
            onClick={load}
            disabled={loading}
            style={{
              display: 'flex', alignItems: 'center', gap: '0.4rem',
              padding: '0.4rem 1rem',
              background: loading ? '#1e293b' : '#f97316',
              color: '#fff', border: 'none', borderRadius: '0.6rem',
              fontSize: '0.8rem', fontWeight: 600, cursor: loading ? 'not-allowed' : 'pointer',
              transition: 'all 0.2s ease',
              boxShadow: loading ? 'none' : '0 4px 12px rgba(249, 115, 22, 0.2)',
            }}
          >
            {loading ? '⏳ Analysing…' : loaded ? '🔄 Refresh' : '✨ Generate Insight'}
          </button>
        </div>
      </div>

      {/* Quick stats row — only after load */}
      {insight && (
        <div style={{ display: 'flex', gap: '2rem', flexWrap: 'wrap' }}>
          <div>
            <div style={{ fontSize: '0.7rem', color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
              Orders Today
            </div>
            <div style={{ fontSize: '1.3rem', fontWeight: 800 }}>
              {insight.context.today_orders}
            </div>
            {pctLabel(insight.context.orders_vs_yesterday)}
          </div>
          <div>
            <div style={{ fontSize: '0.7rem', color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
              Revenue Today
            </div>
            <div style={{ fontSize: '1.3rem', fontWeight: 800 }}>
              ${insight.context.today_revenue.toLocaleString()}
            </div>
            {pctLabel(insight.context.revenue_vs_yesterday)}
          </div>
        </div>
      )}

      {/* Insight text */}
      {error && (
        <div style={{ background: '#450a0a', borderRadius: '0.5rem', padding: '0.75rem 1rem', fontSize: '0.85rem', color: '#fca5a5' }}>
          {error}
        </div>
      )}


      {insight && (
        <div style={{
          background: 'rgba(255,255,255,0.05)',
          borderRadius: '0.75rem',
          padding: '1rem 1.25rem',
          fontSize: '0.9rem',
          lineHeight: '1.7',
          color: '#e2e8f0',
          borderLeft: '3px solid #f97316',
        }}>
          {insight.insight}
        </div>
      )}
    </div>
  );
}
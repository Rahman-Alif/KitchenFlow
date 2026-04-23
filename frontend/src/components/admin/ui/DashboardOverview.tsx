'use client';

import { useEffect, useMemo, useRef, useState } from 'react';
import { DashboardData, getDashboardData } from '@/lib/services/dashboard';
import { LowStockItem, getLowStockItems } from '@/lib/services/lowStock';

const POLL_INTERVAL_DASHBOARD = 30_000;
const POLL_INTERVAL_LOWSTOCK  = 60_000;

// ── Helpers ───────────────────────────────────────────────────

function getTodayString(): string {
  const today = new Date();
  const year  = today.getFullYear();
  const month = `${today.getMonth() + 1}`.padStart(2, '0');
  const day   = `${today.getDate()}`.padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('en-US', {
    style:                 'currency',
    currency:              'USD',
    maximumFractionDigits: 2,
  }).format(value);
}

function formatTime(date: Date): string {
  return date.toLocaleTimeString('en-US', {
    hour:   '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
}

// ── Animated number ───────────────────────────────────────────

function AnimatedValue({ value, format }: { value: number; format?: 'currency' }) {
  const [flash,   setFlash]   = useState(false);
  const [display, setDisplay] = useState(value);
  const prevRef = useRef(value);

  useEffect(() => {
    if (value !== prevRef.current) {
      setFlash(true);
      setDisplay(value);
      prevRef.current = value;
      const t = setTimeout(() => setFlash(false), 600);
      return () => clearTimeout(t);
    }
  }, [value]);

  return (
    <h3 className={`adm-dash-card-value${flash ? ' adm-dash-card-value--flash' : ''}`}>
      {format === 'currency' ? formatCurrency(display) : display}
    </h3>
  );
}

// ── Donut chart ───────────────────────────────────────────────

const STATUS_COLORS: Record<string, string> = {
  pending:   '#f59e0b',
  preparing: '#3b82f6',
  ready:     '#8b5cf6',
  served:    '#22c55e',
  canceled:  '#ef4444',
};

const FALLBACK_COLOR = '#a1a1aa';

interface DonutChartProps {
  segments: { status: string; count: number }[];
  total:    number;
}

function DonutChart({ segments, total }: DonutChartProps) {
  const SIZE   = 140;
  const STROKE = 22;
  const R      = (SIZE - STROKE) / 2;
  const CIRC   = 2 * Math.PI * R;
  const cx     = SIZE / 2;
  const cy     = SIZE / 2;

  let offset = 0;

  const arcs = segments.map((seg) => {
    const pct  = total > 0 ? seg.count / total : 0;
    const dash = pct * CIRC;
    const gap  = CIRC - dash;
    const arc  = { seg, dash, gap, offset, color: STATUS_COLORS[seg.status] ?? FALLBACK_COLOR };
    offset += dash;
    return arc;
  });

  return (
    <div className="adm-donut-wrapper">
      <svg width={SIZE} height={SIZE} viewBox={`0 0 ${SIZE} ${SIZE}`}>
        <circle cx={cx} cy={cy} r={R} fill="none" stroke="var(--adm-border)" strokeWidth={STROKE} />
        {arcs.map((arc) => (
          <circle
            key={arc.seg.status}
            cx={cx} cy={cy} r={R}
            fill="none"
            stroke={arc.color}
            strokeWidth={STROKE}
            strokeDasharray={`${arc.dash} ${arc.gap}`}
            strokeDashoffset={-arc.offset}
            className="adm-donut-arc"
            style={{ transform: 'rotate(-90deg)', transformOrigin: 'center' }}
          />
        ))}
        <text x={cx} y={cy - 6} textAnchor="middle" className="adm-donut-total-num">{total}</text>
        <text x={cx} y={cy + 10} textAnchor="middle" className="adm-donut-total-label">orders</text>
      </svg>

      <ul className="adm-donut-legend">
        {arcs.map((arc) => (
          <li key={arc.seg.status} className="adm-donut-legend-item">
            <span className="adm-donut-legend-dot" style={{ background: arc.color }} />
            <span className="adm-donut-legend-label">{arc.seg.status}</span>
            <strong className="adm-donut-legend-count">{arc.seg.count}</strong>
          </li>
        ))}
      </ul>
    </div>
  );
}

// ── Main component ────────────────────────────────────────────

export default function DashboardOverview() {
  const today = getTodayString();

  const [date,        setDate]        = useState(today);
  const [data,        setData]        = useState<DashboardData | null>(null);
  const [error,       setError]       = useState<string | null>(null);
  const [loading,     setLoading]     = useState(true);
  const [lastUpdated, setLastUpdated] = useState<Date | null>(null);

  const [lowStock,      setLowStock]      = useState<LowStockItem[] | null>(null);
  const [lowStockError, setLowStockError] = useState<string | null>(null);
  const [lowStockLoad,  setLowStockLoad]  = useState(true);

  const isToday = date === today;

  // ── Dashboard fetch ────────────────────────────────────────

  async function loadDashboard(selectedDate: string, silent = false) {
    if (!silent) setLoading(true);
    setError(null);
    const response = await getDashboardData(selectedDate);
    if (response.error || !response.data) {
      setData(null);
      setError(response.error ?? 'Could not load dashboard data.');
    } else {
      setData(response.data);
      setLastUpdated(new Date());
    }
    if (!silent) setLoading(false);
  }

  useEffect(() => { loadDashboard(date); }, [date]);

  // Poll only when viewing today
  useEffect(() => {
    if (!isToday) return;
    const id = setInterval(() => loadDashboard(date, true), POLL_INTERVAL_DASHBOARD);
    return () => clearInterval(id);
  }, [date, isToday]);

  // ── Low stock fetch ────────────────────────────────────────

  async function loadLowStock(silent = false) {
    if (!silent) setLowStockLoad(true);
    const response = await getLowStockItems();
    if (response.error || !response.data) {
      setLowStockError(response.error ?? 'Could not load low stock data.');
    } else {
      setLowStock(response.data);
    }
    if (!silent) setLowStockLoad(false);
  }

  useEffect(() => { loadLowStock(); }, []);

  useEffect(() => {
    const id = setInterval(() => loadLowStock(true), POLL_INTERVAL_LOWSTOCK);
    return () => clearInterval(id);
  }, []);

  // ── Derived ────────────────────────────────────────────────

  const totalStatusCount = useMemo(() => {
    if (!data) return 0;
    return data.ordersByStatus.reduce((sum, row) => sum + row.count, 0);
  }, [data]);

  const sortedTopItems = useMemo(() => {
    if (!data) return [];
    return [...data.topItems].sort((a, b) => b.quantity - a.quantity);
  }, [data]);

  // ── Render ─────────────────────────────────────────────────

  return (
    <section className="adm-dash">

      {/* Toolbar */}
      <div className="adm-dash-toolbar">
        <label htmlFor="dashboard-date" className="adm-dash-label">Date</label>
        <input
          id="dashboard-date"
          type="date"
          className="adm-dash-date-input"
          value={date}
          onChange={(e) => setDate(e.target.value)}
          max={today}
        />
        {isToday && lastUpdated && (
          <span className="adm-dash-live-badge">
            <span className="adm-dash-live-dot" />
            Live · updated {formatTime(lastUpdated)}
          </span>
        )}
        {!isToday && (
          <span className="adm-dash-snapshot-badge">Snapshot — no live updates</span>
        )}
      </div>

      {error && <div className="adm-dash-error">{error}</div>}

      {/* Stat cards */}
      <div className="adm-dash-grid">
        <article className="adm-dash-card">
          <p className="adm-dash-card-label">Total Orders</p>
          {loading
            ? <h3 className="adm-dash-card-value">...</h3>
            : <AnimatedValue value={data?.totalOrders ?? 0} />
          }
        </article>

        <article className="adm-dash-card">
          <p className="adm-dash-card-label">Total Revenue</p>
          {loading
            ? <h3 className="adm-dash-card-value">...</h3>
            : <AnimatedValue value={data?.totalRevenue ?? 0} format="currency" />
          }
        </article>
      </div>

      {/* Donut + Top 5 */}
      <div className="adm-dash-grid adm-dash-grid--split">
        <article className="adm-dash-card">
          <div className="adm-dash-card-head">
            <h3>Orders by Status</h3>
            {!loading && <span>{totalStatusCount} total</span>}
          </div>
          {loading ? (
            <p className="adm-dash-muted">Loading status data...</p>
          ) : data && data.ordersByStatus.length > 0 ? (
            <DonutChart segments={data.ordersByStatus} total={totalStatusCount} />
          ) : (
            <p className="adm-dash-muted">No status data for the selected date.</p>
          )}
        </article>

        <article className="adm-dash-card">
          <div className="adm-dash-card-head">
            <h3>Top 5 Items</h3>
          </div>
          {loading ? (
            <p className="adm-dash-muted">Loading item data...</p>
          ) : sortedTopItems.length > 0 ? (
            <ol className="adm-dash-list">
              {sortedTopItems.map((item) => (
                <li key={item.name} className="adm-dash-list-item adm-dash-list-item--animated">
                  <span>{item.name}</span>
                  <strong>{item.quantity}</strong>
                </li>
              ))}
            </ol>
          ) : (
            <p className="adm-dash-muted">No item data for the selected date.</p>
          )}
        </article>
      </div>

      {/* Low stock */}
      <article className="adm-dash-card">
        <div className="adm-dash-card-head">
          <h3>Low Stock Alerts</h3>
          {!lowStockLoad && lowStock && (
            <span>{lowStock.length} item{lowStock.length !== 1 ? 's' : ''}</span>
          )}
        </div>
        {lowStockLoad ? (
          <p className="adm-dash-muted">Loading stock data...</p>
        ) : lowStockError ? (
          <p className="adm-dash-muted">{lowStockError}</p>
        ) : lowStock && lowStock.length > 0 ? (
          <ul className="adm-dash-list">
            {lowStock.map((item) => (
              <li key={item.id} className="adm-dash-list-item adm-dash-list-item--animated">
                <div className="adm-dash-stock-info">
                  <span className="adm-dash-stock-name">{item.name}</span>
                  <span className="adm-dash-stock-category">{item.category}</span>
                </div>
                <span className="adm-dash-stock-badge">{item.stock_quantity} left</span>
              </li>
            ))}
          </ul>
        ) : (
          <p className="adm-dash-muted">All items are sufficiently stocked.</p>
        )}
      </article>

    </section>
  );
}
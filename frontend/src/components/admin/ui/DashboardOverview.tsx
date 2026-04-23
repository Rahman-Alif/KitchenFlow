'use client';

import { useEffect, useMemo, useState } from 'react';
import { DashboardData, getDashboardData } from '@/lib/services/dashboard';

function getTodayString(): string {
  const today = new Date();
  const year = today.getFullYear();
  const month = `${today.getMonth() + 1}`.padStart(2, '0');
  const day = `${today.getDate()}`.padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 2,
  }).format(value);
}

export default function DashboardOverview() {
  const [date, setDate] = useState(getTodayString);
  const [data, setData] = useState<DashboardData | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  async function loadDashboard(selectedDate: string) {
    setLoading(true);
    setError(null);

    const response = await getDashboardData(selectedDate);
    if (response.error || !response.data) {
      setData(null);
      setError(response.error ?? 'Could not load dashboard data.');
      setLoading(false);
      return;
    }

    setData(response.data);
    setLoading(false);
  }

  useEffect(() => {
    loadDashboard(date);
  }, [date]);

  const totalStatusCount = useMemo(() => {
    if (!data) return 0;
    return data.ordersByStatus.reduce((sum, row) => sum + row.count, 0);
  }, [data]);

  return (
    <section className="adm-dash">
      <div className="adm-dash-toolbar">
        <label htmlFor="dashboard-date" className="adm-dash-label">
          Date
        </label>
        <input
          id="dashboard-date"
          type="date"
          className="adm-dash-date-input"
          value={date}
          onChange={(event) => setDate(event.target.value)}
          max={getTodayString()}
        />
      </div>

      {error && <div className="adm-dash-error">{error}</div>}

      <div className="adm-dash-grid">
        <article className="adm-dash-card">
          <p className="adm-dash-card-label">Total Orders</p>
          <h3 className="adm-dash-card-value">{loading ? '...' : (data?.totalOrders ?? 0)}</h3>
        </article>

        <article className="adm-dash-card">
          <p className="adm-dash-card-label">Total Revenue</p>
          <h3 className="adm-dash-card-value">
            {loading ? '...' : formatCurrency(data?.totalRevenue ?? 0)}
          </h3>
        </article>
      </div>

      <div className="adm-dash-grid adm-dash-grid--split">
        <article className="adm-dash-card">
          <div className="adm-dash-card-head">
            <h3>Orders by Status</h3>
            {!loading && <span>{totalStatusCount} total</span>}
          </div>

          {loading ? (
            <p className="adm-dash-muted">Loading status data...</p>
          ) : data && data.ordersByStatus.length > 0 ? (
            <ul className="adm-dash-list">
              {data.ordersByStatus.map((row) => (
                <li key={row.status} className="adm-dash-list-item">
                  <span>{row.status}</span>
                  <strong>{row.count}</strong>
                </li>
              ))}
            </ul>
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
          ) : data && data.topItems.length > 0 ? (
            <ol className="adm-dash-list">
              {data.topItems.map((item) => (
                <li key={item.name} className="adm-dash-list-item">
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
    </section>
  );
}

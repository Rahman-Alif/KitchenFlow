'use client';

// frontend/src/components/admin/ui/OrderHistory.tsx

import { useState } from 'react';
import {
  AdminOrder,
  OrderFilters,
  OrdersMeta,
  OrderStatus,
  getOrders,
} from '@/lib/services/orders';
import { Hash, User as UserIcon, Activity, DollarSign, Calendar } from 'lucide-react';

// ── Helpers ───────────────────────────────────────────────────

function badgeClass(status: OrderStatus): string {
  return `adm-oh-badge adm-oh-badge--${status}`;
}

function formatDateTime(dt: string): string {
  return new Date(dt).toLocaleString('en-GB', {
    day:    '2-digit',
    month:  'short',
    year:   'numeric',
    hour:   '2-digit',
    minute: '2-digit',
  });
}

// ── Expanded detail ───────────────────────────────────────────

function OrderDetail({ order }: { order: AdminOrder }) {
  return (
    <tr className="adm-oh-detail">
      <td colSpan={5}>
        <div className="adm-oh-detail-inner">

          {/* Order items */}
          <div className="adm-oh-detail-section">
            <div className="adm-oh-detail-title">Items</div>
            <table className="adm-oh-items-table">
              <thead>
                <tr>
                  <th>Item</th>
                  <th>Qty</th>
                  <th>Unit Price</th>
                  <th>Subtotal</th>
                </tr>
              </thead>
              <tbody>
                {order.items.map((item) => (
                  <tr key={item.id}>
                    <td>{item.name}</td>
                    <td>{item.quantity}</td>
                    <td>${item.unit_price}</td>
                    <td>${item.subtotal}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Transaction */}
          <div className="adm-oh-detail-section">
            <div className="adm-oh-detail-title">Transaction</div>
            {order.transaction ? (
              <>
                <div className="adm-oh-txn-row">
                  <span className="adm-oh-txn-label">Tendered</span>
                  <span className="adm-oh-txn-value">${order.transaction.tendered_amount}</span>
                </div>
                <div className="adm-oh-txn-row">
                  <span className="adm-oh-txn-label">Change returned</span>
                  <span className="adm-oh-txn-value">${order.transaction.change_returned}</span>
                </div>
                <div className="adm-oh-txn-row">
                  <span className="adm-oh-txn-label">Recorded by</span>
                  <span className="adm-oh-txn-value">{order.transaction.recorded_by}</span>
                </div>
                <div className="adm-oh-txn-row">
                  <span className="adm-oh-txn-label">Recorded at</span>
                  <span className="adm-oh-txn-value">{formatDateTime(order.transaction.recorded_at)}</span>
                </div>
              </>
            ) : (
              <p style={{ fontSize: '12px', color: 'var(--adm-muted)', margin: 0 }}>
                No transaction recorded.
              </p>
            )}

            {/* Notes */}
            {order.notes && (
              <>
                <div className="adm-oh-detail-title" style={{ marginTop: '16px' }}>Notes</div>
                <p className="adm-oh-notes">{order.notes}</p>
              </>
            )}
          </div>

        </div>
      </td>
    </tr>
  );
}

// ── Main component ────────────────────────────────────────────

export default function OrderHistory() {
  // Filters (form state)
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo,   setDateTo]   = useState('');
  const [status,   setStatus]   = useState('');
  const [search,   setSearch]   = useState('');
  const [item,     setItem]     = useState('');

  // Results
  const [orders,    setOrders]    = useState<AdminOrder[]>([]);
  const [meta,      setMeta]      = useState<OrdersMeta | null>(null);
  const [loading,   setLoading]   = useState(false);
  const [error,     setError]     = useState<string | null>(null);
  const [searched,  setSearched]  = useState(false);

  // Expanded row
  const [expandedId, setExpandedId] = useState<number | null>(null);

  async function fetchOrders(filters: OrderFilters) {
    setLoading(true);
    setError(null);
    setExpandedId(null);

    const response = await getOrders(filters);

    if (response.error || !response.data) {
      setError(response.error ?? 'Failed to load orders.');
      setOrders([]);
      setMeta(null);
    } else {
      setOrders(response.data);
      setMeta(response.meta);
    }

    setLoading(false);
    setSearched(true);
  }

  function handleSearch() {
    fetchOrders({ date_from: dateFrom, date_to: dateTo, status, search, item, page: 1 });
  }

  function handlePage(page: number) {
    fetchOrders({ date_from: dateFrom, date_to: dateTo, status, search, item, page });
  }

  function toggleExpand(id: number) {
    setExpandedId((prev) => (prev === id ? null : id));
  }

  // Build pagination page numbers
  const pageNumbers = meta
    ? Array.from({ length: meta.last_page }, (_, i) => i + 1)
    : [];

  return (
    <section className="adm-oh">

      {/* Filters */}
      <div className="adm-oh-filters">
        <input
          type="date"
          className="adm-oh-input"
          value={dateFrom}
          onChange={(e) => setDateFrom(e.target.value)}
          placeholder="From"
        />
        <input
          type="date"
          className="adm-oh-input"
          value={dateTo}
          onChange={(e) => setDateTo(e.target.value)}
          placeholder="To"
        />
        <select
          className="adm-oh-select"
          value={status}
          onChange={(e) => setStatus(e.target.value)}
        >
          <option value="">All statuses</option>
          <option value="pending">Pending</option>
          <option value="preparing">Preparing</option>
          <option value="ready">Ready</option>
          <option value="served">Served</option>
          <option value="canceled">Canceled</option>
        </select>
        <input
          type="text"
          className="adm-oh-input"
          placeholder="Employee name"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />
        <input
          type="text"
          className="adm-oh-input"
          placeholder="Menu item name"
          value={item}
          onChange={(e) => setItem(e.target.value)}
        />
        <button
          type="button"
          className="adm-oh-search-btn"
          onClick={handleSearch}
          disabled={loading}
        >
          {loading ? 'Searching...' : 'Search'}
        </button>
      </div>

      {error && <p style={{ color: '#ef4444', fontSize: '13px', marginBottom: '12px' }}>{error}</p>}

      {/* Table */}
      {searched && (
        <>
          <div className="adm-oh-table-wrap">
            <table className="adm-oh-table">
              <thead>
                <tr>
                  <th><span className="adm-icon-wrapper"><Hash size={14} className="adm-icon" /> Order ID</span></th>
                  <th><span className="adm-icon-wrapper"><UserIcon size={14} className="adm-icon" /> Placed By</span></th>
                  <th><span className="adm-icon-wrapper"><Activity size={14} className="adm-icon" /> Status</span></th>
                  <th><span className="adm-icon-wrapper"><DollarSign size={14} className="adm-icon" /> Total</span></th>
                  <th><span className="adm-icon-wrapper"><Calendar size={14} className="adm-icon" /> Date</span></th>
                </tr>
              </thead>
              <tbody>
                {loading ? (
                  <tr className="adm-oh-state-row">
                    <td colSpan={5}>Loading orders...</td>
                  </tr>
                ) : orders.length === 0 ? (
                  <tr className="adm-oh-state-row">
                    <td colSpan={5}>No orders found for the selected filters.</td>
                  </tr>
                ) : (
                  orders.map((order) => (
                    <>
                      <tr
                        key={order.id}
                        className={`adm-oh-row adm-oh-row--clickable${expandedId === order.id ? ' adm-oh-row--expanded' : ''}`}
                        onClick={() => toggleExpand(order.id)}
                      >
                        <td>#{order.id}</td>
                        <td>{order.placed_by.name}</td>
                        <td>
                          <span className={badgeClass(order.status)}>{order.status}</span>
                        </td>
                        <td>${order.total_amount}</td>
                        <td>{formatDateTime(order.created_at)}</td>
                      </tr>
                      {expandedId === order.id && (
                        <OrderDetail key={`detail-${order.id}`} order={order} />
                      )}
                    </>
                  ))
                )}
              </tbody>
            </table>
          </div>

          {/* Pagination */}
          {meta && meta.last_page > 1 && (
            <div className="adm-oh-pagination">
              <button
                type="button"
                className="adm-oh-page-btn"
                onClick={() => handlePage(meta.current_page - 1)}
                disabled={meta.current_page === 1 || loading}
              >
                ←
              </button>

              {pageNumbers.map((page) => (
                <button
                  key={page}
                  type="button"
                  className={`adm-oh-page-btn${meta.current_page === page ? ' adm-oh-page-btn--active' : ''}`}
                  onClick={() => handlePage(page)}
                  disabled={loading}
                >
                  {page}
                </button>
              ))}

              <button
                type="button"
                className="adm-oh-page-btn"
                onClick={() => handlePage(meta.current_page + 1)}
                disabled={meta.current_page === meta.last_page || loading}
              >
                →
              </button>
            </div>
          )}

          {meta && (
            <p style={{ textAlign: 'center', fontSize: '12px', color: 'var(--adm-muted)', marginTop: '8px' }}>
              {meta.total} order{meta.total !== 1 ? 's' : ''} found
            </p>
          )}
        </>
      )}

      {!searched && !loading && (
        <p style={{ color: 'var(--adm-muted)', fontSize: '13px' }}>
          Use the filters above and click Search to view orders.
        </p>
      )}

    </section>
  );
}
'use client';

import Link from 'next/link';
import { useEffect, useMemo, useState } from 'react';
import AdminConfirmDialog from '@/components/admin/shared/AdminConfirmDialog';
import {
  AdminMenuItem,
  deleteMenuItem,
  getMenuItems,
  restockMenuItem,
  updateMenuItemAvailability,
} from '@/lib/services/menuItems';
import { Utensils, Folder, DollarSign, Package, Activity, Settings, AlertCircle } from 'lucide-react';

type SortKey = 'name' | 'category' | 'price' | 'stock' | 'status';
type SortDirection = 'asc' | 'desc';

const POLL_INTERVAL = 30_000;

export default function MenuItemsList() {
  const [items, setItems] = useState<AdminMenuItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [sortKey, setSortKey] = useState<SortKey>('name');
  const [sortDirection, setSortDirection] = useState<SortDirection>('asc');
  const [actionItemId, setActionItemId] = useState<number | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<AdminMenuItem | null>(null);
  const [restockTarget, setRestockTarget] = useState<AdminMenuItem | null>(null);
  const [restockQuantity, setRestockQuantity] = useState('10');
  const [availError, setAvailError] = useState<string | null>(null);

  async function loadMenuItems(silent = false) {
    if (!silent) setLoading(true);
    setError(null);
    const response = await getMenuItems();
    if (response.error || !response.data) {
      setItems([]);
      setError(response.error ?? 'Failed to load menu items.');
    } else {
      setItems(response.data);
    }
    if (!silent) setLoading(false);
  }

  useEffect(() => { loadMenuItems(); }, []);

  useEffect(() => {
    const id = setInterval(() => loadMenuItems(true), POLL_INTERVAL);
    return () => clearInterval(id);
  }, []);

  // ── Availability toggle ────────────────────────────────────

  async function handleToggleAvailability(item: AdminMenuItem) {
    if (actionItemId !== null) return;
    setAvailError(null);

    const enabling = !item.is_available;

    if (enabling && item.stock_quantity <= 0) {
      setAvailError(
        `"${item.name}" cannot be enabled — stock is depleted (0 units). Please restock first.`
      );
      return;
    }

    setActionItemId(item.id);

    setItems((prev) =>
      prev.map((i) => i.id === item.id ? { ...i, is_available: enabling } : i)
    );

    const { error: apiError } = await updateMenuItemAvailability(item.id, enabling);

    if (apiError) {
      setItems((prev) =>
        prev.map((i) => i.id === item.id ? { ...i, is_available: item.is_available } : i)
      );
      setAvailError(apiError);
    }

    setActionItemId(null);
  }

  // ── Sort ───────────────────────────────────────────────────

  const visibleItems = useMemo(() => {
    const normalized = search.trim().toLowerCase();
    const filtered = items.filter((item) => {
      if (!normalized) return true;
      return (
        item.name.toLowerCase().includes(normalized) ||
        item.category.name.toLowerCase().includes(normalized)
      );
    });

    return [...filtered].sort((a, b) => {
      const direction = sortDirection === 'asc' ? 1 : -1;
      if (sortKey === 'name') return a.name.localeCompare(b.name) * direction;
      if (sortKey === 'category') return a.category.name.localeCompare(b.category.name) * direction;
      if (sortKey === 'price') return (Number(a.price) - Number(b.price)) * direction;
      if (sortKey === 'stock') return (a.stock_quantity - b.stock_quantity) * direction;
      const statusA = a.is_available ? 'available' : 'unavailable';
      const statusB = b.is_available ? 'available' : 'unavailable';
      return statusA.localeCompare(statusB) * direction;
    });
  }, [items, search, sortDirection, sortKey]);

  function toggleSort(key: SortKey) {
    if (sortKey === key) {
      setSortDirection((prev) => (prev === 'asc' ? 'desc' : 'asc'));
      return;
    }
    setSortKey(key);
    setSortDirection('asc');
  }

  // ── Delete ─────────────────────────────────────────────────

  function handleDelete(item: AdminMenuItem) {
    if (actionItemId !== null) return;
    setDeleteTarget(item);
  }

  async function confirmDelete() {
    if (!deleteTarget) return;

    const targetId = deleteTarget.id;
    setActionItemId(targetId);
    setError(null);
    setDeleteTarget(null);

    // Optimistic update
    setItems((prev) => prev.filter((i) => i.id !== targetId));

    const response = await deleteMenuItem(targetId);
    
    if (response.error) {
      setError(response.error);
      // Rollback optimistic update on error
      await loadMenuItems();
    }
    
    setActionItemId(null);
  }

  // ── Restock ────────────────────────────────────────────────

  function handleRestock(item: AdminMenuItem) {
    if (actionItemId !== null) return;
    setRestockQuantity(item.requested_restock_quantity?.toString() || '10');
    setRestockTarget(item);
  }

  async function confirmRestock() {
    if (!restockTarget) return;
    const quantity = Number(restockQuantity);
    if (Number.isNaN(quantity) || quantity < 1) {
      setError('Please provide a valid quantity (minimum 1).');
      return;
    }
    setActionItemId(restockTarget.id);
    setError(null);
    const response = await restockMenuItem(restockTarget.id, quantity);
    if (response.error) {
      setError(response.error);
      setActionItemId(null);
      return;
    }
    setItems((prev) =>
      prev.map((i) =>
        i.id === restockTarget.id
          ? { ...i, stock_quantity: i.stock_quantity + quantity, is_available: true, needs_restock: false, requested_restock_quantity: null }
          : i
      )
    );
    setRestockTarget(null);
    setActionItemId(null);
  }

  // ── Stats ──────────────────────────────────────────────────
  const requestCount = useMemo(() => items.filter(i => i.needs_restock).length, [items]);

  // ── Render ─────────────────────────────────────────────────

  return (
    <section className="adm-menu">
      <div className="adm-menu-toolbar">
        <input
          type="search"
          className="adm-menu-search"
          placeholder="Search by item or category"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />
        <div className="flex items-center gap-3">
          <div className="adm-stat-badge">
            Total Items: <strong>{items.length}</strong>
          </div>
          <Link href="/menu/create" className="adm-menu-primary-btn">
            Create Menu Item
          </Link>
        </div>
      </div>

      {requestCount > 0 && (
        <div className="adm-menu-request-alert">
          <AlertCircle size={18} />
          <span>Staff Alert: <strong>{requestCount}</strong> {requestCount === 1 ? 'item needs' : 'items need'} restocking.</span>
        </div>
      )}

      {error && <p className="adm-menu-error">{error}</p>}
      {availError && (
        <p className="adm-menu-error" style={{ cursor: 'pointer' }} onClick={() => setAvailError(null)}>
          {availError} ✕
        </p>
      )}

      <div className="adm-menu-table-wrap">
        <table className="adm-menu-table">
          <thead>
            <tr>
              <th>
                <button type="button" className="adm-menu-sort-btn" onClick={() => toggleSort('name')}>
                  <span className="adm-icon-wrapper"><Utensils size={14} className="adm-icon" /> Name</span> {sortKey === 'name' ? (sortDirection === 'asc' ? '↑' : '↓') : ''}
                </button>
              </th>
              <th>
                <button type="button" className="adm-menu-sort-btn" onClick={() => toggleSort('category')}>
                  <span className="adm-icon-wrapper"><Folder size={14} className="adm-icon" /> Category</span> {sortKey === 'category' ? (sortDirection === 'asc' ? '↑' : '↓') : ''}
                </button>
              </th>
              <th>
                <button type="button" className="adm-menu-sort-btn" onClick={() => toggleSort('price')}>
                  <span className="adm-icon-wrapper"><DollarSign size={14} className="adm-icon" /> Price</span> {sortKey === 'price' ? (sortDirection === 'asc' ? '↑' : '↓') : ''}
                </button>
              </th>
              <th>
                <button type="button" className="adm-menu-sort-btn" onClick={() => toggleSort('stock')}>
                  <span className="adm-icon-wrapper"><Package size={14} className="adm-icon" /> Stock</span> {sortKey === 'stock' ? (sortDirection === 'asc' ? '↑' : '↓') : ''}
                </button>
              </th>
              <th>
                <button type="button" className="adm-menu-sort-btn" onClick={() => toggleSort('status')}>
                  <span className="adm-icon-wrapper"><Activity size={14} className="adm-icon" /> Status</span> {sortKey === 'status' ? (sortDirection === 'asc' ? '↑' : '↓') : ''}
                </button>
              </th>
              <th><span className="adm-icon-wrapper" style={{ paddingLeft: '55px' }}><Settings size={14} className="adm-icon" /> Actions</span></th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr><td colSpan={6}>Loading menu items...</td></tr>
            ) : visibleItems.length === 0 ? (
              <tr><td colSpan={6}>No menu items found.</td></tr>
            ) : (
              visibleItems.map((item) => {
                const lowStock = item.stock_quantity <= item.low_stock_threshold;
                const isToggling = actionItemId === item.id;
                const canEnable = item.stock_quantity > item.low_stock_threshold;

                return (
                  <tr key={item.id}>
                    <td>{item.name}</td>
                    <td>{item.category.name}</td>
                    <td>৳{Number(item.price).toFixed(2)}</td>
                    <td>
                      <div className="flex flex-col gap-1">
                        <div className="flex items-center gap-2">
                          <span className={lowStock ? 'text-orange-500 font-bold' : ''}>
                            {item.stock_quantity}
                          </span>
                          {lowStock && <span className="adm-menu-low-stock">Low</span>}
                        </div>
                        {item.needs_restock && (
                          <span className="adm-menu-request-badge">
                             Staff needs {item.requested_restock_quantity || 'any'}
                          </span>
                        )}
                      </div>
                    </td>
                    <td>
                      <button
                        type="button"
                        className={`adm-menu-badge adm-menu-badge--toggle ${item.is_available ? 'adm-menu-badge--active' : ''} ${isToggling ? 'adm-menu-badge--loading' : ''} ${!item.is_available && !canEnable ? 'adm-menu-badge--disabled' : ''}`}
                        onClick={() => handleToggleAvailability(item)}
                        disabled={isToggling}
                        title={
                          !item.is_available && !canEnable
                            ? `Stock too low to enable (threshold: ${item.low_stock_threshold})`
                            : item.is_available ? 'Click to deactivate' : 'Click to activate'
                        }
                      >
                        {isToggling
                          ? '...'
                          : item.is_available ? 'Available' : 'Unavailable'
                        }
                      </button>
                    </td>
                    <td className="adm-menu-row-actions">
                      <Link href={`/menu/${item.id}/edit`} className="adm-menu-inline-btn">
                        Edit
                      </Link>
                      <button
                        type="button"
                        className="adm-menu-inline-btn"
                        onClick={() => handleRestock(item)}
                        disabled={isToggling}
                      >
                        {isToggling ? 'Saving...' : 'Restock'}
                      </button>
                      <button
                        type="button"
                        className="adm-menu-inline-btn adm-menu-inline-btn--danger"
                        onClick={() => handleDelete(item)}
                        disabled={isToggling}
                      >
                        Delete
                      </button>
                    </td>
                  </tr>
                );
              })
            )}
          </tbody>
        </table>
      </div>

      <AdminConfirmDialog
        open={deleteTarget !== null}
        title="Delete Menu Item"
        message={deleteTarget ? `Delete menu item "${deleteTarget.name}"? This action cannot be undone.` : ''}
        confirmText="Delete"
        danger
        onCancel={() => setDeleteTarget(null)}
        onConfirm={confirmDelete}
      />

      <AdminConfirmDialog
        open={restockTarget !== null}
        title="Restock Item"
        message={restockTarget ? `Enter restock quantity for "${restockTarget.name}".` : ''}
        confirmText="Apply Restock"
        inputLabel="Quantity"
        inputType="number"
        inputMin={1}
        inputValue={restockQuantity}
        onInputChange={setRestockQuantity}
        onCancel={() => setRestockTarget(null)}
        onConfirm={confirmRestock}
      />
    </section>
  );
}
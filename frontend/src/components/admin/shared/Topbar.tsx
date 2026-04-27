'use client';

import { useState, useRef, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { apiRequest } from '@/lib/api';
import { clearAuth, getUser } from '@/lib/auth';
import { CreditCard, LogOut, Menu } from 'lucide-react';

interface TopbarProps {
  title: string;
  onMenuClick?: () => void;
}

interface TenantData {
  name: string;
  subscription_active: boolean;
  subscription_ends_at: string;
}

interface TenantResponse {
  data: TenantData;
}

export default function Topbar({ title, onMenuClick }: TopbarProps) {
  const router = useRouter();

  const [logoutLoading, setLogoutLoading] = useState(false);
  const [dropdownOpen, setDropdownOpen] = useState(false);
  const [tenant, setTenant] = useState<TenantData | null>(null);
  const [tenantError, setTenantError] = useState<string | null>(null);
  const [tenantLoading, setTenantLoading] = useState(false);
  const [userName, setUserName] = useState<string>('Admin');
  const [userDropdownOpen, setUserDropdownOpen] = useState(false);

  const dropdownRef = useRef<HTMLDivElement>(null);
  const userDropdownRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const u = getUser();
    if (u) setUserName(u.name ?? u.email ?? 'Admin');
  }, []);
  // Close when clicking outside
  useEffect(() => {
    function handleClickOutside(e: MouseEvent) {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target as Node)) {
        setDropdownOpen(false);
      }
      if (userDropdownRef.current && !userDropdownRef.current.contains(e.target as Node)) {
        setUserDropdownOpen(false);
      }
    }
    if (dropdownOpen || userDropdownOpen) document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [dropdownOpen, userDropdownOpen]);

  async function handleSubscriptionClick() {
    if (dropdownOpen) {
      setDropdownOpen(false);
      return;
    }

    setDropdownOpen(true);

    // Only fetch once
    if (tenant) return;

    setTenantLoading(true);
    const { data, error } = await apiRequest<TenantResponse>('/admin/tenant');
    setTenantLoading(false);

    if (error || !data) {
      setTenantError(error ?? 'Failed to load.');
    } else {
      setTenant(data.data);
    }
  }

  async function handleLogout() {
    if (logoutLoading) return;
    setLogoutLoading(true);
    await apiRequest('/auth/logout', { method: 'POST' });
    clearAuth();
    router.push('/login');
  }

  return (
    <header className="adm-topbar">
      <div className="flex items-center gap-3">
        <button
          type="button"
          className="adm-mobile-menu-btn"
          onClick={onMenuClick}
          aria-label="Open menu"
        >
          <Menu size={20} />
        </button>
        <h1>{title}</h1>
      </div>

      <div className="adm-user-area">
        {/* Subscription button + dropdown */}
        <div className="adm-subscription-wrapper" ref={dropdownRef}>
          <button
            type="button"
            className={`adm-subscription-btn${dropdownOpen ? ' adm-subscription-btn--active' : ''}`}
            onClick={handleSubscriptionClick}
          >
            <span className="adm-icon-wrapper"><CreditCard size={14} className="adm-icon" /> Subscription</span>
          </button>

          {dropdownOpen && (
            <div className="adm-subscription-dropdown">
              <div className="adm-subscription-dropdown-title">Subscription Details</div>

              {tenantLoading && (
                <p className="adm-subscription-muted">Loading…</p>
              )}

              {tenantError && (
                <p className="adm-subscription-error">{tenantError}</p>
              )}

              {tenant && (
                <>
                  <div className="adm-subscription-row">
                    <span className="adm-subscription-label">Organisation</span>
                    <span className="adm-subscription-value">{tenant.name}</span>
                  </div>
                  <div className="adm-subscription-divider" />
                  <div className="adm-subscription-row">
                    <span className="adm-subscription-label">Status</span>
                    <span className={`adm-subscription-badge ${tenant.subscription_active ? 'adm-subscription-badge--active' : 'adm-subscription-badge--inactive'}`}>
                      {tenant.subscription_active ? 'Active' : 'Inactive'}
                    </span>
                  </div>
                  <div className="adm-subscription-divider" />
                  <div className="adm-subscription-row">
                    <span className="adm-subscription-label">Deadline</span>
                    <span className="adm-subscription-value">
                      {new Date(tenant.subscription_ends_at).toLocaleDateString('en-GB', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric',
                      })}
                    </span>
                  </div>
                </>
              )}
            </div>
          )}
        </div>

        {/* User chip + dropdown */}
        <div className="adm-user-dropdown-wrapper" ref={userDropdownRef}>
          <button
            type="button"
            className={`adm-user-chip ${userDropdownOpen ? 'adm-user-chip--active' : ''}`}
            onClick={() => setUserDropdownOpen(!userDropdownOpen)}
          >
            <div className="adm-user-chip-avatar">
              {userName.split(' ').map((n: string) => n[0]).join('').slice(0, 2).toUpperCase()}
            </div>
            {userName}
          </button>

          {userDropdownOpen && (
            <div className="adm-user-dropdown">
              <button
                type="button"
                className="adm-user-dropdown-item adm-user-dropdown-item--danger"
                onClick={handleLogout}
                disabled={logoutLoading}
              >
                <LogOut size={14} className="adm-icon" />
                {logoutLoading ? 'Signing out...' : 'Logout'}
              </button>
            </div>
          )}
        </div>
      </div>

    </header>
  );
}